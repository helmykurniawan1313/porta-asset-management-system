<?php

namespace App\BrandHealth\Console\Commands;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Services\InstagramOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshInstagramTokens extends Command
{
    protected $signature = 'brand-health:refresh-instagram-tokens {--days=10 : Refresh tokens expiring within this many days}';

    protected $description = 'Extend long-lived Instagram tokens that are close to their 60-day expiry';

    public function handle(InstagramOAuthService $oauth): int
    {
        // Instagram only refreshes tokens that are at least 24 hours old and not yet expired.
        $accounts = BrandAccount::query()
            ->whereNotNull('ig_access_token')
            ->where('ig_connected_at', '<=', now()->subDay())
            ->where(fn ($query) => $query
                ->whereNull('ig_token_expires_at')
                ->orWhereBetween('ig_token_expires_at', [now(), now()->addDays((int) $this->option('days'))]))
            ->get();

        $this->info("Refreshing Instagram tokens for {$accounts->count()} account(s)...");

        foreach ($accounts as $account) {
            try {
                $result = $oauth->refreshLongLivedToken($account->ig_access_token);

                $account->forceFill([
                    'ig_access_token' => $result['access_token'],
                    'ig_token_expires_at' => now()->addSeconds($result['expires_in_seconds']),
                ])->save();

                $this->line("✓ {$account->name}");
            } catch (\Throwable $e) {
                Log::error("Instagram token refresh failed for account #{$account->id} ({$account->name}): {$e->getMessage()}");
                $this->error("✗ {$account->name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
