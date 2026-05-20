<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthRecord extends Model
{
protected $fillable = [
        'device_id',
        'recorded_date',
        'health_percentage',
    ];

    // Optional but highly recommended: tell Laravel to treat recorded_date as a Carbon date object!
    protected $casts = [
        'recorded_date' => 'date', 
    ];
    }
