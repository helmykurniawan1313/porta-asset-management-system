@extends('layouts.app')

@section('title', 'Employee Review History | Porta IT')
@section('header_title', 'Review History — ' . $employee['name'])

@section('content')
    <a href="{{ route('hr.employee-reviews.index') }}" class="btn btn-sm btn-light-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Employee Reviews
    </a>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('hr.employee-reviews.report', $employee['id']) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i> View Consolidated Report
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Reviewer</th>
                            <th>Cycle</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->reviewer->name }}</td>
                            <td>{{ $assignment->reviewCycle->name }}</td>
                            <td><span class="badge bg-light-info text-info text-capitalize">{{ $assignment->review_type }}</span></td>
                            <td>
                                @php
                                    $statusColors = ['pending' => 'secondary', 'in_progress' => 'warning', 'submitted' => 'success'];
                                @endphp
                                <span class="badge bg-light-{{ $statusColors[$assignment->status] ?? 'secondary' }} text-{{ $statusColors[$assignment->status] ?? 'secondary' }} text-capitalize">
                                    {{ str_replace('_', ' ', $assignment->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No reviews received yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
