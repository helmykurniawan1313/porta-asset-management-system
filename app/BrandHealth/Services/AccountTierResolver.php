<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandLabelBucket;
use Illuminate\Support\Collection;

/**
 * Resolves a cycle's account-size tier (Rintisan/Kecil/Menengah/Besar, exact
 * names/thresholds editable via Settings) from start_follower, using the same
 * "highest min <= value wins" semantics as every other bucket lookup in this
 * app. Tier is resolved from start_follower (not end_follower) per the health
 * formula brief — a cycle is scored against the size the account was AT THE
 * START of the period, not where it ended up.
 */
class AccountTierResolver
{
    public function __construct(
        private readonly ScoreBucketResolver $resolver = new ScoreBucketResolver(),
    ) {
    }

    /**
     * @param  Collection<int, LabelBucket>|null  $tierBuckets  Loaded from DB if omitted.
     */
    public function resolve(float $startFollower, ?Collection $tierBuckets = null): ?string
    {
        $tierBuckets ??= BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ACCOUNT_TIER)->get();

        return $this->resolver->resolve(
            $tierBuckets,
            $startFollower,
            minKey: 'min_score',
            valueKey: 'label',
        );
    }
}
