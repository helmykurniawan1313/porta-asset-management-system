<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
{
    // Use updateOrCreate to avoid the "Duplicate entry" error
    $laptop = \App\Models\Device::updateOrCreate(
        ['serial_number' => 'DELL-123'], // The unique identifier to check
        [
            'type' => 'main',
            'category' => 'laptop',
            'manufacturer' => 'Dell',
            'model' => 'XPS 15',
        ]
    );

    $ssd = \App\Models\Device::updateOrCreate(
        ['serial_number' => 'SAM-SSD-001'],
        [
            'type' => 'secondary',
            'category' => 'ssd',
            'manufacturer' => 'Samsung',
            'model' => '980 Pro',
            'parent_id' => $laptop->id,
        ]
    );

    $ssd->healthRecords()->create([
        'health_percentage' => rand(80, 100), // Random health for testing
        'notes' => 'Health check recap'
    ]);
}
}
