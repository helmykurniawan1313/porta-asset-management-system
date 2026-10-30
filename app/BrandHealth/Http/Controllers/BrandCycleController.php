<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandCycle;
use App\Models\User;
use App\BrandHealth\Models\BrandFilterSummary;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandPerformance;
use App\BrandHealth\Models\BrandScoreBucket;
use App\BrandHealth\Services\MetricCalculator;
use App\BrandHealth\Services\PerformanceCycleAssigner;
use App\BrandHealth\Services\SummaryProviderResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View as Response;

class BrandCycleController extends Controller
{
    public function index(Request $request, MetricCalculator $calculator): Response
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $labelFilters = $this->parseLabelFilters($request);

        $matching = $this->filteredCycleQuery($request)->orderByDesc('cycle_start_date')->get()->map(fn (BrandCycle $cycle) => [
            ...$cycle->toArray(),
            'scores' => $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights),
        ]);

        $matching = $this->applyLabelFilters($matching, $labelFilters);

        $page = $request->integer('page', 1);
        $perPage = 15;

        $paginator = new LengthAwarePaginator(
            $matching->forPage($page, $perPage)->values(),
            $matching->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $availableYears = BrandCycle::pluck('cycle_start_date')
            ->map(fn ($date) => (int) $date->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        $scoreDistribution = $this->buildScoreDistribution($matching);

        return view('brand-health.cycles.index', [
            'cycles' => $paginator,
            'accounts' => BrandAccount::orderBy('name')->get(),
            'accountDepartmentEmployees' => User::whereHas(
                'department',
                fn ($query) => $query->whereIn('name', ['Accounts', 'Accounts Manager'])
            )->orderBy('name')->get(['id', 'name']),
            'growthLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_GROWTH)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'visibilityLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_VISIBILITY)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'engagementLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ENGAGEMENT)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'healthLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_HEALTH)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'availableYears' => $availableYears,
            'scoreDistribution' => $scoreDistribution,
            'summary' => [
                'total' => $matching->count(),
                'avg_health_rate' => $matching->isEmpty() ? null : round($matching->avg('scores.health_rate'), 2),
                'healthy_count' => $matching->where('scores.health_label', 'SIP')->count(),
                'at_risk_count' => $matching->whereIn('scores.health_label', ['KURANG', 'PARAH'])->count(),
            ],
            'filters' => $this->activeFilters($request),
            'defaultAiProvider' => config('services.brand_health.ai_summary.provider', 'groq'),
            // Sent flat (not pre-grouped by metric) — CycleDetailModal picks
            // the right (platform, tier) subset per cycle client-side, using
            // the same fallback order as MetricCalculator::resolveScore().
            'scoreBuckets' => $scoreBuckets->values(),
        ]);
    }

    /**
     * The filtered, scored cycle collection shared by the PDF and Excel exports —
     * same query/filter logic as index(), plus formatted dates and rounded scores
     * for display. Also returns a human-readable filter summary for both exports'
     * headers.
     */
    private function filteredScoredCyclesForExport(Request $request, MetricCalculator $calculator): array
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $cycles = $this->filteredCycleQuery($request)
            ->orderByDesc('cycle_start_date')
            ->get()
            ->map(function (BrandCycle $cycle) use ($calculator, $scoreBuckets, $labelBuckets, $formulaWeights) {
                $scores = $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights);
                $scores['growth_rate'] = round($scores['growth_rate'], 2);
                $scores['visibility_rate'] = round($scores['visibility_rate'], 2);
                $scores['engagement_score'] = round($scores['engagement_score'], 2);
                $scores['health_rate'] = round($scores['health_rate'], 2);

                return [
                    ...$cycle->toArray(),
                    'cycle_start_date_formatted' => $cycle->cycle_start_date->format('M j, Y'),
                    'cycle_end_date_formatted' => $cycle->cycle_end_date->format('M j, Y'),
                    'scores' => $scores,
                ];
            });

        return [
            'cycles' => $this->applyLabelFilters($cycles, $this->parseLabelFilters($request)),
            'filterSummary' => $this->filterDescription($request)['summary'],
        ];
    }

    public function pdf(Request $request, MetricCalculator $calculator): HttpResponse
    {
        ['cycles' => $cycles, 'filterSummary' => $filterSummary] = $this->filteredScoredCyclesForExport($request, $calculator);
        $healthColors = $this->healthLabelColors();

        $pdf = Pdf::loadView('brand-health.pdf.cycles-list', [
            'cycles' => $cycles,
            'generatedAt' => now()->format('M j, Y g:i A'),
            'filterSummary' => $filterSummary,
            'aiSummary' => $request->string('ai_summary')->trim()->toString() ?: null,
            'healthBreakdown' => $this->healthLabelBreakdown($cycles, $healthColors),
            'healthColors' => $healthColors,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('performance-cycles-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(Request $request, MetricCalculator $calculator): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        ['cycles' => $cycles] = $this->filteredScoredCyclesForExport($request, $calculator);
        $healthColors = $this->healthLabelColors();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\BrandHealth\Exports\CyclesExport($cycles, $this->healthLabelBreakdown($cycles, $healthColors), $healthColors),
            'performance-cycles-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * Each Health label's resolved tier color (BucketColorResolver), ordered
     * best-to-worst, keyed by label text — shared by the summary table AND
     * the Health column of the full per-cycle list, so both exports visually
     * match the app's live StatusBadge for every Health label shown.
     *
     * @return array<string, array{bg: string, ink: string}>
     */
    private function healthLabelColors(): array
    {
        $buckets = BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_HEALTH)
            ->orderByDesc('min_score')
            ->get();

        $colors = [];
        foreach ($buckets->values() as $rank => $bucket) {
            $colors[$bucket->label] = \App\BrandHealth\Services\BucketColorResolver::resolve($bucket, $rank);
        }

        return $colors;
    }

    /**
     * How many cycles in the exported set landed on each Health label, ordered
     * best-to-worst by the label bucket's own min_score — a quick "how many
     * Bagus, how many Kurang..." summary alongside the full row-per-cycle list.
     */
    private function healthLabelBreakdown(Collection $cycles, array $healthColors): Collection
    {
        return collect($healthColors)->map(fn ($color, $label) => [
            'label' => $label,
            'count' => $cycles->filter(fn ($cycle) => $cycle['scores']['health_label'] === $label)->count(),
            'color' => $color,
        ])->values();
    }

    public function pdfSingle(BrandCycle $cycle, MetricCalculator $calculator): HttpResponse
    {
        $scores = $calculator->calculate(
            $cycle->load(['account', 'projectManager']),
            BrandScoreBucket::all(),
            BrandLabelBucket::all(),
            BrandFormulaWeight::all(),
        );

        foreach (['growth_rate', 'reach_rate', 'view_rate', 'er_reach_rate', 'er_follower_rate', 'growth_score', 'reach_score', 'view_score', 'er_reach_score', 'er_follower_score', 'visibility_rate', 'engagement_score', 'health_rate'] as $key) {
            $scores[$key] = round($scores[$key], 2);
        }

        $pdf = Pdf::loadView('brand-health.pdf.cycle-detail', [
            'cycle' => $cycle,
            'scores' => $scores,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            '%s-%s.pdf',
            Str::slug($cycle->account->name),
            $cycle->cycle_start_date->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    public function summarize(Request $request, BrandCycle $cycle, MetricCalculator $calculator, SummaryProviderResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
            'provider' => ['nullable', 'string', 'in:'.implode(',', SummaryProviderResolver::PROVIDERS)],
        ]);

        $scores = $calculator->calculate(
            $cycle->load('account'),
            BrandScoreBucket::all(),
            BrandLabelBucket::all(),
            BrandFormulaWeight::all(),
        );

        try {
            $summary = $resolver->resolve($data['provider'] ?? null)->summarize($cycle, $scores, $data['prompt'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Quiet save: the summary doesn't affect scores, so skip re-resolving this cycle and its neighbours.
        $cycle->forceFill([
            'ai_summary' => $summary,
            'ai_summary_generated_at' => now(),
        ])->saveQuietly();

        return response()->json([
            'ai_summary' => $summary,
            'ai_summary_generated_at' => $cycle->ai_summary_generated_at->toIso8601String(),
        ]);
    }

    public function neighboring(Request $request, BrandAccount $account): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'platform' => ['nullable', 'string', 'in:instagram,tiktok'],
        ]);

        $platform = $data['platform'] ?? 'instagram';

        $response = [];

        if ($startDate = $data['start_date'] ?? null) {
            $response['start_follower'] = ($platform === 'instagram' ? $this->resolveInstagramFollowerNear($account, $startDate) : null)
                ?? $this->resolveStartFollower($account, $startDate, $platform);
        }

        if ($endDate = $data['end_date'] ?? null) {
            $response['end_follower'] = ($platform === 'instagram' ? $this->resolveInstagramFollowerNear($account, $endDate) : null)
                ?? $this->resolveEndFollower($account, $endDate, $platform);
        }

        if ($startDate && $endDate && $platform === 'instagram') {
            $totals = $this->resolveInstagramTotals($account, $startDate, $endDate);

            if ($totals) {
                $response['reach'] = $totals['reach'];
                $response['views'] = $totals['views'];
                $response['engagement'] = $totals['engagement'];
            }
        }

        return response()->json($response);
    }

    /**
     * Uses the closest captured daily snapshot on or before the given date as a
     * point-in-time follower count — falls back to null so the caller can use
     * the manual-entry neighboring-cycle logic instead.
     */
    private function resolveInstagramFollowerNear(BrandAccount $account, string $date): ?int
    {
        if (! $account->ig_business_id) {
            return null;
        }

        $snapshot = $account->instagramDailySnapshots()
            ->whereNotNull('followers_count')
            ->where('captured_date', '<=', $date)
            ->orderByDesc('captured_date')
            ->first();

        return $snapshot?->followers_count;
    }

    /**
     * Sums captured daily snapshots within [start, end] for the period-total fields.
     * Returns null (not zeros) when no snapshots exist in range, so the frontend
     * can distinguish "no Instagram data" from "genuinely zero activity."
     */
    private function resolveInstagramTotals(BrandAccount $account, string $startDate, string $endDate): ?array
    {
        if (! $account->ig_business_id) {
            return null;
        }

        $snapshots = $account->instagramDailySnapshots()
            ->whereBetween('captured_date', [$startDate, $endDate])
            ->get();

        if ($snapshots->isEmpty()) {
            return null;
        }

        return [
            'reach' => (int) $snapshots->sum('reach'),
            'views' => (int) $snapshots->sum('views'),
            'engagement' => (int) $snapshots->sum('total_interactions'),
        ];
    }

    private function resolveStartFollower(BrandAccount $account, string $startDate, string $platform): ?int
    {
        $previous = $account->cycles()
            ->where('platform', $platform)
            ->where('cycle_end_date', '<=', $startDate)
            ->orderByDesc('cycle_end_date')
            ->first();

        if ($previous) {
            return $previous->end_follower;
        }

        $next = $account->cycles()
            ->where('platform', $platform)
            ->where('cycle_start_date', '>', $startDate)
            ->orderBy('cycle_start_date')
            ->first();

        return $next?->start_follower;
    }

    private function resolveEndFollower(BrandAccount $account, string $endDate, string $platform): ?int
    {
        $next = $account->cycles()
            ->where('platform', $platform)
            ->where('cycle_start_date', '>=', $endDate)
            ->orderBy('cycle_start_date')
            ->first();

        return $next?->start_follower;
    }

    public function store(Request $request, PerformanceCycleAssigner $cycleAssigner): RedirectResponse
    {
        $data = $this->validated($request);

        $cycle = BrandCycle::create($data);

        $cycleAssigner->syncForCycle($cycle);

        return back();
    }

    public function update(Request $request, BrandCycle $cycle, PerformanceCycleAssigner $cycleAssigner): RedirectResponse
    {
        $data = $this->validated($request);

        $cycle->update($data);

        // The cycle's account/platform/date range may have changed, so re-sync in both
        // directions against the *new* range: pick up posts newly covered by it (previously
        // unassigned or on another cycle), then re-resolve posts that were pointing at this
        // cycle but may have fallen outside its new range and need to move elsewhere.
        $cycleAssigner->syncForCycle($cycle);
        $cycleAssigner->reassignAwayFromCycle($cycle);

        return back();
    }

    public function destroy(BrandCycle $cycle, PerformanceCycleAssigner $cycleAssigner): RedirectResponse
    {
        // Capture affected posts before deleting — once the cycle is gone, the FK's
        // nullOnDelete has already cleared their cycle_id, and re-resolving after that
        // (rather than before) is what lets them fall back onto another covering cycle
        // instead of just matching this same cycle again because it still existed.
        $affectedPerformanceIds = $cycle->performances()->pluck('id');

        $cycle->delete();

        $cycleAssigner->reassignMany(BrandPerformance::whereIn('id', $affectedPerformanceIds)->get());

        return back();
    }

    /**
     * Counts how many matching cycles resolved to each label (PARAH..SIP) per
     * metric — using each metric's own real label thresholds (already resolved
     * by MetricCalculator into growth_label/visibility_label/etc.), not a
     * shared numeric tier. Health's SIP starts at >=85 while Visibility's SIP
     * starts at >=100, for example, so a shared 0/25/50/75/100 axis would
     * misrepresent metrics whose thresholds don't line up with those numbers.
     */
    private function buildScoreDistribution(Collection $matching): array
    {
        $labelOrder = ['PARAH', 'KURANG', 'CUKUP', 'BAGUS', 'SIP'];
        $metrics = [
            'growth_label' => 'Growth Rate',
            'visibility_label' => 'Visibility',
            'engagement_label' => 'Engagement',
            'health_label' => 'Health',
        ];

        $counts = collect($metrics)->mapWithKeys(function ($label, $key) use ($matching, $labelOrder) {
            $labelCounts = array_fill_keys($labelOrder, 0);

            foreach ($matching as $cycle) {
                $resolved = $cycle['scores'][$key] ?? null;

                if ($resolved !== null && array_key_exists($resolved, $labelCounts)) {
                    $labelCounts[$resolved]++;
                }
            }

            return [$key => ['label' => $label, 'counts' => $labelCounts]];
        });

        return [
            'tiers' => $labelOrder,
            'series' => $counts->values(),
        ];
    }

    private function applyMonthRangeFilter($query, string $monthFrom, ?string $monthTo)
    {
        $start = Carbon::createFromFormat('Y-m-d', "{$monthFrom}-01")->startOfMonth();
        $end = $monthTo
            ? Carbon::createFromFormat('Y-m-d', "{$monthTo}-01")->endOfMonth()
            : $start->copy()->endOfMonth();

        return $query->whereBetween('cycle_start_date', [$start->toDateString(), $end->toDateString()]);
    }

    private function formatMonthRange(string $monthFrom, ?string $monthTo): string
    {
        // createFromFormat('Y-m', ...) without a day defaults the day to today's
        // day-of-month, which overflows into the next month for short months
        // (e.g. parsing "2026-06" on the 31st silently produces July 1st) —
        // always pass an explicit day to avoid that.
        $from = Carbon::createFromFormat('Y-m-d', "{$monthFrom}-01");

        if (! $monthTo || $monthTo === $monthFrom) {
            return $from->format('F Y');
        }

        $to = Carbon::createFromFormat('Y-m-d', "{$monthTo}-01");

        return $from->year === $to->year
            ? $from->format('F').' - '.$to->format('F Y')
            : $from->format('F Y').' - '.$to->format('F Y');
    }

    /**
     * Cycles matching the current request's filters, each paired with its computed scores.
     * Used by summarizeFiltered(); list and exports share the same filteredCycleQuery().
     */
    private function filteredCycles(Request $request, MetricCalculator $calculator): Collection
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $cycles = $this->filteredCycleQuery($request)
            ->orderBy('cycle_start_date')
            ->get()
            ->map(fn (BrandCycle $cycle) => [
                'cycle' => $cycle,
                'scores' => $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights),
            ]);

        return $this->applyLabelFilters($cycles, $this->parseLabelFilters($request));
    }

    /**
     * The SQL half of the cycle filters (everything but the score labels, which
     * need computed scores — see applyLabelFilters()). The one definition the
     * list, exports and AI summary all build on, so they can't drift apart.
     */
    private function filteredCycleQuery(Request $request): Builder
    {
        $filters = $this->activeFilters($request);

        return BrandCycle::with(['account', 'projectManager'])
            ->when($filters['account_id'], fn ($query, $accountId) => $query->where('account_id', $accountId))
            ->when($filters['project_manager_id'], fn ($query, $id) => $query->where('project_manager_id', $id))
            ->when($filters['platform'], fn ($query, $platform) => $query->where('platform', $platform))
            ->when($filters['search'], function ($query, $search) {
                $query->whereHas('account', fn ($accountQuery) => $accountQuery->where('name', 'like', "%{$search}%"));
            })
            ->when($filters['month_from'], fn ($query, $monthFrom) => $this->applyMonthRangeFilter($query, $monthFrom, $filters['month_to']));
    }

    /**
     * Every cycle filter read from the request, normalized (null when unset).
     */
    private function activeFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'account_id' => $request->integer('account_id') ?: null,
            'project_manager_id' => $request->integer('project_manager_id') ?: null,
            'platform' => $request->string('platform')->trim()->toString() ?: null,
            ...$this->parseLabelFilters($request),
            'month_from' => $request->string('month_from')->trim()->toString() ?: null,
            'month_to' => $request->string('month_to')->trim()->toString() ?: null,
        ];
    }

    /**
     * Each *_label filter arrives as a comma-separated list (multi-select) —
     * split and trim into an array, or null when absent, so callers use a
     * single check regardless of which metric's filter param this is.
     */
    private function parseLabelFilter(Request $request, string $param): ?array
    {
        $raw = $request->string($param)->trim()->toString();

        if (! $raw) {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Resolves all four *_label filters (growth/visibility/engagement/health)
     * from the request in one call, keyed by their scores[] field name.
     */
    private function parseLabelFilters(Request $request): array
    {
        return [
            'growth_label' => $this->parseLabelFilter($request, 'growth_label'),
            'visibility_label' => $this->parseLabelFilter($request, 'visibility_label'),
            'engagement_label' => $this->parseLabelFilter($request, 'engagement_label'),
            'health_label' => $this->parseLabelFilter($request, 'health_label'),
        ];
    }

    /**
     * Filters a mapped cycle collection down to those matching every active
     * *_label filter (AND across metrics, OR within each metric's selected
     * labels) — shared by index(), pdf(), and filteredCycles().
     */
    private function applyLabelFilters(Collection $matching, array $labelFilters): Collection
    {
        foreach ($labelFilters as $field => $labels) {
            if (! $labels) {
                continue;
            }

            $matching = $matching->filter(fn ($cycle) => in_array($cycle['scores'][$field], $labels, true))->values();
        }

        return $matching;
    }

    private function filterDescription(Request $request): array
    {
        $filters = $this->activeFilters($request);
        ['search' => $search, 'account_id' => $accountId, 'project_manager_id' => $pmId, 'platform' => $platform,
            'month_from' => $monthFrom, 'month_to' => $monthTo] = $filters;

        $parts = [];
        if ($accountId) {
            $account = BrandAccount::find($accountId);
            $parts[] = 'account: '.($account->name ?? "#{$accountId}");
        }
        if ($pmId) {
            $pm = User::find($pmId);
            $parts[] = 'PM: '.($pm->name ?? "#{$pmId}");
        }
        if ($search) {
            $parts[] = "search: \"{$search}\"";
        }
        if ($platform) {
            $parts[] = 'platform: '.ucfirst($platform);
        }
        foreach (['growth_label' => 'growth', 'visibility_label' => 'visibility', 'engagement_label' => 'engagement', 'health_label' => 'health'] as $field => $shortLabel) {
            if ($filters[$field]) {
                $parts[] = "{$shortLabel}: ".implode(', ', $filters[$field]);
            }
        }
        if ($monthFrom) {
            $parts[] = 'period: '.$this->formatMonthRange($monthFrom, $monthTo);
        }

        return [
            'filters' => $filters,
            'summary' => $parts ? implode(', ', $parts) : 'none (all cycles)',
        ];
    }

    public function summarizeFiltered(Request $request, MetricCalculator $calculator, SummaryProviderResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
            'provider' => ['nullable', 'string', 'in:'.implode(',', SummaryProviderResolver::PROVIDERS)],
            'search' => ['nullable', 'string'],
            'account_id' => ['nullable', 'integer'],
            'project_manager_id' => ['nullable', 'integer'],
            'platform' => ['nullable', 'string', 'in:instagram,tiktok'],
            'growth_label' => ['nullable', 'string'],
            'visibility_label' => ['nullable', 'string'],
            'engagement_label' => ['nullable', 'string'],
            'health_label' => ['nullable', 'string'],
            'month_from' => ['nullable', 'string'],
            'month_to' => ['nullable', 'string'],
        ]);

        $cycles = $this->filteredCycles($request, $calculator);

        if ($cycles->isEmpty()) {
            return response()->json(['message' => 'No cycles match the current filters.'], 422);
        }

        $filterDescription = $this->filterDescription($request);
        $provider = $data['provider'] ?? config('services.brand_health.ai_summary.provider', 'groq');

        try {
            $summary = $resolver->resolve($data['provider'] ?? null)
                ->summarizeFiltered($cycles, $filterDescription, $data['prompt'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $record = BrandFilterSummary::create([
            'filters' => $filterDescription['filters'],
            'provider' => $provider,
            'custom_prompt' => $data['prompt'] ?? null,
            'summary' => $summary,
            'cycle_count' => $cycles->count(),
        ]);

        return response()->json([
            'id' => $record->id,
            'summary' => $summary,
            'cycle_count' => $cycles->count(),
            'filter_summary' => $filterDescription['summary'],
            'generated_at' => $record->created_at->toIso8601String(),
        ]);
    }

    /**
     * Ads Nominal ranking — one row per account, summing reach/views ads spend
     * and engagement ads spend separately (plus their combined total) across
     * whichever cycles fall in the picked month range. Ranked by total spend,
     * highest first.
     */
    public function adsRanking(Request $request): JsonResponse
    {
        return response()->json($this->adsRankingData($request));
    }

    public function adsRankingPdf(Request $request): HttpResponse
    {
        ['rows' => $rows, 'filterSummary' => $filterSummary] = $this->adsRankingData($request);

        $pdf = Pdf::loadView('brand-health.pdf.ads-ranking', [
            'rows' => $rows,
            'byCurrency' => collect($rows)->groupBy('currency'),
            'generatedAt' => now()->format('M j, Y g:i A'),
            'filterSummary' => $filterSummary,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('ads-ranking-'.now()->format('Y-m-d').'.pdf');
    }

    public function adsRankingExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        ['rows' => $rows, 'filterSummary' => $filterSummary] = $this->adsRankingData($request);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\BrandHealth\Exports\AdsRankingExport(collect($rows), $filterSummary),
            'ads-ranking-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    private function adsRankingData(Request $request): array
    {
        $monthFrom = $request->string('month_from')->trim()->toString() ?: null;
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;

        $cycles = BrandCycle::with('account:id,name')
            ->when($monthFrom, fn ($query) => $this->applyMonthRangeFilter($query, $monthFrom, $monthTo))
            ->get();

        // Spend is currency-specific (ads_currency per cycle, mostly IDR with
        // some USD) — summing different currencies together as one number
        // would be meaningless. Group by account + currency instead, so an
        // account that ran ads in both IDR and USD gets one row per currency
        // rather than a single blended (and wrong) total.
        $rows = $cycles
            ->filter(fn (BrandCycle $cycle) => $cycle->account !== null)
            ->groupBy(fn (BrandCycle $cycle) => $cycle->account_id.'|'.($cycle->ads_currency ?: 'IDR'))
            ->map(function (Collection $group) {
                $reachViews = (float) $group->sum('reach_views_ads_spend');
                $engagement = (float) $group->sum('engagement_ads_spend');
                $first = $group->first();

                return [
                    'account_id' => $first->account_id,
                    'account_name' => $first->account->name,
                    'currency' => $first->ads_currency ?: 'IDR',
                    'reach_views_ads_spend' => $reachViews,
                    'engagement_ads_spend' => $engagement,
                    'total_ads_spend' => $reachViews + $engagement,
                    'cycle_count' => $group->count(),
                ];
            })
            ->sortByDesc('total_ads_spend')
            ->values()
            ->all();

        $filterSummary = $monthFrom ? 'period: '.$this->formatMonthRange($monthFrom, $monthTo) : 'period: all time';

        // Plucks just the date column instead of hydrating full Cycle models
        // for every row just to extract/dedupe a formatted month string.
        $months = BrandCycle::query()
            ->pluck('cycle_start_date')
            ->map(fn ($date) => $date->format('Y-m'))
            ->unique()
            ->sort()
            ->values();

        return [
            'rows' => $rows,
            'filters' => ['month_from' => $monthFrom, 'month_to' => $monthTo],
            'filterSummary' => $filterSummary,
            'months' => $months,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'account_id' => ['required', 'exists:brand_accounts,id'],
            'platform' => ['required', 'string', 'in:instagram,tiktok'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
            'cycle_start_date' => ['required', 'date'],
            'cycle_end_date' => ['required', 'date', 'after_or_equal:cycle_start_date'],
            'start_follower' => ['required', 'integer', 'min:0'],
            'end_follower' => ['required', 'integer', 'min:0'],
            'reach' => ['required', 'integer', 'min:0'],
            'views' => ['required', 'integer', 'min:0'],
            'engagement' => ['required', 'integer', 'min:0'],
            'story_performance' => ['nullable', 'integer', 'min:0'],
            'ads_currency' => ['nullable', 'string', 'in:IDR,USD,EUR'],
            'reach_views_ads_used' => ['boolean'],
            'reach_views_ads_spend' => ['nullable', 'numeric', 'min:0'],
            'engagement_ads_used' => ['boolean'],
            'engagement_ads_spend' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
