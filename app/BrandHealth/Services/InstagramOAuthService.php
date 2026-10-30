<?php

namespace App\BrandHealth\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramOAuthService
{
    private const AUTHORIZE_URL = 'https://www.instagram.com/oauth/authorize';

    private const TOKEN_EXCHANGE_URL = 'https://api.instagram.com/oauth/access_token';

    private const GRAPH_BASE_URL = 'https://graph.instagram.com';

    public function authorizeUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => config('services.brand_health.instagram.app_id'),
            'redirect_uri' => config('services.brand_health.instagram.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'instagram_business_basic,instagram_business_manage_insights',
            'state' => $state,
        ]);

        return self::AUTHORIZE_URL.'?'.$params;
    }

    /**
     * Exchanges an OAuth code for a short-lived token, then immediately
     * exchanges that for a 60-day long-lived token (two separate hops).
     */
    public function exchangeCodeForLongLivedToken(string $code): array
    {
        $shortLived = Http::asForm()
            ->post(self::TOKEN_EXCHANGE_URL, [
                'client_id' => config('services.brand_health.instagram.app_id'),
                'client_secret' => config('services.brand_health.instagram.app_secret'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.brand_health.instagram.redirect_uri'),
                'code' => $code,
            ])
            ->throw()
            ->json();

        $longLived = Http::get(self::GRAPH_BASE_URL.'/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => config('services.brand_health.instagram.app_secret'),
            'access_token' => $shortLived['access_token'],
        ])
            ->throw()
            ->json();

        if (! isset($longLived['access_token'])) {
            throw new RuntimeException('Instagram long-lived token exchange returned no access_token.');
        }

        return [
            'ig_business_id' => (string) $shortLived['user_id'],
            'access_token' => $longLived['access_token'],
            'expires_in_seconds' => $longLived['expires_in'] ?? 5184000,
        ];
    }

    /**
     * Must be called before a 60-day token expires; refreshing extends it another 60 days.
     */
    public function refreshLongLivedToken(string $currentToken): array
    {
        $refreshed = Http::get(self::GRAPH_BASE_URL.'/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $currentToken,
        ])
            ->throw()
            ->json();

        return [
            'access_token' => $refreshed['access_token'],
            'expires_in_seconds' => $refreshed['expires_in'] ?? 5184000,
        ];
    }
}
