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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('manufacturer')->nullable()->change();
            $table->string('model')->nullable()->change();
            $table->decimal('price', 15, 2)->nullable()->change();
            $table->string('ram')->nullable()->change();
            $table->string('rom')->nullable()->change();
            $table->string('size')->nullable()->change();
            $table->string('component_type')->nullable()->change();
            $table->integer('current_health')->nullable()->change();
            $table->date('purchase_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            // Note: Returning to 'false' assumes these were originally required.
        });
    }
};