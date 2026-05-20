<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Account;
use App\Models\Employee;
use App\Models\Device;
use App\Models\SubscriptionAllocation;

class SubscriptionController extends Controller
{
    public function index()
    {
        // Eager load account and allocations to avoid N+1 query issues
        $subscriptions = Subscription::with(['account', 'allocations'])->orderBy('next_payment_date', 'asc')->get();
        $accounts = Account::orderBy('email')->get();
        
        return view('subscriptions.index', compact('subscriptions', 'accounts'));
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['account', 'allocations.assignable']);
        
        $employees = Employee::orderBy('name')->get();
        $devices = Device::whereIn('category', ['PC', 'Laptop', 'Phone'])->orderBy('name')->get();
        
        return view('subscriptions.show', compact('subscription', 'employees', 'devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_id'        => 'required|exists:accounts,id',
            'plan'              => 'required|string|max:255',
            'period'            => 'required|in:Monthly,Yearly',
            'bill'              => 'required|numeric|min:0',
            'currency'          => 'required|string|max:3',
            'next_payment_date' => 'required|date',
        ]);

        Subscription::create($request->all());
        
        return back()->with('success', 'Subscription successfully tracked!');
    }

    public function update(Request $request, Subscription $subscription)
    {
        // ADJUSTED: Replaced 'name' and 'url' with 'plan' and 'next_payment_date' to match the UI and store method
        $request->validate([
            'account_id'        => 'required|exists:accounts,id',
            'plan'              => 'required|string|max:255',
            'period'            => 'required|in:Monthly,Yearly',
            'next_payment_date' => 'required|date',
            'bill'              => 'required|numeric|min:0',
            'currency'          => 'required|string|max:3',
        ]);

        $subscription->update($request->all());

        return back()->with('success', 'Subscription updated successfully!');
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

        $subscription->allocations()->create([
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