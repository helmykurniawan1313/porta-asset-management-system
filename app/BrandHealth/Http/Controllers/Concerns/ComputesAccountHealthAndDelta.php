<?php

namespace App\BrandHealth\Http\Controllers\Concerns;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use App\BrandHealth\Services\MetricCalculator;
use App\BrandHealth\Services\ScoreBucketResolver;
use Illuminate\Support\Collection;

/**
 * Shared by AccountHealthController and AccountGrowthController — both pages
 * read the same per-account "latest cycle health + delta vs. previous cycle"
 * shape, just render different halves of it:
 *   - Health: the account's current absolute LEVEL (0-100 health score →
 *     SIP/BAGUS/CUKUP/KURANG/PARAH), from its latest cycle.
 *   - Delta: whether each of Reach/Views/Engagement individually improved or
 *     declined vs. the immediately PREVIOUS cycle (period-over-period %
 *     change, resolved via the *_change LabelBucket metrics).
 * An account can legitimately show poor Health but positive Delta (still in
 * bad shape overall, but recovering) — kept as two separate pages/sidebar
 * items specifically so neither axis is mistaken for the other.
 */
trait ComputesAccountHealthAndDelta
{
    /**
     * Percent change from one value to another — null when the base is 0/missing,
     * matching AccountController::percentChange().
     */
    private function percentChange(?float $from, ?float $to): ?float
    {
        if ($from === null || $to === null || $from == 0.0) {
            return null;
        }

        return (($to - $from) / $from) * 100;
    }

    /**
     * Health level + Reach/Views/Engagement delta of each account's most
     * recently-started cycle, keyed by account_id. Computed PER PLATFORM
     * (instagram/tiktok cycles never compared against each other), same
     * reasoning as AccountController::latestCycleHealthByAccount() — the
     * top-level fields mirror the "primary" platform (Instagram preferred),
     * with a `by_platform` map carrying every platform's own figures.
     */
    private function latestCycleHealthByAccount(Collection $accountIds, MetricCalculator $calculator): Collection
    {
        if ($accountIds->isEmpty()) {
            return collect();
        }

        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();
        $accountGrowthBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_ACCOUNT_GROWTH);
        $viewsChangeBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_VIEWS_CHANGE);
        $reachChangeBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_REACH_CHANGE);
        $engagementChangeBuckets = $labelBuckets->where('metric', BrandLabelBucket::METRIC_ENGAGEMENT_CHANGE);
        $resolver = new ScoreBucketResolver();

        return BrandCycle::whereIn('account_id', $accountIds)
            ->orderBy('cycle_start_date')
            ->get()
            ->groupBy('account_id')
            ->map(function (Collection $accountCycles) use ($calculator, $scoreBuckets, $labelBuckets, $formulaWeights, $accountGrowthBuckets, $viewsChangeBuckets, $reachChangeBuckets, $engagementChangeBuckets, $resolver) {
                $byPlatform = $accountCycles
                    ->groupBy(fn (BrandCycle $cycle) => $cycle->platform ?? 'instagram')
                    ->map(function (Collection $cycles) use ($calculator, $scoreBuckets, $labelBuckets, $formulaWeights, $accountGrowthBuckets, $viewsChangeBuckets, $reachChangeBuckets, $engagementChangeBuckets, $resolver) {
                        $latest = $cycles->last();
                        $previous = $cycles->count() > 1 ? $cycles->slice(-2, 1)->first() : null;
                        $scores = $calculator->calculate($latest, $scoreBuckets, $labelBuckets, $formulaWeights);

                        $viewsChangeRate = $this->percentChange($previous?->views, $latest->views);
                        $reachChangeRate = $this->percentChange($previous?->reach, $latest->reach);
                        $engagementChangeRate = $this->percentChange($previous?->engagement, $latest->engagement);

                        return [
                            'latest_cycle_label' => $latest->cycle_start_date->format('M Y'),
                            'health_score' => round($scores['health_rate'], 1),
                            'health_label' => $scores['health_label'],
                            'growth_score' => round($scores['growth_rate'], 1),
                            // Follower Growth Rate — the account's own latest-cycle growth,
                            // same metric/label bucket (METRIC_ACCOUNT_GROWTH) as the
                            // Accounts table's "Growth Rate" column and the Growth modal —
                            // distinct from the Reach/Views/Engagement deltas below, which
                            // compare raw volume, not follower count.
                            'growth_rate' => round($scores['growth_rate'], 2),
                            'growth_label' => $resolver->resolve($accountGrowthBuckets, $scores['growth_rate'], 'min_score', 'label'),
                            'visibility_rate' => round($scores['visibility_rate'], 1),
                            'engagement_score' => round($scores['engagement_score'], 1),
                            'views_change_rate' => $viewsChangeRate,
                            'views_status' => $viewsChangeRate !== null ? $resolver->resolve($viewsChangeBuckets, $viewsChangeRate, 'min_score', 'label') : null,
                            'reach_change_rate' => $reachChangeRate,
                            'reach_status' => $reachChangeRate !== null ? $resolver->resolve($reachChangeBuckets, $reachChangeRate, 'min_score', 'label') : null,
                            'engagement_change_rate' => $engagementChangeRate,
                            'engagement_status' => $engagementChangeRate !== null ? $resolver->resolve($engagementChangeBuckets, $engagementChangeRate, 'min_score', 'label') : null,
                        ];
                    });

                $primary = $byPlatform->get('instagram') ?? $byPlatform->first();

                return array_merge($primary, ['by_platform' => $byPlatform->all()]);
            });
    }
}
