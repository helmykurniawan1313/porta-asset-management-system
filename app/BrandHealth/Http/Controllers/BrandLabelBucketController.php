<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BrandLabelBucketController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        BrandLabelBucket::create($data);

        return back();
    }

    public function update(Request $request, BrandLabelBucket $labelBucket): RedirectResponse
    {
        $data = $this->validated($request, requireMetric: false);

        // ScoreBucket.tier stores the account_tier label as a plain string
        // (not a foreign key), matched by exact equality in AccountTierResolver/
        // MetricCalculator's fallback lookup. Renaming a tier here would
        // otherwise silently orphan every score_buckets row still tagged with
        // the old name — they'd stop matching and fall through to the global
        // bucket set without any error or warning.
        $oldLabel = $labelBucket->label;
        $newLabel = $data['label'] ?? $oldLabel;
        $isAccountTierRename = $labelBucket->metric === BrandLabelBucket::METRIC_ACCOUNT_TIER
            && $newLabel !== $oldLabel;

        $labelBucket->update($data);

        if ($isAccountTierRename) {
            BrandScoreBucket::where('tier', $oldLabel)->update(['tier' => $newLabel]);
        }

        return back();
    }

    public function destroy(BrandLabelBucket $labelBucket): RedirectResponse
    {
        // Same orphaning risk as the rename case above, but for delete: a
        // tier with existing Score Tiers rows would leave them permanently
        // unreachable (their tier name no longer resolves to anything) —
        // blocked instead of silently deleting or orphaning that data.
        if ($labelBucket->metric === BrandLabelBucket::METRIC_ACCOUNT_TIER) {
            $scoreBucketCount = BrandScoreBucket::where('tier', $labelBucket->label)->count();

            if ($scoreBucketCount > 0) {
                throw ValidationException::withMessages([
                    'label' => "Can't delete \"{$labelBucket->label}\" — it still has {$scoreBucketCount} Score Tiers threshold(s) set. Remove or reassign those first.",
                ]);
            }
        }

        $labelBucket->delete();

        return back();
    }

    private function validated(Request $request, bool $requireMetric = true): array
    {
        return $request->validate([
            'metric' => [$requireMetric ? 'required' : 'sometimes', 'string', 'in:'.implode(',', BrandLabelBucket::METRICS)],
            'min_score' => ['nullable', 'numeric'],
            'label' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'custom_color_bg' => ['nullable', 'string', 'max:20'],
            'custom_color_ink' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
