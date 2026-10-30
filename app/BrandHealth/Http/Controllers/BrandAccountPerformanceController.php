<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Exports\AccountPerformanceExport;
use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandCycle;
use App\Models\User;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use App\BrandHealth\Services\MetricCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View as Response;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Account Performance — same Growth/Visibility/Engagement/Health breakdown
 * as the Cycles table, but collapsed to one row per account+platform (its
 * MOST RECENT cycle only), instead of listing every cycle ever recorded.
 * Clicking a row opens a month-over-month trend across that account's full
 * cycle history for the same platform.
 */
class BrandAccountPerformanceController extends Controller
{
    public function index(Request $request, MetricCalculator $calculator): Response
    {
        ['rows' => $rows] = $this->filteredRows($request, $calculator);

        $page = $request->integer('page', 1);
        $perPage = 15;

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('brand-health.account-performance.index', [
            'rows' => $paginator,
            'summary' => [
                'total' => $rows->count(),
            ],
            'filters' => [
                'search' => $request->string('search')->trim()->toString() ?: null,
                'project_manager_id' => $request->integer('project_manager_id') ?: null,
                'health_label' => $this->parseLabelFilter($request, 'health_label'),
                'month_to' => $request->string('month_to')->trim()->toString() ?: null,
            ],
            'accountDepartmentEmployees' => User::whereHas(
                'department',
                fn ($query) => $query->whereIn('name', ['Accounts', 'Accounts Manager'])
            )->orderBy('name')->get(['id', 'name']),
            'healthLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_HEALTH)
                ->orderByDesc('min_score')
                ->pluck('label'),
            // Portable distinct-months list (no DATE_FORMAT — not supported by
            // the SQLite connection the test suite runs on), same convention
            // as ViewsTrendController's month picker.
            'months' => BrandCycle::query()
                ->pluck('cycle_start_date')
                ->map(fn ($date) => $date->format('Y-m'))
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function pdf(Request $request, MetricCalculator $calculator): HttpResponse
    {
        ['rows' => $rows, 'filterSummary' => $filterSummary] = $this->filteredRows($request, $calculator);

        $pdf = Pdf::loadView('brand-health.pdf.account-performance-list', [
            'rows' => $rows,
            'generatedAt' => now()->format('M j, Y g:i A'),
            'filterSummary' => $filterSummary,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('account-performance-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(Request $request, MetricCalculator $calculator): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        ['rows' => $rows] = $this->filteredRows($request, $calculator);

        return Excel::download(
            new AccountPerformanceExport($rows),
            'account-performance-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * Comma-separated query param -> array of label strings.
     */
    private function parseLabelFilter(Request $request, string $key): array
    {
        return array_values(array_filter(explode(',', $request->string($key)->trim()->toString())));
    }

    /**
     * The filtered row set shared by the page, PDF export, and Excel export —
     * one place for search/PM/health/month filtering so all three stay in sync.
     */
    private function filteredRows(Request $request, MetricCalculator $calculator): array
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $pmId = $request->integer('project_manager_id') ?: null;
        $healthFilter = $this->parseLabelFilter($request, 'health_label');
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;

        $accountIds = BrandAccount::query()
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->pluck('id');

        // PM filter applied to the rows themselves (each row IS a specific
        // cycle, carrying that cycle's own PM) rather than pre-filtering
        // accounts by a column that no longer exists.
        $rows = $this->latestCyclePerAccountPlatform($accountIds, $calculator, $monthTo)
            ->when($pmId, fn ($rows) => $rows->filter(fn ($row) => $row['project_manager']?->id === $pmId))
            ->when($healthFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['health_label'], $healthFilter, true)))
            ->values();

        $parts = [];
        if ($search) {
            $parts[] = 'search: "'.$search.'"';
        }
        if ($healthFilter) {
            $parts[] = 'health: '.implode(', ', $healthFilter);
        }
        if ($monthTo) {
            $parts[] = 'as of: '.$monthTo;
        }

        return [
            'rows' => $rows,
            'filterSummary' => $parts ? implode(' · ', $parts) : 'none',
        ];
    }

    /**
     * Month-over-month trend for one account+platform — every cycle's full
     * score breakdown (Growth/Visibility/Engagement/Health), oldest first, so
     * the detail chart can plot each metric's line across the account's
     * entire history on that platform.
     */
    public function trend(Request $request, BrandAccount $account, MetricCalculator $calculator): JsonResponse
    {
        $platform = $request->string('platform')->trim()->toString() ?: 'instagram';

        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $cycles = $account->cycles()
            ->where(fn ($query) => $query->where('platform', $platform)->orWhere(fn ($q) => $platform === 'instagram' && $q->whereNull('platform')))
            ->orderBy('cycle_start_date')
            ->get();

        $series = $cycles->map(function (BrandCycle $cycle) use ($calculator, $scoreBuckets, $labelBuckets, $formulaWeights) {
            $scores = $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights);

            return [
                'month' => $cycle->cycle_start_date->format('M Y'),
                'cycle_start_date' => $cycle->cycle_start_date->toDateString(),
                'cycle_end_date' => $cycle->cycle_end_date->toDateString(),
                'growth_rate' => round($scores['growth_rate'], 2),
                'visibility_rate' => round($scores['visibility_rate'], 2),
                'engagement_score' => round($scores['engagement_score'], 2),
                'health_rate' => round($scores['health_rate'], 2),
                'health_label' => $scores['health_label'],
            ];
        })->values();

        return response()->json([
            'account' => ['id' => $account->id, 'name' => $account->name],
            'platform' => $platform,
            'series' => $series,
        ]);
    }

    /**
     * One row per account+platform combination, carrying only that
     * combination's MOST RECENT cycle's full score breakdown — same
     * per-platform grouping as Account Health/Account Growth, so an account
     * with both Instagram and TikTok cycles shows two separate rows.
     *
     * $monthTo (optional, 'Y-m'): when set, "most recent" means the latest
     * cycle AT OR BEFORE that month instead of the account's true latest —
     * lets the page be viewed "as of" a past month. An account with no cycle
     * at/before that month is simply excluded, not back-filled with a later one.
     */
    private function latestCyclePerAccountPlatform(Collection $accountIds, MetricCalculator $calculator, ?string $monthTo = null): Collection
    {
        if ($accountIds->isEmpty()) {
            return collect();
        }

        $accountsById = BrandAccount::whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        return BrandCycle::with('projectManager:id,name')
            ->whereIn('account_id', $accountIds)
            ->orderBy('cycle_start_date')
            ->get()
            ->groupBy('account_id')
            ->flatMap(function (Collection $accountCycles, int $accountId) use ($accountsById, $calculator, $scoreBuckets, $labelBuckets, $formulaWeights, $monthTo) {
                $account = $accountsById->get($accountId);
                if (! $account) {
                    return [];
                }

                return $accountCycles
                    ->groupBy(fn (BrandCycle $cycle) => $cycle->platform ?? 'instagram')
                    ->map(function (Collection $cycles, string $platform) use ($account, $calculator, $scoreBuckets, $labelBuckets, $formulaWeights, $monthTo) {
                        if ($monthTo) {
                            $cycles = $cycles->filter(fn (BrandCycle $cycle) => $cycle->cycle_start_date->format('Y-m') <= $monthTo);
                        }
                        $latest = $cycles->last();
                        if (! $latest) {
                            return null;
                        }
                        $scores = $calculator->calculate($latest, $scoreBuckets, $labelBuckets, $formulaWeights);

                        return [
                            'account_id' => $account->id,
                            'account_name' => $account->name,
                            // This row IS a specific cycle (the latest for this
                            // account+platform, possibly as of a chosen month),
                            // so its PM is that cycle's own PM — no separate
                            // "account's current PM" derivation needed here.
                            'project_manager' => $latest->projectManager,
                            'platform' => $platform,
                            'cycle_id' => $latest->id,
                            'latest_cycle_label' => $latest->cycle_start_date->format('M Y'),
                            'cycle_start_date' => $latest->cycle_start_date->toDateString(),
                            'cycle_end_date' => $latest->cycle_end_date->toDateString(),
                            'cycles_count' => $cycles->count(),
                            'growth_rate' => round($scores['growth_rate'], 2),
                            'visibility_rate' => round($scores['visibility_rate'], 2),
                            'engagement_score' => round($scores['engagement_score'], 2),
                            'health_rate' => round($scores['health_rate'], 2),
                            'health_label' => $scores['health_label'],
                        ];
                    })
                    ->filter()
                    ->values();
            })
            ->sortBy('account_name')
            ->values();
    }
}
