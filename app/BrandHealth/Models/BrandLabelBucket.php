<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;

class BrandLabelBucket extends Model
{
    protected $table = 'brand_label_buckets';

    public const METRIC_GROWTH = 'growth';

    public const METRIC_VISIBILITY = 'visibility';

    public const METRIC_ENGAGEMENT = 'engagement';

    public const METRIC_HEALTH = 'health';

    public const METRIC_VIEWS = 'views';

    public const METRIC_FOLLOWERS = 'followers';

    public const METRICS = [
        self::METRIC_GROWTH,
        self::METRIC_VISIBILITY,
        self::METRIC_ENGAGEMENT,
        self::METRIC_HEALTH,
        self::METRIC_VIEWS,
        self::METRIC_FOLLOWERS,
    ];

    protected $fillable = [
        'metric',
        'min_score',
        'label',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
    ];
}
