<?php

namespace App\BrandHealth\Console\Commands;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandInstagramDailySnapshot;
use App\BrandHealth\Services\InstagramInsightsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CaptureInstagramDailySnapshots extends Command
{
    protected $signature = 'brand-health:capture-daily-snapshots';

    protected $description = 'Capture today\'s Instagram profile and insight metrics for every connected account';

    public function handle(InstagramInsightsService $insights): int
    {
        $accounts = BrandAccount::query()
            ->whereNotNull('ig_business_id')
            ->whereNotNull('ig_access_token')
            ->get();

        $this->info("Capturing Instagram snapshots for {$accounts->count()} connected account(s)...");

        foreach ($accounts as $account) {
            try {
                $profile = $insights->profile($account);
                $daily = collect($insights->dailyInsights($account))
                    ->keyBy('name')
                    ->map(fn (array $metric) => $metric['total_value']['value'] ?? null);

                BrandInstagramDailySnapshot::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'captured_date' => today()->toDateString(),
                    ],
                    [
                        'followers_count' => $profile['followers_count'] ?? null,
                        'media_count' => $profile['media_count'] ?? null,
                        'reach' => $daily->get('reach'),
                        'views' => $daily->get('views'),
                        'accounts_engaged' => $daily->get('accounts_engaged'),
                        'total_interactions' => $daily->get('total_interactions'),
                        'captured_at' => now(),
                    ]
                );

                $this->line("✓ {$account->name}");
            } catch (\Throwable $e) {
                Log::error("Instagram daily snapshot failed for account #{$account->id} ({$account->name}): {$e->getMessage()}");
                $this->error("✗ {$account->name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
