<?php

namespace App\BrandHealth\Services;

use Illuminate\Support\Collection;

class ScoreBucketResolver
{
    /**
     * [min, value] pairs extracted from the bucket collection last resolved against.
     * Pages resolve hundreds of records against the same collection, and reading
     * min_score/min_rate through Eloquent's decimal cast on every call was the
     * dominant cost; extracting once per collection keeps each call a plain scan.
     *
     * @var array{key: string, ref: Collection, pairs: array}|null
     */
    private ?array $cache = null;

    /**
     * Resolves a rate to the value of the tier with the highest min_rate that is <= rate.
     * A null min_rate is treated as -infinity (the floor tier), so it always matches
     * unless a higher tier also matches.
     *
     * Single linear scan tracking the best match so far, instead of allocating a
     * filtered + sorted Collection on every call — this is called many times per
     * cycle (once per metric, per cycle), so the allocation overhead compounds
     * noticeably on pages that score dozens/hundreds of cycles at once.
     *
     * @param  Collection  $buckets  Buckets for a single metric, each exposing $minKey and $valueKey.
     */
    public function resolve(Collection $buckets, float $rate, string $minKey = 'min_rate', string $valueKey = 'score'): mixed
    {
        $rate = round($rate, 4);

        $winner = null;
        $winnerMin = -INF;
        $found = false;

        foreach ($this->pairs($buckets, $minKey, $valueKey) as [$min, $value]) {
            if ($min > $rate) {
                continue;
            }

            if (! $found || $min > $winnerMin) {
                $winner = $value;
                $winnerMin = $min;
                $found = true;
            }
        }

        return $winner;
    }

    /**
     * @return array<int, array{0: float, 1: mixed}>
     */
    private function pairs(Collection $buckets, string $minKey, string $valueKey): array
    {
        $key = spl_object_id($buckets).':'.$buckets->count().':'.$minKey.':'.$valueKey;

        if ($this->cache !== null && $this->cache['key'] === $key) {
            return $this->cache['pairs'];
        }

        $pairs = [];
        foreach ($buckets as $bucket) {
            $min = $bucket->{$minKey};
            $pairs[] = [$min === null ? -INF : round((float) $min, 4), $bucket->{$valueKey}];
        }

        // Holding the collection keeps its object id from being reused while cached.
        $this->cache = ['key' => $key, 'ref' => $buckets, 'pairs' => $pairs];

        return $pairs;
    }
}
