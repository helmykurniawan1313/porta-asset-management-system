<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Network gear (routers, access points, switches) belongs to a place
     * rather than a person, and carries Wi-Fi / admin credentials.
     * `in_use` is explicit because a network device with no user_id can
     * still be live (e.g. the Lt. 2 access point).
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('location')->nullable()->after('usability_tier');
            $table->string('area')->nullable()->after('location');
            $table->string('ssid')->nullable()->after('area');
            $table->string('ssid_password')->nullable()->after('ssid');
            $table->string('admin_username')->nullable()->after('ssid_password');
            $table->string('ip_address', 45)->nullable()->after('admin_username');
            $table->boolean('in_use')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['location', 'area', 'ssid', 'ssid_password', 'admin_username', 'ip_address', 'in_use']);
        });
    }
};
