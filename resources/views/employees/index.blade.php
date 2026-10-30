@extends('layouts.app')

@section('title', 'Manage Employees | Porta IT')
@section('header_title', 'Manage Employees')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
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
                        Total: <span class="text-primary fw-bold">{{ $employees->count() }}</span> personnel.
                    </p>
                </div>

                <div class="mt-3 mt-md-0">
                    <button data-bs-toggle="modal" data-bs-target="#add-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-person-plus me-1"></i> Add Employee
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
                                <th>Position</th>
                                @hasrole('Super Admin')
                                <th>Role</th>
                                @endhasrole
                                <th>Status</th>
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
                                        {{ $emp->department->name ?? 'Unassigned' }}
                                    </span>
                                </td>
                                <td class="text-muted py-3">
                                    {{ $emp->department->name ?? '-' }}
                                    @if($emp->job_level)
                                        <span class="text-muted small d-block">{{ $emp->job_level }}</span>
                                    @endif
                                </td>
                                @hasrole('Super Admin')
                                <td class="py-3">
                                    <span class="badge bg-light-secondary text-secondary px-3 rounded-pill" style="font-size: 0.65rem;">
                                        {{ $emp->getRoleNames()->join(', ') ?: 'No Role' }}
                                    </span>
                                </td>
                                @endhasrole
                                <td class="py-3">
                                    @if($emp->resigned_at)
                                        <span class="badge bg-light-danger text-danger px-3 rounded-pill" style="font-size: 0.65rem;">Resigned {{ $emp->resigned_at->format('d/m/Y') }}</span>
                                    @else
                                        <span class="badge bg-light-success text-success px-3 rounded-pill" style="font-size: 0.65rem;">Active</span>
                                    @endif
                                    <div class="text-muted small mt-1" style="font-size: 0.7rem;">
                                        {{ $emp->joined_at ? 'Joined ' . $emp->joined_at->format('d/m/Y') : 'No join date' }}
                                    </div>
                                </td>
                                <td class="text-end py-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('employees.show', $emp->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3" title="View Profile">
                                            <i class="bi bi-person-badge"></i>
                                        </a>
                                        <button data-bs-toggle="modal" data-bs-target="#edit-modal" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit"
                                            data-action="{{ route('employees.update', $emp->id) }}"
                                            data-edit="{{ json_encode(['name' => $emp->name, 'email' => $emp->email, 'department_id' => $emp->department_id, 'job_level' => $emp->job_level, 'role' => $emp->getRoleNames()->first(), 'joined_at' => $emp->joined_at?->format('Y-m-d'), 'resigned_at' => $emp->resigned_at?->format('Y-m-d')]) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Delete employee?')">
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
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">New Personnel Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('employees.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-4">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-lg fs-6" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg fs-6" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg fs-6" minlength="8" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Department Allocation</label>
                            <select name="department_id" class="form-select form-select-lg fs-6">
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @hasrole('Super Admin')
                        <div class="mb-4">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select form-select-lg fs-6" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role }}" {{ $role === 'Employee' ? 'selected' : '' }}>{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <p class="text-muted small mb-4">New employees are created with the baseline "Employee" role. Only a Super Admin can assign a different role.</p>
                        @endhasrole
                        <div class="mb-4">
                            <label class="form-label">Job Level</label>
                            <select name="job_level" class="form-select form-select-lg fs-6">
                                <option value="">-- None --</option>
                                @foreach(['Intern', 'Probation', 'Junior Associate', 'Associate', 'Senior Associate'] as $level)
                                    <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Date of Join</label>
                                <input type="date" name="joined_at" class="form-control form-control-lg fs-6">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Date of Resign</label>
                                <input type="date" name="resigned_at" class="form-control form-control-lg fs-6">
                                <small class="text-muted d-block mt-1">Leave blank if still active.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Register Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- One edit modal for every row: filled from the clicked Edit button (see scripts). --}}
    <div class="modal fade" id="edit-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Employee Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="edit-form">
                    @csrf @method('PUT')
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-4">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-lg fs-6" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg fs-6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Department Allocation</label>
                            <select name="department_id" class="form-select form-select-lg fs-6">
                                <option value="">-- No Department --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Job Level</label>
                            <select name="job_level" class="form-select form-select-lg fs-6">
                                <option value="">-- None --</option>
                                @foreach(['Intern', 'Probation', 'Junior Associate', 'Associate', 'Senior Associate'] as $level)
                                    <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        @hasrole('Super Admin')
                        <div class="mb-4">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select form-select-lg fs-6">
                                @foreach($roles as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endhasrole
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Date of Join</label>
                                <input type="date" name="joined_at" class="form-control form-control-lg fs-6">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Date of Resign</label>
                                <input type="date" name="resigned_at" class="form-control form-control-lg fs-6">
                                <small class="text-muted d-block mt-1">Leave blank if still active.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/extensions/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        // Shared edit modal: load the clicked row's values and point the form at that employee.
        document.getElementById('edit-modal').addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (btn?.dataset.edit) window.fillForm(document.getElementById('edit-form'), JSON.parse(btn.dataset.edit), btn.dataset.action);
        });

        $(document).ready(function() {
            $('#table1').DataTable({
                "pageLength": 15,
                "lengthMenu": [15, 25, 50, 100],
                "language": window.PORTA_DT_LANGUAGE
            });
        });
    </script>
@endsection
