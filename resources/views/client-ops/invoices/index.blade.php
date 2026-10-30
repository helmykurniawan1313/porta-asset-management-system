@extends('layouts.app')

@section('title', 'Invoices | Porta IT')
@section('header_title', 'Invoices')

@section('content')
    @if(session('success'))
        <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ $errors->first() }}
        </div>
    @endif

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="card-title mb-1">Billing Cycles</h5>
                <p class="text-muted small mb-0">Check the cycle's real status before invoicing — don't bill a cycle that isn't finished yet.</p>
            </div>
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Contract / Cycle</th>
                                <th>Planned Window</th>
                                <th>Cycle Status</th>
                                <th>Account Manager</th>
                                <th>Invoice</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cycles as $cycle)
                                @php $invoice = $cycle->invoices->first(); @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $cycle->contract->client->name }}</td>
                                    <td class="small">{{ $cycle->contract->name ?: '#' . $cycle->contract_id }} &middot; Cycle {{ $cycle->cycle_number }}</td>
                                    <td class="small">{{ $cycle->planned_start->format('d M') }} – {{ $cycle->planned_end->format('d M Y') }}</td>
                                    <td>
                                        @if($cycle->status === 'completed')
                                            <span class="badge bg-light-success text-success rounded-pill px-3">Completed</span>
                                        @elseif($cycle->status === 'behind_schedule')
                                            <span class="badge bg-light-danger text-danger rounded-pill px-3">Behind Schedule</span>
                                        @elseif($cycle->status === 'on_track')
                                            <span class="badge bg-light-primary text-primary rounded-pill px-3">On Track</span>
                                        @else
                                            <span class="badge bg-light-warning text-warning rounded-pill px-3">Not Started Yet</span>
                                        @endif
                                        @if($cycle->isOverrun())
                                            <div class="text-warning small mt-1"><i class="bi bi-exclamation-triangle-fill"></i> Past planned end, not marked complete</div>
                                        @endif
                                    </td>
                                    <td class="small">{{ $cycle->accountManager->name ?? '—' }}</td>
                                    <td>
                                        @if($invoice)
                                            @php
                                                $badgeClass = match($invoice->status) {
                                                    'paid' => 'bg-light-success text-success',
                                                    'overdue' => 'bg-light-danger text-danger',
                                                    'invoiced' => 'bg-light-warning text-warning',
                                                    default => 'bg-light-secondary text-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }} rounded-pill px-3 text-capitalize">{{ $invoice->status }}</span>
                                            <div class="text-muted small mt-1">{{ $invoice->invoice_number }}</div>
                                        @else
                                            <span class="text-muted small">Not invoiced</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(! $invoice)
                                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3"
                                                data-bs-toggle="modal" data-bs-target="#invoice-modal-{{ $cycle->id }}">
                                                <i class="bi bi-receipt"></i> Invoice
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-light-primary rounded-pill px-3"
                                                data-bs-toggle="modal" data-bs-target="#update-invoice-{{ $invoice->id }}">
                                                <i class="bi bi-pencil"></i> Update
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No billing cycles yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    @foreach($cycles as $cycle)
        @php $invoice = $cycle->invoices->first(); @endphp
        @if(! $invoice)
            <div class="modal fade" id="invoice-modal-{{ $cycle->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                    <form method="POST" action="{{ route('invoices.store', $cycle) }}" class="modal-content hr-modal">
                        @csrf
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold">Invoice — {{ $cycle->contract->client->name }} / Cycle {{ $cycle->cycle_number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @if($cycle->status !== 'completed')
                                @php
                                    $statusLabel = match($cycle->status) {
                                        'behind_schedule' => 'Behind Schedule',
                                        'on_track' => 'On Track (not yet completed)',
                                        default => 'Not Started Yet',
                                    };
                                @endphp
                                <div class="alert alert-light-warning color-warning small">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    This cycle is marked <strong>{{ $statusLabel }}</strong>. Confirm with the account manager before invoicing.
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" value="{{ $cycle->contract->cycle_amount }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Due Date</label>
                                <input type="date" name="due_at" class="form-control">
                            </div>
                            <div class="mb-1">
                                <label class="form-label small fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary fw-bold">Create Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="modal fade" id="update-invoice-{{ $invoice->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                    <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="modal-content hr-modal">
                        @csrf @method('PUT')
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold">Update Invoice {{ $invoice->invoice_number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-1">
                                <label class="form-label small fw-semibold">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="not_ready" @selected($invoice->status === 'not_ready')>Not Ready</option>
                                    <option value="invoiced" @selected($invoice->status === 'invoiced')>Invoiced</option>
                                    <option value="overdue" @selected($invoice->status === 'overdue')>Overdue</option>
                                    <option value="paid" @selected($invoice->status === 'paid')>Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary fw-bold">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection
