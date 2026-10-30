@extends('layouts.app')

@section('title', 'Manage Departments | Porta IT')
@section('header_title', 'Manage Departments')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
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
                        <i class="bi bi-building-add me-1"></i> Add Department
                    </button>
                </div>
            </div>

            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="table1">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>Head of Department</th>
                                <th class="text-center">Staff Count</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topLevelDepartments as $dept)
                            <tr>
                                <td class="fw-bold text-heading">
                                    {{ $dept->name }}
                                </td>
                                <td class="py-3">
                                    {{ $dept->headOfDepartment->name ?? '-' }}
                                </td>
                                <td class="text-center py-3">
                                    <span class="badge bg-light-primary text-primary px-3 rounded-pill fw-bold" style="font-size: 0.65rem;">
                                        {{ $dept->users_count }} {{ \Illuminate\Support\Str::plural('person', $dept->users_count) }}
                                    </span>
                                </td>
                                <td class="text-end py-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button data-bs-toggle="modal" data-bs-target="#edit-modal-{{ $dept->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('departments.destroy', $dept->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Delete this department?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @foreach($dept->children as $child)
                            <tr>
                                <td class="text-heading ps-5">
                                    <i class="bi bi-arrow-return-right text-muted me-1"></i>{{ $child->name }}
                                </td>
                                <td class="py-3 text-muted small">— reports to {{ $dept->name }}</td>
                                <td class="text-center py-3">
                                    <span class="badge bg-light-primary text-primary px-3 rounded-pill fw-bold" style="font-size: 0.65rem;">
                                        {{ $child->users_count }} {{ \Illuminate\Support\Str::plural('person', $child->users_count) }}
                                    </span>
                                </td>
                                <td class="text-end py-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button data-bs-toggle="modal" data-bs-target="#edit-modal-{{ $child->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('departments.destroy', $child->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Delete this department?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
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
                    <h5 class="modal-title fw-bold">New Department Setup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('departments.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-4">
                            <label class="form-label">Department Name</label>
                            <input type="text" name="name" class="form-control form-control-lg fs-6" placeholder="e.g. Finance, HR, IT Support" required>
                            <small class="text-muted d-block mt-2">Enter a unique name for the organizational unit.</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Parent Department</label>
                            <select name="parent_id" class="form-select form-select-lg fs-6">
                                <option value="">-- None (top-level department) --</option>
                                @foreach($topLevelDepartments as $top)
                                    <option value="{{ $top->id }}">{{ $top->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-2">Leave empty for a standalone department like "Design". Choose a parent to make this a sub-department, like "Finance" under "Operational".</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Save Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($departments as $dept)
    <div class="modal fade" id="edit-modal-{{ $dept->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('departments.update', $dept->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-4">
                            <label class="form-label">Department Name</label>
                            <input type="text" name="name" value="{{ $dept->name }}" class="form-control form-control-lg fs-6" required>
                        </div>

                        @if($dept->children->isEmpty())
                        <div class="mb-4">
                            <label class="form-label">Parent Department</label>
                            <select name="parent_id" class="form-select form-select-lg fs-6">
                                <option value="">-- None (top-level department) --</option>
                                @foreach($topLevelDepartments->where('id', '!=', $dept->id) as $top)
                                    <option value="{{ $top->id }}" {{ $dept->parent_id == $top->id ? 'selected' : '' }}>{{ $top->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="parent_id" value="">
                        <p class="text-muted small mb-4">This department has sub-departments, so it can't become a sub-department itself.</p>
                        @endif

                        @if(is_null($dept->parent_id))
                        <div class="mb-2">
                            <label class="form-label">Head of Department</label>
                            <select name="head_of_department_id" class="form-select form-select-lg fs-6">
                                <option value="">-- None --</option>
                                @foreach($dept->allUsers()->get() as $u)
                                    <option value="{{ $u->id }}" {{ $dept->head_of_department_id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-2">Only top-level departments have a Head. This Head reviews/oversees this department and all its sub-departments.</small>
                        </div>
                        @else
                        <p class="text-muted small mb-0">Sub-departments don't have their own Head — staff here report up to {{ $dept->parent->name ?? 'the parent department' }}'s Head.</p>
                        @endif
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Update Info</button>
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
                "language": window.PORTA_DT_LANGUAGE
            });
        });
    </script>
@endsection