<?php

namespace App\BrandHealth\Models;


use App\Models\User;
use App\BrandHealth\Services\AccountTierResolver;
use App\BrandHealth\Services\Health3cResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrandCycle extends Model
{
    protected $table = 'brand_cycles';

    use HasFactory;

    protected static function booted(): void
    {
        // Resolved on every create/update rather than left to the caller, so
        // every path that saves a Cycle (controller, factory, future admin
        // tooling) gets a consistent tier/needs_check/health_3c without
        // having to remember to call each resolver itself. Order matters:
        // tier must be set before Health3cResolver runs, since it calls
        // MetricCalculator (tier-aware) on this cycle and its prior ones.
        static::saving(function (BrandCycle $cycle) {
            $cycle->tier = (new AccountTierResolver())->resolve((float) $cycle->start_follower);

            $cycle->needs_check = (float) $cycle->start_follower === 0.0
                || abs($cycle->growthRateForNeedsCheck()) > 100.0;

            [$cycle->health_3c, $cycle->health_3c_label, $cycle->health_3c_needs_check] = (new Health3cResolver())->resolve($cycle);
        });

        // health_3c of the NEXT 2 cycles (same account + platform) may include
        // this one in their own 3-cycle window, so their health_3c can go
        // stale the moment this cycle's own Health score changes. Re-saved
        // after this cycle is committed (not in `saving`, which runs before
        // that), via saveQuietly() to avoid recursively re-triggering this
        // same `saved` cascade on each of them.
        static::saved(function (BrandCycle $cycle) {
            BrandCycle::where('account_id', $cycle->account_id)
                ->where('platform', $cycle->platform)
                ->where('cycle_start_date', '>', $cycle->cycle_start_date)
                ->orderBy('cycle_start_date')
                ->limit(2)
                ->get()
                ->each(function (BrandCycle $later) {
                    [$later->health_3c, $later->health_3c_label, $later->health_3c_needs_check] = (new Health3cResolver())->resolve($later);
                    $later->saveQuietly();
                });
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
