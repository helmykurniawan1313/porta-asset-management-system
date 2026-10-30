<?php

namespace App\BrandHealth\Services;

use Illuminate\Support\Collection;

class ScoreBucketResolver
{
    /**
     * Resolves a rate to the value of the tier with the highest min_rate that is <= rate.
     * A null min_rate is treated as -infinity (the floor tier), so it always matches
     * unless a higher tier also matches.
     */
    public function resolve(Collection $buckets, float $rate, string $minKey = 'min_rate', string $valueKey = 'score'): mixed
    {
        $rate = round($rate, 4);

        $winner = null;
        $winnerMin = -INF;

        foreach ($buckets as $bucket) {
            $min = $bucket->{$minKey};
            $min = $min === null ? -INF : round((float) $min, 4);

            if ($min > $rate) {
                continue;
            }

            if ($winner === null || $min > $winnerMin) {
                $winner = $bucket;
                $winnerMin = $min;
            }
        }

        return $winner?->{$valueKey};
    }
}
