<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandInstagramDailySnapshot;
use App\BrandHealth\Models\BrandPerformance;
use App\BrandHealth\Models\BrandPerformanceIgSnapshot;

class PerformanceIgSnapshotService
{
    public function __construct(private InstagramInsightsService $insights)
    {
    }

    /**
     * Fetches current media insights for a linked Performance and stores them
     * as a new snapshot row — media insights are cumulative-since-publish
     * totals, not day-bucketed, so every capture is a distinct point in time.
     */
    public function capture(BrandPerformance $performance): BrandPerformanceIgSnapshot
    {
        $metrics = collect($this->insights->mediaInsights(
            $performance->account,
            $performance->ig_media_id,
            $performance->ig_media_product_type
        ))->keyBy('name')->map(
            fn (array $metric) => $metric['values'][0]['value'] ?? $metric['total_value']['value'] ?? null
        );

        return $performance->igSnapshots()->create([
            'reach' => $metrics->get('reach'),
            'likes' => $metrics->get('likes'),
            'comments' => $metrics->get('comments'),
            'shares' => $metrics->get('shares'),
            'saved' => $metrics->get('saved'),
            'total_interactions' => $metrics->get('total_interactions'),
            'views' => $metrics->get('views'),
            'ig_reels_avg_watch_time' => $metrics->get('ig_reels_avg_watch_time'),
            'ig_reels_video_view_total_time' => $metrics->get('ig_reels_video_view_total_time'),
            'fetched_at' => now(),
        ]);
    }

    /**
     * Sets Followers from the account's daily snapshot matching post_date —
     * media insights don't include follower count (that's account-level
     * data), so this reads from the separate daily-snapshot table instead.
     * Falls back to the closest snapshot on or before post_date when the
     * exact day wasn't captured (e.g. cron hadn't run yet), and records
     * which date the number actually came from.
     */
    public function fillFollowersForPostDate(BrandPerformance $performance): void
    {
        $snapshot = BrandInstagramDailySnapshot::where('account_id', $performance->account_id)
            ->whereNotNull('followers_count')
            ->where('captured_date', '<=', $performance->post_date)
            ->orderByDesc('captured_date')
            ->first();

        if (! $snapshot) {
            $snapshot = BrandInstagramDailySnapshot::where('account_id', $performance->account_id)
                ->whereNotNull('followers_count')
                ->orderBy('captured_date')
                ->first();
        }

        if (! $snapshot) {
            return;
        }

        $performance->forceFill([
            'followers' => $snapshot->followers_count,
            'followers_captured_date' => $snapshot->captured_date,
        ])->save();
    }
}
