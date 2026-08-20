<?php

namespace App\PerformanceReview\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_assignment_id',
        'question_id',
        'answer_rating',
        'answer_text',
    ];

    public function reviewAssignment(): BelongsTo
    {
        return $this->belongsTo(ReviewAssignment::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
