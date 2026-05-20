@extends('layouts.app')

@section('title', 'Manage Employees | Porta IT')
@section('header_title', 'Manage Employees')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
    <style>
        /* 1. Professional Floating Header - NO GRAY */
      

        /* 2. Dark Mode Polish */
        .theme-dark #table1 thead th {
            color: #8c9eff !important; 
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* 3. Row Hover Transitions */
        .table-hover tbody tr:hover {
            background-color: rgba(67, 94, 190, 0.03) !important;
            transition: background-color 0.2s ease;
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
                    <h5 class="card-title mb-1">Employee Directory</h5>
                    <p class="text-muted small mb-0">
                        Total: <span class="text-primary fw-bold">{{ count($employees) }}</span> active personnel.
                    </p>
                </div>
                
                <div class="mt-3 mt-md-0">
                    <button data-bs-toggle="modal" data-bs-target="#add-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-person-plus me-1"></i> ADD EMPLOYEE
                    </button>
                </div>
            </div>

            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="table1">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $emp)
                            <tr>
                                <td class="fw-bold text-heading py-3">{{ $emp->name }}</td>
                                <td class="text-muted py-3">{{ $emp->email ?? '-' }}</td>
                                <td class="py-3">
                                    <span class="badge {{ $emp->department ? 'bg-light-primary text-primary' : 'bg-light-secondary text-secondary' }} px-3 rounded-pill" style="font-size: 0.65rem;">
                                        {{ $emp->department->name ?? 'UNASSIGNED' }}
                                    </span>
                                </td>
                                <td class="text-end py-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('employees.show', $emp->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3" title="View Profile">
                                            <i class="bi bi-person-badge"></i>
                                        </a>
                                        <button data-bs-toggle="modal" data-bs-target="#edit-modal-{{ $emp->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete employee?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modals')
    <div class="modal fade" id="add-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">New Personnel Registration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('employees.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-lg fs-6" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg fs-6">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small text-uppercase">Department Allocation</label>
                            <select name="department_id" class="form-select form-select-lg fs-6">
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">REGISTER EMPLOYEE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($employees as $emp)
    <div class="modal fade" id="edit-modal-{{ $emp->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">Update Employee Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('employees.update', $emp->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Full Name</label>
                            <input type="text" name="name" value="{{ $emp->name }}" class="form-control form-control-lg fs-6" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Email Address</label>
                            <input type="email" name="email" value="{{ $emp->email }}" class="form-control form-control-lg fs-6">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small text-uppercase">Department Allocation</label>
                            <select name="department_id" class="form-select form-select-lg fs-6">
                                <option value="">-- No Department --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ $emp->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">UPDATE PROFILE</button>
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
                    "search": "Quick Filter:",
                    "lengthMenu": "Display _MENU_ users"
                }
            });
        });
    </script>
@endsection