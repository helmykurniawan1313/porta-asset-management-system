<?php

namespace App\ClientOps\Http\Controllers;

use App\ClientOps\Models\Client;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Client::with(['assignments' => fn ($q) => $q->whereNull('ended_at')->with('user:id,name')])
            ->withCount('contracts');

        // A plain account manager only sees clients they own. Finance/HR
        // Admin/Super Admin and the Account Manager department's Head see
        // everyone's clients (the Head's view is read-only, enforced in
        // show/update/destroy/reassign via the manage-client Gate).
        if (! $user->hasAnyRole(['Finance', 'HR Admin', 'Super Admin']) && ! $user->isHeadOfDepartment('Account Manager')) {
            $query->whereHas('assignments', fn ($q) => $q->whereNull('ended_at')->where('user_id', $user->id));
        }

        $clients = $query->orderBy('name')->get();

        $accountManagers = $this->accountManagers();

        return view('client-ops.clients.index', compact('clients', 'accountManagers'));
    }

    public function show(Request $request, Client $client)
    {
        Gate::authorize('view-client', $client);

        $client->load([
            'assignments' => fn ($q) => $q->orderByDesc('started_at')->with('user:id,name'),
            'contracts.cycles.invoices',
            'contracts.cycles.accountManager:id,name',
        ]);

        $accountManagers = $this->accountManagers();
        $canManage = $request->user()->can('manage-client', $client);

        return view('client-ops.clients.show', compact('client', 'accountManagers', 'canManage'));
    }

    /**
     * Account managers are whoever is in the "Account Manager" department —
     * not a separate Spatie role. Matches how staff are actually organized
     * (department = job function) rather than tracking it twice.
     */
    protected function accountManagers()
    {
        $departmentId = Department::where('name', 'Account Manager')->value('id');

        return User::where('department_id', $departmentId)->orderBy('name')->get(['id', 'name']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'account_manager_id' => 'nullable|exists:users,id',
        ]);

        $client = Client::create(collect($data)->except('account_manager_id')->toArray());

        if (! empty($data['account_manager_id'])) {
            $client->reassignAccountManager(User::findOrFail($data['account_manager_id']));
        }

        return back()->with('success', 'Client added successfully!');
    }

    public function update(Request $request, Client $client)
    {
        Gate::authorize('manage-client', $client);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $client->update($data);

        return back()->with('success', 'Client updated successfully!');
    }

    public function destroy(Client $client)
    {
        Gate::authorize('manage-client', $client);

        $client->delete();

        return back()->with('success', 'Client deleted.');
    }

    /**
     * Reassign a client to a different account manager. Closes the current
     * assignment (if any) and opens a new one — never overwrites history,
     * so cycles/invoices already created keep the manager who was actually
     * responsible at the time.
     */
    public function reassign(Request $request, Client $client)
    {
        Gate::authorize('manage-client', $client);

        $data = $request->validate([
            'account_manager_id' => 'required|exists:users,id',
        ]);

        $client->reassignAccountManager(User::findOrFail($data['account_manager_id']));

        return back()->with('success', 'Account manager reassigned.');
    }
}
