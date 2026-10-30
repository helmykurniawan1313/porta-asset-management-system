<?php

namespace App\BrandHealth\Console\Commands;

use App\BrandHealth\Models\BrandPerformance;
use App\BrandHealth\Services\PerformanceCycleAssigner;
use Illuminate\Console\Command;

class BackfillPerformanceCycles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brand-health:backfill-performance-cycles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign cycle_id to existing Performance posts whose post_date falls within a matching Cycle (same account + platform).';

    /**
     * Execute the console command.
     */
    public function handle(PerformanceCycleAssigner $cycleAssigner): int
    {
        $performances = BrandPerformance::all();
        $assigned = 0;

        $this->withProgressBar($performances, function (BrandPerformance $performance) use ($cycleAssigner, &$assigned) {
            $cycleAssigner->assign($performance);

            if ($performance->isDirty('cycle_id')) {
                $performance->save();
                $assigned++;
            }
        });

        $this->newLine(2);
        $this->info("Backfilled cycle_id for {$assigned} of {$performances->count()} performance post(s).");

        return self::SUCCESS;
    }
}
