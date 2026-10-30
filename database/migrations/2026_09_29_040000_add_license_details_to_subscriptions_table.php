<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The license sheet tracks more than a recurring bill: which app, whether
     * it is still active, the app login (the same email uses different
     * passwords per app), who actually shares it, and one-time / free plans
     * that have no amount or renewal date.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('app')->nullable()->after('account_id');
            $table->string('status')->default('Active')->after('app');
            $table->string('password')->nullable()->after('status');
            $table->boolean('login_with_google')->default(false)->after('password');
            $table->string('shared_with')->nullable()->after('login_with_google');
            $table->string('division')->nullable()->after('shared_with');
            $table->string('card_info', 20)->nullable()->after('currency');
            $table->text('notes')->nullable()->after('card_info');

            $table->string('plan')->nullable()->change();
            $table->decimal('bill', 12, 2)->nullable()->change();
            $table->string('currency', 3)->nullable()->default(null)->change();
            $table->date('next_payment_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['app', 'status', 'password', 'login_with_google', 'shared_with', 'division', 'card_info', 'notes']);
        });
    }
};
