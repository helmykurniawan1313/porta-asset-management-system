<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Models\BrandLegalPage;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View as Response;

class BrandLegalPageController extends Controller
{
    public function edit(): Response
    {
        return view('brand-health.settings.legal-pages', [
            'pages' => BrandLegalPage::orderBy('title')->get(['id', 'slug', 'title', 'content_html']),
        ]);
    }

    public function update(Request $request, BrandLegalPage $legalPage): RedirectResponse
    {
        $data = $request->validate([
            'content_html' => ['required', 'string'],
        ]);

        $legalPage->update([
            'content_html' => $this->purify($data['content_html']),
        ]);

        return back();
    }

    /**
     * Strips anything beyond basic formatting (bold/italic/headings/lists/links) —
     * this content is rendered on public, unauthenticated pages, so raw editor HTML
     * must never be trusted as-is.
     */
    private function purify(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,h2,h3,strong,em,ul,ol,li,a[href],br');
        $config->set('HTML.TargetBlank', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

        return (new HTMLPurifier($config))->purify($html);
    }
}
