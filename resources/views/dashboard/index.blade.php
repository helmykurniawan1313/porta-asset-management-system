@extends('layouts.app')

@section('title', 'Dashboard | Porta IT')
@section('header_title', 'Dashboard')

@section('content')
    <div class="row g-4">
        {{-- Profile card --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 text-center">
                    <div class="profile-avatar-lg bg-light-primary text-primary rounded-circle mx-auto mb-3">
                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                    </div>
                    <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                    <p class="text-muted small mb-3">{{ $user->email }}</p>

                    <div class="d-flex justify-content-center gap-2 flex-wrap mb-4">
                        @if($user->resigned_at)
                            <span class="badge bg-light-danger text-danger px-3 rounded-pill fw-bold">RESIGNED</span>
                        @else
                            <span class="badge bg-light-success text-success px-3 rounded-pill fw-bold">ACTIVE</span>
                        @endif
                        @if($user->department)
                            <span class="badge bg-light-primary text-primary px-3 rounded-pill fw-bold">{{ $user->department->name }}</span>
                        @endif
                    </div>

                    <div class="text-start border-top pt-3">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted small">Job Level</span>
                            <span class="fw-semibold small">{{ $user->job_level ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted small">Joined</span>
                            <span class="fw-semibold small">{{ $user->joined_at?->format('d M Y') ?? '—' }}</span>
                        </div>
                    </div>

                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                        <i class="bi bi-pencil me-1"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        {{-- Pending review tasks --}}
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">Tasks Waiting on You</h5>
                        <p class="text-muted small mb-0">Reviews you still need to fill out.</p>
                    </div>
                    @if($pendingReviews->isNotEmpty())
                        <span class="badge bg-light-danger text-danger px-3 rounded-pill fw-bold">{{ $pendingReviews->count() }}</span>
                    @endif
                </div>
                <div class="card-body py-4">
                    @if($pendingReviews->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                            You're all caught up — no pending reviews.
                        </div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($pendingReviews as $assignment)
                                <div class="d-flex justify-content-between align-items-center p-3 rounded task-row">
                                    <div>
                                        <p class="fw-semibold mb-1">
                                            {{ $assignment->reviewCycle->name }}
                                            <span class="badge bg-light-info text-info text-capitalize ms-1" style="font-size: 0.65rem;">{{ $assignment->review_type }}</span>
                                        </p>
                                        <p class="text-muted small mb-0">
                                            Reviewing: {{ $assignment->reviewee->id === $assignment->reviewer_id ? 'Yourself' : $assignment->reviewee->name }}
                                        </p>
                                    </div>
                                    <a href="{{ route('my-reviews.show', $assignment->id) }}" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                                        Fill Out
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Assigned hardware --}}
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h5 class="card-title mb-1">My Hardware</h5>
                    <p class="text-muted small mb-0">Assets currently assigned to you.</p>
                </div>
                <div class="card-body py-4">
                    @if($user->devices->isEmpty())
                        <p class="text-muted small text-center py-4 mb-0">No devices assigned.</p>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($user->devices as $device)
                                <div class="d-flex justify-content-between align-items-center p-3 rounded task-row">
                                    <div>
                                        <p class="fw-semibold mb-0">{{ $device->name }}</p>
                                        <p class="text-muted small mb-0">{{ $device->category }} &middot; {{ $device->manufacturer ?? '—' }}</p>
                                    </div>
                                    <a href="{{ route('inventory.show', $device->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Assigned software / subscriptions --}}
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h5 class="card-title mb-1">My Software</h5>
                    <p class="text-muted small mb-0">Subscriptions allocated to you.</p>
                </div>
                <div class="card-body py-4">
                    @if($user->softwareAllocations->isEmpty())
                        <p class="text-muted small text-center py-4 mb-0">No software allocated.</p>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($user->softwareAllocations as $allocation)
                                <div class="d-flex justify-content-between align-items-center p-3 rounded task-row">
                                    <div>
                                        <p class="fw-semibold mb-0">{{ $allocation->subscription->label ?? '—' }}</p>
                                        <p class="text-muted small mb-0">{{ $allocation->subscription->period ?? '' }}</p>
                                    </div>
                                    <a href="{{ route('subscriptions.show', $allocation->subscription_id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .profile-avatar-lg {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 700;
        }
        .task-row {
            background-color: var(--porta-surface-muted);
            border: 1px solid var(--porta-border);
        }
    </style>
@endsection
