@extends('layouts.app')

@section('title', 'Porta IT | SaaS & Subscriptions')
@section('header_title', 'SaaS & Subscriptions')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
    <style>
        /* 1. Professional Floating Header - NO GRAY */
       

        /* 2. Row Hover & Transitions */
        .table-hover tbody tr:hover {
            background-color: rgba(67, 94, 190, 0.03) !important;
            transition: background-color 0.2s ease;
        }

        /* 3. Dark Mode Polish */
        .theme-dark #table1 thead th {
            color: #8c9eff !important; 
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }
    </style>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
    </div>
@endif

<section class="section">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h4 class="fw-bold mb-1">Active Subscriptions</h4>
                <p class="text-muted small mb-0">Manage and track your recurring software costs.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button data-bs-toggle="modal" data-bs-target="#add-sub-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> ADD SUBSCRIPTION
                </button>
            </div>
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="table1">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Plan & Assignments</th>
                            <th>Period</th>
                            <th>Bill / Currency</th>
                            <th class="text-end">Due In</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $sub)
                        <tr>
                            <td class="fw-bold text-heading py-3">{{ $sub->account->email }}</td>
                            <td class="py-3">
                                <a href="{{ route('subscriptions.show', $sub->id) }}" class="fw-bold text-primary text-decoration-none">
                                    {{ $sub->plan }} <i class="bi bi-box-arrow-up-right small ms-1"></i>
                                </a>
                                <div class="mt-1 d-flex gap-2">
                                    @php
                                        $dCount = $sub->allocations->where('assignable_type', 'App\Models\Device')->count();
                                        $uCount = $sub->allocations->where('assignable_type', 'App\Models\Employee')->count();
                                    @endphp
                                    <span class="badge {{ $dCount > 0 ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }} border-0 px-2" style="font-size: 0.65rem;">
                                        {{ $dCount }} DEVICES
                                    </span>
                                    <span class="badge {{ $uCount > 0 ? 'bg-light-warning text-warning' : 'bg-light-secondary text-secondary' }} border-0 px-2" style="font-size: 0.65rem;">
                                        {{ $uCount }} USERS
                                    </span>
                                </div>
                            </td>
                            <td class="text-muted fw-bold small py-3">{{ strtoupper($sub->period) }}</td>
                            <td class="py-3">
                                <span class="fw-bolder">{{ number_format($sub->bill, 2) }}</span>
                                <small class="text-muted ms-1 fw-bold">{{ $sub->currency }}</small>
                            </td>
                            <td class="text-end py-3">
                                @php $days = $sub->days_remaining; @endphp
                                <span class="badge {{ $days <= 7 ? 'bg-danger' : 'bg-success' }} px-3 rounded-pill shadow-sm" style="font-size: 0.7rem;">
                                    {{ $days < 0 ? 'OVERDUE' : $days . ' DAYS' }}
                                </span>
                            </td>
                            <td class="text-end py-3">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#edit-sub-modal-{{ $sub->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('inventory.destroy', $sub->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this subscription?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('modals')
<div class="modal fade" id="add-sub-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white fw-bold text-uppercase" style="font-size: 0.9rem; letter-spacing: 1px;">Register New Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subscriptions.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 p-md-5">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Master Account</label>
                            <select name="account_id" class="form-select" required>
                                <option value="">-- Select Account --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Plan Name</label>
                            <input type="text" name="plan" placeholder="e.g. Adobe Creative Cloud" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Billing Period</label>
                            <select name="period" class="form-select">
                                <option value="Monthly">Monthly</option>
                                <option value="Yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Next Payment Date</label>
                            <input type="date" name="next_payment_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Amount</label>
                            <input type="number" step="0.01" name="bill" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Currency</label>
                            <select name="currency" class="form-select">
                                <option value="USD">USD</option>
                                <option value="IDR">IDR</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">SAVE SUBSCRIPTION</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($subscriptions as $sub)
<div class="modal fade" id="edit-sub-modal-{{ $sub->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white fw-bold text-uppercase" style="font-size: 0.9rem; letter-spacing: 1px;">Edit {{ $sub->plan }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subscriptions.update', $sub->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4 p-md-5">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Master Account</label>
                            <select name="account_id" class="form-select" required>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ $sub->account_id == $acc->id ? 'selected' : '' }}>{{ $acc->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Plan Name</label>
                            <input type="text" name="plan" value="{{ $sub->plan }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Billing Period</label>
                            <select name="period" class="form-select">
                                <option value="Monthly" {{ $sub->period == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="Yearly" {{ $sub->period == 'Yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Next Payment Date</label>
                            <input type="date" name="next_payment_date" value="{{ \Carbon\Carbon::parse($sub->next_payment_date)->format('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Amount</label>
                            <input type="number" step="0.01" name="bill" value="{{ $sub->bill }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Currency</label>
                            <select name="currency" class="form-select">
                                <option value="USD" {{ $sub->currency == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="IDR" {{ $sub->currency == 'IDR' ? 'selected' : '' }}>IDR</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">UPDATE PLAN</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

@section('scripts')
    <script src="{{ asset('assets/extensions/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize jQuery DataTables
            $('#table1').DataTable({
                "pageLength": 10,
                "order": [[4, "asc"]] // Sort by due date (days remaining) by default
            });
        });
    </script>
@endsection