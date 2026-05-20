<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('devices', function (Blueprint $table) {
        $table->decimal('price', 15, 2)->nullable()->after('purchase_date');
        $table->string('image1')->nullable();
        $table->string('image2')->nullable();
        $table->string('image3')->nullable();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            //
        });
    }
};
