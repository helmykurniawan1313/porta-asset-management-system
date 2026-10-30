<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;

class BrandFormulaWeight extends Model
{
    protected $table = 'brand_formula_weights';

    public const AGGREGATE_VISIBILITY = 'visibility';

    public const AGGREGATE_ENGAGEMENT = 'engagement';

    public const AGGREGATE_HEALTH = 'health';

    public const AGGREGATES = [
        self::AGGREGATE_VISIBILITY,
        self::AGGREGATE_ENGAGEMENT,
        self::AGGREGATE_HEALTH,
    ];

    public const COMPONENTS = [
        self::AGGREGATE_VISIBILITY => ['reach_score', 'view_score'],
        self::AGGREGATE_ENGAGEMENT => ['er_reach_score', 'er_follower_score'],
        self::AGGREGATE_HEALTH => ['growth_score', 'visibility_rate', 'engagement_score'],
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
