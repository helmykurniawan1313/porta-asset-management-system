@extends('layouts.app')

@section('title', 'Review Cycles | Porta IT')
@section('header_title', 'Review Cycles')

@section('content')
    @if(session('status'))
        <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i> Saved successfully.
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h5 class="card-title mb-1">Performance Review Cycles</h5>
                <p class="text-muted small mb-0">Total: <span class="text-primary fw-bold">{{ $reviewCycles->total() }}</span> cycles.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button data-bs-toggle="modal" data-bs-target="#add-cycle-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> NEW CYCLE
                </button>
            </div>
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Starts</th>
                            <th>Ends</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviewCycles as $cycle)
                        <tr>
                            <td class="fw-bold">{{ $cycle->name }}</td>
                            <td>{{ $cycle->starts_at->format('d M Y') }}</td>
                            <td>{{ $cycle->ends_at->format('d M Y') }}</td>
                            <td>
                                @php
                                    $statusColors = ['draft' => 'secondary', 'open' => 'success', 'closed' => 'danger'];
                                @endphp
                                <span class="badge bg-light-{{ $statusColors[$cycle->status] ?? 'secondary' }} text-{{ $statusColors[$cycle->status] ?? 'secondary' }} px-3 rounded-pill fw-bold text-uppercase" style="font-size: 0.65rem;">
                                    {{ $cycle->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hr.review-cycles.templates.index', $cycle) }}" class="btn btn-sm btn-light-info rounded-pill px-3" title="Templates">
                                        <i class="bi bi-card-checklist"></i>
                                    </a>
                                    <a href="{{ route('hr.review-cycles.assignments.index', $cycle) }}" class="btn btn-sm btn-light-primary rounded-pill px-3" title="Assignments">
                                        <i class="bi bi-people"></i>
                                    </a>
                                    <button data-bs-toggle="modal" data-bs-target="#edit-cycle-{{ $cycle->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('hr.review-cycles.destroy', $cycle) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this review cycle?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No review cycles yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $reviewCycles->links() }}</div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="add-cycle-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">New Review Cycle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('hr.review-cycles.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Starts At</label>
                            <input type="datetime-local" name="starts_at" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Ends At</label>
                            <input type="datetime-local" name="ends_at" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-uppercase">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">SAVE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($reviewCycles as $cycle)
    <div class="modal fade" id="edit-cycle-{{ $cycle->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">Edit Review Cycle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('hr.review-cycles.update', $cycle) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Name</label>
                            <input type="text" name="name" value="{{ $cycle->name }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Starts At</label>
                            <input type="datetime-local" name="starts_at" value="{{ $cycle->starts_at->format('Y-m-d\TH:i') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Ends At</label>
                            <input type="datetime-local" name="ends_at" value="{{ $cycle->ends_at->format('Y-m-d\TH:i') }}" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-uppercase">Status</label>
                            <select name="status" class="form-select">
                                @foreach(['draft', 'open', 'closed'] as $status)
                                    <option value="{{ $status }}" {{ $cycle->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">UPDATE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
@endsection
