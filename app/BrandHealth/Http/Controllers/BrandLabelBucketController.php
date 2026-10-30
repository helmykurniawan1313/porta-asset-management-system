<?php

namespace App\BrandHealth\Http\Controllers;

use App\BrandHealth\Models\BrandLabelBucket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BrandLabelBucketController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        BrandLabelBucket::create($this->validated($request));

        return back()->with('status', 'Label bucket created.');
    }

    public function update(Request $request, BrandLabelBucket $labelBucket): RedirectResponse
    {
        $labelBucket->update($this->validated($request));

        return back()->with('status', 'Label bucket updated.');
    }

    public function destroy(BrandLabelBucket $labelBucket): RedirectResponse
    {
        $labelBucket->delete();

        return back()->with('status', 'Label bucket deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'metric' => ['required', 'string', 'in:'.implode(',', BrandLabelBucket::METRICS)],
            'min_score' => ['nullable', 'numeric'],
            'label' => ['required', 'string', 'max:50'],
        ]);
    }
}
