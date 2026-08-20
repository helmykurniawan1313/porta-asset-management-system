<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    /**
     * The attributes that are mass assignable.
     * Including image columns for WEBP optimization support.
     */
    protected $fillable = [
        'name',
        'category',
        'device_group',
        'manufacturer',
        'device_password',
        'model',
        'purchase_date',
        'component_type',
        'size',
        'ram',
        'rom',
        'current_health',
        'user_id',
        'parent_id',
        'price',
        'image1',
        'image2',
        'image3',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'purchase_date' => 'date',
        'price' => 'decimal:2',
    ];

    /* ==========================================\
       RELATIONSHIPS
    ========================================== */
       
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parentDevice()
    {
        return $this->belongsTo(Device::class, 'parent_id');
    }

    public function components()
    {
        return $this->hasMany(Device::class, 'parent_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function serviceHistories() 
    {
        return $this->hasMany(ServiceHistory::class)->orderBy('service_date', 'desc');
    }

    public function softwareAllocations() 
    {
        return $this->morphMany(SubscriptionAllocation::class, 'assignable');
    }

    public function healthRecords()
    {
        return $this->hasMany(HealthRecord::class, 'device_id'); 
    }

    /* ==========================================\
       AUTOMATIC LOGGING & CASCADE TRIGGERS
    ========================================== */

    protected static function booted()
    {
        static::updated(function ($device) {
            
            // 1. DETECT TRANSFER (Employee Changed)
            if ($device->isDirty('user_id')) {
                $oldOwnerId = $device->getOriginal('user_id');
                $newOwnerId = $device->user_id;

                if (!is_null($newOwnerId)) {
                    $newOwner = User::find($newOwnerId);
                    ActivityLog::create([
                        'device_id'   => $device->id,
                        'action'      => 'Deployment',
                        'description' => "Asset assigned to {$newOwner->name}."
                    ]);
                } 
            }

            // 2. DETECT ATTACHMENT/DETACHMENT (Parent Changed)
            if ($device->isDirty('parent_id')) {
                $oldParentId = $device->getOriginal('parent_id');
                $newParentId = $device->parent_id;

                // Scenario A: Newly Attached
                if (is_null($oldParentId) && !is_null($newParentId)) {
                    $parent = Device::find($newParentId);
                    
                    // Log to the Component (e.g. the RAM)
                    ActivityLog::create([
                        'device_id'   => $device->id,
                        'action'      => 'Attachment',
                        'description' => "Installed inside {$parent->name}."
                    ]);

                    // Log to the Main Device (e.g. the PC)
                    ActivityLog::create([
                        'device_id'   => $parent->id,
                        'action'      => 'Component Added',
                        'description' => "Installed {$device->category}: {$device->name}."
                    ]);
                }
                
                // Scenario B: Detached
                elseif (!is_null($oldParentId) && is_null($newParentId)) {
                    $oldParent = Device::find($oldParentId);
                    
                    // Log to the Component
                    ActivityLog::create([
                        'device_id'   => $device->id,
                        'action'      => 'Detach',
                        'description' => "Removed from {$oldParent->name} and returned to STOCK."
                    ]);

                    // Log to the Main Device
                    if ($oldParent) {
                        ActivityLog::create([
                            'device_id'   => $oldParent->id,
                            'action'      => 'Component Detached',
                            'description' => "Removed {$device->category}: {$device->name}."
                        ]);
                    }
                }
            }
        });
    }
}