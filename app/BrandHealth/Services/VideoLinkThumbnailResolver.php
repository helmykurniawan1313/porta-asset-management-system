<?php

namespace App\BrandHealth\Services;

use Illuminate\Support\Facades\Http;

class VideoLinkThumbnailResolver
{
    public function detectPlatform(string $url): string
    {
        return match (true) {
            (bool) preg_match('/instagram\.com/i', $url) => 'instagram',
            (bool) preg_match('/tiktok\.com/i', $url) => 'tiktok',
            default => 'other',
        };
    }

    public function resolveThumbnail(string $url, string $platform): ?string
    {
        if ($platform !== 'tiktok') {
            return null;
        }

        return $this->tiktokOembed($url)?->json('thumbnail_url');
    }

    /**
     * HTML for the platform's native embed widget, rendered client-side in the browser.
     * Instagram's oEmbed API is no longer publicly usable, but its embed.js widget only
     * needs the permalink in a blockquote to render — no server-side API call required.
     */
    public function resolveEmbedHtml(string $url, string $platform): ?string
    {
        return match ($platform) {
            'tiktok' => $this->tiktokOembed($url)?->json('html'),
            'instagram' => $this->instagramBlockquote($url),
            default => null,
        };
    }

    private function tiktokOembed(string $url): ?\Illuminate\Http\Client\Response
    {
        try {
            $response = Http::timeout(4)->connectTimeout(3)->get('https://www.tiktok.com/oembed', ['url' => $url]);

            return $response->successful() ? $response : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function instagramBlockquote(string $url): string
    {
        $safeUrl = e($url);

        return <<<HTML
        <blockquote class="instagram-media" data-instgrm-permalink="{$safeUrl}" data-instgrm-version="14" style="margin:0 auto; max-width:540px; min-width:326px;"></blockquote>
        <script async src="//www.instagram.com/embed.js"></script>
        HTML;
    }
}
