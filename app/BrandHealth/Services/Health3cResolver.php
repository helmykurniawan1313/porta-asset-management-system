<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;

/**
 * health_3c smooths month-to-month Health swings into a trend line: the
 * average of this cycle's Health score and up to its 2 previous cycles (same
 * account + platform, ordered by cycle_start_date — spans AM changes, since
 * it's about the account's actual performance, not who managed it). Fewer
 * than 3 cycles of history just averages however many exist (e.g. an
 * account's first cycle has no history, so health_3c == its own Health).
 *
 * Only resolved for the cycle being saved — editing an older cycle later
 * does NOT cascade a recompute onto newer cycles whose 3-cycle window
 * included it (those stay as they were until next saved themselves). This
 * matches how tier/needs_check already behave (self-contained per save).
 */
class Health3cResolver
{
    public function __construct(
        private readonly MetricCalculator $calculator = new MetricCalculator(),
    ) {
    }

    public function resolve(BrandCycle $cycle): array
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $priorCycles = BrandCycle::where('account_id', $cycle->account_id)
            ->where('platform', $cycle->platform)
            ->where('cycle_start_date', '<', $cycle->cycle_start_date)
            ->when($cycle->exists, fn ($query) => $query->whereKeyNot($cycle->getKey()))
            ->orderByDesc('cycle_start_date')
            ->limit(2)
            ->get();

        $window = $priorCycles->push($cycle);

        $healthRates = $window->map(
            fn (BrandCycle $c) => $this->calculator->calculate($c, $scoreBuckets, $labelBuckets, $formulaWeights)['health_rate']
        );

        $health3c = round($healthRates->avg(), 2);
        $label = $this->resolveLabel($labelBuckets, $health3c);

        // True if ANY cycle in the window (not just this one) is flagged —
        // a suspect growth input from 1-2 cycles ago still taints the
        // averaged trend line, even if this cycle's own data looks fine.
        $needsCheck = $window->contains(fn (BrandCycle $c) => (bool) $c->needs_check);

        return [$health3c, $label, $needsCheck];
    }

    private function resolveLabel($labelBuckets, float $score): ?string
    {
        $resolver = new ScoreBucketResolver();

        return $resolver->resolve(
            $labelBuckets->where('metric', BrandLabelBucket::METRIC_HEALTH),
            $score,
            minKey: 'min_score',
            valueKey: 'label',
        );
    }
}
