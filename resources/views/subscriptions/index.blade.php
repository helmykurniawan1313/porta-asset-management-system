@extends('layouts.app')

@section('title', 'Porta IT | SaaS & Subscriptions')
@section('header_title', 'SaaS & Subscriptions')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
    <style>
        .app-avatar {
            width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; flex-shrink: 0;
            font-weight: 800; font-size: .95rem; color: #fff;
        }
        .stat-card { border-radius: 16px !important; }
        .stat-card .stat-num { font-size: 1.6rem; font-weight: 800; line-height: 1.1; }
        .stat-card .stat-lbl { font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--porta-text-faint); }
        .status-tabs .btn { border-radius: 999px; font-weight: 700; font-size: .8rem; }
        tr.is-inactive td { opacity: .55; }
        .user-chip {
            display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .55rem .15rem .2rem;
            border-radius: 999px; background: rgba(67, 94, 190, .08); color: inherit; font-size: .78rem; font-weight: 600;
            white-space: nowrap; text-decoration: none;
        }
        .user-chip:hover { background: rgba(67, 94, 190, .16); }
        .user-dot {
            width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center;
            background: #435ebe; color: #fff; font-size: .65rem; font-weight: 800;
        }
        span.user-chip:not(:has(.user-dot)) { padding-left: .55rem; }
        [data-bs-theme="dark"] .user-chip { background: rgba(129, 140, 248, .14); color: #e5e7eb; }
        [data-bs-theme="dark"] .user-dot { background: #6366f1; }

    </style>
@endsection

@section('content')
@php
    $active = $subscriptions->where('status', 'Active');
    $dueSoon = $active->filter(fn ($s) => $s->isRecurring() && $s->days_remaining !== null && $s->days_remaining <= 30);
    // Monthly-equivalent spend of active recurring licenses, per currency (no FX conversion).
    $monthly = $active->filter(fn ($s) => $s->isRecurring() && $s->bill)
        ->groupBy('currency')
        ->map(fn ($g) => $g->sum(fn ($s) => $s->period === 'Yearly' ? $s->bill / 12 : $s->bill));
    $palette = ['#435ebe', '#e11d48', '#0891b2', '#7c3aed', '#059669', '#d97706', '#db2777', '#2563eb'];
    $appColor = fn ($app) => $palette[crc32(strtolower($app ?? '')) % count($palette)];
    $money = fn ($amount, $cur) => $amount === null ? '—' : trim(($cur ?? '').' '.number_format($amount, $cur === 'IDR' ? 0 : 2, $cur === 'IDR' ? ',' : '.', $cur === 'IDR' ? '.' : ','));
@endphp

@if(session('success'))
    <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div class="alert alert-light-danger color-danger shadow-sm mb-4">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card border-0 shadow-sm h-100 mb-0"><div class="card-body">
            <div class="stat-lbl">Active licenses</div>
            <div class="stat-num">{{ $active->count() }}</div>
            <small class="text-muted">{{ $subscriptions->count() - $active->count() }} inactive</small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card border-0 shadow-sm h-100 mb-0"><div class="card-body">
            <div class="stat-lbl">Renewing in 30 days</div>
            <div class="stat-num {{ $dueSoon->count() ? 'text-warning' : '' }}">{{ $dueSoon->count() }}</div>
            <small class="text-muted">{{ $dueSoon->filter(fn ($s) => $s->days_remaining < 0)->count() }} overdue</small>
        </div></div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card stat-card border-0 shadow-sm h-100 mb-0"><div class="card-body">
            <div class="stat-lbl">Monthly spend (active, yearly ÷ 12)</div>
            <div class="d-flex flex-wrap gap-4 mt-1">
                @forelse($monthly as $cur => $total)
                    <div class="stat-num" style="font-size: 1.3rem;">{{ $money($total, $cur) }}</div>
                @empty
                    <div class="stat-num">—</div>
                @endforelse
            </div>
        </div></div>
    </div>
</div>

<section class="section">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h4 class="fw-bold mb-1">Software Licenses</h4>
                <p class="text-muted small mb-0">Every app login, who shares it, and when it renews.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="btn-group status-tabs" role="group">
                    <button type="button" class="btn btn-sm btn-primary" data-status="Active">Active</button>
                    <button type="button" class="btn btn-sm btn-light-secondary" data-status="Inactive">Inactive</button>
                    <button type="button" class="btn btn-sm btn-light-secondary" data-status="">All</button>
                </div>
                <a href="{{ route('accounts.index') }}" class="btn btn-light-primary btn-sm px-3 fw-bold">
                    <i class="bi bi-person-lines-fill me-1"></i> Accounts
                </a>
                <button data-bs-toggle="modal" data-bs-target="#add-sub-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Add License
                </button>
            </div>
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="table1">
                    <thead>
                        <tr>
                            <th>App & Plan</th>
                            <th>Login</th>
                            <th>Used By</th>
                            <th>Cost</th>
                            <th class="text-end">Renewal</th>
                            <th class="d-none">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscriptions as $sub)
                        @php $days = $sub->days_remaining; @endphp
                        <tr class="{{ $sub->status === 'Inactive' ? 'is-inactive' : '' }}">
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="app-avatar" style="background: {{ $appColor($sub->app) }};">{{ strtoupper(substr($sub->app ?? '?', 0, 1)) }}</div>
                                    <div class="min-w-0">
                                        <a href="{{ route('subscriptions.show', $sub->id) }}" class="fw-bold text-heading d-block">{{ $sub->app ?? 'Untitled' }}</a>
                                        <small class="text-muted">{{ $sub->plan ?? 'No plan' }}</small>
                                        @if($sub->status === 'Inactive')<span class="badge bg-light-secondary text-secondary ms-1">Inactive</span>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="small fw-semibold text-break">{{ $sub->account->email ?? '—' }}</div>
                                @if($sub->login_with_google)
                                    <span class="badge bg-light-info text-info mt-1"><i class="bi bi-google"></i> Google</span>
                                @endif
                            </td>
                            @php
                                $users = $sub->allocations->where('assignable_type', \App\Models\User::class)
                                    ->map->assignable->filter()->sortBy('name')->values();
                            @endphp
                            <td class="py-3" style="max-width: 300px;" data-search="{{ $users->pluck('name')->implode(' ') }} {{ $sub->shared_with }}">
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($users->take(3) as $u)
                                        <a href="{{ route('employees.show', $u->id) }}" class="user-chip" title="{{ $u->name }}">
                                            <span class="user-dot">{{ strtoupper(substr($u->name, 0, 1)) }}</span>{{ \Illuminate\Support\Str::words($u->name, 2, '') }}
                                        </a>
                                    @endforeach
                                    @if($users->count() > 3)
                                        <span class="user-chip" title="{{ $users->slice(3)->pluck('name')->implode(', ') }}">+{{ $users->count() - 3 }} more</span>
                                    @endif
                                </div>
                                @if($sub->shared_with)
                                    <small class="text-muted d-block mt-1 text-truncate" title="{{ $sub->shared_with }}"><i class="bi bi-people"></i> {{ $sub->shared_with }}</small>
                                @elseif($users->isEmpty())
                                    <span class="text-muted">—</span>
                                @endif
                                @if($sub->division)<small class="text-muted">{{ $sub->division }}</small>@endif
                            </td>
                            <td class="py-3 text-nowrap" data-order="{{ $sub->bill ?? -1 }}">
                                <div class="fw-bold">{{ $money($sub->bill, $sub->currency) }}</div>
                                <small class="text-muted">{{ $sub->period }}</small>
                            </td>
                            <td class="text-end py-3 text-nowrap" data-order="{{ $days ?? 99999 }}">
                                @if($sub->status === 'Inactive')
                                    <span class="text-muted small">{{ $sub->next_payment_date ? 'Ended '.$sub->next_payment_date->format('d M Y') : '—' }}</span>
                                @elseif($days === null)
                                    <span class="badge bg-light-secondary text-secondary">{{ $sub->period === 'Free' ? 'Free' : 'No renewal' }}</span>
                                @else
                                    <span class="badge {{ $days < 0 ? 'bg-danger' : ($days <= 7 ? 'bg-light-danger text-danger' : ($days <= 30 ? 'bg-light-warning text-warning' : 'bg-light-success text-success')) }} px-3 rounded-pill">
                                        {{ $days < 0 ? abs($days).' days overdue' : ($days === 0 ? 'Today' : $days.' days') }}
                                    </span>
                                    <div class="text-muted small mt-1">{{ $sub->next_payment_date->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td class="d-none">{{ $sub->status }}</td>
                            <td class="text-end py-3">
                                <div class="d-flex justify-content-end gap-2">
                                    @if($sub->status === 'Active' && $sub->isRecurring())
                                        <form action="{{ route('subscriptions.renew', $sub->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Mark {{ e(addslashes($sub->label)) }} as paid?\n\nRenewal: {{ $sub->next_payment_date?->format('d M Y') ?? 'not set' }} → {{ $sub->nextRenewalDate()->format('d M Y') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light-success rounded-pill px-3" title="Paid — renew {{ $sub->period === 'Yearly' ? '1 year' : '1 month' }}">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('subscriptions.show', $sub->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3" title="View"><i class="bi bi-eye"></i></a>
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#edit-sub-modal-{{ $sub->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('subscriptions.destroy', $sub->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Delete this license?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<datalist id="app-options">
    @foreach($subscriptions->pluck('app')->filter()->unique()->sort() as $app)
        <option value="{{ $app }}">
    @endforeach
</datalist>
@endsection

@section('modals')
<div class="modal fade" id="add-sub-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg hr-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Add License</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subscriptions.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    @include('subscriptions._form', ['sub' => null])
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Save License</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($subscriptions as $sub)
<div class="modal fade" id="edit-sub-modal-{{ $sub->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg hr-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit {{ $sub->label }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subscriptions.update', $sub->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4">
                    @include('subscriptions._form', ['sub' => $sub])
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Update License</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

@section('scripts')
    <script src="{{ asset('assets/extensions/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(document).ready(function () {
            const table = $('#table1').DataTable({
                pageLength: 25,
                order: [[4, 'asc']], // soonest renewal first
                columnDefs: [{ targets: [5], visible: false }],
                language: window.PORTA_DT_LANGUAGE
            });

            // Status tabs filter the hidden Status column (exact match).
            const tabs = document.querySelectorAll('.status-tabs [data-status]');
            function applyStatus(status) {
                table.column(5).search(status ? '^' + status + '$' : '', true, false).draw();
                tabs.forEach(b => {
                    const on = b.dataset.status === status;
                    b.classList.toggle('btn-primary', on);
                    b.classList.toggle('btn-light-secondary', !on);
                });
            }
            tabs.forEach(b => b.addEventListener('click', () => applyStatus(b.dataset.status)));
            applyStatus('Active');
            // "Used by" and the other long selects become searchable via the app-wide helper in the layout.
        });
    </script>
@endsection
