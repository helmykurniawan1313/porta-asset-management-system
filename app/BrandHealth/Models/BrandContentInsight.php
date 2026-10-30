<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per account + cycle, holding the Instagram Insights "Overview"
 * numbers the app can't pull automatically: Viewers and Interactions,
 * each broken down by content type (Posts / Reels / Story).
 *
 * Fully standalone — nothing else in the app reads or writes this table.
 */
class BrandContentInsight extends Model
{
    protected $table = 'brand_content_insights';

    protected $fillable = [
        'account_id',
        'cycle_id',
        'viewers_posts',
        'viewers_reels',
        'viewers_story',
        'interactions_posts',
        'interactions_reels',
        'interactions_story',
    ];

    protected $casts = [
        'viewers_posts' => 'integer',
        'viewers_reels' => 'integer',
        'viewers_story' => 'integer',
        'interactions_posts' => 'integer',
        'interactions_reels' => 'integer',
        'interactions_story' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BrandAccount::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BrandCycle::class);
    }

    public function getViewersTotalAttribute(): int
    {
        return $this->viewers_posts + $this->viewers_reels + $this->viewers_story;
    }

    public function getInteractionsTotalAttribute(): int
    {
        return $this->interactions_posts + $this->interactions_reels + $this->interactions_story;
    }
}
