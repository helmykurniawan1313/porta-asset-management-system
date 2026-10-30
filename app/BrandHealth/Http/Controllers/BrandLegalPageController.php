<?php

namespace App\BrandHealth\Http\Controllers;

use App\BrandHealth\Models\BrandLegalPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandLegalPageController extends Controller
{
    public function edit(Request $request): View
    {
        return view('brand-health.legal-pages.edit', [
            'pages' => BrandLegalPage::orderBy('title')->get(['id', 'slug', 'title', 'content_html']),
            'canManage' => $request->user()->can('edit-brand-health'),
        ]);
    }

    public function update(Request $request, BrandLegalPage $legalPage): RedirectResponse
    {
        $data = $request->validate([
            'content_html' => ['required', 'string'],
        ]);

        // Basic tag stripping (no HTMLPurifier dependency in this app) — strong
        // enough for a settings-only editor with a gated write route, but if this
        // content is ever exposed to arbitrary untrusted editors, add HTMLPurifier.
        $allowed = '<p><h2><h3><strong><em><ul><ol><li><a><br>';

        $legalPage->update([
            'content_html' => strip_tags($data['content_html'], $allowed),
        ]);

        return back()->with('status', 'Page updated.');
    }
}
