<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;

class BrandFormulaWeight extends Model
{
    protected $table = 'brand_formula_weights';

    public const AGGREGATE_VISIBILITY = 'visibility';

    public const AGGREGATE_ENGAGEMENT = 'engagement';

    public const AGGREGATE_HEALTH = 'health';

    // TikTok's Visibility/Engagement use different components (profile_score
    // instead of reach_score; er_view_score instead of er_reach_score +
    // er_follower_score — see MetricCalculator), so they're their own
    // Settings-adjustable aggregates rather than forced to share one weight
    // set with Instagram's differently-shaped formula. Health stays shared —
    // both platforms feed it the same 3 components (growth/visibility/engagement score).
    public const AGGREGATE_VISIBILITY_TIKTOK = 'visibility_tiktok';

    public const AGGREGATE_ENGAGEMENT_TIKTOK = 'engagement_tiktok';

    public const AGGREGATES = [
        self::AGGREGATE_VISIBILITY,
        self::AGGREGATE_ENGAGEMENT,
        self::AGGREGATE_HEALTH,
        self::AGGREGATE_VISIBILITY_TIKTOK,
        self::AGGREGATE_ENGAGEMENT_TIKTOK,
    ];

    public const COMPONENTS = [
        self::AGGREGATE_VISIBILITY => ['reach_score', 'view_score'],
        self::AGGREGATE_ENGAGEMENT => ['er_reach_score', 'er_follower_score'],
        self::AGGREGATE_HEALTH => ['growth_score', 'visibility_rate', 'engagement_score'],
        self::AGGREGATE_VISIBILITY_TIKTOK => ['profile_score', 'view_score'],
        self::AGGREGATE_ENGAGEMENT_TIKTOK => ['er_view_score'],
    ];

    protected $fillable = [
        'aggregate',
        'component',
        'weight',
    ];

    protected $casts = [
        'weight' => 'decimal:4',
    ];
}
