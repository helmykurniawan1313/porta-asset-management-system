<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionAllocation extends Model
{
    protected $fillable = ['subscription_id', 'assignable_type', 'assignable_id'];

    public function assignable() {
        return $this->morphTo();
    }
    
    public function subscription() {
        return $this->belongsTo(Subscription::class);
    }
}