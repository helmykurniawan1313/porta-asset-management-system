<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per billing cycle of a contract, e.g. Contract 2 / Cycle 3.
     * cycle_number is sequential per contract, not derived from calendar
     * months, so a cycle that overruns its planned window doesn't shift
     * the numbering of the ones after it.
     */
    public function up(): void
    {
        Schema::create('contract_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cycle_number');
            $table->date('planned_start');
            $table->date('planned_end');
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->enum('status', ['on_track', 'behind_schedule', 'completed'])->default('on_track');
            $table->text('notes')->nullable();
            // Snapshot of who was the client's account manager when this
            // cycle was created, so reassigning the client later doesn't
            // rewrite history for cycles already in progress or closed.
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contract_id', 'cycle_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_cycles');
    }
};
