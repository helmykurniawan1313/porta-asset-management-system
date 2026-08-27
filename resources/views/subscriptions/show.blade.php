@extends('layouts.app')

@section('title', 'License Control | ' . $subscription->plan)
@section('header_title', 'License Control')

@section('styles')
    <style>
        /* Modern Floating Header - No Gray Background */
        .table-assignments thead th {
            background-color: transparent !important;
            border-bottom: 2px solid rgba(67, 94, 190, 0.15) !important; /* Brand Blue line */
            color: #435ebe !important; /* Brand Blue text */
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 1px;
            padding: 1.2rem 1rem !important;
        }
        
        .theme-dark .table-assignments thead th {
            color: #8c9eff !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* Stats Icon Style to match Mazer Dashboard */
        .license-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
    </style>
@endsection

@section('content')
<div class="row">
    <div class="col-12 col-xl-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center p-5 border-bottom">
                <div class="license-icon bg-light-primary text-primary rounded-circle mb-3 shadow-sm">
                    <span class="fs-1 fw-black">{{ $subscription->currency }}</span>
                </div>
                <h4 class="fw-bold text-uppercase tracking-tight mb-1">{{ $subscription->plan }}</h4>
                <p class="text-muted small fw-bold text-uppercase mb-0">{{ $subscription->account->email }}</p>
            </div>
            
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted fw-bold small text-uppercase">Annual Cost</span>
                    <span class="fw-bold fs-5">{{ number_format($subscription->bill, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted fw-bold small text-uppercase">Next Renewal</span>
                    <span class="fw-bold text-primary">{{ $subscription->next_payment_date->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent border-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center py-4">
                <div>
                    <h5 class="card-title mb-1 fw-bold">Authorized Users & Devices</h5>
                    <p class="text-muted small mb-0">Found <span class="text-primary fw-bold">{{ $subscription->allocations->count() }}</span> active assignments.</p>
                </div>
                <button data-bs-toggle="modal" data-bs-target="#assign-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm mt-3 mt-sm-0">
                    <i class="bi bi-shield-check me-1"></i> GRANT ACCESS
                </button>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-assignments mb-0">
                        <thead>
                            <tr>
                                <th class="px-4">Target Name</th>
                                <th>Category</th>
                                <th class="text-end px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscription->allocations as $alloc)
                                <tr>
                                    <td class="px-4 fw-bold text-heading py-3">
                                        {{ $alloc->assignable->name ?? 'Deleted Asset' }}
                                    </td>
                                    <td class="py-3">
                                        @php $isStaff = str_contains($alloc->assignable_type, 'User'); @endphp
                                        <span class="badge {{ $isStaff ? 'bg-light-warning text-warning' : 'bg-light-success text-success' }} px-3" style="font-size: 0.65rem;">
                                            {{ $isStaff ? 'STAFF MEMBER' : 'REGISTERED DEVICE' }}
                                        </span>
                                    </td>
                                    <td class="text-end px-4 py-3">
                                        <form action="{{ route('subscriptions.revoke', $alloc->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Revoke this access?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger px-3 fw-bold text-uppercase" style="font-size: 0.65rem;">
                                                Revoke Access
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-5 text-center text-muted fst-italic">
                                        No access granted yet. Click "Grant Access" to assign this software.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
<div class="modal fade" id="assign-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg hr-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-uppercase" style="font-size: 0.9rem;">Assign Software License</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subscriptions.allocate', $subscription->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">Target Assignment</label>
                        <select name="assignable_target" class="form-select" required>
                            <option value="">-- Choose User or Device --</option>
                            <optgroup label="Staff Members">
                                @foreach($employees as $emp) 
                                    <option value="App\Models\User,{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Registered Devices">
                                @foreach($devices as $dev) 
                                    <option value="App\Models\Device,{{ $dev->id }}">{{ $dev->name }}</option> 
                                @endforeach
                            </optgroup>
                        </select>
                        <small class="text-muted d-block mt-2">
                            The selected target will be granted a license seat for {{ $subscription->plan }}.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">GRANT ACCESS</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection