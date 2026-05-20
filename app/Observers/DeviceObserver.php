<?php
namespace App\Observers;

use App\Models\Device;
use App\Models\ActivityLog;

class DeviceObserver
{
    public function updated(Device $device): void
    {
        // 1. Log if the User Changes
        if ($device->isDirty('user_id')) {
            $oldUser = $device->getOriginal('user_id') ?? 'Stock';
            $newUser = $device->user_id ?? 'Stock';

            ActivityLog::create([
                'device_id' => $device->id,
                'action' => 'user_change',
                'description' => "User changed from [ID: $oldUser] to [ID: $newUser]"
            ]);

            // AUTOMATION: If it's a Main Device, all components follow the user
            if ($device->type === 'main') {
                foreach ($device->components as $component) {
                    $component->update(['user_id' => $device->user_id]);
                }
            }
        }

        // 2. Log if it's Attached or Detached
        if ($device->isDirty('parent_id')) {
            $newParent = $device->parent_id;
            
            if (is_null($newParent)) {
                // Detached: Automatically goes to Stock
                $device->updateQuietly(['user_id' => null]);
                
                ActivityLog::create([
                    'device_id' => $device->id,
                    'action' => 'detached',
                    'description' => "Component detached and returned to stock."
                ]);
            } else {
                // Attached: Automatically takes the Parent's user
                $parent = Device::find($newParent);
                $device->updateQuietly(['user_id' => $parent->user_id]);

                ActivityLog::create([
                    'device_id' => $device->id,
                    'action' => 'attached',
                    'description' => "Attached to " . $parent->manufacturer . " " . $parent->model
                ]);
            }
        }
    }
}