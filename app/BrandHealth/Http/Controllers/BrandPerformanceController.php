<?php

namespace App\BrandHealth\Http\Controllers;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandPerformance;
use App\BrandHealth\Services\SummaryProviderResolver;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandPerformanceController extends Controller
{
    public function index(Request $request): View
    {
        $performances = BrandPerformance::with(['account', 'projectManager', 'conceptor', 'editor'])
            ->when($request->integer('account_id'), fn ($q, $id) => $q->where('account_id', $id))
            ->orderByDesc('post_date')
            ->paginate(15)
            ->withQueryString();

        return view('brand-health.performances.index', [
            'performances' => $performances,
            'accounts' => BrandAccount::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'noteOptions' => BrandPerformance::NOTE_OPTIONS,
            'canManage' => $request->user()->can('edit-brand-health'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BrandPerformance::create($this->validated($request));

        return back()->with('status', 'Performance entry created.');
    }

    public function update(Request $request, BrandPerformance $performance): RedirectResponse
    {
        $performance->update($this->validated($request));

        return back()->with('status', 'Performance entry updated.');
    }

    public function destroy(BrandPerformance $performance): RedirectResponse
    {
        $performance->delete();

        return back()->with('status', 'Performance entry deleted.');
    }

    public function summarize(Request $request, BrandPerformance $performance, SummaryProviderResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
            'provider' => ['nullable', 'string', 'in:'.implode(',', SummaryProviderResolver::PROVIDERS)],
        ]);

        try {
            $summary = $resolver->resolve($data['provider'] ?? null)
                ->summarizePerformance($performance->load(['account', 'igSnapshots']), $data['prompt'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $performance->forceFill([
            'ai_summary' => $summary,
            'ai_summary_generated_at' => now(),
        ])->save();

        return response()->json([
            'ai_summary' => $summary,
            'ai_summary_generated_at' => $performance->ai_summary_generated_at->toIso8601String(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'account_id' => ['required', 'exists:brand_accounts,id'],
            'platform' => ['nullable', 'string', 'max:255'],
            'post_date' => ['required', 'date'],
            'preview_date' => ['nullable', 'date'],
            'ads' => ['nullable', 'boolean'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
            'conceptor_id' => ['nullable', 'exists:users,id'],
            'editor_id' => ['nullable', 'exists:users,id'],
            'followers' => ['nullable', 'integer', 'min:0'],
            'total_views_h7' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
