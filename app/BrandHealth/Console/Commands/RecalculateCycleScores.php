<?php

namespace App\BrandHealth\Console\Commands;

use App\BrandHealth\Models\BrandCycle;
use Illuminate\Console\Command;

/**
 * Re-saves every Cycle so tier/needs_check/health_3c (resolved once at save
 * time by Cycle::saving(), not recomputed on every page load like health_rate
 * itself) pick up any Settings change made since they were last saved — e.g.
 * editing a score bucket threshold, a formula weight, or an account_tier
 * boundary. Run manually (via this command, or the Settings UI button that
 * calls it) after making a scoring change you want reflected everywhere.
 */
class RecalculateCycleScores extends Command
{
    protected $signature = 'brand-health:recalculate-cycles';

    protected $description = 'Recompute tier, needs_check, and health_3c for every cycle using current Settings thresholds/weights';

    public function handle(): int
    {
        $cycles = BrandCycle::orderBy('cycle_start_date')->get();
        $total = $cycles->count();

        $this->withProgressBar($cycles, function (BrandCycle $cycle) {
            $cycle->save();
        });
        $this->newLine(2);

        $this->info("Recalculated {$total} cycles.");

        return self::SUCCESS;
    }
}
