<?php

namespace App\BrandHealth\Http\Controllers;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use App\BrandHealth\Services\MetricCalculator;
use App\BrandHealth\Services\SummaryProviderResolver;
use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $pmId = $request->integer('project_manager_id') ?: null;

        $accounts = BrandAccount::with('projectManager:id,name')
            ->withCount('cycles')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($pmId, fn ($query, $pmId) => $query->where('project_manager_id', $pmId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('brand-health.accounts.index', [
            'accounts' => $accounts,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'projectManagerId' => $pmId,
            'canManage' => $request->user()->can('edit-brand-health'),
        ]);
    }

    public function growth(BrandAccount $account, MetricCalculator $calculator): View
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $cycles = $account->cycles()
            ->orderBy('cycle_start_date')
            ->get()
            ->map(function ($cycle) use ($calculator, $scoreBuckets, $labelBuckets, $formulaWeights) {
                $scores = $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights);

                return [
                    'label' => $cycle->cycle_start_date->format('M Y'),
                    'cycle_start_date' => $cycle->cycle_start_date->toDateString(),
                    'platform' => $cycle->platform,
                    'growth_rate' => round($scores['growth_rate'], 2),
                    'end_follower' => $cycle->end_follower,
                    'reach' => $cycle->reach,
                    'views' => $cycle->views,
                    'engagement' => $cycle->engagement,
                    'visibility_rate' => round($scores['visibility_rate'], 2),
                    'engagement_score' => round($scores['engagement_score'], 2),
                    'health_rate' => round($scores['health_rate'], 2),
                    'er_reach_rate' => round($scores['er_reach_rate'], 2),
                    'er_follower_rate' => round($scores['er_follower_rate'], 2),
                ];
            });

        return view('brand-health.accounts.growth', [
            'account' => $account,
            'cycles' => $cycles,
            'canManage' => request()->user()->can('edit-brand-health'),
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

    public function pdf(BrandAccount $account, MetricCalculator $calculator): HttpResponse
    {
        $cycles = $account->cycles()->orderBy('cycle_start_date')->get()->map(
            fn ($cycle) => ['cycle' => $cycle, 'scores' => $calculator->calculate($cycle)]
        );

        $pdf = Pdf::loadView('brand-health.pdf.account-growth', [
            'account' => $account,
            'cycles' => $cycles,
            'generatedAt' => now()->format('M j, Y g:i A'),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf('%s-growth-%s.pdf', Str::slug($account->name), now()->format('Y-m-d'));

        return $pdf->download($filename);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
        ]);

        BrandAccount::create($data);

        return back()->with('status', 'Account created.');
    }

    public function update(Request $request, BrandAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $account->update($data);

        return back()->with('status', 'Account updated.');
    }

    public function destroy(BrandAccount $account): RedirectResponse
    {
        $account->delete();

        return back()->with('status', 'Account deleted.');
    }
}
