<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Http\Controllers\Concerns\ComputesAccountHealthAndDelta;
use App\BrandHealth\Services\AccountProjectManagerHistory;
use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandCycle;
use App\Models\User;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Services\MetricCalculator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View as Response;

/**
 * Account Growth page — whether each account's Reach/Views/Engagement
 * individually improved or declined vs. the cycle right before it (a TREND,
 * not a level). Deliberately kept as its own sidebar item/page, separate
 * from Account Health (current standing), so neither axis is mistaken for
 * the other — an account can legitimately show poor Health but a positive
 * Growth trend (still in bad shape overall, but recovering), or vice versa.
 * See ComputesAccountHealthAndDelta for the shared computation both pages read.
 */
class BrandAccountGrowthController extends Controller
{
    use ComputesAccountHealthAndDelta;

    public function index(Request $request, MetricCalculator $calculator): Response
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $pmId = $request->integer('project_manager_id') ?: null;
        $growthFilter = $this->parseLabelFilter($request, 'growth_label');
        $reachFilter = $this->parseLabelFilter($request, 'reach_status');
        $viewsFilter = $this->parseLabelFilter($request, 'views_status');
        $engagementFilter = $this->parseLabelFilter($request, 'engagement_status');
        $page = $request->integer('page', 1);
        $perPage = 15;

        $accounts = BrandAccount::withCount('cycles')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        $pmByAccount = (new AccountProjectManagerHistory())->currentByAccount($accounts->pluck('id'));
        $pmNames = User::whereIn('id', $pmByAccount->filter()->unique()->values())->get(['id', 'name'])->keyBy('id');

        // PM filter applied in PHP, after deriving each account's current PM
        // from its latest cycle — it's no longer a real accounts column.
        if ($pmId) {
            $accounts = $accounts->filter(fn (BrandAccount $account) => $pmByAccount->get($account->id) === $pmId)->values();
        }

        $healthByAccount = $this->latestCycleHealthByAccount($accounts->pluck('id'), $calculator);

        $rows = $accounts->map(fn (BrandAccount $account) => $this->presentAccount(
            $account,
            $healthByAccount->get($account->id),
            $pmByAccount->get($account->id),
            $pmNames,
        ))
            ->when($growthFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['growth_label'], $growthFilter, true)))
            ->when($reachFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['reach_status'], $reachFilter, true)))
            ->when($viewsFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['views_status'], $viewsFilter, true)))
            ->when($engagementFilter, fn ($rows) => $rows->filter(fn ($row) => in_array($row['engagement_status'], $engagementFilter, true)))
            ->values();

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('brand-health.account-growth.index', [
            'accounts' => $paginator,
            'summary' => [
                'total' => $accounts->count(),
                'improving' => $rows->filter(fn ($row) => $this->isImproving($row))->count(),
                'declining' => $rows->filter(fn ($row) => $this->isDeclining($row))->count(),
            ],
            'filters' => [
                'search' => $search,
                'project_manager_id' => $pmId,
                'growth_label' => $growthFilter,
                'reach_status' => $reachFilter,
                'views_status' => $viewsFilter,
                'engagement_status' => $engagementFilter,
            ],
            'accountDepartmentEmployees' => User::whereHas(
                'department',
                fn ($query) => $query->whereIn('name', ['Accounts', 'Accounts Manager'])
            )->orderBy('name')->get(['id', 'name']),
            'defaultAiProvider' => config('services.brand_health.ai_summary.provider', 'groq'),
            'growthLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ACCOUNT_GROWTH)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'reachLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_REACH_CHANGE)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'viewsLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_VIEWS_CHANGE)
                ->orderByDesc('min_score')
                ->pluck('label'),
            'engagementLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ENGAGEMENT_CHANGE)
                ->orderByDesc('min_score')
                ->pluck('label'),
        ]);
    }

    /**
     * Comma-separated query param -> array of label strings, same convention
     * as AccountController::parseLabelFilter().
     */
    private function parseLabelFilter(Request $request, string $key): array
    {
        return array_values(array_filter(explode(',', $request->string($key)->trim()->toString())));
    }

    /**
     * An account counts as "improving" when at least one of its Reach/Views/
     * Engagement deltas is NOT a decline — a quick summary tile, not a
     * precise metric; the real per-metric breakdown lives in the table below.
     */
    private function isImproving(array $row): bool
    {
        $declineLabels = ['KURANG', 'PARAH', 'PERLU PERHATIAN'];

        foreach (['reach_status', 'views_status', 'engagement_status'] as $field) {
            $value = $row[$field] ?? null;
            if ($value && ! in_array(strtoupper($value), $declineLabels, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * An account counts as "declining" when at least one of its Reach/Views/
     * Engagement deltas IS a decline — an account can be both improving (one
     * metric up) and declining (another metric down) at once, so these two
     * tiles aren't mutually exclusive counts.
     */
    private function isDeclining(array $row): bool
    {
        $declineLabels = ['KURANG', 'PARAH', 'PERLU PERHATIAN'];

        foreach (['reach_status', 'views_status', 'engagement_status'] as $field) {
            $value = $row[$field] ?? null;
            if ($value && in_array(strtoupper($value), $declineLabels, true)) {
                return true;
            }
        }

        return false;
    }

    private function presentAccount(BrandAccount $account, ?array $scores, ?int $pmId = null, ?Collection $pmNames = null): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'project_manager_id' => $pmId,
            'project_manager' => $pmId && $pmNames ? $pmNames->get($pmId) : null,
            'cycles_count' => $account->cycles_count,
            'latest_cycle_label' => $scores['latest_cycle_label'] ?? null,
            'growth_rate' => $scores['growth_rate'] ?? null,
            'growth_label' => $scores['growth_label'] ?? null,
            'reach_change_rate' => $scores['reach_change_rate'] ?? null,
            'reach_status' => $scores['reach_status'] ?? null,
            'views_change_rate' => $scores['views_change_rate'] ?? null,
            'views_status' => $scores['views_status'] ?? null,
            'engagement_change_rate' => $scores['engagement_change_rate'] ?? null,
            'engagement_status' => $scores['engagement_status'] ?? null,
        ];
    }
}
