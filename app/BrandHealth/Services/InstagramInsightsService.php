<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramInsightsService
{
    private const BASE_URL = 'https://graph.instagram.com';

    /**
     * Feed/carousel media insight metrics vs. Reels metrics differ —
     * calling the wrong set for a media_product_type throws a hard error, not a silent drop.
     */
    private const FEED_MEDIA_METRICS = 'reach,likes,comments,shares,saved,total_interactions';

    private const REELS_MEDIA_METRICS = 'reach,likes,comments,shares,saved,views,ig_reels_avg_watch_time,ig_reels_video_view_total_time';

    public function profile(BrandAccount $account): array
    {
        return $this->request($account)
            ->get($this->url($account, ''), [
                'fields' => 'id,username,followers_count,media_count',
            ])
            ->throw()
            ->json();
    }

    public function dailyInsights(BrandAccount $account): array
    {
        return $this->request($account)
            ->get($this->url($account, '/insights'), [
                'metric' => 'reach,accounts_engaged,total_interactions,views',
                'period' => 'day',
                'metric_type' => 'total_value',
            ])
            ->throw()
            ->json('data', []);
    }

    public function followerCountInsight(BrandAccount $account): array
    {
        return $this->request($account)
            ->get($this->url($account, '/insights'), [
                'metric' => 'follower_count',
                'period' => 'lifetime',
                'metric_type' => 'total_value',
            ])
            ->throw()
            ->json('data', []);
    }

    public function media(BrandAccount $account): array
    {
        return $this->request($account)
            ->get($this->url($account, '/media'), [
                'fields' => 'id,caption,media_type,media_product_type,timestamp',
            ])
            ->throw()
            ->json('data', []);
    }

    public function mediaInsights(BrandAccount $account, string $mediaId, string $mediaProductType): array
    {
        $metrics = strtoupper($mediaProductType) === 'REELS'
            ? self::REELS_MEDIA_METRICS
            : self::FEED_MEDIA_METRICS;

        return $this->request($account)
            ->get("/{$mediaId}/insights", [
                'metric' => $metrics,
            ])
            ->throw()
            ->json('data', []);
    }

    private function request(BrandAccount $account): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)->withQueryParameters([
            'access_token' => $this->token($account),
        ]);
    }

    private function url(BrandAccount $account, string $suffix): string
    {
        return "/{$account->ig_business_id}{$suffix}";
    }

    private function token(BrandAccount $account): string
    {
        if (! $account->ig_access_token) {
            throw new RuntimeException("Account #{$account->id} has no Instagram access token connected.");
        }

        return $account->ig_access_token;
    }
}
