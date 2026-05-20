<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('device_password')->nullable()->after('name');
        });
    }
    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('device_password');
        });
    }
};