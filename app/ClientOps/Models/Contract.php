<?php

namespace App\ClientOps\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'start_date',
        'total_cycles',
        'cycle_length_days',
        'cycle_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'cycle_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(ContractCycle::class)->orderBy('cycle_number');
    }

    /**
     * Generates all N cycles for this contract up front, each planned
     * back-to-back from the contract start date. Planned dates are just a
     * baseline — actual_start/actual_end and status track real progress,
     * which is what lets a cycle run long without messing up numbering.
     */
    public function generateCycles(): void
    {
        $accountManagerId = $this->client->currentAccountManager()?->id;
        $cursor = $this->start_date->copy();

        for ($n = 1; $n <= $this->total_cycles; $n++) {
            $plannedEnd = $cursor->copy()->addDays($this->cycle_length_days - 1);

            $this->cycles()->create([
                'cycle_number' => $n,
                'planned_start' => $cursor->copy(),
                'planned_end' => $plannedEnd,
                'account_manager_id' => $accountManagerId,
            ]);

            $cursor = $plannedEnd->copy()->addDay();
        }
    }

    /**
     * Flips out of "not_started" the moment any cycle actually begins.
     * Called from ContractCycle's model event as well as the controller, so
     * this can't drift out of sync no matter which code path (UI, tinker,
     * a future import) sets a cycle's actual_start.
     */
    public function markStartedIfNeeded(): void
    {
        if ($this->status === 'not_started' && $this->cycles()->whereNotNull('actual_start')->exists()) {
            $this->update(['status' => 'active']);
        }
    }
}
