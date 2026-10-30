<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Exports\ContentInsightExport;
use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandContentInsight;
use App\BrandHealth\Models\BrandCycle;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View as Response;
use Maatwebsite\Excel\Facades\Excel;

class BrandContentInsightController extends Controller
{
    public function index(Request $request): Response
    {
        $all = $this->allInsights();

        return view('brand-health.content-insights.index', [
            'allInsights' => $all->values(),
            'months' => $all->pluck('month')->filter()->unique()->sort()->values(),
            'accounts' => BrandAccount::orderBy('name')->get(['id', 'name']),
            'projectManagers' => User::whereIn('id', BrandCycle::query()->whereNotNull('project_manager_id')->distinct()->pluck('project_manager_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function pdf(Request $request): HttpResponse
    {
        ['rows' => $rows, 'filters' => $filters] = $this->filteredSortedRows($request);

        $pdf = Pdf::loadView('brand-health.pdf.content-insights-list', [
            'rows' => $rows->values(),
            'generatedAt' => now()->format('M j, Y g:i A'),
            'filterSummary' => $this->filterSummary($filters),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('content-insights-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        ['rows' => $rows] = $this->filteredSortedRows($request);

        return Excel::download(
            new ContentInsightExport($rows->values()),
            'content-insights-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * The flat, ungrouped insight rows — same shape the page's client-side
     * computeds are built from (one row per account+cycle, not yet grouped
     * into "one row per account").
     */
    private function allInsights(): Collection
    {
        return BrandContentInsight::with([
            'account:id,name',
            'cycle:id,account_id,cycle_start_date,cycle_end_date,platform,end_follower,project_manager_id',
            'cycle.projectManager:id,name',
        ])
            ->join('brand_cycles', 'brand_cycles.id', '=', 'brand_content_insights.cycle_id')
            ->orderByDesc('brand_cycles.cycle_start_date')
            ->select('brand_content_insights.*')
            ->get()
            ->map(fn (BrandContentInsight $insight) => [
                'id' => $insight->id,
                'account_id' => $insight->account_id,
                'cycle_id' => $insight->cycle_id,
                'account_name' => $insight->account?->name,
                // This insight belongs to one specific cycle, so it's that
                // cycle's own PM — not the account's derived "current" PM.
                'project_manager_id' => $insight->cycle?->project_manager_id,
                'project_manager_name' => $insight->cycle?->projectManager?->name,
                'platform' => $insight->cycle?->platform,
                'month' => $insight->cycle?->cycle_start_date?->format('Y-m'),
                'cycle_start_date' => $insight->cycle?->cycle_start_date?->toDateString(),
                'cycle_end_date' => $insight->cycle?->cycle_end_date?->toDateString(),
                'end_follower' => $insight->cycle?->end_follower,
                'viewers_posts' => $insight->viewers_posts,
                'viewers_reels' => $insight->viewers_reels,
                'viewers_story' => $insight->viewers_story,
                'interactions_posts' => $insight->interactions_posts,
                'interactions_reels' => $insight->interactions_reels,
                'interactions_story' => $insight->interactions_story,
            ]);
    }

    // --- Weighted Content Score — ports the page's client-side computeds
    // (weightedScoresFor / accountRows / filteredRows / sortedRows in
    // ContentInsights/Index.vue) so the PDF/Excel exports show exactly what
    // the table shows, for the same search/trend/month-range/sort params. ---

    private const SCORE_WEIGHTS = [
        'posts' => [['source' => 'views', 'weight' => 0.5], ['source' => 'interactions', 'weight' => 0.2], ['source' => 'growth', 'weight' => 0.3]],
        'reels' => [['source' => 'views', 'weight' => 0.5], ['source' => 'interactions', 'weight' => 0.2], ['source' => 'growth', 'weight' => 0.3]],
        'story' => [['source' => 'views', 'weight' => 0.8], ['source' => 'interactions', 'weight' => 0.2]],
    ];

    private const CONTENT_TYPE_LABELS = ['posts' => 'Posts', 'reels' => 'Reels', 'story' => 'Story'];

    private function pct(?float $from, ?float $to): ?float
    {
        if ($from === null || $to === null || $from == 0) {
            return null;
        }

        return (($to - $from) / $from) * 100;
    }

    private function weightedScoresFor(array $from, array $to): array
    {
        $growthRate = $this->pct($from['end_follower'], $to['end_follower']);

        $result = [];
        foreach (self::SCORE_WEIGHTS as $type => $components) {
            $values = [
                'views' => $this->pct($from["viewers_{$type}"], $to["viewers_{$type}"]),
                'interactions' => $this->pct($from["interactions_{$type}"], $to["interactions_{$type}"]),
                'growth' => $growthRate,
            ];

            $available = array_filter($components, fn ($c) => $values[$c['source']] !== null);
            $weightSum = array_sum(array_column($available, 'weight'));

            $score = empty($available)
                ? null
                : array_sum(array_map(fn ($c) => ($values[$c['source']] * $c['weight']) / $weightSum, $available));

            $result[] = [
                'key' => $type,
                'label' => self::CONTENT_TYPE_LABELS[$type],
                'score' => $score,
            ];
        }

        return $result;
    }

    /**
     * One row per account — default: latest cycle vs. the one before it.
     * With month_from/month_to: that account's cycle in each picked month.
     * Then filtered by search/PM/trend and sorted, matching the page exactly.
     */
    private function filteredSortedRows(Request $request): array
    {
        $all = $this->allInsights();

        $search = $request->string('search')->trim()->lower()->toString();
        $pm = $request->string('pm')->trim()->toString() ?: 'all';
        $trends = array_values(array_filter(explode(',', $request->string('trend')->trim()->toString())));
        $monthFrom = $request->string('month_from')->trim()->toString() ?: null;
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;
        $sortKey = $request->string('sort')->trim()->toString() ?: 'account';
        $direction = $request->string('direction')->trim()->toString() ?: 'asc';
        $hasRange = $monthFrom && $monthTo;

        if ($hasRange) {
            $byAccount = [];
            foreach ($all as $insight) {
                if ($insight['month'] !== $monthFrom && $insight['month'] !== $monthTo) {
                    continue;
                }
                $byAccount[$insight['account_id']][$insight['month']] = $insight;
            }

            $rows = collect();
            foreach ($byAccount as $pair) {
                $from = $pair[$monthFrom] ?? null;
                $to = $pair[$monthTo] ?? null;
                if (!$from || !$to) {
                    continue;
                }
                $rows->push(['current' => $to, 'previous' => $from, 'weightedScores' => $this->weightedScoresFor($from, $to)]);
            }
        } else {
            $latestByAccount = [];
            foreach ($all as $insight) {
                if (!$insight['cycle_start_date']) {
                    continue;
                }
                $existing = $latestByAccount[$insight['account_id']] ?? null;
                if (!$existing || $insight['cycle_start_date'] > $existing['cycle_start_date']) {
                    $latestByAccount[$insight['account_id']] = $insight;
                }
            }

            $rows = collect($latestByAccount)->map(function ($insight) use ($all) {
                $prev = collect($all)
                    ->filter(fn ($i) => $i['account_id'] === $insight['account_id']
                        && $i['cycle_start_date']
                        && $i['cycle_start_date'] < $insight['cycle_start_date'])
                    ->sortByDesc('cycle_start_date')
                    ->first();

                return [
                    'current' => $insight,
                    'previous' => $prev,
                    'weightedScores' => $prev ? $this->weightedScoresFor($prev, $insight) : null,
                ];
            })->values();
        }

        $scoreFor = fn ($row, $type) => collect($row['weightedScores'] ?? [])->firstWhere('key', $type)['score'] ?? null;

        // Overall trend — the average of whichever content-type scores are
        // available for the row, bucketed the same way Views Trend classifies
        // its own delta (>=+1% growing, <=-1% setback, else flat/stable).
        // A row with no weighted scores at all (no prior cycle) is 'no_data'.
        $overallTrend = function ($row) {
            $scores = collect($row['weightedScores'] ?? [])->pluck('score')->filter(fn ($s) => $s !== null);
            if ($scores->isEmpty()) {
                return 'no_data';
            }
            $avg = $scores->avg();

            return match (true) {
                $avg <= -1 => 'down',
                $avg >= 1 => 'up',
                default => 'flat',
            };
        };

        $rows = $rows->filter(function ($row) use ($search, $pm, $trends, $overallTrend) {
            $matchesSearch = !$search || str_contains(strtolower($row['current']['account_name'] ?? ''), $search);
            $matchesPm = $pm === 'all' || (string) $row['current']['project_manager_id'] === $pm;
            $matchesTrend = empty($trends) || in_array($overallTrend($row), $trends, true);

            return $matchesSearch && $matchesPm && $matchesTrend;
        })->values();

        if ($sortKey === 'account') {
            $rows = $rows->sortBy(fn ($row) => $row['current']['account_name'] ?? '', SORT_NATURAL | SORT_FLAG_CASE);
            if ($direction === 'desc') {
                $rows = $rows->reverse();
            }
        } else {
            $withValue = $rows->filter(fn ($row) => $scoreFor($row, $sortKey) !== null);
            $withoutValue = $rows->filter(fn ($row) => $scoreFor($row, $sortKey) === null);

            $withValue = $withValue->sortBy(fn ($row) => $scoreFor($row, $sortKey));
            if ($direction === 'desc') {
                $withValue = $withValue->reverse();
            }
            $rows = $withValue->values()->concat($withoutValue->values());
        }

        // Stamp each row with its trend classification — computed above for
        // filtering, but the exports need it too (as an actual column, not
        // just baked into the header's filter summary), so it rides along on
        // the row rather than being recomputed a third time there.
        $rows = $rows->map(fn ($row) => [...$row, 'overall_trend' => $overallTrend($row)]);

        return [
            'rows' => $rows->values(),
            'filters' => [
                'search' => $search,
                'pm' => $pm,
                'trends' => $trends,
                'month_from' => $monthFrom,
                'month_to' => $monthTo,
                'has_range' => $hasRange,
            ],
        ];
    }

    private function filterSummary(array $filters): string
    {
        $parts = [];

        if (!empty($filters['search'])) {
            $parts[] = 'search: "'.$filters['search'].'"';
        }
        if (!empty($filters['pm']) && $filters['pm'] !== 'all') {
            $pmName = User::find($filters['pm'])?->name;
            if ($pmName) {
                $parts[] = 'PM: '.$pmName;
            }
        }
        if (!empty($filters['trends'])) {
            $labels = array_map(fn ($t) => ucfirst(str_replace('_', ' ', $t)), $filters['trends']);
            $parts[] = 'trend: '.implode(', ', $labels);
        }
        if (!empty($filters['has_range'])) {
            $parts[] = 'compared: '.$filters['month_from'].' vs '.$filters['month_to'];
        } else {
            $parts[] = "scored against each account's previous cycle";
        }

        return $parts ? implode(' · ', $parts) : 'none';
    }

    /**
     * Cycles for a given account, for the dependent "cycle" select box.
     */
    public function cycles(BrandAccount $account): JsonResponse
    {
        // Existing insights for this account, keyed by cycle_id — so the
        // create/edit form can auto-fill Viewers/Interactions the moment a
        // cycle with recorded data is picked, without a second request.
        $insightsByCycle = BrandContentInsight::where('account_id', $account->id)
            ->get()
            ->keyBy('cycle_id');

        return response()->json(
            $account->cycles()
                ->orderByDesc('cycle_start_date')
                ->get(['id', 'cycle_start_date', 'cycle_end_date', 'platform'])
                ->map(function (BrandCycle $cycle) use ($insightsByCycle) {
                    $insight = $insightsByCycle->get($cycle->id);

                    return [
                        'id' => $cycle->id,
                        'platform' => $cycle->platform,
                        'cycle_start_date' => $cycle->cycle_start_date->toDateString(),
                        'cycle_end_date' => $cycle->cycle_end_date->toDateString(),
                        'label' => $this->cycleLabel($cycle),
                        'insight' => $insight ? [
                            'id' => $insight->id,
                            'viewers_posts' => $insight->viewers_posts,
                            'viewers_reels' => $insight->viewers_reels,
                            'viewers_story' => $insight->viewers_story,
                            'interactions_posts' => $insight->interactions_posts,
                            'interactions_reels' => $insight->interactions_reels,
                            'interactions_story' => $insight->interactions_story,
                        ] : null,
                    ];
                })
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // One row per account + cycle — updating the same pair overwrites.
        BrandContentInsight::updateOrCreate(
            ['account_id' => $data['account_id'], 'cycle_id' => $data['cycle_id']],
            $data,
        );

        return back();
    }

    public function update(Request $request, BrandContentInsight $contentInsight): RedirectResponse
    {
        $contentInsight->update($this->validated($request));

        return back();
    }

    public function destroy(BrandContentInsight $contentInsight): RedirectResponse
    {
        $contentInsight->delete();

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'account_id' => ['required', 'exists:brand_accounts,id'],
            'cycle_id' => [
                'required',
                Rule::exists('brand_cycles', 'id')->where(
                    fn ($query) => $query->where('account_id', $request->input('account_id'))
                ),
            ],
            'viewers_posts' => ['required', 'integer', 'min:0'],
            'viewers_reels' => ['required', 'integer', 'min:0'],
            'viewers_story' => ['required', 'integer', 'min:0'],
            'interactions_posts' => ['required', 'integer', 'min:0'],
            'interactions_reels' => ['required', 'integer', 'min:0'],
            'interactions_story' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function cycleLabel(BrandCycle $cycle): string
    {
        $range = $cycle->cycle_start_date->format('d M Y').' – '.$cycle->cycle_end_date->format('d M Y');

        return ucfirst($cycle->platform).' · '.$range;
    }
}
