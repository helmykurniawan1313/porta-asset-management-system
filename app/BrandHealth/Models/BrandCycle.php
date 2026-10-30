<?php

namespace App\BrandHealth\Models;

use App\Models\User;
use App\BrandHealth\Services\AccountTierResolver;
use App\BrandHealth\Services\Health3cResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BrandCycle extends Model
{
    protected $table = 'brand_cycles';

    use HasFactory;

    protected static function booted(): void
    {
        // Resolved on every create/update rather than left to the caller, so
        // every path that saves a Cycle (controller, factory, future admin
        // tooling) gets a consistent tier/needs_check/health_3c without
        // having to remember to call each resolver itself.
        static::saving(fn (BrandCycle $cycle) => $cycle->resolveDerivedFields());

        // health_3c of the NEXT 2 cycles (same account + platform) may include
        // this one in their own 3-cycle window, so they go stale whenever this
        // cycle's Health changes, it moves (new account/platform/start date —
        // both its old and new neighbours are affected), or it's deleted.
        // Runs after the write, and neighbours are saved quietly so the
        // cascade doesn't recurse.
        static::saved(function (BrandCycle $cycle) {
            $resolver = new Health3cResolver();
            static::refreshHealth3cAfter($cycle->account_id, $cycle->platform, $cycle->cycle_start_date, $resolver);

            if (! $cycle->wasRecentlyCreated && $cycle->wasChanged(['account_id', 'platform', 'cycle_start_date'])) {
                static::refreshHealth3cAfter(
                    $cycle->getOriginal('account_id'),
                    $cycle->getOriginal('platform'),
                    $cycle->getOriginal('cycle_start_date'),
                    $resolver,
                    exceptId: $cycle->id,
                );
            }
        });

        static::deleted(fn (BrandCycle $cycle) => static::refreshHealth3cAfter(
            $cycle->account_id, $cycle->platform, $cycle->cycle_start_date, new Health3cResolver(),
        ));
    }

    /**
     * Sets tier, needs_check and health_3c from the current Settings. Order
     * matters: tier must be set before Health3cResolver runs, since it calls
     * MetricCalculator (tier-aware) on this cycle and its prior ones.
     *
     * @param  Collection<int, BrandLabelBucket>|null  $tierBuckets
     * @param  Collection<int, BrandCycle>|null  $priorCycles
     */
    public function resolveDerivedFields(
        ?Collection $tierBuckets = null,
        ?Health3cResolver $health3c = null,
        ?Collection $priorCycles = null,
    ): void {
        $this->tier = (new AccountTierResolver())->resolve((float) $this->start_follower, $tierBuckets);

        $this->needs_check = (float) $this->start_follower === 0.0
            || abs($this->growthRateForNeedsCheck()) > 100.0;

        [$health, $this->health_3c_label, $this->health_3c_needs_check] = ($health3c ?? new Health3cResolver())->resolve($this, $priorCycles);

        // Same string form the decimal(…,2) column reads back as, so an unchanged value isn't seen as dirty.
        $this->health_3c = number_format($health, 2, '.', '');
    }

    /**
     * Re-resolves every cycle's tier/needs_check/health_3c against the
     * current Settings in one pass: settings load once, each account +
     * platform is walked oldest-first so a cycle's window uses its priors'
     * fresh tiers, and only cycles whose values changed are written.
     *
     * @return int Number of cycles whose stored values changed.
     */
    public static function recalculateAll(): int
    {
        $tierBuckets = BrandLabelBucket::where('metric', BrandLabelBucket::METRIC_ACCOUNT_TIER)->get();
        $health3c = new Health3cResolver();
        $changed = 0;

        $groups = static::orderBy('account_id')->orderBy('platform')->orderBy('cycle_start_date')->orderBy('id')->get()
            ->groupBy(fn (BrandCycle $cycle) => $cycle->account_id.'|'.$cycle->platform);

        DB::transaction(function () use ($groups, $tierBuckets, $health3c, &$changed) {
            foreach ($groups as $cycles) {
                $cycles = $cycles->values();

                foreach ($cycles as $i => $cycle) {
                    // Same window the resolver would query: the 2 latest cycles starting strictly earlier.
                    $prior = $cycles->take($i)
                        ->filter(fn (BrandCycle $p) => $p->cycle_start_date->lt($cycle->cycle_start_date))
                        ->slice(-2)
                        ->values();

                    $cycle->resolveDerivedFields($tierBuckets, $health3c, $prior);

                    if ($cycle->isDirty()) {
                        $cycle->saveQuietly();
                        $changed++;
                    }
                }
            }
        });

        return $changed;
    }

    private static function refreshHealth3cAfter(?int $accountId, ?string $platform, $afterDate, Health3cResolver $resolver, ?int $exceptId = null): void
    {
        if ($accountId === null || $afterDate === null) {
            return;
        }

        static::where('account_id', $accountId)
            ->where('platform', $platform)
            ->where('cycle_start_date', '>', $afterDate)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->orderBy('cycle_start_date')
            ->limit(2)
            ->get()
            ->each(function (BrandCycle $later) use ($resolver) {
                [$later->health_3c, $later->health_3c_label, $later->health_3c_needs_check] = $resolver->resolve($later);
                $later->saveQuietly();
            });
    }

    /**
     * Growth rate computed independently of MetricCalculator — needs_check is
     * a data-quality flag resolved at save time, before scores exist, so it
     * can't depend on the scoring pipeline itself.
     */
    private function growthRateForNeedsCheck(): float
    {
        $start = (float) $this->start_follower;

        if ($start === 0.0) {
            return 0.0;
        }

        return (((float) $this->end_follower - $start) / $start) * 100;
    }

    protected $fillable = [
        'account_id',
        'platform',
        'tier',
        'needs_check',
        'project_manager_id',
        'cycle_start_date',
        'cycle_end_date',
        'start_follower',
        'end_follower',
        'reach',
        'views',
        'engagement',
        'story_performance',
        'ads_currency',
        'reach_views_ads_used',
        'reach_views_ads_spend',
        'engagement_ads_used',
        'engagement_ads_spend',
    ];

    protected $casts = [
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'ai_summary_generated_at' => 'datetime',
        'reach_views_ads_used' => 'boolean',
        'engagement_ads_used' => 'boolean',
        'needs_check' => 'boolean',
        'health_3c_needs_check' => 'boolean',
        'formula_changed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BrandAccount::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function performances(): HasMany
    {
        return $this->hasMany(BrandPerformance::class, 'cycle_id');
    }
}
