@extends('layouts.app')

@section('title', 'Porta IT | Master Accounts')
@section('header_title', 'Master Accounts')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
    <style>
        /* Modern Floating Header - NO GRAY */
       

        /* Dark Mode Adjustment */
        .theme-dark #table1 thead th {
            color: #8c9eff !important; 
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* Soften the row transitions */
        .table-hover tbody tr:hover {
            background-color: rgba(67, 94, 190, 0.03) !important;
            transition: background-color 0.2s ease;
        }
    </style>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

<section class="section">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h4 class="fw-bold mb-1">Shared Email Accounts</h4>
                <p class="text-muted small mb-0">Master accounts used to manage shared software and cloud services.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button data-bs-toggle="modal" data-bs-target="#add-account-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> ADD ACCOUNT
                </button>
            </div>
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="table1">
                    <thead>
                        <tr>
                            <th>Account Email</th>
                            <th>Primary Purpose</th>
                            <th class="text-center">Active Subs</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $acc)
                        <tr>
                            <td class="fw-bold text-heading py-3">{{ $acc->email }}</td>
                            <td class="text-muted py-3">{{ $acc->purpose ?? 'General Use' }}</td>
                            <td class="text-center py-3">
                                <span class="badge {{ $acc->subscriptions_count > 0 ? 'bg-light-primary text-primary' : 'bg-light-secondary text-secondary' }} px-3 rounded-pill" style="font-size: 0.7rem;">
                                    {{ $acc->subscriptions_count }} Apps
                                </span>
                            </td>
                            <td class="text-end py-3">
                                <div class="d-flex justify-content-end gap-2">
                                    <button data-bs-toggle="modal" data-bs-target="#edit-account-modal-{{ $acc->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('accounts.destroy', $acc->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Warning: Deleting this account will also delete all subscriptions tied to it. Proceed?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3" title="Delete">
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
<div class="modal fade" id="add-account-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg hr-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-uppercase" style="font-size: 0.9rem;">Register Master Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('accounts.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small text-uppercase">Email Address</label>
                        <input type="email" name="email" placeholder="e.g. marketing@porta.com" class="form-control form-control-lg fs-6" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">Department / Purpose</label>
                        <input type="text" name="purpose" placeholder="e.g. Design Team Shared Logins" class="form-control form-control-lg fs-6">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">SAVE ACCOUNT</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($accounts as $acc)
<div class="modal fade" id="edit-account-modal-{{ $acc->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg hr-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-uppercase" style="font-size: 0.9rem;">Edit Account Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('accounts.update', $acc->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small text-uppercase">Account Email</label>
                        <input type="email" name="email" value="{{ $acc->email }}" class="form-control form-control-lg fs-6" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">Primary Purpose</label>
                        <input type="text" name="purpose" value="{{ $acc->purpose }}" class="form-control form-control-lg fs-6">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">SAVE CHANGES</button>
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
                "language": {
                    "search": "Filter accounts:",
                    "lengthMenu": "Show _MENU_ entries"
                }
            });
        });
    </script>
@endsection