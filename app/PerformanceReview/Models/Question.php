<?php

namespace App\PerformanceReview\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_template_id',
        'text',
        'type',
        'options',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function reviewTemplate(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplate::class);
    }

    public function reviewResponses(): HasMany
    {
        return $this->hasMany(ReviewResponse::class);
    }
}
