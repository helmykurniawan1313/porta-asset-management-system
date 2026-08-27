<?php

namespace App\ClientOps\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'cycle_number',
        'planned_start',
        'planned_end',
        'actual_start',
        'actual_end',
        'status',
        'notes',
        'account_manager_id',
    ];

    protected function casts(): array
    {
        return [
            'planned_start' => 'date',
            'planned_end' => 'date',
            'actual_start' => 'date',
            'actual_end' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * True when this cycle's planned window has passed but it hasn't been
     * marked completed — the situation that used to silently trigger a
     * premature invoice. Doesn't change stored status by itself; callers
     * (e.g. an index listing) can highlight this without a background job.
     */
    public function isOverrun(): bool
    {
        return $this->status !== 'completed' && $this->planned_end->isPast();
    }

    protected static function booted(): void
    {
        static::saved(function (ContractCycle $cycle) {
            if ($cycle->wasChanged('actual_start') && $cycle->actual_start) {
                $cycle->contract->markStartedIfNeeded();
            }
        });
    }
}
