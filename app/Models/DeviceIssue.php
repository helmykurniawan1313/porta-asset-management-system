<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeviceIssue extends Model
{
    protected $fillable = [
        'device_id',
        'description',
        'reported_at',
        'resolved_at',
    ];

    protected $casts = [
        'reported_at' => 'date',
        'resolved_at' => 'date',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
