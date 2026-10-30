<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandPerformance;
use Illuminate\Support\Collection;

/**
 * Auto-assigns a Performance post to the Cycle (same account + platform) whose
 * date range covers the post's post_date — e.g. a cycle running Jul 1–31 owns
 * any post dated within that range for the same account/platform. Boundaries
 * are inclusive on both ends, matching how cycle date ranges are treated
 * elsewhere in this app (CycleController's neighboring-cycle <=/>= logic).
 *
 * A post whose date falls inside no cycle (none exists yet, or there's a gap
 * between cycles) is simply left unassigned (cycle_id = null) rather than
 * blocking the save — it can be backfilled automatically once a matching
 * cycle is created or edited to cover that date.
 */
class PerformanceCycleAssigner
{
    /**
     * Finds the cycle (if any) that should own a post for the given
     * account/platform/date, without touching the database.
     */
    public function resolve(int $accountId, string $platform, string $postDate): ?BrandCycle
    {
        return BrandCycle::where('account_id', $accountId)
            ->where('platform', $platform)
            ->whereDate('cycle_start_date', '<=', $postDate)
            ->whereDate('cycle_end_date', '>=', $postDate)
            ->first();
    }

    /**
     * Resolves and assigns cycle_id on an unsaved/in-memory Performance, based
     * on its own account_id/platform/post_date. Caller is responsible for
     * saving the model.
     */
    public function assign(BrandPerformance $performance): BrandPerformance
    {
        $cycle = $this->resolve($performance->account_id, $performance->platform ?? 'instagram', $performance->post_date instanceof \DateTimeInterface
            ? $performance->post_date->format('Y-m-d')
            : (string) $performance->post_date);

        $performance->cycle_id = $cycle?->id;

        return $performance;
    }

    /**
     * Re-syncs every Performance post whose date falls inside the given
     * cycle's range (same account + platform) to point at it — used after a
     * Cycle is created or its account/platform/date range is edited, so
     * previously-unassigned or now-out-of-range posts stay correct without
     * requiring a manual re-save of each post.
     */
    public function syncForCycle(BrandCycle $cycle): void
    {
        BrandPerformance::where('account_id', $cycle->account_id)
            ->where('platform', $cycle->platform)
            ->whereDate('post_date', '>=', $cycle->cycle_start_date)
            ->whereDate('post_date', '<=', $cycle->cycle_end_date)
            ->update(['cycle_id' => $cycle->id]);
    }

    /**
     * Re-resolves cycle_id for every Performance post currently pointing at
     * the given cycle — used right before that cycle is deleted, so posts
     * fall back to whichever other cycle (if any) now covers their date
     * instead of just going null when a replacement cycle already exists.
     */
    public function reassignAwayFromCycle(BrandCycle $cycle): void
    {
        $affected = BrandPerformance::where('cycle_id', $cycle->id)->get();

        $this->reassignMany($affected);
    }

    /**
     * Re-resolves cycle_id for an arbitrary set of Performance posts — shared
     * helper for reassignAwayFromCycle() and any future bulk re-sync needs.
     */
    public function reassignMany(Collection $performances): void
    {
        foreach ($performances as $performance) {
            $this->assign($performance);
            $performance->save();
        }
    }
}
