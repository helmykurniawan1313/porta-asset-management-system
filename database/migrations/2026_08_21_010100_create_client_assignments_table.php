<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * History of which account manager handled which client, over time.
     * Reassigning a client never deletes or overwrites a row — it closes
     * the current one (sets ended_at) and opens a new one. The "current"
     * account manager for a client is whichever row has ended_at = null.
     */
    public function up(): void
    {
        Schema::create('client_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_assignments');
    }
};
