<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phone-specific details (OS, chipset, color, accessories, condition,
     * usability tier) that previously had nowhere to live on a device.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('os')->nullable()->after('rom');
            $table->string('os_version')->nullable()->after('os');
            $table->string('processor')->nullable()->after('os_version');
            $table->string('color')->nullable()->after('processor');
            $table->string('accessories')->nullable()->after('color');
            $table->string('condition')->nullable()->after('accessories');
            $table->unsignedTinyInteger('usability_tier')->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['os', 'os_version', 'processor', 'color', 'accessories', 'condition', 'usability_tier']);
        });
    }
};
