<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandWeeklyReport extends Model
{
    protected $table = 'brand_weekly_reports';

    protected $fillable = [
        'account_id',
        'platform',
        'week_start_date',
        'week_end_date',
        'follower_count',
        'reach',
        'views',
        'profile_visits',
        'link_clicks',
        'engagement',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BrandAccount::class);
    }
}
