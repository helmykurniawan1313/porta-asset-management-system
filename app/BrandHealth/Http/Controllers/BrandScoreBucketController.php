<?php

namespace App\BrandHealth\Http\Controllers;

use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandScoreBucketController extends Controller
{
    public function index(Request $request): View
    {
        return view('brand-health.scoring.index', [
            'scoreBuckets' => BrandScoreBucket::orderBy('metric')->orderByDesc('min_rate')->get(),
            'labelBuckets' => BrandLabelBucket::orderBy('metric')->orderByDesc('min_score')->get(),
            'scoreMetrics' => BrandScoreBucket::METRICS,
            'labelMetrics' => BrandLabelBucket::METRICS,
            'formulaWeights' => BrandFormulaWeight::all(),
            'formulaComponents' => BrandFormulaWeight::COMPONENTS,
            'canManage' => $request->user()->can('edit-brand-health'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BrandScoreBucket::create($this->validated($request));

        return back()->with('status', 'Score bucket created.');
    }

    public function update(Request $request, BrandScoreBucket $scoreBucket): RedirectResponse
    {
        $scoreBucket->update($this->validated($request));

        return back()->with('status', 'Score bucket updated.');
    }

    public function destroy(BrandScoreBucket $scoreBucket): RedirectResponse
    {
        $scoreBucket->delete();

        return back()->with('status', 'Score bucket deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'metric' => ['required', 'string', 'in:'.implode(',', BrandScoreBucket::METRICS)],
            'min_rate' => ['nullable', 'numeric'],
            'score' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
