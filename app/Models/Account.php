<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['email', 'purpose'];

    public function subscriptions() {
        return $this->hasMany(Subscription::class);
    }

    // Employees who use this login.
    public function users() {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
