<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Known faults on a device or component ("Keyboard huruf L rusak",
     * "Speaker pelan"). Kept as rows rather than a text field so an asset can
     * carry several at once and each can be resolved on its own.
     */
    public function up(): void
    {
        Schema::create('device_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->date('reported_at');
            $table->date('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_issues');
    }
};
