<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\BrandHealth\Http\Controllers\Concerns\ComputesAccountHealthAndDelta;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandContentInsight;
use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandPerformance;
use App\BrandHealth\Services\AccountProjectManagerHistory;
use App\BrandHealth\Services\MetricCalculator;
use App\BrandHealth\Services\ScoreBucketResolver;
use App\BrandHealth\Services\SummaryProviderResolver;
use App\Models\User;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View as Response;

class BrandAccountController extends Controller
{
    use ComputesAccountHealthAndDelta;

    public function index(Request $request, MetricCalculator $calculator): Response
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $pmId = $request->integer('project_manager_id') ?: null;
        $sortKey = $request->string('sort')->toString() ?: 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $healthFilter = $this->parseLabelFilter($request, 'health_label');
        $growthFilter = $this->parseLabelFilter($request, 'growth_label');
        $reachFilter = $this->parseLabelFilter($request, 'reach_status');
        $viewsFilter = $this->parseLabelFilter($request, 'views_status');
        $engagementFilter = $this->parseLabelFilter($request, 'engagement_status');

        $allAccountIds = BrandAccount::pluck('id');

        // The health-sort path already computes health/growth for every *matching*
        // account (it has to, to sort/filter by it) — when there's no search/PM/
        // health/growth filter narrowing that set, "matching" is the same as "every
        // account," so the summary KPI (which always covers every account,
        // unfiltered) can reuse that map instead of running MetricCalculator over
        // every account a second time. Any filter active means the health-sort
        // path's set is a subset of all accounts, so the summary needs its own
        // unfiltered pass regardless of which one narrowed it.
        $healthForSummary = null;

        // A status filter, a sort by any computed column (health, growth,
        // reach, views, engagement — none are real DB columns), a PM filter,
        // or a PM sort can only be resolved after scores/PM are computed
        // (PM is derived from each account's latest cycle, not its own
        // column anymore) — all force the same PHP-side compute-then-filter-
        // then-paginate path, even when sorting by name/cycles otherwise would.
        $hasStatusFilter = $healthFilter || $growthFilter || $reachFilter || $viewsFilter || $engagementFilter;
        $needsScorePath = in_array($sortKey, ['health', 'growth', 'reach', 'views', 'engagement', 'pm'], true)
            || $hasStatusFilter
            || $pmId;

        $accounts = $needsScorePath
            ? $this->paginateSortedByHealth($request, $search, $pmId, $direction, $calculator, $healthFilter, $growthFilter, $reachFilter, $viewsFilter, $engagementFilter, $sortKey, $healthForSummary)
            : $this->paginateSortedBySqlColumn($request, $search, $pmId, $sortKey, $direction);

        $allHealth = ($healthForSummary !== null && ! $search && ! $pmId && ! $hasStatusFilter)
            ? $healthForSummary
            : $this->latestCycleHealthByAccount($allAccountIds, $calculator);

        return view('brand-health.accounts.index', [
            'accounts' => $accounts,
            'summary' => [
                'total' => $allAccountIds->count(),
                'instagram_connected' => BrandAccount::whereNotNull('ig_business_id')->count(),
                'at_risk' => $allHealth->filter(fn ($row) => in_array($row['health_label'] ?? null, ['KURANG', 'PARAH'], true))->count(),
            ],
            'filters' => [
                'search' => $search,
                'project_manager_id' => $pmId,
                'sort' => $sortKey,
                'direction' => $direction,
                'health_label' => $healthFilter,
                'growth_label' => $growthFilter,
                'reach_status' => $reachFilter,
                'views_status' => $viewsFilter,
                'engagement_status' => $engagementFilter,
            ],
            'accountDepartmentEmployees' => User::whereHas(
                'department',
                fn ($query) => $query->whereIn('name', ['Accounts', 'Accounts Manager'])
            )->orderBy('name')->get(['id', 'name']),
            'healthLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_HEALTH)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'growthLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ACCOUNT_GROWTH)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'reachStatusLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_REACH_CHANGE)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'viewsStatusLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_VIEWS_CHANGE)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'engagementStatusLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ENGAGEMENT_CHANGE)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'defaultAiProvider' => config('services.brand_health.ai_summary.provider', 'groq'),
        ]);
    }

    /**
     * Median of a collection of numeric values — the middle value when sorted,
     * or the average of the two middle values for an even count. Used
     * alongside the mean (avg_views) so a handful of viral outlier posts
     * doesn't make the "typical" post look higher than it really is — same
     * reasoning as ViewsTrendController's median().
     */
    private function median(Collection $values): ?float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $sorted[$middle];
        }

        return (float) (($sorted[$middle - 1] + $sorted[$middle]) / 2);
    }

    /**
     * Parses a comma-separated multi-value filter param (e.g. "SIP,BAGUS") into
     * an array, or null if absent — same convention as CycleController's label
     * filters, reused here for Accounts' Health/Growth Rate filters.
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
     * One row per cycle (not per account) — respects the same
     * search/PM/Health/Growth filters as index(). Health/Growth still filter
     * by each account's *latest* cycle (same semantics as the accounts list),
     * but once an account passes that filter, every one of its cycles is
     * exported as its own row, each carrying that cycle's own rates/statuses.
     * The Cycle Period column shows the cycle's exact date range (e.g. "1 Feb
     * 2026 - 28 Feb 2026"), not just a month label — cycles don't always align
     * to calendar months (e.g. "20 Jul 2026 - 19 Aug 2026").
     */
    public function exportExcel(Request $request, MetricCalculator $calculator): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $pmId = $request->integer('project_manager_id') ?: null;
        $healthFilter = $this->parseLabelFilter($request, 'health_label');
        $growthFilter = $this->parseLabelFilter($request, 'growth_label');
        $reachFilter = $this->parseLabelFilter($request, 'reach_status');
        $viewsFilter = $this->parseLabelFilter($request, 'views_status');
        $engagementFilter = $this->parseLabelFilter($request, 'engagement_status');

        $accounts = BrandAccount::query()
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($pmId, fn ($query, $pmId) => $query->where('project_manager_id', $pmId))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($healthFilter || $growthFilter || $reachFilter || $viewsFilter || $engagementFilter) {
            $latest = $this->latestCycleHealthByAccount($accounts->pluck('id'), $calculator);
            $accounts = $accounts->filter(function (BrandAccount $account) use ($latest, $healthFilter, $growthFilter, $reachFilter, $viewsFilter, $engagementFilter) {
                $row = $latest->get($account->id);
                if (! $row) {
                    return false;
                }
                if ($healthFilter && ! in_array($row['health_label'], $healthFilter, true)) {
                    return false;
                }
                if ($growthFilter && ! in_array($row['growth_label'], $growthFilter, true)) {
                    return false;
                }
                if ($reachFilter && ! in_array($row['reach_status'] ?? null, $reachFilter, true)) {
                    return false;
                }
                if ($viewsFilter && ! in_array($row['views_status'] ?? null, $viewsFilter, true)) {
                    return false;
                }
                if ($engagementFilter && ! in_array($row['engagement_status'] ?? null, $engagementFilter, true)) {
                    return false;
                }

                return true;
            })->values();
        }

        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();
        $accountGrowthBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_ACCOUNT_GROWTH);
        $viewsChangeBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_VIEWS_CHANGE);
        $reachChangeBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_REACH_CHANGE);
        $engagementChangeBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_ENGAGEMENT_CHANGE);
        $resolver = new ScoreBucketResolver();

        $rows = BrandCycle::whereIn('account_id', $accounts->pluck('id'))
            ->orderBy('cycle_start_date')
            ->get()
            ->groupBy('account_id')
            ->flatMap(function (Collection $accountCycles, $accountId) use ($accounts, $calculator, $scoreBuckets, $labelBuckets, $formulaWeights, $accountGrowthBuckets, $viewsChangeBuckets, $reachChangeBuckets, $engagementChangeBuckets, $resolver) {
                $accountName = $accounts->firstWhere('id', $accountId)?->name ?? '—';

                // "Previous cycle" must be the previous cycle of the SAME
                // platform — an account with interleaved Instagram/TikTok cycles
                // would otherwise compare one platform's views/reach against the
                // other platform's, producing a meaningless % change. Group by
                // platform first, compute previous within each group, then
                // re-flatten (order within the export doesn't matter here).
                return $accountCycles
                    ->groupBy(fn (BrandCycle $cycle) => $cycle->platform ?? 'instagram')
                    ->flatMap(function (Collection $cycles) use ($accountName, $calculator, $scoreBuckets, $labelBuckets, $formulaWeights, $accountGrowthBuckets, $viewsChangeBuckets, $reachChangeBuckets, $engagementChangeBuckets, $resolver) {
                        $ordered = $cycles->values();

                        return $ordered->map(function (BrandCycle $cycle, int $index) use ($accountName, $ordered, $calculator, $scoreBuckets, $labelBuckets, $formulaWeights, $accountGrowthBuckets, $viewsChangeBuckets, $reachChangeBuckets, $engagementChangeBuckets, $resolver) {
                            $scores = $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights);
                            $previous = $index > 0 ? $ordered->get($index - 1) : null;

                            // Views/Reach/Engagement % here are cycle-over-previous-cycle
                            // change (same "vs last cycle" comparison shown in the Growth
                            // modal's Volume panel) — not the absolute view_rate/reach_rate
                            // from MetricCalculator, which is a different (rate-vs-followers)
                            // figure.
                            $viewsChangeRate = $this->percentChange($previous?->views, $cycle->views);
                            $reachChangeRate = $this->percentChange($previous?->reach, $cycle->reach);
                            $engagementChangeRate = $this->percentChange($previous?->engagement, $cycle->engagement);

                            return [
                                'account_name' => $accountName,
                                'cycle_label' => sprintf(
                                    '%s - %s',
                                    $cycle->cycle_start_date->format('j M Y'),
                                    $cycle->cycle_end_date->format('j M Y'),
                                ),
                                'platform' => $cycle->platform,
                                'growth_rate' => round($scores['growth_rate'], 2),
                                'growth_status' => $resolver->resolve($accountGrowthBuckets, $scores['growth_rate'], 'min_score', 'label'),
                                'view_rate' => $viewsChangeRate !== null ? round($viewsChangeRate, 2) : null,
                                'view_status' => $viewsChangeRate !== null ? $resolver->resolve($viewsChangeBuckets, $viewsChangeRate, 'min_score', 'label') : null,
                                'reach_rate' => $reachChangeRate !== null ? round($reachChangeRate, 2) : null,
                                'reach_status' => $reachChangeRate !== null ? $resolver->resolve($reachChangeBuckets, $reachChangeRate, 'min_score', 'label') : null,
                                'engagement_rate' => $engagementChangeRate !== null ? round($engagementChangeRate, 2) : null,
                                'engagement_status' => $engagementChangeRate !== null ? $resolver->resolve($engagementChangeBuckets, $engagementChangeRate, 'min_score', 'label') : null,
                            ];
                        });
                    });
            })
            ->values();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\BrandHealth\Exports\AccountsExport($rows),
            'accounts-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * Sort by name/PM/cycle-count — all real SQL columns, so this stays a normal
     * paginated query. Health/platform data is layered on afterward per-page,
     * same as the health-sort path below.
     */
    private function paginateSortedBySqlColumn(Request $request, ?string $search, ?int $pmId, string $sortKey, string $direction): LengthAwarePaginator
    {
        // 'pm' sort and any PM filter are routed to paginateSortedByHealth()
        // instead (PM is derived from cycles, not a real column to sort/
        // filter in SQL anymore) — this path only ever sees 'name'/'cycles' now.
        $sortColumns = [
            'name' => 'brand_accounts.name',
            'cycles' => 'cycles_count',
        ];
        $sortColumn = $sortColumns[$sortKey] ?? 'brand_accounts.name';

        $accounts = BrandAccount::withCount('cycles')
            ->when($search, fn ($query, $search) => $query->where('brand_accounts.name', 'like', "%{$search}%"))
            ->orderBy($sortColumn, $direction)
            ->orderBy('brand_accounts.name')
            ->paginate(15)
            ->withQueryString();

        $this->decorateWithHealthAndPlatforms($accounts, app(MetricCalculator::class));

        return $accounts;
    }

    /**
     * Sort by Health, and/or filter by Health/Growth Rate — none of these are
     * real columns (they're computed per-account from that account's latest
     * cycle via MetricCalculator, and bucket thresholds are user-configurable
     * so they can't be precomputed in SQL). Loads every account matching the
     * search/PM filters, computes health+growth for all of them, filters by
     * the selected labels, sorts (by severity when sort=health, otherwise
     * name — the caller only routes here for sort=health or an active
     * health/growth filter), then paginates the PHP collection manually —
     * same pattern CycleController uses for its own score-based filtering.
     */
    private function paginateSortedByHealth(Request $request, ?string $search, ?int $pmId, string $direction, MetricCalculator $calculator, ?array $healthFilter, ?array $growthFilter, ?array $reachFilter, ?array $viewsFilter, ?array $engagementFilter, string $sortKey, ?Collection &$healthByAccountOut = null): LengthAwarePaginator
    {
        $allAccounts = BrandAccount::withCount('cycles')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        $pmByAccount = (new AccountProjectManagerHistory())->currentByAccount($allAccounts->pluck('id'));
        $pmNames = User::whereIn('id', $pmByAccount->filter()->unique()->values())->get(['id', 'name'])->keyBy('id');

        // PM filter can only be applied once each account's current PM is
        // derived (it's no longer a real column to filter on in SQL).
        $matching = $pmId
            ? $allAccounts->filter(fn (BrandAccount $account) => $pmByAccount->get($account->id) === $pmId)->values()
            : $allAccounts;

        $healthByAccount = $this->latestCycleHealthByAccount($matching->pluck('id'), $calculator);
        $healthByAccountOut = $healthByAccount;
        $platformsByAccount = $this->platformsByAccount($matching->pluck('id'));

        // Severity order worst-to-best; accounts with no cycles (no health label at
        // all) sort last regardless of direction — "no data" isn't better or worse
        // than a real score, it just doesn't belong ranked among scored accounts.
        $severityRank = ['PARAH' => 0, 'KURANG' => 1, 'CUKUP' => 2, 'BAGUS' => 3, 'SIP' => 4];

        $rows = $matching
            ->map(fn (BrandAccount $account) => [
                'account' => $account,
                'health_label' => $healthByAccount->get($account->id)['health_label'] ?? null,
                'growth_rate' => $healthByAccount->get($account->id)['growth_rate'] ?? null,
                'growth_label' => $healthByAccount->get($account->id)['growth_label'] ?? null,
                'scores' => $healthByAccount->get($account->id),
                'platforms' => $platformsByAccount->get($account->id, []),
                'pm_id' => $pmByAccount->get($account->id),
                'pm_name' => $pmNames->get($pmByAccount->get($account->id))?->name,
            ])
            ->when($healthFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['health_label'], $healthFilter, true)))
            ->when($growthFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['growth_label'], $growthFilter, true)))
            ->when($reachFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['scores']['reach_status'] ?? null, $reachFilter, true)))
            ->when($viewsFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['scores']['views_status'] ?? null, $viewsFilter, true)))
            ->when($engagementFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['scores']['engagement_status'] ?? null, $engagementFilter, true)))
            ->values();

        // Numeric field each sort key ranks by — growth/reach/views/engagement
        // sort by their actual rate (finer-grained than the status label alone),
        // so two accounts both labeled "Sip" still order sensibly against each
        // other instead of tying.
        $numericField = [
            'growth' => 'growth_rate',
            'reach' => 'reach_change_rate',
            'views' => 'views_change_rate',
            'engagement' => 'engagement_change_rate',
        ][$sortKey] ?? null;

        if ($sortKey === 'health') {
            $sorted = $rows->sort(function ($a, $b) use ($severityRank, $direction) {
                $rankA = $severityRank[$a['health_label']] ?? PHP_INT_MAX;
                $rankB = $severityRank[$b['health_label']] ?? PHP_INT_MAX;

                if ($rankA === PHP_INT_MAX && $rankB === PHP_INT_MAX) {
                    return 0;
                }
                if ($rankA === PHP_INT_MAX || $rankB === PHP_INT_MAX) {
                    return $rankA <=> $rankB;
                }

                return $direction === 'desc' ? $rankB <=> $rankA : $rankA <=> $rankB;
            })->values();
        } elseif ($sortKey === 'pm') {
            // No-PM accounts sort last regardless of direction, same "no data"
            // convention as health/numeric sorts above.
            $sorted = $rows->sort(function ($a, $b) use ($direction) {
                $nameA = $a['pm_name'];
                $nameB = $b['pm_name'];

                if ($nameA === null && $nameB === null) {
                    return 0;
                }
                if ($nameA === null || $nameB === null) {
                    return $nameA === null ? 1 : -1;
                }

                return $direction === 'desc' ? strcasecmp($nameB, $nameA) : strcasecmp($nameA, $nameB);
            })->values();
        } elseif ($numericField) {
            // Accounts with no value (no cycles, or no prior cycle to compare
            // against) sort last regardless of direction — same "no data isn't
            // better or worse than a real value" rule as the health sort above.
            $sorted = $rows->sort(function ($a, $b) use ($numericField, $direction) {
                $valueA = $a['scores'][$numericField] ?? null;
                $valueB = $b['scores'][$numericField] ?? null;

                if ($valueA === null && $valueB === null) {
                    return 0;
                }
                if ($valueA === null || $valueB === null) {
                    return $valueA === null ? 1 : -1;
                }

                return $direction === 'desc' ? $valueB <=> $valueA : $valueA <=> $valueB;
            })->values();
        } else {
            $sorted = $rows;
        }

        $page = $request->integer('page', 1);
        $perPage = 15;

        $paginator = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $paginator->through(fn ($row) => $this->presentAccount($row['account'], $row['scores'], $row['platforms'], $row['pm_id'], $pmNames));

        return $paginator;
    }

    /**
     * Layers health_label/platforms onto an already-paginated (SQL-sorted) page
     * of accounts — shared by the SQL-sort path so it doesn't duplicate the
     * present-a-row shape used by the health-sort path.
     */
    private function decorateWithHealthAndPlatforms(LengthAwarePaginator $accounts, MetricCalculator $calculator): void
    {
        $healthByAccount = $this->latestCycleHealthByAccount($accounts->pluck('id'), $calculator);
        $platformsByAccount = $this->platformsByAccount($accounts->pluck('id'));
        $pmByAccount = (new AccountProjectManagerHistory())->currentByAccount($accounts->pluck('id'));
        $pmNames = User::whereIn('id', $pmByAccount->filter()->unique()->values())->get(['id', 'name'])->keyBy('id');

        $accounts->through(fn (BrandAccount $account) => $this->presentAccount(
            $account,
            $healthByAccount->get($account->id),
            $platformsByAccount->get($account->id, []),
            $pmByAccount->get($account->id),
            $pmNames,
        ));
    }

    /**
     * @param  array{health_label?: ?string, growth_rate?: ?float, growth_label?: ?string, views_status?: ?string, reach_status?: ?string, engagement_status?: ?string}|null  $scores
     */
    private function presentAccount(BrandAccount $account, ?array $scores, array $platforms, ?int $pmId = null, ?Collection $pmNames = null): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'project_manager_id' => $pmId,
            'project_manager' => $pmId && $pmNames ? $pmNames->get($pmId) : null,
            'cycles_count' => $account->cycles_count,
            'ig_business_id' => $account->ig_business_id,
            'ig_username' => $account->ig_username,
            'ig_connected_at' => $account->ig_connected_at?->toIso8601String(),
            'health_label' => $scores['health_label'] ?? null,
            'growth_rate' => isset($scores['growth_rate']) ? round($scores['growth_rate'], 2) : null,
            'growth_label' => $scores['growth_label'] ?? null,
            'views_status' => $scores['views_status'] ?? null,
            'reach_status' => $scores['reach_status'] ?? null,
            'engagement_status' => $scores['engagement_status'] ?? null,
            'by_platform' => $scores['by_platform'] ?? [],
            'platforms' => $platforms,
        ];
    }

    /**
     * Which platforms (instagram/tiktok) each account has at least one cycle
     * on, keyed by account_id — drives the platform icons on the accounts list.
     */
    private function platformsByAccount(Collection $accountIds): Collection
    {
        if ($accountIds->isEmpty()) {
            return collect();
        }

        return BrandCycle::whereIn('account_id', $accountIds)
            ->get(['account_id', 'platform'])
            ->groupBy('account_id')
            ->map(fn (Collection $cycles) => $cycles->pluck('platform')->unique()->values()->all());
    }

    public function growth(BrandAccount $account, MetricCalculator $calculator): JsonResponse
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();
        $accountGrowthBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_ACCOUNT_GROWTH);
        $resolver = new ScoreBucketResolver();

        // Fetched once and passed into both helpers below — they used to each run
        // their own identical Performance::where('account_id', ...)->with('igSnapshots')
        // query independently, doubling the query for every "View Growth" modal open.
        $performances = BrandPerformance::where('account_id', $account->id)
            ->with(['igSnapshots' => fn ($query) => $query->limit(1)])
            ->get();

        $postCountsByCycle = $this->postCountsByCycle($performances);

        // Manually-entered Instagram Insights "Overview" numbers (Viewers /
        // Interactions, each split Posts / Reels / Story), keyed by cycle.
        $contentInsightsByCycle = BrandContentInsight::where('account_id', $account->id)
            ->get()
            ->keyBy('cycle_id');

        $cycles = $account->cycles()
            ->with('projectManager:id,name')
            ->orderBy('cycle_start_date')
            ->get()
            ->map(function ($cycle) use ($calculator, $scoreBuckets, $labelBuckets, $formulaWeights, $postCountsByCycle, $accountGrowthBuckets, $resolver, $contentInsightsByCycle) {
                $scores = $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights);
                $postCounts = $postCountsByCycle->get($cycle->id, ['post_count' => 0, 'view_count' => 0]);
                $insight = $contentInsightsByCycle->get($cycle->id);

                return [
                    'label' => $cycle->cycle_start_date->format('M Y'),
                    'cycle_start_date' => $cycle->cycle_start_date->toDateString(),
                    'cycle_end_date' => $cycle->cycle_end_date?->toDateString(),
                    'platform' => $cycle->platform,
                    'project_manager_name' => $cycle->projectManager?->name,
                    'growth_rate' => round($scores['growth_rate'], 2),
                    'growth_rate_label' => $resolver->resolve($accountGrowthBuckets, $scores['growth_rate'], 'min_score', 'label'),
                    'end_follower' => $cycle->end_follower,
                    'reach' => $cycle->reach,
                    'views' => $cycle->views,
                    'engagement' => $cycle->engagement,
                    'story_performance' => $cycle->story_performance,
                    'visibility_rate' => round($scores['visibility_rate'], 2),
                    'engagement_score' => round($scores['engagement_score'], 2),
                    'health_rate' => round($scores['health_rate'], 2),
                    'health_label' => $scores['health_label'],
                    'er_reach_rate' => round($scores['er_reach_rate'], 2),
                    'er_follower_rate' => round($scores['er_follower_rate'], 2),
                    'reach_views_ads_spend' => (float) $cycle->reach_views_ads_spend,
                    'engagement_ads_spend' => (float) $cycle->engagement_ads_spend,
                    'ads_currency' => $cycle->ads_currency,
                    'post_count' => $postCounts['post_count'],
                    'view_count' => $postCounts['view_count'],
                    // null when no Content Insight was entered for this cycle —
                    // the chart skips (spanGaps) those points.
                    'content_insight' => $insight ? [
                        'viewers_posts' => $insight->viewers_posts,
                        'viewers_reels' => $insight->viewers_reels,
                        'viewers_story' => $insight->viewers_story,
                        'interactions_posts' => $insight->interactions_posts,
                        'interactions_reels' => $insight->interactions_reels,
                        'interactions_story' => $insight->interactions_story,
                    ] : null,
                ];
            });

        $pmTimeline = (new AccountProjectManagerHistory())->timeline($account);
        $pmIds = $pmTimeline->pluck('project_manager_id')->filter()->unique()->values();
        $pmNames = User::whereIn('id', $pmIds)->pluck('name', 'id');

        return response()->json([
            'account' => ['id' => $account->id, 'name' => $account->name],
            'cycles' => $cycles,
            // Consecutive-same-PM runs across the account's whole cycle
            // history (oldest first) — "No PM yet" is its own run so a gap
            // still shows up rather than silently vanishing from the list.
            'pmHistory' => $pmTimeline->map(fn ($run) => [
                'project_manager_name' => $run['project_manager_id'] ? $pmNames->get($run['project_manager_id']) : null,
                'from' => $run['from']->toDateString(),
                'to' => $run['to']->toDateString(),
            ])->values(),
            'postSummary' => $this->postSummaryByPlatform($performances),
            'ai_summary' => $account->ai_summary,
            'ai_summary_generated_at' => $account->ai_summary_generated_at?->toIso8601String(),
            // Change-labeling buckets for the Growth Analysis panel — resolved
            // client-side against the two picked cycles' raw values, since that
            // comparison is computed in the browser (same reasoning growth_rate_label
            // above is precomputed per-cycle: identical "highest min <= rate wins"
            // rule, just applied to a period-over-period % change instead of a
            // single cycle's own rate).
            'changeLabelBuckets' => [
                'views' => $labelBuckets->where('metric', BrandLabelBucket::METRIC_VIEWS_CHANGE)->values(),
                'reach' => $labelBuckets->where('metric', BrandLabelBucket::METRIC_REACH_CHANGE)->values(),
                'engagement' => $labelBuckets->where('metric', BrandLabelBucket::METRIC_ENGAGEMENT_CHANGE)->values(),
            ],
        ]);
    }

    /**
     * Views H+7 for a post, overlaid from its latest Instagram snapshot when
     * linked — same source used everywhere else this metric is shown, since
     * the manually-typed total_views_h7 column goes stale once a post is
     * linked to Instagram.
     */
    private function viewsFor(BrandPerformance $performance): ?int
    {
        $snapshot = $performance->igSnapshots->first();

        return $snapshot ? ($snapshot->views ?? $snapshot->total_interactions) : $performance->total_views_h7;
    }

    /**
     * Total post count, total Views H+7, and average Views H+7 for this
     * account, split by platform (matching the modal's Instagram/TikTok
     * tabs) — plus a with-cycle / without-cycle breakdown of posts and views,
     * since a post's cycle_id is auto-assigned only when a cycle's date range
     * actually covers its post_date (see PerformanceCycleAssigner) and some
     * posts legitimately have none yet.
     */
    private function postSummaryByPlatform(Collection $performances): array
    {
        $countAndViews = function (Collection $group) {
            $viewsWithValue = $group->map(fn (BrandPerformance $performance) => $this->viewsFor($performance))
                ->filter(fn ($views) => $views !== null);

            return [
                'total_posts' => $group->count(),
                'total_views' => (int) $viewsWithValue->sum(),
            ];
        };

        $summaryFor = function (string $platform) use ($performances, $countAndViews) {
            $platformPerformances = $performances->filter(fn (BrandPerformance $performance) => ($performance->platform ?? 'instagram') === $platform);
            $viewsWithValue = $platformPerformances->map(fn (BrandPerformance $performance) => $this->viewsFor($performance))
                ->filter(fn ($views) => $views !== null);

            return [
                'total_posts' => $platformPerformances->count(),
                'total_views' => (int) $viewsWithValue->sum(),
                // Averaged/medianed over posts with a recorded view count, not every
                // post — a post with no data yet shouldn't drag either figure toward
                // zero (matches avg_views/median_views elsewhere).
                'avg_views' => $viewsWithValue->isEmpty() ? null : (int) round($viewsWithValue->avg()),
                'median_views' => $viewsWithValue->isEmpty() ? null : (int) round($this->median($viewsWithValue)),
                'with_cycle' => $countAndViews($platformPerformances->filter(fn (BrandPerformance $performance) => $performance->cycle_id !== null)),
                'without_cycle' => $countAndViews($platformPerformances->filter(fn (BrandPerformance $performance) => $performance->cycle_id === null)),
            ];
        };

        return [
            'instagram' => $summaryFor('instagram'),
            'tiktok' => $summaryFor('tiktok'),
        ];
    }

    /**
     * Post count and total Views H+7 per cycle, keyed by cycle_id — used to
     * plot "posts/views per cycle" trend lines alongside the existing
     * follower/rate charts. Posts with no matching cycle (Performance's
     * auto-assigned cycle_id is null) are simply excluded, since this report
     * is indexed by cycle.
     */
    private function postCountsByCycle(Collection $performances): Collection
    {
        return $performances
            ->whereNotNull('cycle_id')
            ->groupBy('cycle_id')
            ->map(fn (Collection $performances) => [
                'post_count' => $performances->count(),
                'view_count' => (int) $performances->sum(fn (BrandPerformance $performance) => $this->viewsFor($performance) ?? 0),
            ]);
    }

    public function summarize(Request $request, BrandAccount $account, MetricCalculator $calculator, SummaryProviderResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
            'provider' => ['nullable', 'string', 'in:'.implode(',', SummaryProviderResolver::PROVIDERS)],
        ]);

        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $cycles = $account->cycles()
            ->orderBy('cycle_start_date')
            ->get()
            ->map(fn ($cycle) => [
                'cycle' => $cycle->setRelation('account', $account),
                'scores' => $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights),
            ]);

        if ($cycles->isEmpty()) {
            return response()->json(['message' => 'No cycles recorded for this account yet.'], 422);
        }

        $filterDescription = ['summary' => "account: {$account->name}"];

        try {
            $summary = $resolver->resolve($data['provider'] ?? null)
                ->summarizeFiltered($cycles, $filterDescription, $data['prompt'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $account->forceFill([
            'ai_summary' => $summary,
            'ai_summary_generated_at' => now(),
        ])->save();

        return response()->json([
            'summary' => $summary,
            'cycle_count' => $cycles->count(),
            'generated_at' => $account->ai_summary_generated_at->toIso8601String(),
        ]);
    }

    public function pdf(Request $request, BrandAccount $account): HttpResponse
    {
        $data = $request->validate([
            'charts' => ['required', 'array', 'min:1'],
            'charts.*.label' => ['required', 'string'],
            'charts.*.image' => ['required', 'string'],
            'ai_summary' => ['nullable', 'string'],
            'summary' => ['nullable', 'array'],
            'summary.platform' => ['nullable', 'string'],
            'summary.followers' => ['nullable', 'numeric'],
            'summary.followers_delta' => ['nullable', 'numeric'],
            'summary.health_label' => ['nullable', 'string'],
            'summary.health_rate' => ['nullable', 'numeric'],
            'summary.growth_rate' => ['nullable', 'numeric'],
            'summary.avg_views' => ['nullable', 'numeric'],
            'summary.median_views' => ['nullable', 'numeric'],
            'summary.total_posts' => ['nullable', 'numeric'],
            'summary.latest_cycle_label' => ['nullable', 'string'],
            'summary.volume' => ['nullable', 'array'],
            'summary.volume.*.label' => ['required_with:summary.volume', 'string'],
            'summary.volume.*.value' => ['nullable', 'numeric'],
            'summary.volume.*.delta' => ['nullable', 'numeric'],
        ]);

        $pdf = Pdf::loadView('brand-health.pdf.account-growth', [
            'account' => $account,
            'charts' => $data['charts'],
            'aiSummary' => $data['ai_summary'] ?? null,
            'summary' => $data['summary'] ?? null,
            'generatedAt' => now()->format('M j, Y g:i A'),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf('%s-growth-%s.pdf', Str::slug($account->name), now()->format('Y-m-d'));

        return $pdf->download($filename);
    }

    public function store(Request $request): RedirectResponse
    {
        // No project_manager_id here — an account's PM is derived from its
        // cycles (see AccountProjectManagerHistory::currentByAccount()), and a brand-new account has none
        // yet. It'll show "No PM yet" until its first cycle is created.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        BrandAccount::create($data);

        return back();
    }

    public function update(Request $request, BrandAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $account->update($data);

        return back();
    }

    public function destroy(BrandAccount $account): RedirectResponse
    {
        $account->delete();

        return back();
    }
}
