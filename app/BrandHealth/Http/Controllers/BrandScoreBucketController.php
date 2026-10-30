<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View as Response;

class BrandScoreBucketController extends Controller
{
    public function index(): Response
    {
        return view('brand-health.settings.buckets', [
            'scoreBuckets' => BrandScoreBucket::orderBy('metric')->orderByDesc('min_rate')->get(),
            'labelBuckets' => BrandLabelBucket::orderBy('metric')->orderByDesc('min_score')->get(),
            'scoreMetrics' => BrandScoreBucket::METRICS,
            'labelMetrics' => BrandLabelBucket::METRICS,
            'formulaWeights' => BrandFormulaWeight::all(),
            'formulaComponents' => BrandFormulaWeight::COMPONENTS,
            // Known account_tier labels, for the Settings UI's tier picker —
            // constrains new score_bucket rows to a tier AccountTierResolver
            // can actually resolve to, instead of free text that could typo
            // into a dead bucket set no cycle ever matches.
            'accountTiers' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ACCOUNT_TIER)
                ->orderBy('min_score')
                ->pluck('label'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $this->assertGrowthThresholdNotNegative($data);
        $this->assertGrowthFloorIsZero($data);
        $this->assertAscendingWithinGroup($data);

        BrandScoreBucket::create($data);

        return back();
    }

    public function update(Request $request, BrandScoreBucket $scoreBucket): RedirectResponse
    {
        $data = $this->validated($request, requireMetric: false);

        $merged = array_merge($scoreBucket->only(['metric', 'platform', 'tier']), $data);
        $this->assertGrowthThresholdNotNegative($merged);
        $this->assertGrowthFloorIsZero($merged, ignoreId: $scoreBucket->id);
        $this->assertAscendingWithinGroup($merged, ignoreId: $scoreBucket->id);

        $scoreBucket->update($data);

        return back();
    }

    public function destroy(BrandScoreBucket $scoreBucket): RedirectResponse
    {
        $scoreBucket->delete();

        return back();
    }

    /**
     * tier/needs_check/health_3c are resolved once at save time (Cycle::
     * saving()), not recomputed live on every page load like health_rate
     * itself — so a threshold/weight/tier-boundary change made here doesn't
     * retroactively update them on existing cycles until each one is saved
     * again. This re-saves every cycle so they catch up with whatever
     * Settings changes were made since.
     */
    public function recalculate(): RedirectResponse
    {
        BrandCycle::orderBy('cycle_start_date')->get()->each->save();

        return back()->with('status', 'Recalculated tier, needs_check, and health_3c for all cycles.');
    }

    private function validated(Request $request, bool $requireMetric = true): array
    {
        $accountTiers = BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ACCOUNT_TIER)->pluck('label');

        return $request->validate([
            'metric' => [$requireMetric ? 'required' : 'sometimes', 'string', 'in:'.implode(',', BrandScoreBucket::METRICS)],
            'platform' => ['nullable', 'string', 'in:instagram,tiktok'],
            'tier' => ['nullable', 'string', 'in:'.$accountTiers->implode(',')],
            'min_rate' => ['nullable', 'numeric'],
            'score' => ['required', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:20'],
            'custom_color_bg' => ['nullable', 'string', 'max:20'],
            'custom_color_ink' => ['nullable', 'string', 'max:20'],
        ]);
    }

    /**
     * Growth's lowest non-floor threshold must be >= 0% — a negative
     * threshold (e.g. -1%) would let losing followers score positively
     * (25+), defeating the floor-is-0 rule above it: the floor only catches
     * rates BELOW the lowest threshold, so a negative threshold moves the
     * "never scores positively" boundary into territory that includes
     * follower loss.
     */
    private function assertGrowthThresholdNotNegative(array $data): void
    {
        if (($data['metric'] ?? null) !== BrandScoreBucket::METRIC_GROWTH) {
            return;
        }

        if (! array_key_exists('min_rate', $data) || $data['min_rate'] === null) {
            return;
        }

        if ((float) $data['min_rate'] < 0) {
            throw ValidationException::withMessages([
                'min_rate' => 'Growth thresholds must be 0% or higher — a negative threshold would let losing followers score positively.',
            ]);
        }
    }

    /**
     * Growth's floor bucket (min_rate = null, the catch-all below every other
     * threshold) must score 0 — losing followers (or flat growth below the
     * lowest configured threshold) must never score positively. Only checked
     * when this save IS the floor row; other rows in the same group are
     * unaffected.
     */
    private function assertGrowthFloorIsZero(array $data, ?int $ignoreId = null): void
    {
        if (($data['metric'] ?? null) !== BrandScoreBucket::METRIC_GROWTH) {
            return;
        }

        $isFloorRow = array_key_exists('min_rate', $data) && $data['min_rate'] === null;
        if (! $isFloorRow) {
            return;
        }

        if ((float) ($data['score'] ?? 0) !== 0.0) {
            throw ValidationException::withMessages([
                'score' => 'Growth\'s floor tier (no minimum) must score 0 — losing followers must never score positively.',
            ]);
        }
    }

    /**
     * Within one (metric, platform, tier) group, a higher min_rate must score
     * at least as much as a lower one — otherwise ScoreBucketResolver's
     * "highest min <= rate wins" rule would let two rates earn an
     * inconsistent/out-of-order score, which reads as a data-entry mistake
     * rather than an intentional curve.
     */
    private function assertAscendingWithinGroup(array $data, ?int $ignoreId = null): void
    {
        if (! array_key_exists('metric', $data) || ! array_key_exists('score', $data)) {
            return;
        }

        $siblings = BrandScoreBucket::where('metric', $data['metric'])
            ->where('platform', $data['platform'] ?? null)
            ->where('tier', $data['tier'] ?? null)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get();

        $thisMin = array_key_exists('min_rate', $data) ? $data['min_rate'] : null;
        $thisScore = (float) $data['score'];

        foreach ($siblings as $sibling) {
            $siblingMin = $sibling->min_rate === null ? null : (float) $sibling->min_rate;
            $siblingScore = (float) $sibling->score;

            $thisIsHigher = $thisMin !== null && ($siblingMin === null || $thisMin > $siblingMin);
            $thisIsLower = $siblingMin !== null && ($thisMin === null || $thisMin < $siblingMin);

            if ($thisIsHigher && $thisScore < $siblingScore) {
                throw ValidationException::withMessages([
                    'score' => "A higher threshold must score at least as much as a lower one in the same tier (conflicts with ≥{$sibling->min_rate} → {$sibling->score}).",
                ]);
            }
            if ($thisIsLower && $thisScore > $siblingScore) {
                throw ValidationException::withMessages([
                    'score' => "A lower threshold must score at most as much as a higher one in the same tier (conflicts with ≥{$sibling->min_rate} → {$sibling->score}).",
                ]);
            }
        }
    }
}
