<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandCycle;
use Illuminate\Support\Collection;

/**
 * An account's "current PM" is no longer its own stored field — it's derived
 * from whoever managed its most recent cycle (across platforms; cycles are
 * ordered by cycle_start_date regardless of Instagram/TikTok). This service
 * is the one place that derivation happens, so every page that needs
 * "this account's PM" (filters, detail view, exports) goes through the same
 * logic instead of each re-deriving it slightly differently.
 */
class AccountProjectManagerHistory
{
    /**
     * The PM of the account's most recent cycle, or null if it has none yet
     * (a brand-new account has no PM until its first cycle is created).
     */
    public function current(BrandAccount $account): ?int
    {
        return $account->cycles()
            ->orderByDesc('cycle_start_date')
            ->orderByDesc('id')
            ->value('project_manager_id');
    }

    /**
     * current() for many accounts in one query. Ties on cycle_start_date
     * (same-day Instagram + TikTok cycles) break on the higher id, as in
     * accountIdsCurrentlyManagedBy(). Accounts with no cycles are absent.
     *
     * @return Collection<int, ?int> account_id => project_manager_id
     */
    public function currentByAccount(Collection $accountIds): Collection
    {
        if ($accountIds->isEmpty()) {
            return collect();
        }

        return BrandCycle::whereIn('account_id', $accountIds)
            ->orderByDesc('cycle_start_date')
            ->orderByDesc('id')
            ->get(['account_id', 'project_manager_id'])
            ->unique('account_id')
            ->pluck('project_manager_id', 'account_id');
    }

    /**
     * Groups the account's cycles into consecutive same-PM runs, ordered
     * oldest-first, e.g. [Sarah: May 1 - Aug 16] [Natalie: Aug 18 - Sep 17]
     * [Lauren: Sep 18 - Oct 17]. A gap in PM (null) is its own run too, so a
     * period with no PM assigned still shows up rather than silently
     * vanishing from the timeline.
     *
     * @return Collection<int, array{project_manager_id: ?int, from: \Carbon\Carbon, to: \Carbon\Carbon}>
     */
    public function timeline(BrandAccount $account): Collection
    {
        $cycles = $account->cycles()
            ->orderBy('cycle_start_date')
            ->get(['project_manager_id', 'cycle_start_date', 'cycle_end_date']);

        // Plain array, not a Collection, while accumulating — mutating an
        // element nested inside a Collection by index (`$runs[$i]['to'] = ..`)
        // silently no-ops (Collection's ArrayAccess doesn't support nested
        // writes), which left every run's `to` stuck at its first cycle's end
        // date instead of extending to the last one in that run.
        $runs = [];

        foreach ($cycles as $cycle) {
            $lastIndex = count($runs) - 1;

            if ($lastIndex >= 0 && $runs[$lastIndex]['project_manager_id'] === $cycle->project_manager_id) {
                $runs[$lastIndex]['to'] = $cycle->cycle_end_date;

                continue;
            }

            $runs[] = [
                'project_manager_id' => $cycle->project_manager_id,
                'from' => $cycle->cycle_start_date,
                'to' => $cycle->cycle_end_date,
            ];
        }

        return collect($runs);
    }

    /**
     * Account IDs whose CURRENT (most recent cycle's) PM is $employeeId —
     * the derived replacement for the old `accounts.project_manager_id`
     * column when filtering an account list by PM. Ties on cycle_start_date
     * (e.g. same-day Instagram + TikTok cycles) break on the higher id,
     * i.e. whichever was created/saved most recently.
     */
    public function accountIdsCurrentlyManagedBy(int $employeeId): Collection
    {
        return BrandCycle::query()
            ->selectRaw('account_id, project_manager_id')
            ->orderByDesc('cycle_start_date')
            ->orderByDesc('id')
            ->get()
            ->unique('account_id')
            ->where('project_manager_id', $employeeId)
            ->pluck('account_id');
    }
}
