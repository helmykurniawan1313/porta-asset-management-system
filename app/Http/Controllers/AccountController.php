<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        // Fetch all accounts and count the number of subscriptions attached to them
        $accounts = Account::withCount('subscriptions')->orderBy('email')->get();
        
        return view('accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:accounts,email',
            'purpose' => 'nullable|string|max:255',
        ]);

        Account::create($request->all());

        return back()->with('success', 'Master Account successfully registered!');
    }

    public function update(Request $request, Account $account)
    {
        $request->validate([
            'email' => 'required|email|unique:accounts,email,' . $account->id,
            'purpose' => 'nullable|string|max:255',
        ]);

        $account->update($request->all());

        return back()->with('success', 'Master Account details updated successfully!');
    }

    public function destroy(Account $account)
    {
        // This will cascade and delete the subscriptions attached to it too!
        $account->delete();
        return back()->with('success', 'Master Account removed from the system.');
    }
    public function show(Account $account)
{
    // Load all subscriptions tied to this account
    $account->load('subscriptions');

    // Calculate total monthly spend for this specific account
    $totalMonthly = $account->subscriptions->where('period', 'Monthly')->sum('bill');
    $totalYearly = $account->subscriptions->where('period', 'Yearly')->sum('bill');

    return view('accounts.show', compact('account', 'totalMonthly', 'totalYearly'));
}
}