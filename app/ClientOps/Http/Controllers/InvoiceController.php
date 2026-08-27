<?php

namespace App\ClientOps\Http\Controllers;

use App\ClientOps\Models\ContractCycle;
use App\ClientOps\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Finance's view: every cycle across every client, with its current
     * status front and center, so "cycle not finished yet" is visible
     * before an invoice ever gets created — not discovered after the fact.
     */
    public function index()
    {
        $cycles = ContractCycle::with(['contract.client', 'accountManager:id,name', 'invoices'])
            ->orderByDesc('planned_start')
            ->get();

        return view('client-ops.invoices.index', compact('cycles'));
    }

    public function store(Request $request, ContractCycle $cycle)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'due_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $cycle->invoices()->create([
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'amount' => $data['amount'],
            'issued_at' => now()->toDateString(),
            'due_at' => $data['due_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'invoiced',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Invoice created.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status' => 'required|in:not_ready,invoiced,overdue,paid',
            'paid_at' => 'nullable|date',
        ]);

        if ($data['status'] === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = now()->toDateString();
        }

        $invoice->update($data);

        return back()->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return back()->with('success', 'Invoice deleted.');
    }
}
