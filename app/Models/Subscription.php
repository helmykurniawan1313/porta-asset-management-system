<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['account_id', 'plan', 'period', 'bill', 'currency', 'next_payment_date'];
    
    protected $casts = ['next_payment_date' => 'date'];

    // Auto-calculates "Days Before Next Pay" dynamically
    public function getDaysRemainingAttribute()
    {
        if (!$this->next_payment_date) return 0;
        return (int) now()->startOfDay()->diffInDays($this->next_payment_date->startOfDay(), false);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function allocations() {
        return $this->hasMany(SubscriptionAllocation::class);
    }
}
