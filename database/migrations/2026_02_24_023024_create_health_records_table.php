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
      Schema::create('health_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('device_id')->nullable()->constrained()->cascadeOnDelete();
    $table->integer('health_percentage')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps(); 
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
