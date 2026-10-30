<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandPerformanceVideoLink extends Model
{
    protected $table = 'brand_performance_video_links';

    protected $fillable = [
        'performance_id',
        'url',
        'platform',
        'thumbnail_url',
        'embed_html',
        'order',
    ];

    public function performance(): BelongsTo
    {
        return $this->belongsTo(BrandPerformance::class);
    }
}
