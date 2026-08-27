@extends('layouts.app')

@section('title', 'Review Assignments | Porta IT')
@section('header_title', 'Assignments — ' . $reviewCycle->name)

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
@endsection

@section('content')
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ $errors->first() }}
        </div>
    @endif

    <a href="{{ route('hr.review-cycles.index') }}" class="btn btn-sm btn-light-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Cycles
    </a>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-1">Assignments</h5>
            <div class="d-flex gap-2 flex-wrap">
                <button data-bs-toggle="modal" data-bs-target="#bulk-org-chart-modal" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                    <i class="bi bi-diagram-3 me-1"></i> BULK FROM ORG CHART
                </button>
                <button data-bs-toggle="modal" data-bs-target="#add-assignment-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> NEW ASSIGNMENT
                </button>
            </div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="table1">
                    <thead>
                        <tr>
                            <th>Reviewer</th>
                            <th>Reviewee</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->reviewer->name }}</td>
                            <td>{{ $assignment->reviewee->name }}</td>
                            <td><span class="badge bg-light-info text-info text-capitalize">{{ $assignment->review_type }}</span></td>
                            <td>
                                @php
                                    $statusColors = ['pending' => 'secondary', 'in_progress' => 'warning', 'submitted' => 'success'];
                                @endphp
                                <span class="badge bg-light-{{ $statusColors[$assignment->status] ?? 'secondary' }} text-{{ $statusColors[$assignment->status] ?? 'secondary' }} text-capitalize">
                                    {{ str_replace('_', ' ', $assignment->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hr.review-cycles.assignments.show', [$reviewCycle, $assignment]) }}" class="btn btn-sm btn-light-primary rounded-pill px-3">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('hr.review-cycles.assignments.destroy', [$reviewCycle, $assignment]) }}" method="POST" onsubmit="return confirmDelete(this, 'Delete this assignment?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3"><i class="bi bi-trash"></i></button>
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
@endsection

@section('modals')
    <div class="modal fade" id="add-assignment-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg assignment-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">New Assignment(s)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-cycles.assignments.store', $reviewCycle) }}" method="POST" id="assignment-form">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Review Type</label>
                            <select name="review_type" id="review_type" class="form-select" onchange="toggleReviewerField()">
                                <option value="self">Self</option>
                                <option value="peer">Peer</option>
                                <option value="upward">Upward</option>
                                <option value="downward">Downward</option>
                            </select>
                            <small class="text-muted">For "self", the reviewee reviews themselves — no separate reviewer needed.</small>
                        </div>
                        <div class="mb-4" id="reviewer-field">
                            <label class="form-label fw-bold small text-uppercase text-muted">Reviewer</label>
                            <div class="reviewer-select position-relative">
                                <input type="hidden" name="reviewer_id" id="reviewer_id">
                                <input type="text" id="reviewer-search" class="form-control" placeholder="-- Select Reviewer --" autocomplete="off">
                                <div id="reviewer-options" class="reviewer-options"></div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold small text-uppercase text-muted mb-0">Reviewee(s)</label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="reviewee-select-all">
                                    <label class="form-check-label small fw-bold" for="reviewee-select-all">Select all</label>
                                </div>
                            </div>

                            <div id="reviewee-chips" class="d-flex flex-wrap gap-1 mb-2"></div>

                            <input type="text" id="reviewee-search" class="form-control mb-2" placeholder="Search by name or email…">

                            <div class="reviewee-picker">
                                @php $employeesByDept = $employees->groupBy(fn ($e) => $e->department->name ?? 'No Department'); @endphp
                                @foreach($employeesByDept as $deptName => $deptEmployees)
                                    <div class="reviewee-dept-group">
                                        <div class="reviewee-dept-header">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input reviewee-dept-select-all" id="dept-select-{{ $loop->index }}">
                                                <label class="form-check-label fw-bold small" for="dept-select-{{ $loop->index }}">{{ $deptName }}</label>
                                            </div>
                                        </div>
                                        <div class="reviewee-dept-body">
                                            @foreach($deptEmployees as $emp)
                                                <div class="form-check reviewee-row" data-search="{{ strtolower($emp->name.' '.$emp->email) }}">
                                                    <input type="checkbox" class="form-check-input reviewee-checkbox" name="reviewee_ids[]" value="{{ $emp->id }}" id="reviewee-{{ $emp->id }}" data-name="{{ $emp->name }}">
                                                    <label class="form-check-label" for="reviewee-{{ $emp->id }}">
                                                        {{ $emp->name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                <p id="reviewee-no-results" class="text-muted small text-center py-3 mb-0" style="display:none;">No matching employees.</p>
                            </div>
                            <small class="text-muted">Tick individuals, or use "Select all" at the top of a department.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulk-org-chart-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg assignment-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Bulk Create from Org Chart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-cycles.assignments.bulk-org-chart', $reviewCycle) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-muted small">Upward: every staff member reviews their department's Head. Downward: every Head reviews their own staff.</p>
                        <label class="form-label fw-bold small text-uppercase text-muted">Review Type</label>
                        <select name="review_type" class="form-select">
                            <option value="upward">Upward</option>
                            <option value="downward">Downward</option>
                        </select>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Generate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .assignment-modal {
            border-radius: 1rem;
            overflow: hidden;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
        }
        .assignment-modal .modal-header {
            padding: 1.5rem 1.5rem 0;
        }
        .assignment-modal .modal-footer {
            border-top: 1px solid var(--bs-border-color);
            background-color: var(--bs-tertiary-bg, var(--bs-body-bg)) !important;
        }
        .reviewee-picker {
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.6rem;
        }
        .reviewee-chip {
            background: var(--porta-accent-soft, rgba(79, 70, 229, 0.1));
            color: var(--porta-accent, #4f46e5);
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.35rem 0.6rem;
        }
        .reviewee-chip i:hover {
            opacity: 0.7;
        }
        .reviewer-options {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 20;
            max-height: 220px;
            overflow-y: auto;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            margin-top: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .reviewer-options.show {
            display: block;
        }
        .reviewer-option {
            padding: 0.5rem 0.9rem;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .reviewer-option:hover,
        .reviewer-option.active {
            background: var(--porta-accent-soft, rgba(79, 70, 229, 0.1));
        }
        .reviewer-option.no-results {
            color: var(--bs-secondary-color);
            cursor: default;
        }
        .reviewee-dept-group + .reviewee-dept-group {
            border-top: 1px solid var(--bs-border-color);
        }
        .reviewee-dept-header {
            background: var(--bs-tertiary-bg, var(--bs-secondary-bg));
            padding: 0.5rem 0.9rem;
        }
        .reviewee-dept-body {
            padding: 0.4rem 0.9rem 0.6rem 1.6rem;
        }
        .reviewee-dept-body .form-check {
            padding-top: 0.15rem;
            padding-bottom: 0.15rem;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('assets/extensions/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('#table1').DataTable({
                "pageLength": 15,
                "language": {
                    "search": "Filter assignments:",
                    "lengthMenu": "Display _MENU_ entries"
                }
            });
        });
    </script>

    <script>
        function toggleReviewerField() {
            const type = document.getElementById('review_type').value;
            document.getElementById('reviewer-field').style.display = type === 'self' ? 'none' : 'block';
        }
        document.addEventListener('DOMContentLoaded', toggleReviewerField);

        document.addEventListener('DOMContentLoaded', function () {
            const reviewerList = @json($employees->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->values());

            const hiddenInput = document.getElementById('reviewer_id');
            const searchInput = document.getElementById('reviewer-search');
            const optionsBox = document.getElementById('reviewer-options');

            function renderOptions(term) {
                const filtered = reviewerList.filter(e => e.name.toLowerCase().includes(term.toLowerCase()));
                optionsBox.innerHTML = '';

                if (filtered.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'reviewer-option no-results';
                    empty.textContent = 'No matching employees.';
                    optionsBox.appendChild(empty);
                    return;
                }

                filtered.forEach(function (emp) {
                    const option = document.createElement('div');
                    option.className = 'reviewer-option';
                    option.textContent = emp.name;
                    option.addEventListener('click', function () {
                        hiddenInput.value = emp.id;
                        searchInput.value = emp.name;
                        optionsBox.classList.remove('show');
                    });
                    optionsBox.appendChild(option);
                });
            }

            searchInput.addEventListener('focus', function () {
                renderOptions(searchInput.value);
                optionsBox.classList.add('show');
            });

            searchInput.addEventListener('input', function () {
                hiddenInput.value = '';
                renderOptions(searchInput.value);
                optionsBox.classList.add('show');
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.reviewer-select')) {
                    optionsBox.classList.remove('show');
                }
            });
        });

        @if(session('assignmentResults'))
            document.addEventListener('DOMContentLoaded', function () {
                window.showToast(@json(session('assignmentResults')['created'] . ' created, ' . session('assignmentResults')['skipped'] . ' skipped (already existed).'), 'success');
            });
        @endif

        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('reviewee-select-all');
            const allCheckboxes = Array.prototype.slice.call(document.querySelectorAll('.reviewee-checkbox'));
            const deptGroups = Array.prototype.slice.call(document.querySelectorAll('.reviewee-dept-group'));
            const chipsContainer = document.getElementById('reviewee-chips');
            const searchInput = document.getElementById('reviewee-search');
            const noResults = document.getElementById('reviewee-no-results');

            function refreshSelectAll() {
                selectAll.checked = allCheckboxes.length > 0 && allCheckboxes.every(cb => cb.checked);
            }

            function refreshDeptSelectAll(group) {
                const deptSelectAll = group.querySelector('.reviewee-dept-select-all');
                const deptCheckboxes = Array.prototype.slice.call(group.querySelectorAll('.reviewee-checkbox'));
                deptSelectAll.checked = deptCheckboxes.length > 0 && deptCheckboxes.every(cb => cb.checked);
            }

            function refreshChips() {
                chipsContainer.innerHTML = '';

                allCheckboxes.filter(cb => cb.checked).forEach(function (cb) {
                    const chip = document.createElement('span');
                    chip.className = 'reviewee-chip badge rounded-pill d-inline-flex align-items-center gap-1';
                    chip.innerHTML = '<span></span><i class="bi bi-x-lg" style="cursor:pointer; font-size:0.65rem;"></i>';
                    chip.querySelector('span').textContent = cb.dataset.name;
                    chip.querySelector('i').addEventListener('click', function () {
                        cb.checked = false;
                        cb.dispatchEvent(new Event('change'));
                    });
                    chipsContainer.appendChild(chip);
                });
            }

            selectAll.addEventListener('change', function () {
                allCheckboxes.forEach(cb => cb.checked = selectAll.checked);
                deptGroups.forEach(refreshDeptSelectAll);
                refreshChips();
            });

            deptGroups.forEach(function (group) {
                const deptSelectAll = group.querySelector('.reviewee-dept-select-all');
                const deptCheckboxes = Array.prototype.slice.call(group.querySelectorAll('.reviewee-checkbox'));

                deptSelectAll.addEventListener('change', function () {
                    deptCheckboxes.forEach(cb => cb.checked = deptSelectAll.checked);
                    refreshSelectAll();
                    refreshChips();
                });

                deptCheckboxes.forEach(function (cb) {
                    cb.addEventListener('change', function () {
                        refreshDeptSelectAll(group);
                        refreshSelectAll();
                        refreshChips();
                    });
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = searchInput.value.trim().toLowerCase();
                    let anyVisible = false;

                    deptGroups.forEach(function (group) {
                        const rows = Array.prototype.slice.call(group.querySelectorAll('.reviewee-row'));
                        let groupHasMatch = false;

                        rows.forEach(function (row) {
                            const matches = term === '' || row.dataset.search.includes(term);
                            row.style.display = matches ? '' : 'none';
                            if (matches) groupHasMatch = true;
                        });

                        group.style.display = groupHasMatch ? '' : 'none';
                        if (groupHasMatch) anyVisible = true;
                    });

                    noResults.style.display = anyVisible ? 'none' : '';
                });
            }
        });
    </script>
@endsection
