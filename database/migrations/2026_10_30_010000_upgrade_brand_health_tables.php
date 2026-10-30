<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the Brand Health tables up to Brand Matrix Experimental Plus's current
 * schema (account-size tiers, Health Rate v1 / Health 3C, bucket colours,
 * per-platform score buckets, cycle-linked performances, Instagram profile
 * visits / link clicks) and adds the tables for Settings, Content Insights
 * and Weekly Reports. PM / conceptor / editor still point at users, since
 * it-inventory's employees are its users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_cycles', function (Blueprint $table) {
            $table->string('tier')->nullable()->after('platform');
            $table->boolean('needs_check')->default(false)->after('tier');
            $table->decimal('health_rate_v1', 6, 2)->nullable()->after('needs_check');
            $table->string('health_label_v1')->nullable()->after('health_rate_v1');
            $table->timestamp('formula_changed_at')->nullable()->after('health_label_v1');
            $table->decimal('health_3c', 6, 2)->nullable()->after('formula_changed_at');
            $table->string('health_3c_label')->nullable()->after('health_3c');
            $table->boolean('health_3c_needs_check')->default(false)->after('health_3c_label');
            $table->index('cycle_start_date');
            $table->index(['account_id', 'platform']);
        });

        Schema::table('brand_score_buckets', function (Blueprint $table) {
            $table->string('platform')->nullable()->after('metric');
            $table->string('tier')->nullable()->after('platform');
            $table->string('color')->nullable()->after('score');
            $table->string('custom_color_bg')->nullable()->after('color');
            $table->string('custom_color_ink')->nullable()->after('custom_color_bg');
            $table->index(['platform', 'tier', 'metric', 'min_rate']);
        });

        Schema::table('brand_label_buckets', function (Blueprint $table) {
            $table->string('color')->nullable()->after('label');
            $table->string('custom_color_bg')->nullable()->after('color');
            $table->string('custom_color_ink')->nullable()->after('custom_color_bg');
        });

        Schema::table('brand_instagram_daily_snapshots', function (Blueprint $table) {
            // Follower / interaction deltas can be negative day-over-day.
            $table->bigInteger('followers_count')->nullable()->change();
            $table->bigInteger('total_interactions')->nullable()->change();
            $table->unsignedBigInteger('profile_visits')->nullable()->after('views');
            $table->unsignedBigInteger('link_clicks')->nullable()->after('profile_visits');
        });

        Schema::table('brand_performances', function (Blueprint $table) {
            $table->foreignId('cycle_id')->nullable()->after('platform')->constrained('brand_cycles')->nullOnDelete();
            $table->index('post_date');
            $table->index(['account_id', 'platform']);
        });

        Schema::table('brand_performance_video_links', function (Blueprint $table) {
            $table->text('thumbnail_url')->nullable()->change();
        });

        Schema::create('brand_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_content_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('brand_accounts')->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('brand_cycles')->cascadeOnDelete();
            $table->unsignedBigInteger('viewers_posts')->default(0);
            $table->unsignedBigInteger('viewers_reels')->default(0);
            $table->unsignedBigInteger('viewers_story')->default(0);
            $table->unsignedBigInteger('interactions_posts')->default(0);
            $table->unsignedBigInteger('interactions_reels')->default(0);
            $table->unsignedBigInteger('interactions_story')->default(0);
            $table->timestamps();

            $table->unique(['account_id', 'cycle_id']);
        });

        Schema::create('brand_weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('brand_accounts')->cascadeOnDelete();
            $table->string('platform');
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->unsignedBigInteger('follower_count')->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('views')->nullable();
            $table->unsignedBigInteger('profile_visits')->nullable();
            $table->unsignedBigInteger('link_clicks')->nullable();
            $table->bigInteger('engagement')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'platform', 'week_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_weekly_reports');
        Schema::dropIfExists('brand_content_insights');
        Schema::dropIfExists('brand_settings');

        Schema::table('brand_performances', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'platform']);
            $table->dropIndex(['post_date']);
            $table->dropConstrainedForeignId('cycle_id');
        });
        Schema::table('brand_instagram_daily_snapshots', function (Blueprint $table) {
            $table->dropColumn(['profile_visits', 'link_clicks']);
        });
        Schema::table('brand_label_buckets', function (Blueprint $table) {
            $table->dropColumn(['color', 'custom_color_bg', 'custom_color_ink']);
        });
        Schema::table('brand_score_buckets', function (Blueprint $table) {
            $table->dropIndex(['platform', 'tier', 'metric', 'min_rate']);
            $table->dropColumn(['platform', 'tier', 'color', 'custom_color_bg', 'custom_color_ink']);
        });
        Schema::table('brand_cycles', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'platform']);
            $table->dropIndex(['cycle_start_date']);
            $table->dropColumn(['tier', 'needs_check', 'health_rate_v1', 'health_label_v1', 'formula_changed_at',
                'health_3c', 'health_3c_label', 'health_3c_needs_check']);
        });
    }
};
