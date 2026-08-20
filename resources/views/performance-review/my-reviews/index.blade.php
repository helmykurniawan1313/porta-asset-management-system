@extends('layouts.app')

@section('title', 'My Reviews | Porta IT')
@section('header_title', 'My Reviews')

@section('content')
    @if(session('status') === 'review-submitted')
        <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i> Review submitted. Thank you.
        </div>
    @elseif(session('status') === 'review-saved')
        <div class="alert alert-light-info color-info d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-info-circle me-2"></i> Draft saved.
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-3 text-end">
        <a href="{{ route('my-reviews.report') }}" class="btn btn-sm btn-light-primary">
            <i class="bi bi-bar-chart me-1"></i> View My Consolidated Report
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <h5 class="card-title mb-1">Reviews Assigned To Me</h5>
            <p class="text-muted small mb-0">
                Total: <span class="text-primary fw-bold">{{ $assignments->count() }}</span> assignments.
            </p>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Cycle</th>
                            <th>Reviewee</th>
                            <th>Type</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                        <tr>
                            <td class="py-3">{{ $assignment->reviewCycle->name }}</td>
                            <td class="py-3">
                                {{ $assignment->reviewee->id === $assignment->reviewer_id ? 'Myself' : $assignment->reviewee->name }}
                            </td>
                            <td class="py-3 text-capitalize">{{ $assignment->review_type }}</td>
                            <td class="text-center py-3">
                                @php
                                    $statusColors = ['pending' => 'secondary', 'in_progress' => 'warning', 'submitted' => 'success'];
                                @endphp
                                <span class="badge bg-light-{{ $statusColors[$assignment->status] ?? 'secondary' }} text-{{ $statusColors[$assignment->status] ?? 'secondary' }} px-3 rounded-pill fw-bold text-uppercase" style="font-size: 0.65rem;">
                                    {{ str_replace('_', ' ', $assignment->status) }}
                                </span>
                            </td>
                            <td class="text-end py-3">
                                <a href="{{ route('my-reviews.show', $assignment->id) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                    {{ $assignment->status === 'submitted' ? 'View' : 'Fill Out' }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">You have no review assignments right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
