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
    Schema::create('subscriptions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('account_id')->constrained()->cascadeOnDelete();
        $table->string('plan'); // e.g., "Adobe CC All Apps"
        $table->string('period'); // e.g., "Monthly", "Yearly"
        $table->decimal('bill', 10, 2); 
        $table->string('currency', 3)->default('USD');
        $table->date('next_payment_date'); 
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
