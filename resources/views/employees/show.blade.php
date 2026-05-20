@extends('layouts.app')

@section('title', ($employee->name ?? 'Employee') . ' | Porta IT')
@section('header_title', 'Employee Profile')

@section('styles')
    <style>
        /* 1. Floating Header Table Style */
        .table-custom thead th {
            background-color: transparent !important;
            border-bottom: 2px solid rgba(67, 94, 190, 0.15) !important;
            color: #435ebe !important; /* Brand Blue text */
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            padding: 1.2rem 1rem !important;
        }

        .theme-dark .table-custom thead th {
            color: #8c9eff !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* 2. Profile Avatar Styling */
        .profile-avatar {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 2rem;
        }
    </style>
@endsection

@section('content')
    <div class="mb-4">
        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-light-primary px-3 fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="profile-avatar bg-light-primary text-primary rounded-circle shadow-sm">
                        {{ strtoupper(substr($employee->name ?? 'E', 0, 1)) }}
                    </div>
                </div>
                <div class="col mt-3 mt-md-0">
                    <h2 class="fw-bold mb-1">{{ $employee->name ?? 'Unnamed Employee' }}</h2>
                    <p class="text-muted fw-semibold mb-3">{{ $employee->email ?? 'No email provided' }}</p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-light-success text-success px-3 rounded-pill fw-bold">ACTIVE</span>
                        <span class="badge bg-light-primary text-primary px-3 rounded-pill fw-bold">
                            {{ $employee->department->name ?? 'NO DEPARTMENT' }}
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-auto mt-4 mt-md-0 text-md-end border-start-md ps-md-5">
                    <h6 class="text-muted fw-bold small text-uppercase tracking-wider mb-1">Assigned Hardware</h6>
                    <h1 class="display-5 fw-black text-primary mb-0">{{ $employee->devices->count() }}</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <h5 class="card-title fw-bold">Hardware Checked Out</h5>
            <p class="text-muted small mb-0">Assets currently in possession of this employee.</p>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom" id="table-hardware">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Asset Name</th>
                            <th>Details</th>
                            <th class="text-center">Health</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->devices as $asset)
                        <tr>
                            <td class="py-3">
                                <span class="badge bg-light-secondary text-secondary px-2" style="font-size: 0.65rem;">
                                    {{ strtoupper($asset->category) }}
                                </span>
                            </td>
                            <td class="fw-bold py-3 text-heading">{{ $asset->name }}</td>
                            <td class="py-3">
                                <span class="fw-semibold">{{ $asset->manufacturer }} {{ $asset->model }}</span>
                                @if($asset->device_group == 'Peripheral')
                                    <small class="d-block text-muted fst-italic" style="font-size: 0.7rem;">Installed inside parent device</small>
                                @endif
                            </td>
                            <td class="text-center py-3">
                                <span class="badge {{ $asset->current_health < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-3">
                                    {{ $asset->current_health ?? 100 }}%
                                </span>
                            </td>
                            <td class="text-end py-3">
                                <a href="{{ route('inventory.show', $asset->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3" title="View Asset">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted fst-italic">No hardware is currently assigned to this employee.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title fw-bold">Software & Licenses</h5>
                <p class="text-muted small mb-0">Subscription seats assigned to this user.</p>
            </div>
            <button class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                <i class="bi bi-link-45deg me-1"></i> LINK SOFTWARE
            </button>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom" id="table-software">
                    <thead>
                        <tr>
                            <th>Software Plan</th>
                            <th>Master Account</th>
                            <th>Cycle</th>
                            <th class="text-end">Next Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->softwareAllocations as $allocation)
                            @if($allocation->subscription)
                                <tr>
                                    <td class="py-3">
                                        <div class="fw-bold text-primary">{{ $allocation->subscription->plan }}</div>
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Software License</small>
                                    </td>
                                    <td class="py-3 fw-semibold small">{{ $allocation->subscription->account->email ?? 'Unlinked Account' }}</td>
                                    <td class="py-3">
                                        <span class="badge bg-light-secondary text-secondary px-2 fw-bold" style="font-size: 0.65rem;">
                                            {{ strtoupper($allocation->subscription->period) }}
                                        </span>
                                    </td>
                                    <td class="text-end py-3 fw-bold small">
                                        {{ \Carbon\Carbon::parse($allocation->subscription->next_payment_date)->format('M d, Y') }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted fst-italic">No software subscriptions directly assigned.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection