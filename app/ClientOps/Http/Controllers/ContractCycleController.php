<?php

namespace App\ClientOps\Http\Controllers;

use App\ClientOps\Models\ContractCycle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractCycleController extends Controller
{
    /**
     * Account manager updates cycle progress/status — this is the signal
     * Finance reads before deciding whether it's actually safe to invoice.
     *
     * Only one cycle in a contract can be "running" (on_track/behind_schedule)
     * at a time: a cycle can't move past not_started until the previous
     * cycle in the same contract is completed, and a completed/not_started
     * cycle can't be reopened while a later cycle is already running or
     * completed — a contract's cycles always progress strictly in order.
     */
    public function update(Request $request, ContractCycle $cycle)
    {
        Gate::authorize('manage-client', $cycle->contract->client);

        $data = $request->validate([
            'status' => 'required|in:not_started,on_track,behind_schedule,completed',
            'actual_start' => 'nullable|date',
            'actual_end' => 'nullable|date|after_or_equal:actual_start',
            'notes' => 'nullable|string',
        ]);

        if ($data['status'] !== 'not_started') {
            $previousUnfinished = $cycle->contract->cycles()
                ->where('cycle_number', '<', $cycle->cycle_number)
                ->where('status', '!=', 'completed')
                ->exists();

            if ($previousUnfinished) {
                return back()->withErrors(['status' => 'Cycle ' . $cycle->cycle_number . ' can\'t start until Cycle ' . ($cycle->cycle_number - 1) . ' is completed.']);
            }
        } else {
            $laterCycleStarted = $cycle->contract->cycles()
                ->where('cycle_number', '>', $cycle->cycle_number)
                ->where('status', '!=', 'not_started')
                ->exists();

            if ($laterCycleStarted) {
                return back()->withErrors(['status' => 'This cycle can\'t be reset — a later cycle has already started.']);
            }
        }

        // A cycle can't be "On Track" / "Behind Schedule" / "Completed" with
        // no actual_start — that combination is exactly the bug that showed
        // Cycle 1 as On Track while its Actual column was still empty.
        if ($data['status'] !== 'not_started' && empty($data['actual_start'])) {
            $data['actual_start'] = $cycle->actual_start?->toDateString() ?? now()->toDateString();
        }

        if ($data['status'] === 'completed' && empty($data['actual_end'])) {
            $data['actual_end'] = now()->toDateString();
        }

        if ($data['status'] === 'not_started') {
            $data['actual_start'] = null;
            $data['actual_end'] = null;
        }

        $cycle->update($data);

        return back()->with('success', 'Cycle status updated.');
    }
}
