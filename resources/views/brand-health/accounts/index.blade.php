@extends('layouts.app')

@section('title', 'Brand Accounts | Porta IT')
@section('header_title', 'Brand Accounts')

@section('content')
    @if(session('status'))
        <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i> {{ session('status') }}
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
                    <h5 class="card-title mb-1">Brand Accounts</h5>
                    <p class="text-muted small mb-0">
                        Total: <span class="text-primary fw-bold">{{ $accounts->total() }}</span> accounts.
                    </p>
                </div>
                @if($canManage)
                <div class="mt-3 mt-md-0">
                    <button data-bs-toggle="modal" data-bs-target="#add-account-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> ADD ACCOUNT
                    </button>
                </div>
                @endif
            </div>
            <div class="card-body py-4">
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search account name...">
                    </div>
                    <div class="col-md-3">
                        <select name="project_manager_id" class="form-select form-select-sm">
                            <option value="">-- All PMs --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($projectManagerId == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-light-primary w-100">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="table1">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Project Manager</th>
                                <th>Cycles</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td>
                                        <a href="{{ route('brand-health.accounts.growth', $account) }}" class="fw-semibold text-decoration-none">{{ $account->name }}</a>
                                    </td>
                                    <td>
                                        @if($account->projectManager)
                                            <span class="badge bg-light-primary text-primary rounded-pill px-3">{{ $account->projectManager->name }}</span>
                                        @else
                                            <span class="badge bg-light-secondary text-secondary rounded-pill px-3">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $account->cycles_count }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('brand-health.accounts.growth', $account) }}" class="btn btn-sm btn-light-primary rounded-pill px-3">
                                            <i class="bi bi-graph-up"></i> Growth
                                        </a>
                                        @if($canManage)
                                        <button type="button" class="btn btn-sm btn-light-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#edit-account-modal-{{ $account->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light-danger rounded-pill px-3"
                                            onclick="confirmDelete(document.getElementById('delete-account-form-{{ $account->id }}'), 'Delete account {{ $account->name }}? This also deletes all its cycles.', 'Delete Account')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No accounts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $accounts->links() }}
                </div>
            </div>
        </div>
    </section>

    @if($canManage)
    {{-- Add Account modal --}}
    <div class="modal fade" id="add-account-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('brand-health.accounts.store') }}" class="modal-content hr-modal">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Add Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Account Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Project Manager</label>
                        <select name="project_manager_id" class="form-select">
                            <option value="">-- Unassigned --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Add Account</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@if($canManage)
@section('scripts')
    @foreach($accounts as $account)
    <div class="modal fade" id="edit-account-modal-{{ $account->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('brand-health.accounts.update', $account) }}" class="modal-content hr-modal">
                @csrf
                @method('PUT')
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Account Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $account->name }}" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Project Manager</label>
                        <select name="project_manager_id" class="form-select">
                            <option value="">-- Unassigned --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($account->project_manager_id == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save</button>
                </div>
            </form>
        </div>
    </div>
    <form id="delete-account-form-{{ $account->id }}" method="POST" action="{{ route('brand-health.accounts.destroy', $account) }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endforeach
@endsection
@endif
