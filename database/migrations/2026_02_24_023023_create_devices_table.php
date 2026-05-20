<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('devices', function (Blueprint $table) {
        $table->id();
        
        // Grouping & Category (For your Sidebar)
        $table->string('device_group')->nullable(); // 'Device' or 'Peripheral'
        $table->string('category')->nullable(); // 'PC', 'Laptop', 'Phone', 'SSD', 'RAM', etc.

        // General Info (For all)
        $table->string('name')->nullable(); // Porta-PC-01
        $table->string('manufacturer')->nullable(); // MSI, Samsung
        $table->string('model')->nullable(); // PS42 8RC
        $table->date('purchase_date')->nullable(); // 1 Jan 2026

        // Specifics (For Peripherals & Components)
        $table->string('component_type')->nullable(); // NVME, DDR 4 1600Mhz
        $table->string('size')->nullable(); // 512 GB, 24 inch, 650 va

        // Specifics (For Phones)
        $table->string('ram')->nullable(); // 8 GB
        $table->string('rom')->nullable(); // 128 GB

        // Health Snapshot
        $table->integer('current_health')->nullable(); // 90, 100

        // Relationships
$table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
        $table->foreignId('parent_id')->nullable()->constrained('devices')->nullOnDelete(); // To attach SSD to PC
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
