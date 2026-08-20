<?php

namespace App\PerformanceReview\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_cycle_id',
        'reviewer_id',
        'reviewee_id',
        'review_type',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(ReviewCycle::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function reviewResponses(): HasMany
    {
        return $this->hasMany(ReviewResponse::class);
    }
}
