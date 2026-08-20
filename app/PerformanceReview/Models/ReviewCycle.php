<?php

namespace App\PerformanceReview\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function reviewTemplates(): HasMany
    {
        return $this->hasMany(ReviewTemplate::class);
    }

    public function reviewAssignments(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class);
    }
}
