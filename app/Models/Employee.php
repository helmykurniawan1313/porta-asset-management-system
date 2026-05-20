<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $guarded = [];

    // Link to Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Link to Devices (Hardware)
    public function devices()
    {
        return $this->hasMany(Device::class);
    }
    public function softwareAllocations() {
    return $this->morphMany(SubscriptionAllocation::class, 'assignable');
}
}
