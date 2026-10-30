<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandScoreBucket;
use Illuminate\Support\Collection;

/**
 * health_3c smooths month-to-month Health swings into a trend line: the
 * average of this cycle's Health score and up to its 2 previous cycles (same
 * account + platform, ordered by cycle_start_date — spans AM changes, since
 * it's about the account's actual performance, not who managed it). Fewer
 * than 3 cycles of history just averages however many exist (e.g. an
 * account's first cycle has no history, so health_3c == its own Health).
 *
 * BrandCycle's model events keep neighbours in step: saving, moving or
 * deleting a cycle re-resolves the next 2 cycles whose window included it.
 * Scoring settings are loaded once per resolver instance, so reusing one
 * instance across many cycles (bulk recalculation) doesn't re-query them.
 */
class Health3cResolver
{
    public function __construct(
        private readonly MetricCalculator $calculator = new MetricCalculator(),
    ) {
    }

    /** @var array{0: Collection, 1: Collection, 2: Collection}|null */
    private ?array $settings = null;

    /**
     * @param  Collection<int, BrandCycle>|null  $priorCycles  Up to 2 earlier cycles of the same account + platform; queried if omitted.
     */
    public function resolve(BrandCycle $cycle, ?Collection $priorCycles = null): array
    {
        [$scoreBuckets, $labelBuckets, $formulaWeights] = $this->settings ??= [
            BrandScoreBucket::all(),
            BrandLabelBucket::all(),
            BrandFormulaWeight::all(),
        ];

        $priorCycles ??= BrandCycle::where('account_id', $cycle->account_id)
            ->where('platform', $cycle->platform)
            ->where('cycle_start_date', '<', $cycle->cycle_start_date)
            ->when($cycle->exists, fn ($query) => $query->whereKeyNot($cycle->getKey()))
            ->orderByDesc('cycle_start_date')
            ->limit(2)
            ->get();

        $window = $priorCycles->concat([$cycle]);

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
