<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Exports\ViewsTrendExport;
use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandPerformance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View as Response;
use Maatwebsite\Excel\Facades\Excel;

class BrandViewsTrendController extends Controller
{
    /**
     * A drop of 15%+ in median views vs the previous cycle.
     */
    private const SETBACK_THRESHOLD = -0.15;

    /**
     * A rise of 15%+ in median views vs the previous cycle.
     */
    private const GROWTH_THRESHOLD = 0.15;

    /**
     * Cycle-to-cycle swing within this band counts as "flat" for stagnation purposes.
     */
    private const STAGNANT_BAND = 0.10;

    /**
     * How many trailing flat cycles in a row before we call it stagnant.
     */
    private const STAGNANT_STREAK = 3;

    public function index(Request $request): Response
    {
        ['rows' => $sorted, 'counts' => $counts, 'filters' => $filters] = $this->filteredSortedRows($request);

        $page = $request->integer('page', 1);
        $perPage = 15;

        $paginator = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        // The chart is scoped by filters only, never by the table's sort/page —
        // it always shows every filtered account (worst-first), so sorting or
        // paging the table below it doesn't reshuffle or truncate the chart.
        $chartRequest = Request::create($request->url(), 'GET', array_merge($request->query(), ['sort' => null, 'direction' => null]));
        ['rows' => $chartRows] = $this->filteredSortedRows($chartRequest);

        return view('brand-health.views-trend.index', [
            'filters' => $filters,
            'rows' => $paginator,
            'chartRows' => $chartRows->values(),
            'counts' => $counts,
            // Distinct months a cycle actually starts in, for the month picker —
            // same convention used by the PM/Conceptor ranking's cycle picker.
            // Plucks just the date column (skips hydrating full Cycle models
            // for every row) rather than DATE_FORMAT() in SQL, which isn't
            // portable to the SQLite connection the test suite runs on.
            'months' => BrandCycle::query()
                ->when(
                    in_array($filters['platform'], ['instagram', 'tiktok'], true),
                    fn ($query) => $query->where('platform', $filters['platform']),
                )
                ->pluck('cycle_start_date')
                ->map(fn ($date) => $date->format('Y-m'))
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function pdf(Request $request): HttpResponse
    {
        ['rows' => $rows, 'filters' => $filters] = $this->filteredSortedRows($request);

        $pdf = Pdf::loadView('brand-health.pdf.views-trend-list', [
            'rows' => $rows->values(),
            'generatedAt' => now()->format('M j, Y g:i A'),
            'filterSummary' => $this->filterSummary($filters),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('views-trend-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        ['rows' => $rows] = $this->filteredSortedRows($request);

        return Excel::download(
            new ViewsTrendExport($rows->values()),
            'views-trend-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * The filtered, sorted (but not yet paginated) row set shared by the page,
     * the PDF export, and the Excel export — one place for search/platform/trend
     * filtering and sort logic so all three stay in sync.
     */
    private function filteredSortedRows(Request $request): array
    {
        $search = $request->string('search')->trim()->toString() ?: null;

        $platform = $request->string('platform')->toString();
        $platform = in_array($platform, ['instagram', 'tiktok'], true) ? $platform : 'all';

        // The UI only exposes two buckets (Increasing/Decreasing) over the
        // underlying 4-way classification — growing+stable count as
        // Increasing, setback+stagnant count as Decreasing. insufficient_data
        // stays its own bucket since it isn't a growth/decline reading at all.
        $trendGroupValues = [
            'increasing' => ['growing', 'stable'],
            'decreasing' => ['setback', 'stagnant'],
            'insufficient_data' => ['insufficient_data'],
        ];
        $trendFilterGroup = $request->string('trend')->toString();
        $trendFilterValues = $trendGroupValues[$trendFilterGroup] ?? null;

        // From/To month picker — an explicit comparison, not just "whatever
        // cycle happens to precede the picked month". "Last cycle" becomes
        // each account's cycle at-or-before month_to, "Prior cycle" becomes
        // its cycle at-or-before month_from (so picking Jun -> Aug compares
        // June against August specifically, even if the account also has a
        // July cycle in between). With only month_to set (or neither), falls
        // back to today's default: latest cycle vs. the one right before it.
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;
        $monthFrom = $request->string('month_from')->trim()->toString() ?: null;

        $sortKey = $request->string('sort')->toString();
        $sortKey = in_array($sortKey, ['account', 'last_avg_views', 'prior_avg_views', 'last_max_views', 'last_min_views', 'last_post_count', 'delta_pct'], true) ? $sortKey : 'trend';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $accounts = BrandAccount::orderBy('name')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->get(['id', 'name']);

        // Keyed by "{account_id}:{platform}", never merged across platforms — an
        // account's Instagram and TikTok view counts are different audiences and
        // averaging/interleaving them into one series would misrepresent both.
        $cyclesByAccountPlatform = BrandCycle::orderBy('cycle_start_date')
            ->when($platform !== 'all', fn ($query) => $query->where('platform', $platform))
            ->get(['id', 'account_id', 'platform', 'cycle_start_date'])
            ->groupBy(fn (BrandCycle $cycle) => "{$cycle->account_id}:{$cycle->platform}");

        $viewsByCycle = $this->avgViewsByCycle();
        $maxViewsByCycle = $this->maxViewsByCycle();
        $minViewsByCycle = $this->minViewsByCycle();
        $postCountByCycle = $this->postCountByCycle();
        // All four derive from the same per-cycle performance scan (viewsByCyclePerformances(),
        // memoized below) — this used to independently re-run that full-table query, once
        // per method call. Since index() calls filteredSortedRows() twice (table + chart)
        // and pdf()/exportExcel() add a third, that meant up to 4 full scans of Performance
        // per page load. Memoizing the underlying scan collapses that back to 1.

        $rows = $accounts
            ->flatMap(function (BrandAccount $account) use ($cyclesByAccountPlatform, $viewsByCycle, $maxViewsByCycle, $minViewsByCycle, $postCountByCycle, $platform, $monthFrom, $monthTo) {
                $platforms = $platform === 'all' ? ['instagram', 'tiktok'] : [$platform];

                return collect($platforms)->map(function (string $accountPlatform) use ($account, $cyclesByAccountPlatform, $viewsByCycle, $maxViewsByCycle, $minViewsByCycle, $postCountByCycle, $monthFrom, $monthTo) {
                    $cycles = $cyclesByAccountPlatform->get("{$account->id}:{$accountPlatform}", collect());

                    $series = $cycles->map(fn (BrandCycle $cycle) => [
                        'cycle_id' => $cycle->id,
                        'label' => $cycle->cycle_start_date->format('M Y'),
                        'month' => $cycle->cycle_start_date->format('Y-m'),
                        'avg_views' => $viewsByCycle->get($cycle->id),
                        'max_views' => $maxViewsByCycle->get($cycle->id),
                        'min_views' => $minViewsByCycle->get($cycle->id),
                        'post_count' => $postCountByCycle->get($cycle->id, 0),
                    ])->filter(fn ($point) => $point['avg_views'] !== null)->values();

                    // month_to trims the series so "last" is this account's cycle
                    // at-or-before month_to (the whole "as of a past month" behavior
                    // from before) — the untrimmed series is kept separately so an
                    // explicit month_from can still look further back than month_to
                    // - 1 cycle for "prior".
                    $trimmedSeries = $monthTo
                        ? $series->filter(fn ($point) => $point['month'] <= $monthTo)->values()
                        : $series;

                    // An explicit two-month comparison (both From and To picked) needs
                    // an account to actually have a cycle in BOTH months themselves —
                    // without this, buildRow()'s "nearest cycle at-or-before" fallback
                    // would silently pair, say, August against some unrelated June
                    // cycle (missing a From-month cycle) or resolve "last" to that same
                    // stale cycle (missing a To-month cycle), either of which reads as
                    // a wrong/stale comparison rather than "no data for this account."
                    if ($monthFrom && $monthTo) {
                        $hasFromMonth = $series->contains(fn ($point) => $point['month'] === $monthFrom);
                        $hasToMonth = $series->contains(fn ($point) => $point['month'] === $monthTo);
                        if (!$hasFromMonth || !$hasToMonth) {
                            return null;
                        }
                    }

                    return $this->buildRow($account, $accountPlatform, $trimmedSeries, $monthFrom);
                });
            })
            ->filter(fn ($row) => $row !== null)
            ->values();

        $counts = [
            'setback' => $rows->where('trend', 'setback')->count(),
            'stagnant' => $rows->where('trend', 'stagnant')->count(),
            'growing' => $rows->where('trend', 'growing')->count(),
            'stable' => $rows->where('trend', 'stable')->count(),
            'insufficient_data' => $rows->where('trend', 'insufficient_data')->count(),
            // Grouped counts the UI's two stat cards actually show — Increasing
            // (growing + stable) / Decreasing (setback + stagnant).
            'increasing' => $rows->whereIn('trend', ['growing', 'stable'])->count(),
            'decreasing' => $rows->whereIn('trend', ['setback', 'stagnant'])->count(),
            'total' => $rows->count(),
        ];

        // Worst-first is the default: setback -> stagnant -> stable -> growing -> insufficient,
        // since that's what a boss scanning this page cares about seeing at the top.
        // Any other column can be sorted explicitly via the table header.
        $trendRank = ['setback' => 0, 'stagnant' => 1, 'stable' => 2, 'growing' => 3, 'insufficient_data' => 4];

        $comparator = match ($sortKey) {
            'account' => fn ($a, $b) => $a['account_name'] <=> $b['account_name'],
            // nulls (insufficient data) always sort last, regardless of direction.
            'last_avg_views' => fn ($a, $b) => ($a['last_avg_views'] ?? -1) <=> ($b['last_avg_views'] ?? -1),
            'prior_avg_views' => fn ($a, $b) => ($a['prior_avg_views'] ?? -1) <=> ($b['prior_avg_views'] ?? -1),
            'last_max_views' => fn ($a, $b) => ($a['last_max_views'] ?? -1) <=> ($b['last_max_views'] ?? -1),
            'last_min_views' => fn ($a, $b) => ($a['last_min_views'] ?? -1) <=> ($b['last_min_views'] ?? -1),
            'last_post_count' => fn ($a, $b) => ($a['last_post_count'] ?? -1) <=> ($b['last_post_count'] ?? -1),
            'delta_pct' => fn ($a, $b) => ($a['delta_pct'] ?? -INF) <=> ($b['delta_pct'] ?? -INF),
            default => fn ($a, $b) => $trendRank[$a['trend']] <=> $trendRank[$b['trend']],
        };

        $sorted = $rows
            ->when($trendFilterValues, fn (Collection $rows) => $rows->whereIn('trend', $trendFilterValues))
            ->sort(function ($a, $b) use ($comparator, $sortKey, $direction) {
                $result = $comparator($a, $b);

                if ($sortKey !== 'trend' && $direction === 'desc') {
                    $result = -$result;
                }

                return $result
                    ?: $a['account_name'] <=> $b['account_name']
                    ?: $a['platform'] <=> $b['platform'];
            })
            ->values();

        return [
            'rows' => $sorted,
            'counts' => $counts,
            'filters' => ['search' => $search, 'platform' => $platform, 'trend' => $trendFilterGroup ?: null, 'month_from' => $monthFrom, 'month_to' => $monthTo, 'sort' => $sortKey, 'direction' => $direction],
        ];
    }

    /**
     * Human-readable filter description for the PDF export header, matching the
     * pattern used by the Cycles/Performance list exports.
     */
    private function filterSummary(array $filters): string
    {
        $parts = [];

        if ($filters['search']) {
            $parts[] = "search: \"{$filters['search']}\"";
        }
        if ($filters['platform'] !== 'all') {
            $parts[] = 'platform: '.($filters['platform'] === 'tiktok' ? 'TikTok' : 'Instagram');
        }
        if ($filters['trend']) {
            $parts[] = 'trend: '.$filters['trend'];
        }
        if (!empty($filters['month_from']) && !empty($filters['month_to'])) {
            $parts[] = 'compared: '.$filters['month_from'].' vs '.$filters['month_to'];
        } elseif (!empty($filters['month_to'])) {
            $parts[] = 'as of: '.$filters['month_to'];
        }

        $sortLabels = [
            'account' => 'Account',
            'last_avg_views' => 'Last Cycle Median Views',
            'prior_avg_views' => 'Prior Cycle Median Views',
            'last_max_views' => 'Last Cycle Max Views',
            'last_min_views' => 'Last Cycle Min Views',
            'last_post_count' => 'Total Reels',
            'delta_pct' => 'Delta %',
            'trend' => 'Trend (default)',
        ];
        $parts[] = 'sorted by: '.($sortLabels[$filters['sort']] ?? $filters['sort']).' ('.strtoupper($filters['direction']).')';

        return implode(', ', $parts);
    }

    /**
     * Full per-cycle views series for one account+platform — the data behind
     * the detail modal. Views-only (no followers/health/reach): this endpoint
     * exists specifically so the modal can stay focused on what this page is
     * about, unlike the general-purpose AccountGrowthModal.
     */
    public function detail(BrandAccount $account, Request $request): JsonResponse
    {
        $platform = $request->string('platform')->toString();
        $platform = in_array($platform, ['instagram', 'tiktok'], true) ? $platform : 'instagram';

        // Scoped to this account+platform only — unlike the page/export queries above,
        // which need every account's data at once, this endpoint only ever needs one
        // account's rows, so there's no reason to scan the whole Performance table.
        $performances = BrandPerformance::where('account_id', $account->id)
            ->where('platform', $platform)
            ->whereNotNull('cycle_id')
            ->with(['igSnapshots' => fn ($query) => $query->limit(1)])
            ->get()
            ->groupBy('cycle_id');

        // Median/max/min per cycle — median for the "typical post" trend line,
        // max/min for the ceiling and floor each cycle actually reached.
        $viewsPerCycle = $performances->map(function (Collection $cyclePerformances) {
            $views = $cyclePerformances
                ->map(function (BrandPerformance $performance) {
                    $snapshot = $performance->igSnapshots->first();

                    return $snapshot ? ($snapshot->views ?? $snapshot->total_interactions) : $performance->total_views_h7;
                })
                ->filter(fn ($views) => $views !== null);

            return $views->isEmpty() ? null : $views;
        });

        $medianByCycle = $viewsPerCycle->map(fn ($views) => $views === null ? null : (int) round($this->median($views)));
        $maxByCycle = $viewsPerCycle->map(fn ($views) => $views === null ? null : (int) $views->max());
        $minByCycle = $viewsPerCycle->map(fn ($views) => $views === null ? null : (int) $views->min());
        $totalByCycle = $viewsPerCycle->map(fn ($views) => $views === null ? null : (int) $views->sum());

        $postCountsByCycle = $performances->map(fn (Collection $cyclePerformances) => $cyclePerformances->count());

        $cycles = BrandCycle::where('account_id', $account->id)
            ->where('platform', $platform)
            ->orderBy('cycle_start_date')
            ->get();

        $series = $cycles->map(function (BrandCycle $cycle) use ($medianByCycle, $maxByCycle, $minByCycle, $totalByCycle, $postCountsByCycle) {
            return [
                'cycle_id' => $cycle->id,
                'label' => $cycle->cycle_start_date->format('M Y'),
                'cycle_start_date' => $cycle->cycle_start_date->toDateString(),
                'cycle_end_date' => $cycle->cycle_end_date->toDateString(),
                'avg_views' => $medianByCycle->get($cycle->id),
                'max_views' => $maxByCycle->get($cycle->id),
                'min_views' => $minByCycle->get($cycle->id),
                'total_views' => $totalByCycle->get($cycle->id),
                'post_count' => $postCountsByCycle->get($cycle->id, 0),
            ];
        })->values();

        // Delta vs the immediately preceding cycle, per point (based on median
        // views) — drives the sparkline of ups/downs in the table without
        // repeating the page's aggregate classification.
        $seriesWithDelta = $series->values()->map(function ($point, $index) use ($series) {
            if ($index === 0 || $point['avg_views'] === null) {
                return [...$point, 'delta_pct' => null];
            }

            $previous = $series[$index - 1];
            if ($previous['avg_views'] === null || $previous['avg_views'] <= 0) {
                return [...$point, 'delta_pct' => null];
            }

            return [...$point, 'delta_pct' => round((($point['avg_views'] - $previous['avg_views']) / $previous['avg_views']) * 100, 1)];
        });

        return response()->json([
            'account' => ['id' => $account->id, 'name' => $account->name],
            'platform' => $platform,
            'series' => $seriesWithDelta,
        ]);
    }

    /**
     * The individual posts behind one cycle row in the detail modal's
     * per-cycle table — lets a user drill from "this cycle's numbers" straight
     * to "what did we actually post that cycle."
     */
    public function cyclePosts(BrandAccount $account, BrandCycle $cycle): JsonResponse
    {
        $posts = BrandPerformance::where('account_id', $account->id)
            ->where('cycle_id', $cycle->id)
            ->with(['videoLinks', 'igSnapshots' => fn ($query) => $query->limit(1)])
            ->get()
            ->map(function (BrandPerformance $performance) {
                $snapshot = $performance->igSnapshots->first();
                $resolvedViews = $snapshot ? ($snapshot->views ?? $snapshot->total_interactions) : $performance->total_views_h7;

                return [
                    'id' => $performance->id,
                    'platform' => $performance->platform,
                    'post_date' => $performance->post_date?->toDateString(),
                    'ads' => (bool) $performance->ads,
                    'views' => $resolvedViews !== null ? (int) $resolvedViews : null,
                    'link' => $performance->videoLinks->first()?->url,
                    'media_product_type' => $performance->ig_media_product_type,
                ];
            })
            ->sortByDesc('post_date')
            ->values();

        return response()->json([
            'account' => ['id' => $account->id, 'name' => $account->name],
            'cycle' => ['id' => $cycle->id, 'label' => $cycle->cycle_start_date->format('M Y')],
            'posts' => $posts,
        ]);
    }

    /**
     * PM and Conceptor leaderboards, ranked by average Views H+7 across every
     * post they're credited on — "best" here means the highest average views
     * per post, not total volume, so someone with 5 posts isn't automatically
     * beaten by someone with 50. Only counts posts with a recorded view value;
     * a person with zero recorded-view posts has nothing to rank and is
     * excluded rather than shown with a misleading "0 avg views".
     */
    public function ranking(Request $request): JsonResponse
    {
        ['filters' => $filters, 'projectManagers' => $pms, 'conceptors' => $conceptors] = $this->rankingData($request);

        return response()->json([
            'filters' => $filters,
            'projectManagers' => $pms,
            'conceptors' => $conceptors,
            // Distinct months a cycle actually starts in — so the "By Cycle"
            // picker can offer only real cycle months rather than an open-ended
            // date input. Sorted oldest-first. Plucks just the date column
            // instead of hydrating full Cycle models for every row.
            'cycleMonths' => BrandCycle::query()
                ->when(
                    in_array($filters['platform'], ['instagram', 'tiktok'], true),
                    fn ($query) => $query->where('platform', $filters['platform']),
                )
                ->pluck('cycle_start_date')
                ->map(fn ($date) => $date->format('Y-m'))
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    /**
     * PM/Conceptor ranking as a PDF, respecting the same platform + month-range
     * filters the modal shows. Both leaderboards on one document.
     */
    public function rankingPdf(Request $request): HttpResponse
    {
        ['filters' => $filters, 'projectManagers' => $pms, 'conceptors' => $conceptors] = $this->rankingData($request);

        $pdf = Pdf::loadView('brand-health.pdf.pm-conceptor-ranking', [
            'projectManagers' => $pms,
            'conceptors' => $conceptors,
            'filterSummary' => $this->rankingFilterSummary($filters),
            'groupedBy' => $this->rankingGroupedByLabel($filters),
            'generatedAt' => now()->format('M j, Y g:i A'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('best-pm-conceptor-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Same ranking data as rankingPdf(), as an .xlsx — PM rows first, then a
     * blank row, then Conceptor rows, each section with its own header.
     */
    public function rankingExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        ['filters' => $filters, 'projectManagers' => $pms, 'conceptors' => $conceptors] = $this->rankingData($request);

        return Excel::download(
            new \App\BrandHealth\Exports\PmConceptorRankingExport(
                collect($pms),
                collect($conceptors),
                $this->rankingFilterSummary($filters),
                $this->rankingGroupedByLabel($filters),
            ),
            'best-pm-conceptor-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * "post month" / "cycle start month" — the bucketing rule the ranking used,
     * for the export headers.
     */
    private function rankingGroupedByLabel(array $filters): string
    {
        return ($filters['range_mode'] ?? 'month') === 'cycle' ? 'cycle start month' : 'post month';
    }

    /**
     * Shared filter parsing + performance fetch + ranking for ranking(),
     * rankingPdf() and rankingExcel() — one place for the platform/month-range
     * logic so the three stay in sync.
     */
    private function rankingData(Request $request): array
    {
        $platform = $request->string('platform')->toString();
        $platform = in_array($platform, ['instagram', 'tiktok'], true) ? $platform : 'all';

        // 'month' (default) buckets each post by its own post_date month.
        // 'cycle' first restricts to posts assigned to a cycle, then buckets by
        // the month of that cycle's start date — so a cycle running 26 Jul–25 Aug
        // (or 31 Jul–30 Aug) both count as "July", regardless of when in the
        // cycle each individual post went out.
        $rangeMode = $request->string('range_mode')->toString();
        $rangeMode = $rangeMode === 'cycle' ? 'cycle' : 'month';

        $monthFrom = $request->string('month_from')->trim()->toString() ?: null;
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;

        // Ads checklist: 'all' (default) counts every post, 'yes' only ads
        // content, 'no' only organic (non-ads) posts.
        $ads = $request->string('ads')->toString();
        $ads = in_array($ads, ['yes', 'no'], true) ? $ads : 'all';

        $query = BrandPerformance::with(['igSnapshots' => fn ($query) => $query->limit(1)])
            ->when($platform !== 'all', fn ($query) => $query->where('platform', $platform))
            ->when($ads !== 'all', fn ($query) => $query->where('ads', $ads === 'yes'));

        if ($rangeMode === 'cycle') {
            $query->whereNotNull('cycle_id')
                ->when($monthFrom, fn ($query) => $query->whereHas('cycle', fn ($q) => $this->applyMonthRange($q, $monthFrom, $monthTo, 'cycle_start_date')));
        } else {
            $query->when($monthFrom, fn ($query) => $this->applyMonthRange($query, $monthFrom, $monthTo));
        }

        $performances = $query->get()
            ->map(function (BrandPerformance $performance) {
                $snapshot = $performance->igSnapshots->first();
                $performance->resolved_views = $snapshot ? ($snapshot->views ?? $snapshot->total_interactions) : $performance->total_views_h7;

                return $performance;
            })
            ->filter(fn (BrandPerformance $performance) => $performance->resolved_views !== null);

        return [
            'filters' => ['platform' => $platform, 'range_mode' => $rangeMode, 'month_from' => $monthFrom, 'month_to' => $monthTo, 'ads' => $ads],
            'projectManagers' => $this->rankByEmployee($performances, 'project_manager_id'),
            'conceptors' => $this->rankByEmployee($performances, 'conceptor_id'),
        ];
    }

    /**
     * Human-readable one-liner for the ranking PDF header.
     */
    private function rankingFilterSummary(array $filters): string
    {
        $parts = [];

        $parts[] = 'platform: '.match ($filters['platform']) {
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            default => 'All',
        };

        $unit = ($filters['range_mode'] ?? 'month') === 'cycle' ? 'cycle month' : 'month';

        if ($filters['month_from']) {
            $to = $filters['month_to'] ?: $filters['month_from'];
            $parts[] = $filters['month_from'] === $to
                ? "{$unit}: {$filters['month_from']}"
                : "{$unit}s: {$filters['month_from']} to {$to}";
        } else {
            $parts[] = ($filters['range_mode'] ?? 'month') === 'cycle'
                ? 'cycles: all (posts assigned to a cycle)'
                : 'months: all time';
        }

        if (($filters['ads'] ?? 'all') !== 'all') {
            $parts[] = 'ads content: '.($filters['ads'] === 'yes' ? 'Yes only' : 'No only');
        }

        return implode(', ', $parts);
    }

    /**
     * Monthly views trend for one employee (as PM and/or Conceptor), used by the
     * ranking modal's per-person drill-down chart. Always buckets the series by
     * post_date month; the range filter honours the modal's range_mode (posts'
     * own month, or the start month of the cycle they belong to).
     */
    public function personSeries(Request $request, User $employee): JsonResponse
    {
        ['performances' => $performances, 'platform' => $platform, 'role' => $role, 'monthFrom' => $monthFrom, 'monthTo' => $monthTo]
            = $this->personFilteredPerformances($request, $employee);

        $series = $performances
            ->groupBy(fn (BrandPerformance $performance) => $performance->post_date->format('Y-m'))
            ->map(function (Collection $group, string $month) {
                $views = $group->pluck('resolved_views');

                return [
                    'month' => $month,
                    'label' => Carbon::createFromFormat('Y-m-d', "{$month}-01")->format('M Y'),
                    'avg_views' => (int) round($this->median($views)),
                    'total_views' => (int) $views->sum(),
                    'post_count' => $group->count(),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();

        return response()->json([
            'employee' => ['id' => $employee->id, 'name' => $employee->name],
            'filters' => ['platform' => $platform, 'role' => $role, 'month_from' => $monthFrom, 'month_to' => $monthTo],
            'series' => $series,
        ]);
    }

    /**
     * The individual posts behind a person's ranking row — same filters as
     * personSeries() (platform/range_mode/month range/ads), but returns each
     * post rather than a monthly aggregate. Used by the ranking modal's
     * "Content" tab, so clicking into a person shows exactly what they made,
     * not just a views-over-time chart.
     */
    public function personPosts(Request $request, User $employee): JsonResponse
    {
        ['performances' => $performances, 'platform' => $platform, 'role' => $role, 'monthFrom' => $monthFrom, 'monthTo' => $monthTo]
            = $this->personFilteredPerformances($request, $employee);

        $posts = $performances
            ->sortByDesc('post_date')
            ->values()
            ->map(fn (BrandPerformance $performance) => [
                'id' => $performance->id,
                'account_name' => $performance->account?->name,
                'platform' => $performance->platform,
                'post_date' => $performance->post_date?->toDateString(),
                'ads' => (bool) $performance->ads,
                'views' => (int) $performance->resolved_views,
                'link' => $performance->videoLinks->first()?->url,
                'media_product_type' => $performance->ig_media_product_type,
            ]);

        return response()->json([
            'employee' => ['id' => $employee->id, 'name' => $employee->name],
            'filters' => ['platform' => $platform, 'role' => $role, 'month_from' => $monthFrom, 'month_to' => $monthTo],
            'posts' => $posts,
        ]);
    }

    /**
     * Shared query behind personSeries()/personPosts() — one employee's posts
     * (as PM or Conceptor), filtered by platform/range_mode/month range/ads,
     * each with resolved_views attached and only posts with a resolved view
     * count included.
     */
    private function personFilteredPerformances(Request $request, User $employee): array
    {
        $platform = $request->string('platform')->toString();
        $platform = in_array($platform, ['instagram', 'tiktok'], true) ? $platform : 'all';
        $role = $request->string('role')->toString();
        $role = in_array($role, ['project_manager_id', 'conceptor_id'], true) ? $role : 'project_manager_id';

        $rangeMode = $request->string('range_mode')->toString();
        $rangeMode = $rangeMode === 'cycle' ? 'cycle' : 'month';

        $monthFrom = $request->string('month_from')->trim()->toString() ?: null;
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;

        $ads = $request->string('ads')->toString();
        $ads = in_array($ads, ['yes', 'no'], true) ? $ads : 'all';

        $query = BrandPerformance::with(['account:id,name', 'videoLinks', 'igSnapshots' => fn ($query) => $query->limit(1)])
            ->where($role, $employee->id)
            ->when($platform !== 'all', fn ($query) => $query->where('platform', $platform))
            ->when($ads !== 'all', fn ($query) => $query->where('ads', $ads === 'yes'));

        if ($rangeMode === 'cycle') {
            $query->whereNotNull('cycle_id')
                ->when($monthFrom, fn ($query) => $query->whereHas('cycle', fn ($q) => $this->applyMonthRange($q, $monthFrom, $monthTo, 'cycle_start_date')));
        } else {
            $query->when($monthFrom, fn ($query) => $this->applyMonthRange($query, $monthFrom, $monthTo));
        }

        $performances = $query
            ->orderBy('post_date')
            ->get()
            ->map(function (BrandPerformance $performance) {
                $snapshot = $performance->igSnapshots->first();
                $performance->resolved_views = $snapshot ? ($snapshot->views ?? $snapshot->total_interactions) : $performance->total_views_h7;

                return $performance;
            })
            ->filter(fn (BrandPerformance $performance) => $performance->resolved_views !== null);

        return [
            'performances' => $performances,
            'platform' => $platform,
            'role' => $role,
            'monthFrom' => $monthFrom,
            'monthTo' => $monthTo,
        ];
    }

    /**
     * Applies a month_from/month_to range (Y-m format, inclusive) to a date
     * column (default post_date). Same semantics as CycleController's
     * month-range filter: a single month if month_to is omitted.
     */
    private function applyMonthRange($query, string $monthFrom, ?string $monthTo, string $column = 'post_date')
    {
        $start = Carbon::createFromFormat('Y-m-d', "{$monthFrom}-01")->startOfMonth();
        $end = $monthTo
            ? Carbon::createFromFormat('Y-m-d', "{$monthTo}-01")->endOfMonth()
            : $start->copy()->endOfMonth();

        return $query->whereBetween($column, [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * Groups the given (already view-resolved) performances by an employee
     * foreign key, computes avg/total views + post count per person, and
     * returns them ranked best-to-worst by average views.
     */
    private function rankByEmployee(Collection $performances, string $employeeIdColumn): array
    {
        $grouped = $performances
            ->whereNotNull($employeeIdColumn)
            ->groupBy($employeeIdColumn);

        if ($grouped->isEmpty()) {
            return [];
        }

        $employeeNames = User::whereIn('id', $grouped->keys())->pluck('name', 'id');

        return $grouped
            ->map(function (Collection $group, $employeeId) use ($employeeNames) {
                $views = $group->pluck('resolved_views');

                return [
                    'employee_id' => (int) $employeeId,
                    'employee_name' => $employeeNames->get($employeeId, 'Unknown'),
                    'post_count' => $group->count(),
                    'avg_views' => (int) round($this->median($views)),
                    'total_views' => (int) $views->sum(),
                    'min_views' => (int) $views->min(),
                    'max_views' => (int) $views->max(),
                ];
            })
            ->sortByDesc('avg_views')
            ->values()
            ->all();
    }

    /**
     * Median Views H+7 per cycle, across all accounts/platforms, keyed by cycle_id.
     * Same view-source precedence as AccountController::viewsFor() (Instagram
     * snapshot overrides the manually-typed column when a post is linked).
     * Median rather than mean so a single viral post doesn't drag the whole
     * cycle's "typical" views figure upward.
     */
    private function avgViewsByCycle(): Collection
    {
        return $this->viewsByCyclePerformances()
            ->map(fn (Collection $views) => $this->median($views))
            ->filter(fn ($median) => $median !== null);
    }

    /**
     * Highest single post's views per cycle — alongside the median, shows the
     * ceiling a cycle's best post reached. Same source/precedence as
     * avgViewsByCycle().
     */
    private function maxViewsByCycle(): Collection
    {
        return $this->viewsByCyclePerformances()
            ->map(fn (Collection $views) => $views->isEmpty() ? null : (int) $views->max())
            ->filter(fn ($max) => $max !== null);
    }

    /**
     * Lowest single post's views per cycle — the floor, alongside the median
     * and max. Same source/precedence as avgViewsByCycle().
     */
    private function minViewsByCycle(): Collection
    {
        return $this->viewsByCyclePerformances()
            ->map(fn (Collection $views) => $views->isEmpty() ? null : (int) $views->min())
            ->filter(fn ($min) => $min !== null);
    }

    /**
     * Number of (view-resolved) posts/reels per cycle — the "Total Reels"
     * column, alongside median/max/min. Same source as the others.
     */
    private function postCountByCycle(): Collection
    {
        return $this->viewsByCyclePerformances()
            ->map(fn (Collection $views) => $views->count());
    }

    /**
     * Shared groundwork for avgViewsByCycle()/totalViewsByCycle(): every
     * performance's resolved view count, grouped by cycle_id. Memoized per
     * request — this scans the whole Performance table, and both callers
     * (plus multiple call sites within a single index()/pdf()/exportExcel()
     * request) would otherwise each trigger their own independent full scan.
     */
    private ?Collection $viewsByCyclePerformancesCache = null;

    private function viewsByCyclePerformances(): Collection
    {
        return $this->viewsByCyclePerformancesCache ??= BrandPerformance::whereNotNull('cycle_id')
            ->with(['igSnapshots' => fn ($query) => $query->limit(1)])
            ->get()
            ->groupBy('cycle_id')
            ->map(function (Collection $performances) {
                return $performances
                    ->map(function (BrandPerformance $performance) {
                        $snapshot = $performance->igSnapshots->first();

                        return $snapshot ? ($snapshot->views ?? $snapshot->total_interactions) : $performance->total_views_h7;
                    })
                    ->filter(fn ($views) => $views !== null);
            });
    }

    /**
     * Classify one account+platform's cycle-ordered series into a trend row.
     * Delta/trend/streak are based on median views. Returns null when there's
     * no cycle data at all for this account on this platform (nothing to
     * show), as opposed to exactly one cycle's worth (shown as
     * 'insufficient_data' so the account is still visible).
     */
    private function buildRow(BrandAccount $account, string $platform, Collection $series, ?string $explicitPriorMonth = null): ?array
    {
        $rowId = "{$account->id}:{$platform}";

        // Explicit From-month pick: "prior" is this account's own cycle
        // at-or-before that month specifically (e.g. always June, even if the
        // account also has a July cycle in between it and "last") — not just
        // whichever cycle happens to immediately precede "last" in $series.
        $explicitPrior = $explicitPriorMonth
            ? $series->filter(fn ($point) => $point['month'] <= $explicitPriorMonth)->last()
            : null;

        if ($series->count() < 2 && !$explicitPrior) {
            return $series->isEmpty() ? null : [
                'row_id' => $rowId,
                'account_id' => $account->id,
                'account_name' => $account->name,
                'platform' => $platform,
                'last_avg_views' => (int) round($series->last()['avg_views']),
                'prior_avg_views' => null,
                'last_max_views' => (int) ($series->last()['max_views'] ?? 0),
                'last_min_views' => (int) ($series->last()['min_views'] ?? 0),
                'last_post_count' => (int) ($series->last()['post_count'] ?? 0),
                'delta_pct' => null,
                'trend' => 'insufficient_data',
                'stagnant_streak' => 0,
                'last_cycle_label' => $series->last()['label'],
            ];
        }

        if ($series->isEmpty()) {
            return null;
        }

        $last = $series->last();
        $prior = $explicitPrior ?? $series->slice(-2, 1)->first();

        // No cycle at or before the picked From-month for this account, and
        // no fallback pair either — nothing to compare, but still show the
        // account with its "last" figure and no delta (mirrors the < 2 branch
        // above for the explicit-From-month case).
        if ($prior === null) {
            return [
                'row_id' => $rowId,
                'account_id' => $account->id,
                'account_name' => $account->name,
                'platform' => $platform,
                'last_avg_views' => (int) round($last['avg_views']),
                'prior_avg_views' => null,
                'last_max_views' => (int) ($last['max_views'] ?? 0),
                'last_min_views' => (int) ($last['min_views'] ?? 0),
                'last_post_count' => (int) ($last['post_count'] ?? 0),
                'delta_pct' => null,
                'trend' => 'insufficient_data',
                'stagnant_streak' => 0,
                'last_cycle_label' => $last['label'],
            ];
        }

        // From/To both resolved to the same cycle (e.g. picking the same
        // month twice, or a From-month with no earlier cycle than "last") —
        // there's nothing to compare against.
        if ($prior['cycle_id'] === $last['cycle_id']) {
            return [
                'row_id' => $rowId,
                'account_id' => $account->id,
                'account_name' => $account->name,
                'platform' => $platform,
                'last_avg_views' => (int) round($last['avg_views']),
                'prior_avg_views' => null,
                'last_max_views' => (int) ($last['max_views'] ?? 0),
                'last_min_views' => (int) ($last['min_views'] ?? 0),
                'last_post_count' => (int) ($last['post_count'] ?? 0),
                'delta_pct' => null,
                'trend' => 'insufficient_data',
                'stagnant_streak' => 0,
                'last_cycle_label' => $last['label'],
            ];
        }

        // Delta / trend classification is based on median views per post
        // (avg_views), matching the table's client-facing Median Views columns.
        $deltaPct = ($prior['avg_views'] ?? 0) > 0
            ? (($last['avg_views'] ?? 0) - $prior['avg_views']) / $prior['avg_views']
            : null;

        $stagnantStreak = $this->trailingFlatStreak($series);

        $trend = match (true) {
            $deltaPct === null => 'insufficient_data',
            $deltaPct <= self::SETBACK_THRESHOLD => 'setback',
            $stagnantStreak >= self::STAGNANT_STREAK => 'stagnant',
            $deltaPct >= self::GROWTH_THRESHOLD => 'growing',
            default => 'stable',
        };

        return [
            'row_id' => $rowId,
            'account_id' => $account->id,
            'account_name' => $account->name,
            'platform' => $platform,
            'last_avg_views' => (int) round($last['avg_views']),
            'prior_avg_views' => (int) round($prior['avg_views']),
            'last_max_views' => (int) ($last['max_views'] ?? 0),
            'last_min_views' => (int) ($last['min_views'] ?? 0),
            'last_post_count' => (int) ($last['post_count'] ?? 0),
            'delta_pct' => $deltaPct === null ? null : round($deltaPct * 100, 1),
            'trend' => $trend,
            'stagnant_streak' => $stagnantStreak,
            'last_cycle_label' => $last['label'],
        ];
    }

    /**
     * Median of a collection of numeric values — the middle value when sorted,
     * or the average of the two middle values for an even count. Used instead
     * of the arithmetic mean everywhere this page reports "views per post,"
     * since a handful of viral outlier posts would otherwise pull the mean far
     * above what a typical post actually earned.
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
     * How many consecutive cycles, counting back from the most recent, stayed
     * within +/-STAGNANT_BAND of the cycle immediately before them. E.g. cycles
     * [100, 105, 98, 102] (latest last) -> compares 105->98 (flat), 98->102
     * (flat), so streak = 2 flat transitions = 3 flat cycles. Based on median
     * views per post, matching deltaPct above.
     */
    private function trailingFlatStreak(Collection $series): int
    {
        $values = $series->pluck('avg_views')->values();
        $streak = 1; // the latest cycle itself always counts as 1

        for ($i = $values->count() - 1; $i > 0; $i--) {
            $current = $values[$i];
            $previous = $values[$i - 1];

            if ($previous <= 0) {
                break;
            }

            $swing = abs($current - $previous) / $previous;

            if ($swing > self::STAGNANT_BAND) {
                break;
            }

            $streak++;
        }

        return $streak;
    }
}
