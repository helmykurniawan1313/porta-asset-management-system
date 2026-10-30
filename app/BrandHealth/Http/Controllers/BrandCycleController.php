<?php

namespace App\BrandHealth\Http\Controllers;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandFilterSummary;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use App\BrandHealth\Services\MetricCalculator;
use App\BrandHealth\Services\SummaryProviderResolver;
use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandCycleController extends Controller
{
    public function index(Request $request, MetricCalculator $calculator): View
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $labelFilters = $this->parseLabelFilters($request);
        $monthFrom = $request->string('month_from')->trim()->toString() ?: null;
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;

        $filtered = BrandCycle::with(['account', 'projectManager'])
            ->when($request->integer('account_id'), fn ($query, $accountId) => $query->where('account_id', $accountId))
            ->when($request->integer('project_manager_id'), fn ($query, $id) => $query->where('project_manager_id', $id))
            ->when($request->string('platform')->trim()->toString(), fn ($query, $platform) => $query->where('platform', $platform))
            ->when($request->string('search')->trim()->toString(), function ($query, $search) {
                $query->whereHas('account', fn ($accountQuery) => $accountQuery->where('name', 'like', "%{$search}%"));
            })
            ->when($monthFrom, fn ($query, $monthFrom) => $this->applyMonthRangeFilter($query, $monthFrom, $monthTo))
            ->orderByDesc('cycle_start_date');

        $matching = $filtered->get()->map(fn (BrandCycle $cycle) => [
            'cycle' => $cycle,
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

        return view('brand-health.cycles.index', [
            'cycles' => $paginator,
            'accounts' => BrandAccount::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'healthLabels' => BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_HEALTH)
                ->orderByDesc('min_score')->pluck('label'),
            'filters' => [
                'search' => $request->string('search')->trim()->toString() ?: null,
                'account_id' => $request->integer('account_id') ?: null,
                'project_manager_id' => $request->integer('project_manager_id') ?: null,
                'platform' => $request->string('platform')->trim()->toString() ?: null,
                'health_label' => $labelFilters['health_label'],
                'month_from' => $monthFrom,
                'month_to' => $monthTo,
            ],
            'canManage' => $request->user()->can('edit-brand-health'),
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
            $response['start_follower'] = $this->resolveStartFollower($account, $startDate, $platform);
        }

        if ($endDate = $data['end_date'] ?? null) {
            $response['end_follower'] = $this->resolveEndFollower($account, $endDate, $platform);
        }

        return response()->json($response);
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

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        BrandCycle::create($data);

        return back()->with('status', 'Cycle created.');
    }

    public function update(Request $request, BrandCycle $cycle): RedirectResponse
    {
        $data = $this->validated($request);

        $cycle->update($data);

        return back()->with('status', 'Cycle updated.');
    }

    public function destroy(BrandCycle $cycle): RedirectResponse
    {
        $cycle->delete();

        return back()->with('status', 'Cycle deleted.');
    }

    public function summarize(Request $request, BrandCycle $cycle, MetricCalculator $calculator, SummaryProviderResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
            'provider' => ['nullable', 'string', 'in:'.implode(',', SummaryProviderResolver::PROVIDERS)],
        ]);

        $scores = $calculator->calculate($cycle->load('account'), BrandScoreBucket::all(), BrandLabelBucket::all(), BrandFormulaWeight::all());

        try {
            $summary = $resolver->resolve($data['provider'] ?? null)->summarize($cycle, $scores, $data['prompt'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $cycle->forceFill([
            'ai_summary' => $summary,
            'ai_summary_generated_at' => now(),
        ])->save();

        return response()->json([
            'ai_summary' => $summary,
            'ai_summary_generated_at' => $cycle->ai_summary_generated_at->toIso8601String(),
        ]);
    }

    public function summarizeFiltered(Request $request, MetricCalculator $calculator, SummaryProviderResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
            'provider' => ['nullable', 'string', 'in:'.implode(',', SummaryProviderResolver::PROVIDERS)],
        ]);

        $cycles = $this->filteredCycles($request, $calculator);

        if ($cycles->isEmpty()) {
            return response()->json(['message' => 'No cycles match the current filters.'], 422);
        }

        $filterDescription = ['filters' => $request->query(), 'summary' => 'filtered cycles'];
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
            'generated_at' => $record->created_at->toIso8601String(),
        ]);
    }

    public function pdf(Request $request, MetricCalculator $calculator): HttpResponse
    {
        $cycles = $this->filteredCycles($request, $calculator);

        $pdf = Pdf::loadView('brand-health.pdf.cycles-list', [
            'cycles' => $cycles,
            'generatedAt' => now()->format('M j, Y g:i A'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('brand-health-cycles-'.now()->format('Y-m-d').'.pdf');
    }

    public function pdfSingle(BrandCycle $cycle, MetricCalculator $calculator): HttpResponse
    {
        $scores = $calculator->calculate($cycle->load(['account', 'projectManager']));

        $pdf = Pdf::loadView('brand-health.pdf.cycle-detail', [
            'cycle' => $cycle,
            'scores' => $scores,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf('%s-%s.pdf', Str::slug($cycle->account->name), $cycle->cycle_start_date->format('Y-m-d'));

        return $pdf->download($filename);
    }

    private function filteredCycles(Request $request, MetricCalculator $calculator): Collection
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $labelFilters = $this->parseLabelFilters($request);
        $monthFrom = $request->string('month_from')->trim()->toString() ?: null;
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;

        $cycles = BrandCycle::with(['account', 'projectManager'])
            ->when($request->integer('account_id'), fn ($query, $accountId) => $query->where('account_id', $accountId))
            ->when($request->integer('project_manager_id'), fn ($query, $id) => $query->where('project_manager_id', $id))
            ->when($request->string('platform')->trim()->toString(), fn ($query, $platform) => $query->where('platform', $platform))
            ->when($request->string('search')->trim()->toString(), function ($query, $search) {
                $query->whereHas('account', fn ($accountQuery) => $accountQuery->where('name', 'like', "%{$search}%"));
            })
            ->when($monthFrom, fn ($query, $monthFrom) => $this->applyMonthRangeFilter($query, $monthFrom, $monthTo))
            ->orderBy('cycle_start_date')
            ->get()
            ->map(fn (BrandCycle $cycle) => [
                'cycle' => $cycle,
                'scores' => $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights),
            ]);

        return $this->applyLabelFilters($cycles, $labelFilters);
    }

    private function applyMonthRangeFilter($query, string $monthFrom, ?string $monthTo)
    {
        $start = Carbon::createFromFormat('Y-m-d', "{$monthFrom}-01")->startOfMonth();
        $end = $monthTo
            ? Carbon::createFromFormat('Y-m-d', "{$monthTo}-01")->endOfMonth()
            : $start->copy()->endOfMonth();

        return $query->whereBetween('cycle_start_date', [$start->toDateString(), $end->toDateString()]);
    }

    private function parseLabelFilter(Request $request, string $param): ?array
    {
        $raw = $request->string($param)->trim()->toString();

        if (! $raw) {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function parseLabelFilters(Request $request): array
    {
        return [
            'growth_label' => $this->parseLabelFilter($request, 'growth_label'),
            'visibility_label' => $this->parseLabelFilter($request, 'visibility_label'),
            'engagement_label' => $this->parseLabelFilter($request, 'engagement_label'),
            'health_label' => $this->parseLabelFilter($request, 'health_label'),
        ];
    }

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
