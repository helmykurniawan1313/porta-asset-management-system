<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use Illuminate\Support\Collection;

class MetricCalculator
{
    /**
     * Settings indexed into plain arrays, keyed by the identity of the collections they
     * came from. Pages score hundreds of cycles with the same three collections, so the
     * grouping/filtering below happens once per request instead of once per cycle.
     *
     * @var array{key: string, refs: array, scores: array, labels: array, weights: array}|null
     */
    private ?array $index = null;

    public function __construct(
        private readonly ScoreBucketResolver $resolver = new ScoreBucketResolver(),
    ) {
    }

    /**
     * @param  Collection<int, ScoreBucket>|null  $scoreBuckets  Rate-based buckets. Loaded from DB if omitted.
     * @param  Collection<int, LabelBucket>|null  $labelBuckets  Label-based buckets. Loaded from DB if omitted.
     * @param  Collection<int, FormulaWeight>|null  $formulaWeights  Aggregate weights. Loaded from DB if omitted.
     *
     * Reads tier from $cycle->tier — resolved once (via AccountTierResolver)
     * and stored on the cycle when it's created/updated, not re-resolved live
     * on every calculate() call. A cycle with no tier stored yet (not
     * resolved, or tiering not configured) falls back to the global bucket
     * set via resolveScore()'s (platform, null) / (null, null) fallbacks.
     */
    public function calculate(
        BrandCycle $cycle,
        ?Collection $scoreBuckets = null,
        ?Collection $labelBuckets = null,
        ?Collection $formulaWeights = null,
    ): array {
        $scoreBuckets ??= BrandScoreBucket::all();
        $labelBuckets ??= BrandLabelBucket::all();
        $formulaWeights ??= BrandFormulaWeight::all();

        $tier = $cycle->tier;
        $platform = $cycle->platform;

        // Grouped by metric only — the (platform, tier) fallback itself is
        // resolved per-metric in resolveScore() below, since a metric can
        // have a platform+tier-specific bucket set, a platform-only one, or
        // only the global one, and the right fallback differs per metric.
        ['scores' => $scoreBucketsByMetric, 'labels' => $labelBucketsByMetric, 'weights' => $formulaWeights] = $this->index($scoreBuckets, $labelBuckets, $formulaWeights);

        $isTiktok = $platform === 'tiktok';

        $growth = $cycle->end_follower - $cycle->start_follower;
        $growthRate = $this->percentage($growth, $cycle->start_follower);
        $viewRate = $this->percentage($cycle->views, $cycle->end_follower);

        // TikTok's "reach" column is actually profile views (per-post profile
        // visits, not unique accounts reached) — reach_rate/er_reach_rate are
        // still computed below (never null in the response, so existing
        // display code doesn't break), but they must NOT feed TikTok's score
        // or aggregates: profile_rate/er_view_rate replace them instead.
        $reachRate = $this->percentage($cycle->reach, $cycle->end_follower);
        $erReachRate = $this->percentage($cycle->engagement, $cycle->reach);
        $erFollowerRate = $this->percentage($cycle->engagement, $cycle->end_follower);
        $profileRate = $this->percentage($cycle->reach, $cycle->views) * 10; // profile visits per 1,000 views
        $erViewRate = $this->percentage($cycle->engagement, $cycle->views);

        $growthScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_GROWTH, $growthRate, $platform, $tier);
        $viewScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_VIEW, $viewRate, $platform, $tier);

        if ($isTiktok) {
            $profileScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_PROFILE, $profileRate, $platform, $tier);
            $erViewScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_ER_VIEW, $erViewRate, $platform, $tier);

            // Still resolved for display/consistency — not used in any TikTok aggregate.
            $reachScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_REACH, $reachRate, $platform, $tier);
            $erReachScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_ER_REACH, $erReachRate, $platform, $tier);
            $erFollowerScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_ER_FOLLOWER, $erFollowerRate, $platform, $tier);

            $visibilityRate = $this->weightedAverage($formulaWeights, BrandFormulaWeight::AGGREGATE_VISIBILITY_TIKTOK, [
                'profile_score' => $profileScore,
                'view_score' => $viewScore,
            ]);

            $engagementScore = $this->weightedAverage($formulaWeights, BrandFormulaWeight::AGGREGATE_ENGAGEMENT_TIKTOK, [
                'er_view_score' => $erViewScore,
            ]);
        } else {
            $reachScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_REACH, $reachRate, $platform, $tier);
            $erReachScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_ER_REACH, $erReachRate, $platform, $tier);
            $erFollowerScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_ER_FOLLOWER, $erFollowerRate, $platform, $tier);
            $profileScore = 0.0;
            $erViewScore = 0.0;

            $visibilityRate = $this->weightedAverage($formulaWeights, BrandFormulaWeight::AGGREGATE_VISIBILITY, [
                'reach_score' => $reachScore,
                'view_score' => $viewScore,
            ]);

            $engagementScore = $this->weightedAverage($formulaWeights, BrandFormulaWeight::AGGREGATE_ENGAGEMENT, [
                'er_reach_score' => $erReachScore,
                'er_follower_score' => $erFollowerScore,
            ]);
        }

        // Uses growth_score (0-100, tier-aware), not the raw growthRate — a
        // tiered score/rate mismatch here previously let e.g. a 6.92% growth
        // rate score 100 (strong for a Menengah/Besar account) but still
        // label CUKUP, because the label cutoffs were still being checked
        // against the pre-tiering percentage scale.
        $growthLabel = $this->resolveLabel($labelBucketsByMetric, BrandLabelBucket::METRIC_GROWTH, $growthScore);
        $visibilityLabel = $this->resolveLabel($labelBucketsByMetric, BrandLabelBucket::METRIC_VISIBILITY, $visibilityRate);
        $engagementLabel = $this->resolveLabel($labelBucketsByMetric, BrandLabelBucket::METRIC_ENGAGEMENT, $engagementScore);

        $healthRate = $this->weightedAverage($formulaWeights, BrandFormulaWeight::AGGREGATE_HEALTH, [
            'growth_score' => $growthScore,
            'visibility_rate' => $visibilityRate,
            'engagement_score' => $engagementScore,
        ]);
        $healthLabel = $this->resolveLabel($labelBucketsByMetric, BrandLabelBucket::METRIC_HEALTH, $healthRate);

        return [
            'tier' => $tier,
            'growth' => $growth,
            'growth_rate' => $growthRate,
            'reach_rate' => $reachRate,
            'view_rate' => $viewRate,
            'er_reach_rate' => $erReachRate,
            'er_follower_rate' => $erFollowerRate,
            'profile_rate' => $profileRate,
            'er_view_rate' => $erViewRate,
            'growth_score' => $growthScore,
            'growth_label' => $growthLabel,
            'reach_score' => $reachScore,
            'view_score' => $viewScore,
            'er_reach_score' => $erReachScore,
            'er_follower_score' => $erFollowerScore,
            'profile_score' => $profileScore,
            'er_view_score' => $erViewScore,
            'visibility_rate' => $visibilityRate,
            'visibility_label' => $visibilityLabel,
            'engagement_score' => $engagementScore,
            'engagement_label' => $engagementLabel,
            'health_rate' => $healthRate,
            'health_label' => $healthLabel,
        ];
    }

    /**
     * @param  array<string, float>  $components  component name => its computed score/rate value.
     */
    private function weightedAverage(array $formulaWeights, string $aggregate, array $components): float
    {
        $weights = $formulaWeights[$aggregate] ?? [];

        $equalWeight = 1 / count($components);
        $totalWeight = 0.0;
        $weightedSum = 0.0;

        foreach ($components as $component => $value) {
            $weight = array_key_exists($component, $weights) ? (float) $weights[$component] : $equalWeight;
            $weightedSum += $value * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight == 0.0) {
            return 0.0;
        }

        return $weightedSum / $totalWeight;
    }

    /**
     * Resolves a rate to a score for one metric, preferring the most specific
     * matching bucket set and falling back to progressively less specific
     * ones: (platform, tier) -> (platform, no tier) -> (no platform, no
     * tier). A bucket row with platform/tier = null acts as that fallback's
     * catch-all, so an admin can define global defaults and only add
     * platform/tier-specific overrides where the brief's thresholds differ.
     */
    private function resolveScore(array $scoreBucketsByMetric, string $metric, float $rate, ?string $platform, ?string $tier): float
    {
        $sets = $scoreBucketsByMetric[$metric] ?? [];

        $fallbacks = [
            [$platform, $tier],
            [$platform, null],
            [null, null],
        ];

        foreach ($fallbacks as [$fallbackPlatform, $fallbackTier]) {
            $matching = $sets[self::setKey($fallbackPlatform, $fallbackTier)] ?? [];

            if ($matching !== []) {
                $value = self::pick($matching, $rate);

                return $value === null ? 0.0 : (float) $value;
            }
        }

        return 0.0;
    }

    private function resolveLabel(array $labelBucketsByMetric, string $metric, float $score): ?string
    {
        return self::pick($labelBucketsByMetric[$metric] ?? [], $score);
    }

    /**
     * Same rule as ScoreBucketResolver::resolve() (highest min <= value, a null min
     * is the floor, earlier rows win ties, both sides rounded to 4 places), over
     * pre-extracted [min, value] pairs.
     *
     * @param  array<int, array{0: float, 1: mixed}>  $tiers
     */
    private static function pick(array $tiers, float $value): mixed
    {
        $value = round($value, 4);
        $winner = null;
        $winnerMin = -INF;
        $found = false;

        foreach ($tiers as [$min, $result]) {
            if ($min > $value) {
                continue;
            }

            if (! $found || $min > $winnerMin) {
                $winner = $result;
                $winnerMin = $min;
                $found = true;
            }
        }

        return $winner;
    }

    private static function setKey(?string $platform, ?string $tier): string
    {
        return ($platform ?? "\0").'|'.($tier ?? "\0");
    }

    /**
     * @return array{scores: array, labels: array, weights: array}
     */
    private function index(Collection $scoreBuckets, Collection $labelBuckets, Collection $formulaWeights): array
    {
        $key = spl_object_id($scoreBuckets).':'.$scoreBuckets->count().'|'.spl_object_id($labelBuckets).':'.$labelBuckets->count()
            .'|'.spl_object_id($formulaWeights).':'.$formulaWeights->count();

        if ($this->index !== null && $this->index['key'] === $key) {
            return $this->index;
        }

        $min = fn ($v) => $v === null ? -INF : round((float) $v, 4);

        $scores = [];
        foreach ($scoreBuckets as $bucket) {
            $scores[$bucket->metric][self::setKey($bucket->platform, $bucket->tier)][] = [$min($bucket->min_rate), $bucket->score];
        }

        $labels = [];
        foreach ($labelBuckets as $bucket) {
            $labels[$bucket->metric][] = [$min($bucket->min_score), $bucket->label];
        }

        $weights = [];
        foreach ($formulaWeights as $weight) {
            // pluck('weight', 'component') semantics: the last row for a component wins.
            $weights[$weight->aggregate][$weight->component] = $weight->weight;
        }

        // Holding the collections keeps their object ids from being reused while this index lives.
        return $this->index = ['key' => $key, 'refs' => [$scoreBuckets, $labelBuckets, $formulaWeights], 'scores' => $scores, 'labels' => $labels, 'weights' => $weights];
    }

    private function percentage(float $numerator, float $denominator): float
    {
        if ($denominator == 0.0) {
            return 0.0;
        }

        return ($numerator / $denominator) * 100;
    }
}
