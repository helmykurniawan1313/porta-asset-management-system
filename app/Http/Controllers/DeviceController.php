<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // Uses standard PHP GD extension

class DeviceController extends Controller
{
    /**
     * Display the inventory masterlist based on category.
     */
    public function index($category = 'Dashboard')
    {
        $query = Device::query();

        if ($category !== 'Dashboard') {
            $query->where('category', $category);
        }

        // Fetch items with assigned employees
        $items = $query->with('employee')->latest()->get();
        $employees = Employee::orderBy('name')->get();

        return view('inventory.index', compact('items', 'category', 'employees'));
    }

    /**
     * Display the detailed Asset Profile.
     */
    public function show($id)
    {
        $device = Device::with([
            'employee.department', 
            'components', 
            'parentDevice',
            'serviceHistories', 
            'activityLogs', 
            'healthRecords'
        ])->findOrFail($id);

        $employees = Employee::orderBy('name')->get();
        
        // Fetch unassigned components that can be attached to this device
        $stockItems = Device::where('device_group', 'Peripheral')
                            ->whereNull('parent_id')
                            ->whereNull('employee_id')
                            ->orderBy('category')
                            ->get();

        return view('inventory.show', compact('device', 'employees', 'stockItems'));
    }

    /**
     * Store a new asset with automatic WEBP compression.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category'       => 'nullable|string',
            'name'           => 'required|string',
            'manufacturer'   => 'nullable|string',
            'device_password' => 'nullable|string',
            'model'          => 'nullable|string',
            'purchase_date'  => 'nullable|date',
            'employee_id'    => 'nullable|exists:employees,id',
            'ram'            => 'nullable|string',
            'rom'            => 'nullable|string',
            'size'           => 'nullable|string',
            'component_type' => 'nullable|string',
            'current_health' => 'nullable|integer|min:0|max:100',
            'price'          => 'nullable|numeric|min:0',
            'images'         => 'nullable|array|max:3',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // OPTIMIZED: Handle Image Uploads with WEBP Conversion (Intervention v3)
        if ($request->hasFile('images')) {
            // Initialize the Image Manager with the GD driver
            $manager = new ImageManager(new Driver());

            foreach ($request->file('images') as $index => $file) {
                // 1. Create a unique filename with .webp extension
                $filename = Str::random(40) . '.webp';

                // 2. Read the image and encode to WEBP (80% quality)
                $image = $manager->read($file);
                $encodedImage = $image->toWebp(80);

                // 3. Save the binary string to public storage
                Storage::disk('public')->put('assets/' . $filename, $encodedImage->toString());

                // 4. Store the path in the database data array
                $data['image' . ($index + 1)] = 'assets/' . $filename;
            }
        }

        // Determine if it is a main device or a peripheral component
        $mainDevices = ['PC', 'Laptop', 'Phone'];
        $data['device_group'] = in_array($request->category ?? '', $mainDevices) ? 'Device' : 'Peripheral';

        unset($data['images']); // Remove array from data before insertion
        
        $device = Device::create($data);

        // Log initial creation
        $device->activityLogs()->create([
            'action'      => 'Asset Registered',
            'description' => 'Asset was added to the inventory.',
            'created_at'  => now(),
        ]);

        return back()->with('success', ($request->category ?? 'Asset') . ' has been registered successfully.');
    }

    /**
     * Update an existing asset with WEBP compression.
     */
    public function update(Request $request, Device $device)
    {
        $data = $request->validate([
            'name'           => 'required|string',
            'manufacturer'   => 'nullable|string',
            'device_password' => 'nullable|string',
            'model'          => 'nullable|string',
            'purchase_date'  => 'nullable|date',
            'employee_id'    => 'nullable|exists:employees,id',
            'ram'            => 'nullable|string',
            'rom'            => 'nullable|string',
            'size'           => 'nullable|string',
            'component_type' => 'nullable|string',
            'current_health' => 'nullable|integer|min:0|max:100',
            'price'          => 'nullable|numeric|min:0',
            'parent_id'      => 'nullable|exists:devices,id',
            'images'         => 'nullable|array|max:3',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_images'  => 'nullable|array', // Allow removal tracking
        ]);

        // 1. Handle Deletions First
        if ($request->has('remove_images')) {
            foreach ($request->remove_images as $fieldToRemove) {
                // Ensure they only delete valid image columns
                if (in_array($fieldToRemove, ['image1', 'image2', 'image3']) && $device->$fieldToRemove) {
                    
                    // Delete the physical file from storage
                    Storage::disk('public')->delete($device->$fieldToRemove);
                    
                    // Set the database column to null
                    $data[$fieldToRemove] = null; 
                    $device->$fieldToRemove = null; 
                }
            }
        }

        // 2. Handle Image Uploads with WEBP Conversion (Intervention v3)
        if ($request->hasFile('images')) {
            // Find the next available empty slots so we don't overwrite kept images
            $availableSlots = [];
            if (!$device->image1 && !isset($data['image1'])) $availableSlots[] = 'image1';
            if (!$device->image2 && !isset($data['image2'])) $availableSlots[] = 'image2';
            if (!$device->image3 && !isset($data['image3'])) $availableSlots[] = 'image3';

            $manager = new ImageManager(new Driver());

            foreach ($request->file('images') as $index => $file) {
                // Only process if we have an empty slot available
                if (isset($availableSlots[$index])) {
                    $filename = Str::random(40) . '.webp';
                    
                    $image = $manager->read($file);
                    $encodedImage = $image->toWebp(80);
                    
                    Storage::disk('public')->put('assets/' . $filename, $encodedImage->toString());
                    
                    // Assign to the specific empty slot (e.g., image2)
                    $data[$availableSlots[$index]] = 'assets/' . $filename;
                }
            }
        }

        // Track ownership changes for logs
        if ($request->filled('employee_id') && $device->employee_id != $request->employee_id) {
            $newOwner = Employee::find($request->employee_id);
            $device->activityLogs()->create([
                'action'      => 'Reassigned',
                'description' => 'Assigned to ' . $newOwner->name,
            ]);
        } elseif (array_key_exists('employee_id', $data) && empty($data['employee_id']) && $device->employee_id) {
            $device->activityLogs()->create([
                'action'      => 'Returned to Stock',
                'description' => 'Asset ownership removed.',
            ]);
        }

        unset($data['images']);
        unset($data['remove_images']);
        $device->update($data);

        return back()->with('success', 'Asset updated successfully.');
    }

    /**
     * Delete an asset.
     */
    public function destroy(Device $device)
    {
        $device->delete();
        return back()->with('success', 'Asset removed from inventory.');
    }

    /**
     * Log a Maintenance or Service Record.
     */
    public function service(Request $request, Device $device)
    {
        $request->validate([
            'log_date'    => 'required|date',
            'cost'        => 'nullable|numeric|min:0',
            'description' => 'required|string',
        ]);

        $device->serviceHistories()->create([
            'service_date' => $request->log_date ?? now(),
            'description'  => $request->description,
            'cost'         => $request->cost ?? 0,
        ]);

        $device->activityLogs()->create([
            'action'      => 'Service & Maintenance',
            'description' => $request->description,
            'created_at'  => $request->log_date ?? now(), 
        ]);

        return back()->with('success', 'Service history securely recorded.');
    }

    /**
     * Log a Health/Diagnostic Record.
     */
    public function health(Request $request, Device $device)
    {
        $request->validate([
            'recorded_date'     => 'required|date',
            'health_percentage' => 'required|integer|min:0|max:100',
        ]);

        // Save the health record historical log
        $device->healthRecords()->create([
            'recorded_date'     => $request->recorded_date,
            'health_percentage' => $request->health_percentage,
        ]);

        // Update the main device's current health so the UI progress bar updates
        $device->update([
            'current_health' => $request->health_percentage
        ]);

        $device->activityLogs()->create([
            'action'      => 'Health Diagnostic',
            'description' => 'Health logged at ' . $request->health_percentage . '%',
            'created_at'  => $request->recorded_date,
        ]);

        return back()->with('success', 'Health diagnostic recorded.');
    }

    /**
     * Attach a Component (Child) to a Device (Parent).
     */
    public function attach(Request $request)
    {
        $request->validate([
            'parent_id'   => 'required|exists:devices,id',
            'child_id'    => 'required|exists:devices,id',
            'action_date' => 'required|date',
        ]);

        $parent = Device::findOrFail($request->parent_id);
        $child = Device::findOrFail($request->child_id);

        $child->update([
            'parent_id'   => $parent->id,
            'employee_id' => null, // Attached components take on the parent's environment
        ]);

        $parent->activityLogs()->create([
            'action'      => 'Hardware Upgrade',
            'description' => $child->name . ' (' . $child->category . ') was installed.',
            'created_at'  => $request->action_date,
        ]);

        return back()->with('success', 'Component successfully attached to system.');
    }

    /**
     * Detach a Component from its Parent Device.
     */
    public function detach(Request $request, $id)
    {
        $request->validate([
            'action_date' => 'required|date',
        ]);

        $child = Device::findOrFail($id);
        $parent = $child->parentDevice;

        if ($parent) {
            $parent->activityLogs()->create([
                'action'      => 'Hardware Removed',
                'description' => $child->name . ' (' . $child->category . ') was detached.',
                'created_at'  => $request->action_date,
            ]);
        }

        $child->update([
            'parent_id' => null,
        ]);

        return back()->with('success', 'Component detached and returned to stock.');
    }
}