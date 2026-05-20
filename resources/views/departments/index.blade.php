@extends('layouts.app')

@section('title', 'Manage Departments | Porta IT')
@section('header_title', 'Manage Departments')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
    <style>
        /* 1. Professional Floating Header - NO GRAY */
        #table1 thead th {
            background-color: transparent !important; 
            border-bottom: 2px solid rgba(67, 94, 190, 0.15) !important; /* Subtle Brand Blue line */
            color: #435ebe !important; /* Primary Brand Blue text */
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            padding: 1.2rem 1rem !important;
        }

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
            <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h5 class="card-title mb-1">Organization Structure</h5>
                    <p class="text-muted small mb-0">
                        Total: <span class="text-primary fw-bold">{{ count($departments) }}</span> active departments.
                    </p>
                </div>
                
                <div class="mt-3 mt-md-0">
                    <button data-bs-toggle="modal" data-bs-target="#add-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-building-add me-1"></i> ADD DEPARTMENT
                    </button>
                </div>
            </div>

            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="table1">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th class="text-center">Staff Count</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departments as $dept)
                            <tr>
                                <td class="fw-bold text-heading py-3 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                    {{ $dept->name }}
                                </td>
                                <td class="text-center py-3">
                                    <span class="badge bg-light-primary text-primary px-3 rounded-pill fw-bold" style="font-size: 0.65rem;">
                                        {{ $dept->employees_count }} PERSONNEL
                                    </span>
                                </td>
                                <td class="text-end py-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button data-bs-toggle="modal" data-bs-target="#edit-modal-{{ $dept->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('departments.destroy', $dept->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this department?')">
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
                    <h5 class="modal-title text-white fw-bold">New Department Setup</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('departments.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small text-uppercase">Department Name</label>
                            <input type="text" name="name" class="form-control form-control-lg fs-6" placeholder="e.g. Finance, HR, IT Support" required>
                            <small class="text-muted d-block mt-2">Enter a unique name for the organizational unit.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">SAVE DEPARTMENT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($departments as $dept)
    <div class="modal fade" id="edit-modal-{{ $dept->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">Update Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('departments.update', $dept->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small text-uppercase">Department Name</label>
                            <input type="text" name="name" value="{{ $dept->name }}" class="form-control form-control-lg fs-6" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">UPDATE INFO</button>
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
            $('#table1').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "Filter Departments:",
                    "lengthMenu": "Display _MENU_ units"
                }
            });
        });
    </script>
@endsection