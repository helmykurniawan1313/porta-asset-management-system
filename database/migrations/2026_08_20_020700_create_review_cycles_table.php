<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_cycles');
    }
};
