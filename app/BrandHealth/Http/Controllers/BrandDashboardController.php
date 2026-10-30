<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandPerformance;
use App\BrandHealth\Models\BrandScoreBucket;
use App\BrandHealth\Services\MetricCalculator;
use App\BrandHealth\Services\ScoreBucketResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View as Response;

class BrandDashboardController extends Controller
{
    public function index(Request $request, MetricCalculator $calculator, ScoreBucketResolver $resolver): Response
    {
        $platform = in_array($request->query('platform'), ['instagram', 'tiktok'], true)
            ? $request->query('platform')
            : 'all';

        // range = 'all' (all-time) or 'Y-m' (a specific month), applied against the record's own date field.
        $range = $request->query('range', 'all');

        $platformCounts = [
            'instagram' => BrandCycle::where('platform', 'instagram')->count(),
            'tiktok' => BrandCycle::where('platform', 'tiktok')->count(),
        ];

        return view('brand-health.dashboard.index', [
            'filters' => [
                'platform' => $platform,
                'range' => $range,
            ],
            'platformCounts' => $platformCounts,
            'cycleData' => fn () => $this->buildCycleData($platform, $range, $calculator),
            'performanceData' => fn () => $this->buildPerformanceData($platform, $range, $resolver),
        ]);
    }

    private function buildCycleData(string $platform, string $range, MetricCalculator $calculator): array
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $cycleQuery = BrandCycle::query()->orderBy('cycle_start_date');
        if ($platform !== 'all') {
            $cycleQuery->where('platform', $platform);
        }

        $allCyclesForPlatform = $cycleQuery->get();

        $availableMonths = $allCyclesForPlatform
            ->map(fn (BrandCycle $cycle) => $cycle->cycle_start_date->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        $cycles = $range === 'all'
            ? $allCyclesForPlatform
            : $allCyclesForPlatform->filter(fn (BrandCycle $cycle) => $cycle->cycle_start_date->format('Y-m') === $range)->values();

        $scoredCycles = $cycles->map(fn (BrandCycle $cycle) => [
            'cycle' => $cycle,
            'scores' => $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights),
        ]);

        $healthLabelOrder = ['PARAH', 'KURANG', 'CUKUP', 'BAGUS', 'SIP'];

        $healthDistribution = collect($healthLabelOrder)->mapWithKeys(fn ($label) => [
            $label => $scoredCycles->where('scores.health_label', $label)->count(),
        ]);

        // Monthly cycle-count trend for the last 6 months, always all-time regardless of range filter.
        $monthlyTrend = $this->buildMonthlyTrend($allCyclesForPlatform, fn (BrandCycle $cycle) => $cycle->cycle_start_date);

        $accountsCount = $platform === 'all'
            ? BrandAccount::count()
            : $allCyclesForPlatform->pluck('account_id')->unique()->count();

        return [
            'availableMonths' => $availableMonths,
            'summary' => [
                'accounts_count' => $accountsCount,
                'cycles_count' => $cycles->count(),
                'avg_health_rate' => $scoredCycles->isEmpty() ? null : round($scoredCycles->avg('scores.health_rate'), 2),
                'healthy_cycles_count' => $scoredCycles->where('scores.health_label', 'SIP')->count(),
                'at_risk_cycles_count' => $scoredCycles->whereIn('scores.health_label', ['KURANG', 'PARAH'])->count(),
            ],
            'healthDistribution' => [
                'tiers' => $healthLabelOrder,
                'counts' => $healthDistribution,
            ],
            'monthlyTrend' => $monthlyTrend,
        ];
    }

    private function buildPerformanceData(string $platform, string $range, ScoreBucketResolver $resolver): array
    {
        $viewsBuckets = BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_VIEWS)->get();

        $performanceQuery = BrandPerformance::with(['igSnapshots' => fn ($query) => $query->limit(1)])
            ->orderBy('post_date');
        if ($platform !== 'all') {
            $performanceQuery->where('platform', $platform);
        }

        $allPerformancesForPlatform = $performanceQuery->get()->map(function (BrandPerformance $performance) use ($resolver, $viewsBuckets) {
            $snapshot = $performance->igSnapshots->first();
            if ($snapshot) {
                $performance->total_views_h7 = $snapshot->views ?? $snapshot->total_interactions;
            }

            $performance->views_status = $performance->total_views_h7 === null
                ? null
                : $resolver->resolve($viewsBuckets, (float) $performance->total_views_h7, 'min_score', 'label');

            return $performance;
        });

        $availableMonths = $allPerformancesForPlatform
            ->filter(fn (BrandPerformance $performance) => $performance->post_date !== null)
            ->map(fn (BrandPerformance $performance) => $performance->post_date->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        $performances = $range === 'all'
            ? $allPerformancesForPlatform
            : $allPerformancesForPlatform->filter(
                fn (BrandPerformance $performance) => $performance->post_date !== null && $performance->post_date->format('Y-m') === $range
            )->values();

        $viewsLabelOrder = $viewsBuckets->sortByDesc('min_score')->pluck('label')->values();
        $topTierCount = (int) ceil($viewsLabelOrder->count() / 2);
        $topTierLabels = $viewsLabelOrder->take($topTierCount);
        $bottomTierLabels = $viewsLabelOrder->skip($topTierCount);

        $viewsWithValue = $performances->filter(fn ($performance) => $performance->total_views_h7 !== null);

        $viewsDistributionTiers = $viewsLabelOrder->reverse()->values();
        $viewsCounts = array_fill_keys($viewsDistributionTiers->all(), 0);
        foreach ($performances as $performance) {
            if ($performance->views_status !== null && array_key_exists($performance->views_status, $viewsCounts)) {
                $viewsCounts[$performance->views_status]++;
            }
        }

        $monthlyTrend = $this->buildMonthlyTrend($allPerformancesForPlatform, fn (BrandPerformance $performance) => $performance->post_date, fn (BrandPerformance $performance) => $performance->post_date !== null);

        $accountsCount = $platform === 'all'
            ? BrandAccount::count()
            : $allPerformancesForPlatform->pluck('account_id')->unique()->count();

        return [
            'availableMonths' => $availableMonths,
            'summary' => [
                'accounts_count' => $accountsCount,
                'performances_count' => $performances->count(),
                'avg_views' => $viewsWithValue->isEmpty() ? null : round($viewsWithValue->avg('total_views_h7')),
                'top_performing_count' => $performances->whereIn('views_status', $topTierLabels)->count(),
                'at_risk_count' => $performances->whereIn('views_status', $bottomTierLabels)->count(),
            ],
            'viewsDistribution' => [
                'tiers' => $viewsDistributionTiers,
                'counts' => $viewsCounts,
            ],
            'monthlyTrend' => $monthlyTrend,
            'platformCounts' => [
                'instagram' => $performances->where('platform', 'instagram')->count(),
                'tiktok' => $performances->where('platform', 'tiktok')->count(),
            ],
        ];
    }

    /**
     * Record count per month for the 6 most recent months that actually have
     * data, oldest first — a simple activity trend for the dashboard. Shared
     * by Cycles (cycle_start_date) and Performance (post_date).
     */
    private function buildMonthlyTrend(Collection $records, \Closure $dateGetter, ?\Closure $filter = null): array
    {
        if ($filter) {
            $records = $records->filter($filter);
        }

        $byMonth = $records->groupBy(fn ($record) => $dateGetter($record)->format('Y-m'));

        $months = $byMonth->keys()->sort()->values()->take(-6);

        return $months->map(fn ($month) => [
            'label' => \Carbon\Carbon::createFromFormat('Y-m-d', "{$month}-01")->format('M Y'),
            'count' => $byMonth->get($month)->count(),
        ])->values()->all();
    }

}
