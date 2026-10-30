@extends('layouts.app')

@section('title', 'Brand Health Cycles | Porta IT')
@section('header_title', 'Brand Health — Cycles')

@section('content')
    @if(session('status'))
        <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i> {{ session('status') }}
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Account</label>
                    <select name="account_id" class="form-select">
                        <option value="">All</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" @selected($filters['account_id'] == $acc->id)>{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Health Label</label>
                    <select name="health_label" class="form-select">
                        <option value="">All</option>
                        @foreach($healthLabels as $label)
                            <option value="{{ $label }}" @selected($filters['health_label'] && in_array($label, $filters['health_label']))>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Month From</label>
                    <input type="month" name="month_from" class="form-control" value="{{ $filters['month_from'] }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Apply</button>
                </div>
            </form>
        </div>
    </div>

    @if($canManage)
    <div class="mb-3 text-end">
        <a href="{{ route('brand-health.cycles.pdf', request()->query()) }}" class="btn btn-light-secondary"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create-cycle-modal"><i class="bi bi-plus-lg"></i> Add Cycle</button>
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Platform</th>
                            <th>Period</th>
                            <th>Followers</th>
                            <th>Health</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycles as $row)
                            @php [$cycle, $scores] = [$row['cycle'], $row['scores']]; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $cycle->account->name }}</td>
                                <td class="text-capitalize">{{ $cycle->platform }}</td>
                                <td class="small">{{ $cycle->cycle_start_date->format('M j') }} – {{ $cycle->cycle_end_date->format('M j, Y') }}</td>
                                <td>{{ number_format($cycle->start_follower) }} → {{ number_format($cycle->end_follower) }}</td>
                                <td>
                                    <span class="badge bg-light-primary text-primary rounded-pill px-3">{{ $scores['health_label'] }} ({{ round($scores['health_rate'], 1) }})</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('brand-health.cycles.pdf-single', $cycle) }}" class="btn btn-sm btn-light-secondary rounded-pill px-3"><i class="bi bi-file-earmark-pdf"></i></a>
                                    @if($canManage)
                                    <form method="POST" action="{{ route('brand-health.cycles.destroy', $cycle) }}" class="d-inline" id="delete-cycle-{{ $cycle->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-light-danger rounded-pill px-3"
                                            onclick="confirmDelete(document.getElementById('delete-cycle-{{ $cycle->id }}'), 'Delete this cycle?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No cycles found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $cycles->links() }}
        </div>
    </div>
@endsection

@if($canManage)
<div class="modal fade" id="create-cycle-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('brand-health.cycles.store') }}" class="modal-content hr-modal">
            @csrf
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Add Cycle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Account</label>
                        <select name="account_id" class="form-select" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Platform</label>
                        <select name="platform" class="form-select" required>
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Cycle Start</label>
                        <input type="date" name="cycle_start_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Cycle End</label>
                        <input type="date" name="cycle_end_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Start Followers</label>
                        <input type="number" name="start_follower" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">End Followers</label>
                        <input type="number" name="end_follower" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Reach</label>
                        <input type="number" name="reach" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Views</label>
                        <input type="number" name="views" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Engagement</label>
                        <input type="number" name="engagement" class="form-control" value="0" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Create</button>
            </div>
        </form>
    </div>
</div>
@endif
