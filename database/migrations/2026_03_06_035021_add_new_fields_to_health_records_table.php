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
    Schema::table('health_records', function (Blueprint $table) {
        
        // Only add recorded_date if it is genuinely missing
        if (!Schema::hasColumn('health_records', 'recorded_date')) {
            $table->date('recorded_date')->nullable()->after('device_id');
        }

        // Only add health_percentage if it is missing (we know it exists, but this prevents crashes!)
        if (!Schema::hasColumn('health_records', 'health_percentage')) {
            $table->integer('health_percentage')->nullable()->after('recorded_date');
        }
        
    });
}

public function down()
{
    Schema::table('health_records', function (Blueprint $table) {
        $table->dropColumn(['recorded_date', 'health_percentage']);
    });
}
};
