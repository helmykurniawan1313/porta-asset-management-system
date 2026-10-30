<?php

namespace App\BrandHealth\Http\Controllers;

use App\BrandHealth\Models\BrandFormulaWeight;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BrandFormulaWeightController extends Controller
{
    public function update(Request $request, string $aggregate): RedirectResponse
    {
        if (! in_array($aggregate, BrandFormulaWeight::AGGREGATES, true)) {
            abort(404);
        }

        $components = BrandFormulaWeight::COMPONENTS[$aggregate];

        $data = $request->validate([
            'weights' => ['required', 'array'],
            'weights.*' => ['required', 'numeric', 'min:0'],
        ]);

        $weights = collect($data['weights'])->only($components);

        if ($weights->count() !== count($components) || $weights->sum() <= 0) {
            return back()->withErrors([
                'weights' => 'A positive weight is required for every component.',
            ]);
        }

        foreach ($components as $component) {
            BrandFormulaWeight::updateOrCreate(
                ['aggregate' => $aggregate, 'component' => $component],
                ['weight' => $weights->get($component)],
            );
        }

        return back()->with('status', 'Formula weights updated.');
    }
}
