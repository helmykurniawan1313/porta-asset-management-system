<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_cycle_id')->constrained('review_cycles')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->enum('review_type', ['self', 'peer', 'upward', 'downward']);
            $table->enum('status', ['pending', 'in_progress', 'submitted'])->default('pending');
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['review_cycle_id', 'reviewer_id', 'reviewee_id', 'review_type'],
                'review_assignments_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_assignments');
    }
};
