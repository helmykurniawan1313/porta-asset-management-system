@extends('layouts.app')

@section('title', 'Clients | Porta IT')
@section('header_title', 'Clients')

@section('styles')
    @include('client-ops.partials.am-search-styles')
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
                    <h5 class="card-title mb-1">Clients</h5>
                    <p class="text-muted small mb-0">
                        Total: <span class="text-primary fw-bold">{{ $clients->count() }}</span> clients.
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button data-bs-toggle="modal" data-bs-target="#add-client-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-person-plus me-1"></i> ADD CLIENT
                    </button>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Company</th>
                                <th>Account Manager</th>
                                <th>Contracts</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                                @php $am = $client->assignments->first()?->user; @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('clients.show', $client) }}" class="fw-semibold text-decoration-none">{{ $client->name }}</a>
                                        <div class="text-muted small">{{ $client->contact_email }}</div>
                                    </td>
                                    <td>{{ $client->company ?? '—' }}</td>
                                    <td>
                                        @if($am)
                                            <span class="badge bg-light-primary text-primary rounded-pill px-3">{{ $am->name }}</span>
                                        @else
                                            <span class="badge bg-light-danger text-danger rounded-pill px-3">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $client->contracts_count }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-light-primary rounded-pill px-3">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No clients yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- Add Client modal --}}
    <div class="modal fade" id="add-client-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('clients.store') }}" class="modal-content hr-modal">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Add Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Client Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Company</label>
                        <input type="text" name="company" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Account Manager</label>
                        <div class="am-select position-relative">
                            <input type="hidden" name="account_manager_id" id="add-client-am-id">
                            <input type="text" id="add-client-am-search" class="form-control" placeholder="-- Unassigned --" autocomplete="off">
                            <div id="add-client-am-options" class="am-options"></div>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Add Client</button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
        @include('client-ops.partials.am-search-script', [
            'hiddenId' => 'add-client-am-id',
            'searchId' => 'add-client-am-search',
            'optionsId' => 'add-client-am-options',
        ])
    @endsection
@endsection
