<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use Illuminate\Support\Collection;

class MetricCalculator
{
    public function __construct(
        private readonly ScoreBucketResolver $resolver = new ScoreBucketResolver(),
    ) {
    }

    /**
     * @param  Collection<int, BrandScoreBucket>|null  $scoreBuckets  Rate-based buckets. Loaded from DB if omitted.
     * @param  Collection<int, BrandLabelBucket>|null  $labelBuckets  Label-based buckets. Loaded from DB if omitted.
     * @param  Collection<int, BrandFormulaWeight>|null  $formulaWeights  Aggregate weights. Loaded from DB if omitted.
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

        $scoreBucketsByMetric = $scoreBuckets->groupBy('metric');
        $labelBucketsByMetric = $labelBuckets->groupBy('metric');

        $growth = $cycle->end_follower - $cycle->start_follower;
        $growthRate = $this->percentage($growth, $cycle->start_follower);
        $reachRate = $this->percentage($cycle->reach, $cycle->end_follower);
        $viewRate = $this->percentage($cycle->views, $cycle->end_follower);
        $erReachRate = $this->percentage($cycle->engagement, $cycle->reach);
        $erFollowerRate = $this->percentage($cycle->engagement, $cycle->end_follower);

        $growthScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_GROWTH, $growthRate);
        $reachScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_REACH, $reachRate);
        $viewScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_VIEW, $viewRate);
        $erReachScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_ER_REACH, $erReachRate);
        $erFollowerScore = $this->resolveScore($scoreBucketsByMetric, BrandScoreBucket::METRIC_ER_FOLLOWER, $erFollowerRate);

        $visibilityRate = $this->weightedAverage($formulaWeights, BrandFormulaWeight::AGGREGATE_VISIBILITY, [
            'reach_score' => $reachScore,
            'view_score' => $viewScore,
        ]);

        $engagementScore = $this->weightedAverage($formulaWeights, BrandFormulaWeight::AGGREGATE_ENGAGEMENT, [
            'er_reach_score' => $erReachScore,
            'er_follower_score' => $erFollowerScore,
        ]);

        $growthLabel = $this->resolveLabel($labelBucketsByMetric, BrandLabelBucket::METRIC_GROWTH, $growthRate);
        $visibilityLabel = $this->resolveLabel($labelBucketsByMetric, BrandLabelBucket::METRIC_VISIBILITY, $visibilityRate);
        $engagementLabel = $this->resolveLabel($labelBucketsByMetric, BrandLabelBucket::METRIC_ENGAGEMENT, $engagementScore);

        $healthRate = $this->weightedAverage($formulaWeights, BrandFormulaWeight::AGGREGATE_HEALTH, [
            'growth_score' => $growthScore,
            'visibility_rate' => $visibilityRate,
            'engagement_score' => $engagementScore,
        ]);
        $healthLabel = $this->resolveLabel($labelBucketsByMetric, BrandLabelBucket::METRIC_HEALTH, $healthRate);

        return [
            'growth' => $growth,
            'growth_rate' => $growthRate,
            'reach_rate' => $reachRate,
            'view_rate' => $viewRate,
            'er_reach_rate' => $erReachRate,
            'er_follower_rate' => $erFollowerRate,
            'growth_score' => $growthScore,
            'growth_label' => $growthLabel,
            'reach_score' => $reachScore,
            'view_score' => $viewScore,
            'er_reach_score' => $erReachScore,
            'er_follower_score' => $erFollowerScore,
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
    private function weightedAverage(Collection $formulaWeights, string $aggregate, array $components): float
    {
        $weights = $formulaWeights->where('aggregate', $aggregate)->pluck('weight', 'component');

        $equalWeight = 1 / count($components);
        $totalWeight = 0.0;
        $weightedSum = 0.0;

        foreach ($components as $component => $value) {
            $weight = $weights->has($component) ? (float) $weights->get($component) : $equalWeight;
            $weightedSum += $value * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight == 0.0) {
            return 0.0;
        }

        return $weightedSum / $totalWeight;
    }

    private function resolveScore(Collection $scoreBucketsByMetric, string $metric, float $rate): float
    {
        $value = $this->resolver->resolve($scoreBucketsByMetric->get($metric, Collection::empty()), $rate);

        return $value === null ? 0.0 : (float) $value;
    }

    private function resolveLabel(Collection $labelBucketsByMetric, string $metric, float $score): ?string
    {
        return $this->resolver->resolve(
            $labelBucketsByMetric->get($metric, Collection::empty()),
            $score,
            minKey: 'min_score',
            valueKey: 'label',
        );
    }

    private function percentage(float $numerator, float $denominator): float
    {
        if ($denominator == 0.0) {
            return 0.0;
        }

        return ($numerator / $denominator) * 100;
    }
}
