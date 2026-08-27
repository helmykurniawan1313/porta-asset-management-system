<?php

namespace App\ClientOps\Http\Controllers;

use App\ClientOps\Models\Client;
use App\ClientOps\Models\Contract;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractController extends Controller
{
    public function store(Request $request, Client $client)
    {
        Gate::authorize('manage-client', $client);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'total_cycles' => 'required|integer|min:1|max:60',
            'cycle_length_days' => 'required|integer|min:1|max:365',
            'cycle_amount' => 'nullable|numeric|min:0',
        ]);

        $contract = $client->contracts()->create($data);
        $contract->generateCycles();

        return back()->with('success', 'Contract created and cycles generated.');
    }

    public function update(Request $request, Contract $contract)
    {
        Gate::authorize('manage-client', $contract->client);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'status' => 'required|in:not_started,active,completed,terminated',
        ]);

        $contract->update($data);

        return back()->with('success', 'Contract updated.');
    }

    public function destroy(Contract $contract)
    {
        Gate::authorize('manage-client', $contract->client);

        $contract->delete();

        return back()->with('success', 'Contract deleted.');
    }
}
