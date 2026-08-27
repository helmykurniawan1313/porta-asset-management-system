@extends('layouts.app')

@section('title', 'Employee Reviews | Porta IT')
@section('header_title', 'Employee Review Summaries')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="card-title mb-1">Per-Employee Review Summary</h5>
                <p class="text-muted small mb-0">Total: <span class="text-primary fw-bold">{{ $employees->total() }}</span> employees.</p>
            </div>
            <a href="{{ route('hr.employee-reviews.download-all') }}" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                <i class="bi bi-file-earmark-excel me-1"></i> EXPORT ALL (XLSX)
            </a>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Pending</th>
                            <th class="text-center">In Progress</th>
                            <th class="text-center">Submitted</th>
                            <th class="text-center">Avg Rating</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaries as $s)
                        <tr>
                            <td class="fw-bold">{{ $s['name'] }}</td>
                            <td>{{ $s['department'] ?? '-' }}</td>
                            <td class="text-center">{{ $s['total'] }}</td>
                            <td class="text-center">{{ $s['pending'] }}</td>
                            <td class="text-center">{{ $s['in_progress'] }}</td>
                            <td class="text-center">{{ $s['submitted'] }}</td>
                            <td class="text-center">{{ $s['average_rating'] ?? '—' }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hr.employee-reviews.show', $s['id']) }}" class="btn btn-sm btn-light-primary rounded-pill px-3">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('hr.employee-reviews.report', $s['id']) }}" class="btn btn-sm btn-light-info rounded-pill px-3">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($employees->hasPages())
                <div class="mt-3">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
