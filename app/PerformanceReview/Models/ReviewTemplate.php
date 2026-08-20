<?php

namespace App\PerformanceReview\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_cycle_id',
        'name',
    ];

    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(ReviewCycle::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
