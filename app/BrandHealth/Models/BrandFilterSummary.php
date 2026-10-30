<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;

class BrandFilterSummary extends Model
{
    protected $table = 'brand_filter_summaries';

    protected $fillable = [
        'filters',
        'provider',
        'custom_prompt',
        'summary',
        'cycle_count',
    ];

    protected $casts = [
        'filters' => 'array',
    ];
}
