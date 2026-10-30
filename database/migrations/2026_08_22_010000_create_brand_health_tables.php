<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ig_business_id')->nullable()->unique();
            $table->string('ig_username')->nullable();
            $table->text('ig_access_token')->nullable();
            $table->timestamp('ig_token_expires_at')->nullable();
            $table->timestamp('ig_connected_at')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamp('ai_summary_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('brand_accounts')->cascadeOnDelete();
            $table->string('platform')->default('instagram');
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('cycle_start_date');
            $table->date('cycle_end_date');
            $table->unsignedBigInteger('start_follower');
            $table->unsignedBigInteger('end_follower');
            $table->unsignedBigInteger('reach');
            $table->unsignedBigInteger('views');
            $table->unsignedBigInteger('engagement');
            $table->unsignedBigInteger('story_performance')->nullable();
            $table->string('ads_currency', 3)->nullable();
            $table->boolean('reach_views_ads_used')->default(false);
            $table->decimal('reach_views_ads_spend', 15, 2)->nullable();
            $table->boolean('engagement_ads_used')->default(false);
            $table->decimal('engagement_ads_spend', 15, 2)->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamp('ai_summary_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_score_buckets', function (Blueprint $table) {
            $table->id();
            $table->string('metric');
            $table->decimal('min_rate', 8, 2)->nullable();
            $table->decimal('score', 5, 2);
            $table->timestamps();

            $table->index(['metric', 'min_rate']);
        });

        Schema::create('brand_label_buckets', function (Blueprint $table) {
            $table->id();
            $table->string('metric');
            $table->decimal('min_score', 12, 2)->nullable();
            $table->string('label');
            $table->timestamps();

            $table->index(['metric', 'min_score']);
        });

        Schema::create('brand_formula_weights', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate');
            $table->string('component');
            $table->decimal('weight', 6, 4);
            $table->timestamps();

            $table->unique(['aggregate', 'component']);
        });

        Schema::create('brand_filter_summaries', function (Blueprint $table) {
            $table->id();
            $table->json('filters')->nullable();
            $table->string('provider');
            $table->text('custom_prompt')->nullable();
            $table->longText('summary');
            $table->unsignedInteger('cycle_count');
            $table->timestamps();
        });

        Schema::create('brand_instagram_daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('brand_accounts')->cascadeOnDelete();
            $table->date('captured_date');
            $table->unsignedBigInteger('followers_count')->nullable();
            $table->unsignedBigInteger('media_count')->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('views')->nullable();
            $table->unsignedBigInteger('accounts_engaged')->nullable();
            $table->unsignedBigInteger('total_interactions')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->unique(['account_id', 'captured_date']);
        });

        Schema::create('brand_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('brand_accounts')->cascadeOnDelete();
            $table->string('platform')->nullable();
            $table->date('post_date');
            $table->date('preview_date')->nullable();
            $table->boolean('ads')->nullable();
            $table->json('notes')->nullable();
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('conceptor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('followers')->nullable();
            $table->date('followers_captured_date')->nullable();
            $table->unsignedInteger('total_views_h7')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('ig_media_id')->nullable();
            $table->string('ig_media_product_type')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamp('ai_summary_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_performance_video_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_id')->constrained('brand_performances')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('platform')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->text('embed_html')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('brand_performance_ig_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_id')->constrained('brand_performances')->cascadeOnDelete();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('likes')->nullable();
            $table->unsignedBigInteger('comments')->nullable();
            $table->unsignedBigInteger('shares')->nullable();
            $table->unsignedBigInteger('saved')->nullable();
            $table->unsignedBigInteger('total_interactions')->nullable();
            $table->unsignedBigInteger('views')->nullable();
            $table->unsignedBigInteger('ig_reels_avg_watch_time')->nullable();
            $table->unsignedBigInteger('ig_reels_video_view_total_time')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['performance_id', 'fetched_at']);
        });

        Schema::create('brand_legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content_html');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_performance_ig_snapshots');
        Schema::dropIfExists('brand_performance_video_links');
        Schema::dropIfExists('brand_performances');
        Schema::dropIfExists('brand_instagram_daily_snapshots');
        Schema::dropIfExists('brand_filter_summaries');
        Schema::dropIfExists('brand_formula_weights');
        Schema::dropIfExists('brand_label_buckets');
        Schema::dropIfExists('brand_score_buckets');
        Schema::dropIfExists('brand_cycles');
        Schema::dropIfExists('brand_accounts');
        Schema::dropIfExists('brand_legal_pages');
    }
};
