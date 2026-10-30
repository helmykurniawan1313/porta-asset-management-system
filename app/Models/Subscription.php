<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public const PERIODS = ['Monthly', 'Yearly', 'One-time', 'Free'];

    protected $fillable = [
        'account_id', 'app', 'status', 'password', 'login_with_google', 'shared_with', 'division',
        'plan', 'period', 'bill', 'currency', 'card_info', 'notes', 'next_payment_date',
    ];

    protected $casts = [
        'next_payment_date' => 'date',
        'login_with_google' => 'boolean',
    ];

    // Auto-calculates "Days Before Next Pay" dynamically; null when there is no renewal.
    public function getDaysRemainingAttribute()
    {
        if (!$this->next_payment_date) return null;
        return (int) now()->startOfDay()->diffInDays($this->next_payment_date->startOfDay(), false);
    }

    // "Adobe · PS & LR" — the app is what people recognise; the plan narrows it down.
    public function getLabelAttribute(): string
    {
        // A plan that already names the app ("Google Workspace Business Standard") stands on its own.
        if ($this->app && $this->plan && stripos($this->plan, $this->app) === 0) {
            return $this->plan;
        }

        return collect([$this->app, $this->plan])->filter()->unique()->implode(' · ') ?: 'Untitled license';
    }

    // "Renews 24 Nov 2026", "One-time purchase", "Free".
    public function getRenewalLabelAttribute(): string
    {
        return match (true) {
            $this->status === 'Inactive' => $this->next_payment_date ? 'Ended '.$this->next_payment_date->format('d M Y') : 'Inactive',
            $this->period === 'One-time' => 'One-time purchase',
            $this->period === 'Free' => 'Free plan',
            (bool) $this->next_payment_date => 'Renews '.$this->next_payment_date->format('d M Y'),
            default => 'No renewal date',
        };
    }

    // The renewal date after one more paid period. Month-end dates stay in range (31 Jan -> 28 Feb).
    public function nextRenewalDate(): ?\Illuminate\Support\Carbon
    {
        if (! $this->isRecurring()) return null;

        $from = ($this->next_payment_date ?? now())->copy();
        return $this->period === 'Yearly' ? $from->addYearNoOverflow() : $from->addMonthNoOverflow();
    }

    public function isRecurring(): bool
    {
        return in_array($this->period, ['Monthly', 'Yearly'], true);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function allocations() {
        return $this->hasMany(SubscriptionAllocation::class);
    }
}
