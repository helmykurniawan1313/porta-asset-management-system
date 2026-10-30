<?php

namespace App\BrandHealth\Models;


use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrandPerformance extends Model
{
    protected $table = 'brand_performances';

    use HasFactory;

    protected static function booted(): void
    {
        // A post with no platform is an Instagram post. Storing that, rather than
        // null, keeps cycle assignment and every where('platform', …) filter in step.
        static::saving(function (BrandPerformance $performance) {
            $performance->platform ??= 'instagram';
        });
    }

    public const NOTE_OPTIONS = [
        'Weekend Off',
        'National Holiday Off',
        'Late Update',
        'Collab Post',
    ];

    protected $fillable = [
        'account_id',
        'platform',
        'cycle_id',
        'post_date',
        'preview_date',
        'ads',
        'notes',
        'project_manager_id',
        'conceptor_id',
        'editor_id',
        'followers',
        'followers_captured_date',
        'total_views_h7',
        'proof_path',
        'ig_media_id',
        'ig_media_product_type',
    ];

    protected $casts = [
        'post_date' => 'date',
        'preview_date' => 'date',
        'followers_captured_date' => 'date',
        'ads' => 'boolean',
        'notes' => 'array',
        'ai_summary_generated_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BrandAccount::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BrandCycle::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function conceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conceptor_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function videoLinks(): HasMany
    {
        return $this->hasMany(BrandPerformanceVideoLink::class, 'performance_id')->orderBy('order');
    }

    public function igSnapshots(): HasMany
    {
        return $this->hasMany(BrandPerformanceIgSnapshot::class, 'performance_id')->orderByDesc('fetched_at');
    }

    public function latestIgSnapshot(): HasMany
    {
        return $this->igSnapshots()->limit(1);
    }
}
