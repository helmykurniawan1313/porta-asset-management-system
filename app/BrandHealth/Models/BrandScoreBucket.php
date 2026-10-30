<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;

class BrandScoreBucket extends Model
{
    protected $table = 'brand_score_buckets';

    public const METRIC_GROWTH = 'growth';

    public const METRIC_REACH = 'reach';

    public const METRIC_VIEW = 'view';

    public const METRIC_ER_REACH = 'er_reach';

    public const METRIC_ER_FOLLOWER = 'er_follower';

    public const METRICS = [
        self::METRIC_GROWTH,
        self::METRIC_REACH,
        self::METRIC_VIEW,
        self::METRIC_ER_REACH,
        self::METRIC_ER_FOLLOWER,
    ];

    protected $fillable = [
        'metric',
        'min_rate',
        'score',
    ];

    protected $casts = [
        'min_rate' => 'decimal:2',
        'score' => 'decimal:2',
    ];
}
