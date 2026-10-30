<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandInstagramDailySnapshot extends Model
{
    protected $table = 'brand_instagram_daily_snapshots';

    protected $fillable = [
        'account_id',
        'captured_date',
        'followers_count',
        'media_count',
        'reach',
        'views',
        'accounts_engaged',
        'total_interactions',
        'captured_at',
    ];

    protected $casts = [
        'captured_date' => 'date',
        'captured_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BrandAccount::class, 'account_id');
    }
}
