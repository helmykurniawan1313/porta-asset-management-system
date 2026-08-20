@extends('layouts.app')

@section('title', 'Review Assignments | Porta IT')
@section('header_title', 'Assignments — ' . $reviewCycle->name)

@section('content')
    @if(session('status'))
        <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i>
            @if(session('assignmentResults'))
                {{ session('assignmentResults')['created'] }} created, {{ session('assignmentResults')['skipped'] }} skipped (already existed).
            @else
                Saved successfully.
            @endif
        </div>
    @endif
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
                <table class="table table-hover align-middle">
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
                                    <form action="{{ route('hr.review-cycles.assignments.destroy', [$reviewCycle, $assignment]) }}" method="POST" onsubmit="return confirm('Delete this assignment?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No assignments yet.</td></tr>
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
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">New Assignment(s)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-cycles.assignments.store', $reviewCycle) }}" method="POST" id="assignment-form">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Review Type</label>
                            <select name="review_type" id="review_type" class="form-select" onchange="toggleReviewerField()">
                                <option value="self">Self</option>
                                <option value="peer">Peer</option>
                                <option value="upward">Upward</option>
                                <option value="downward">Downward</option>
                            </select>
                            <small class="text-muted">For "self", the reviewee reviews themselves — no separate reviewer needed.</small>
                        </div>
                        <div class="mb-3" id="reviewer-field">
                            <label class="form-label fw-bold small text-uppercase">Reviewer</label>
                            <select name="reviewer_id" class="form-select">
                                <option value="">-- Select Reviewer --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-uppercase">Reviewee(s)</label>
                            <select name="reviewee_ids[]" class="form-select" multiple size="8" required>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->email }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">CREATE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulk-org-chart-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">Bulk Create from Org Chart</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-cycles.assignments.bulk-org-chart', $reviewCycle) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-muted small">Upward: every staff member reviews their department's Head. Downward: every Head reviews their own staff.</p>
                        <label class="form-label fw-bold small text-uppercase">Review Type</label>
                        <select name="review_type" class="form-select">
                            <option value="upward">Upward</option>
                            <option value="downward">Downward</option>
                        </select>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">GENERATE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleReviewerField() {
            const type = document.getElementById('review_type').value;
            document.getElementById('reviewer-field').style.display = type === 'self' ? 'none' : 'block';
        }
        document.addEventListener('DOMContentLoaded', toggleReviewerField);
    </script>
@endsection
