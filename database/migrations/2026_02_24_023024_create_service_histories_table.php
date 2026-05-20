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
     Schema::create('service_histories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('device_id')->nullable()->constrained()->cascadeOnDelete();
    $table->date('service_date')->nullable();
    $table->text('description')->nullable();
    $table->decimal('cost', 10, 2)->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_histories');
    }
};
