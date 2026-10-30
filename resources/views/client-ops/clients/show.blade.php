@extends('layouts.app')

@section('title', $client->name . ' | Porta IT')
@section('header_title', 'Client Detail')

@section('styles')
    @include('client-ops.partials.am-search-styles')
@endsection

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

    @php
        $currentAssignment = $client->assignments->firstWhere('ended_at', null);
    @endphp

    <div class="row g-4 mb-2">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1">{{ $client->name }}</h5>
                    <p class="text-muted small mb-3">{{ $client->company ?? '—' }}</p>
                    <div class="d-flex justify-content-between py-1 border-top pt-3">
                        <span class="text-muted small">Email</span>
                        <span class="fw-semibold small">{{ $client->contact_email ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted small">Phone</span>
                        <span class="fw-semibold small">{{ $client->contact_phone ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span class="text-muted small">Account Manager</span>
                        <span class="fw-semibold small">
                            @if($currentAssignment)
                                {{ $currentAssignment->user->name }}
                            @else
                                <span class="text-danger">Unassigned</span>
                            @endif
                        </span>
                    </div>
                    @if($canManage)
                        <button data-bs-toggle="modal" data-bs-target="#reassign-modal" class="btn btn-outline-primary btn-sm rounded-pill px-3 mt-3 w-100">
                            <i class="bi bi-arrow-left-right me-1"></i> Reassign Account Manager
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h5 class="card-title mb-1">Assignment History</h5>
                    <p class="text-muted small mb-0">Every account manager who has handled this client.</p>
                </div>
                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr><th>Account Manager</th><th>From</th><th>To</th></tr>
                            </thead>
                            <tbody>
                                @forelse($client->assignments as $a)
                                    <tr>
                                        <td class="fw-semibold">{{ $a->user->name }}</td>
                                        <td>{{ $a->started_at->format('d M Y') }}</td>
                                        <td>{{ $a->ended_at ? $a->ended_at->format('d M Y') : 'Present' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">No assignment history yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h5 class="card-title mb-1">Contracts</h5>
                    <p class="text-muted small mb-0">Each contract auto-generates its billing cycles.</p>
                </div>
                @if($canManage)
                    <div class="mt-3 mt-md-0">
                        <button data-bs-toggle="modal" data-bs-target="#add-contract-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                            <i class="bi bi-file-earmark-plus me-1"></i> Add Contract
                        </button>
                    </div>
                @endif
            </div>
            <div class="card-body py-4">
                @forelse($client->contracts as $contract)
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="fw-bold">{{ $contract->name ?: 'Contract #' . $contract->id }}</span>
                                @php
                                    $contractBadgeClass = match($contract->status) {
                                        'active' => 'bg-light-success text-success',
                                        'completed' => 'bg-light-primary text-primary',
                                        'terminated' => 'bg-light-danger text-danger',
                                        default => 'bg-light-warning text-warning',
                                    };
                                    $contractStatusLabel = $contract->status === 'not_started' ? 'Not Started Yet' : ucfirst($contract->status);
                                @endphp
                                <span class="badge {{ $contractBadgeClass }} ms-2">{{ $contractStatusLabel }}</span>
                                <span class="text-muted small ms-2">Starts {{ $contract->start_date->format('d M Y') }} &middot; {{ $contract->total_cycles }} cycles</span>
                            </div>
                            @if($canManage)
                                <div>
                                    <button type="button" class="btn btn-sm btn-light-primary rounded-pill px-3"
                                        data-bs-toggle="modal" data-bs-target="#edit-contract-modal-{{ $contract->id }}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light-danger rounded-pill px-3"
                                        onclick="confirmDelete(document.getElementById('delete-contract-form-{{ $contract->id }}'), 'Delete this contract? All its cycles and invoices will be deleted too.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-contract-form-{{ $contract->id }}" method="POST" action="{{ route('contracts.destroy', $contract) }}" class="d-none">
                                        @csrf @method('DELETE')
                                    </form>
                                </div>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Cycle</th>
                                        <th>Planned Window</th>
                                        <th>Actual</th>
                                        <th>Status</th>
                                        <th>Account Manager</th>
                                        <th>Invoices</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contract->cycles as $cycle)
                                        <tr>
                                            <td class="fw-semibold">Cycle {{ $cycle->cycle_number }}</td>
                                            <td class="small">{{ $cycle->planned_start->format('d M') }} – {{ $cycle->planned_end->format('d M Y') }}</td>
                                            <td class="small">
                                                {{ $cycle->actual_start?->format('d M') ?? '—' }}
                                                @if($cycle->actual_end) – {{ $cycle->actual_end->format('d M Y') }} @endif
                                            </td>
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
                                                    <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Past planned end date"></i>
                                                @endif
                                            </td>
                                            <td class="small">{{ $cycle->accountManager->name ?? '—' }}</td>
                                            <td class="small">
                                                @forelse($cycle->invoices as $inv)
                                                    @php
                                                        $badgeClass = match($inv->status) {
                                                            'paid' => 'bg-light-success text-success',
                                                            'overdue' => 'bg-light-danger text-danger',
                                                            'invoiced' => 'bg-light-warning text-warning',
                                                            default => 'bg-light-secondary text-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }} rounded-pill px-2 text-capitalize mb-1 d-inline-block">{{ $inv->status }}</span>
                                                @empty
                                                    <span class="text-muted">Not invoiced</span>
                                                @endforelse
                                            </td>
                                            <td class="text-end">
                                                @if($canManage)
                                                    <button type="button" class="btn btn-sm btn-light-primary rounded-pill px-2"
                                                        data-bs-toggle="modal" data-bs-target="#cycle-modal-{{ $cycle->id }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($canManage)
                        @foreach($contract->cycles as $cycle)
                            {{-- Update cycle modal --}}
                            <div class="modal fade" id="cycle-modal-{{ $cycle->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                    <form method="POST" action="{{ route('contract-cycles.update', $cycle) }}" class="modal-content hr-modal">
                                        @csrf @method('PUT')
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title fw-bold">Cycle {{ $cycle->cycle_number }} Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="not_started" @selected($cycle->status === 'not_started')>Not Started Yet</option>
                                                    <option value="on_track" @selected($cycle->status === 'on_track')>On Track</option>
                                                    <option value="behind_schedule" @selected($cycle->status === 'behind_schedule')>Behind Schedule</option>
                                                    <option value="completed" @selected($cycle->status === 'completed')>Completed</option>
                                                </select>
                                                <small class="text-muted">A cycle can only start once the previous cycle in this contract is completed.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Actual Start</label>
                                                <input type="date" name="actual_start" class="form-control" value="{{ $cycle->actual_start?->format('Y-m-d') }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Actual End</label>
                                                <input type="date" name="actual_end" class="form-control" value="{{ $cycle->actual_end?->format('Y-m-d') }}">
                                            </div>
                                            <div class="mb-1">
                                                <label class="form-label small fw-semibold">Notes</label>
                                                <textarea name="notes" class="form-control" rows="2">{{ $cycle->notes }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary fw-bold">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach

                        {{-- Edit contract modal --}}
                        <div class="modal fade" id="edit-contract-modal-{{ $contract->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                <form method="POST" action="{{ route('contracts.update', $contract) }}" class="modal-content hr-modal">
                                    @csrf @method('PUT')
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title fw-bold">Edit Contract</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Contract Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $contract->name }}" placeholder="e.g. Retainer 2026">
                                        </div>
                                        <div class="mb-1">
                                            <label class="form-label small fw-semibold">Status</label>
                                            <select name="status" class="form-select" required>
                                                <option value="not_started" @selected($contract->status === 'not_started')>Not Started Yet</option>
                                                <option value="active" @selected($contract->status === 'active')>Active</option>
                                                <option value="completed" @selected($contract->status === 'completed')>Completed</option>
                                                <option value="terminated" @selected($contract->status === 'terminated')>Terminated</option>
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
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">No contracts yet for this client.</p>
                @endforelse
            </div>
        </div>
    </section>

    @if($canManage)
    {{-- Reassign modal --}}
    <div class="modal fade" id="reassign-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <form method="POST" action="{{ route('clients.reassign', $client) }}" class="modal-content hr-modal">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Reassign Account Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">The current assignment closes and history is kept — past cycles and invoices stay linked to whoever managed them at the time.</p>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">New Account Manager</label>
                        <div class="am-select position-relative">
                            <input type="hidden" name="account_manager_id" id="reassign-am-id" required>
                            <input type="text" id="reassign-am-search" class="form-control" placeholder="-- Select --" autocomplete="off" required>
                            <div id="reassign-am-options" class="am-options"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Reassign</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add contract modal --}}
    <div class="modal fade" id="add-contract-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <form method="POST" action="{{ route('contracts.store', $client) }}" class="modal-content hr-modal">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Add Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contract Name (optional)</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Retainer 2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Total Cycles</label>
                        <input type="number" name="total_cycles" class="form-control" min="1" max="60" value="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cycle Length (days)</label>
                        <input type="number" name="cycle_length_days" class="form-control" min="1" max="365" value="30" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Amount per Cycle</label>
                        <input type="number" step="0.01" name="cycle_amount" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Contract</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @section('scripts')
        @include('client-ops.partials.am-search-script', [
            'hiddenId' => 'reassign-am-id',
            'searchId' => 'reassign-am-search',
            'optionsId' => 'reassign-am-options',
        ])
    @endsection
@endsection
