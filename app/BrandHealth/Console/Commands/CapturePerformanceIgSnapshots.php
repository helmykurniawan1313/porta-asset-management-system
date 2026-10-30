<?php

namespace App\BrandHealth\Console\Commands;

use App\BrandHealth\Models\BrandPerformance;
use App\BrandHealth\Services\PerformanceIgSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CapturePerformanceIgSnapshots extends Command
{
    protected $signature = 'brand-health:capture-performance-snapshots';

    protected $description = 'Refresh Instagram media insights for every Performance record linked to an IG post';

    public function handle(PerformanceIgSnapshotService $snapshots): int
    {
        $performances = BrandPerformance::query()
            ->whereNotNull('ig_media_id')
            ->whereHas('account', function ($query) {
                $query->whereNotNull('ig_business_id')->whereNotNull('ig_access_token');
            })
            ->with('account')
            ->get();

        $this->info("Refreshing Instagram snapshots for {$performances->count()} linked performance record(s)...");

        foreach ($performances as $performance) {
            try {
                $snapshots->capture($performance);

                // Followers is a point-in-time fact for post_date, not something that
                // should keep changing — only (re)resolve it if it was never set, or
                // if it was set via fallback and an exact-date snapshot has since landed.
                if ($performance->followers === null || $performance->followers_captured_date?->ne($performance->post_date)) {
                    $snapshots->fillFollowersForPostDate($performance);
                }

                $this->line("✓ Performance #{$performance->id} ({$performance->account->name})");
            } catch (\Throwable $e) {
                Log::error("Performance IG snapshot failed for performance #{$performance->id}: {$e->getMessage()}");
                $this->error("✗ Performance #{$performance->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
