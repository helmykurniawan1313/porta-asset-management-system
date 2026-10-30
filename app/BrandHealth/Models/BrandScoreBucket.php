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

    // TikTok-only metrics — TikTok's "reach" field is actually profile views
    // (see MetricCalculator), so it gets its own rate/metric rather than
    // reusing METRIC_REACH/METRIC_ER_REACH, which would mix two differently-
    // defined formulas under the same metric name in Settings.
    public const METRIC_PROFILE = 'profile';

    public const METRIC_ER_VIEW = 'er_view';

    public const METRICS = [
        self::METRIC_GROWTH,
        self::METRIC_REACH,
        self::METRIC_VIEW,
        self::METRIC_ER_REACH,
        self::METRIC_ER_FOLLOWER,
        self::METRIC_PROFILE,
        self::METRIC_ER_VIEW,
    ];

    protected $fillable = [
        'metric',
        'platform',
        'tier',
        'min_rate',
        'score',
        'color',
        'custom_color_bg',
        'custom_color_ink',
    ];

    protected $casts = [
        'min_rate' => 'decimal:2',
        'score' => 'decimal:2',
    ];
}
