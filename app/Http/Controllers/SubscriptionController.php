<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Account;
use App\Models\User;
use App\Models\Device;
use App\Models\SubscriptionAllocation;

class SubscriptionController extends Controller
{
    public function index()
    {
        // Eager load account and allocations to avoid N+1 query issues
        $subscriptions = Subscription::with(['account', 'allocations.assignable'])->orderBy('next_payment_date', 'asc')->get();
        $accounts = Account::orderBy('email')->get();
        $employees = User::orderBy('name')->get(['id', 'name']);

        return view('subscriptions.index', compact('subscriptions', 'accounts', 'employees'));
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['account', 'allocations.assignable']);
        
        $employees = User::orderBy('name')->get();
        $devices = Device::whereIn('category', ['PC', 'Laptop', 'Phone'])->orderBy('name')->get();
        
        return view('subscriptions.show', compact('subscription', 'employees', 'devices'));
    }

    public function store(Request $request)
    {
        $subscription = Subscription::create($this->validated($request));
        $this->syncUsers($subscription, $request);

        return back()->with('success', 'Subscription successfully tracked!');
    }

    public function update(Request $request, Subscription $subscription)
    {
        $subscription->update($this->validated($request));
        $this->syncUsers($subscription, $request);

        return back()->with('success', 'Subscription updated successfully!');
    }

    /**
     * Make the license's employee allocations match the "Used by" picker exactly.
     * Device allocations are managed separately on the detail page and left alone.
     */
    protected function syncUsers(Subscription $subscription, Request $request): void
    {
        $ids = collect($request->validate([
            'user_ids'   => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ])['user_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();

        $userType = User::class;
        $subscription->allocations()->where('assignable_type', $userType)
            ->whereNotIn('assignable_id', $ids)->delete();

        $existing = $subscription->allocations()->where('assignable_type', $userType)->pluck('assignable_id');
        foreach ($ids->diff($existing) as $id) {
            $subscription->allocations()->create(['assignable_type' => $userType, 'assignable_id' => $id]);
        }
    }

    /**
     * Mark the current period as paid: push the renewal date one month or one year forward.
     */
    public function renew(Subscription $subscription)
    {
        if (! $subscription->isRecurring()) {
            return back()->withErrors(['period' => 'Only monthly or yearly licenses can be renewed.']);
        }

        $old = $subscription->next_payment_date;
        $subscription->update([
            'next_payment_date' => $subscription->nextRenewalDate(),
            'status' => 'Active',
        ]);

        return back()->with('success', $subscription->label.' renewed: '
            .($old ? $old->format('d M Y').' → ' : '').$subscription->next_payment_date->format('d M Y').'.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'account_id'        => 'required|exists:accounts,id',
            'app'               => 'required|string|max:255',
            'status'            => 'required|in:Active,Inactive',
            'plan'              => 'nullable|string|max:255',
            'period'            => 'required|in:'.implode(',', Subscription::PERIODS),
            // Only recurring plans need a renewal date.
            'next_payment_date' => 'nullable|required_if:period,Monthly,Yearly|date',
            'bill'              => 'nullable|numeric|min:0',
            'currency'          => 'nullable|string|max:3',
            'password'          => 'nullable|string|max:255',
            'login_with_google' => 'nullable|boolean',
            'shared_with'       => 'nullable|string|max:255',
            'division'          => 'nullable|string|max:255',
            'card_info'         => 'nullable|string|max:20',
            'notes'             => 'nullable|string',
        ]);
        $data['login_with_google'] = $request->boolean('login_with_google');

        return $data;
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted.');
    }

    public function allocate(Request $request, Subscription $subscription)
    {
        $request->validate(['assignable_target' => 'required|string']);
        list($type, $id) = explode(',', $request->assignable_target);

        if (! in_array($type, [User::class, Device::class], true)) {
            return back()->withErrors(['assignable_target' => 'Invalid assignment target.']);
        }

        // Granting the same person/device twice would just duplicate the row.
        $subscription->allocations()->firstOrCreate([
            'assignable_type' => $type,
            'assignable_id'   => $id,
        ]);

        return back()->with('success', 'Access granted successfully!');
    }

    public function revoke(SubscriptionAllocation $allocation)
    {
        $allocation->delete();
        return back()->with('success', 'Access revoked.');
    }
}