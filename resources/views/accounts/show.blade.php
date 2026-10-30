@extends('layouts.app')

@section('title', $account->email . ' | Account')
@section('header_title', 'Account Profile')

@section('styles')
    <style>
        .acc-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 1rem; }
        .acc-grid > .tile { grid-column: span 12; }
        @media (min-width: 1200px) {
            .tile-xl-4 { grid-column: span 4 !important; }
            .tile-xl-8 { grid-column: span 8 !important; }
        }
        .tile {
            border-radius: 20px !important; margin-bottom: 0 !important; padding: 1.5rem; min-width: 0;
            border: 1px solid rgba(67, 94, 190, .08) !important;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 8px 24px rgba(67, 94, 190, .05) !important;
        }
        .tile-label { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #8a94a6; margin-bottom: .35rem; display: flex; align-items: center; gap: .4rem; }
        .tile-hero { background: linear-gradient(145deg, rgba(67, 94, 190, .10), rgba(67, 94, 190, .02) 60%) !important; }
        .acc-icon { width: 64px; height: 64px; border-radius: 18px; display: grid; place-items: center; font-size: 1.8rem; color: #fff; flex-shrink: 0; }
        .acc-email { font-size: 1.35rem; font-weight: 800; letter-spacing: -.01em; overflow-wrap: anywhere; margin: 0; }
        .stat { padding: .9rem 1rem; border-radius: 14px; background: rgba(67, 94, 190, .05); }
        .stat-num { font-size: 1.35rem; font-weight: 800; line-height: 1.2; }
        .min-w-0 { min-width: 0 !important; }
        .person { display: flex; align-items: center; gap: .75rem; padding: .6rem .5rem; border-radius: 12px; color: inherit; text-decoration: none; }
        .person:hover { background: rgba(67, 94, 190, .05); }
        .person + .person { border-top: 1px dashed rgba(67, 94, 190, .1); }
        .avatar-sm { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: linear-gradient(135deg, #435ebe, #7c8ff0); color: #fff; font-weight: 800; font-size: .85rem; flex-shrink: 0; }
        .app-dot { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; color: #fff; font-weight: 800; flex-shrink: 0; }
        .lic-table td { vertical-align: middle; }
        .lic-table tr.is-inactive td { opacity: .55; }
        .user-chip { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 999px; background: rgba(67, 94, 190, .08); font-size: .75rem; font-weight: 600; white-space: nowrap; color: inherit; text-decoration: none; }
        .bi::before { vertical-align: 0; line-height: 1; }

        [data-bs-theme="dark"] .tile { border-color: rgba(255, 255, 255, .06) !important; box-shadow: 0 1px 2px rgba(0, 0, 0, .3) !important; }
        [data-bs-theme="dark"] .tile-hero { background: linear-gradient(145deg, rgba(99, 102, 241, .16), rgba(99, 102, 241, .03) 60%) !important; }
        [data-bs-theme="dark"] .stat { background: rgba(255, 255, 255, .04); }
        [data-bs-theme="dark"] .person + .person { border-top-color: rgba(255, 255, 255, .06); }
        [data-bs-theme="dark"] .user-chip { background: rgba(129, 140, 248, .14); color: #e5e7eb; }
        [data-bs-theme="dark"] .btn-light-primary  { background-color: rgba(129, 140, 248, .16) !important; color: #a5b4fc !important; }
    </style>
@endsection

@section('content')
@php
    $provider = match (true) {
        str_ends_with($account->email, 'gmail.com') => ['bi-google', '#ea4335', 'Google account'],
        str_contains($account->email, 'outlook') || str_contains($account->email, 'hotmail') => ['bi-microsoft', '#0078d4', 'Microsoft account'],
        default => ['bi-envelope-fill', '#435ebe', 'Company email'],
    };
    $active = $account->subscriptions->where('status', 'Active');
    $palette = ['#435ebe', '#e11d48', '#0891b2', '#7c3aed', '#059669', '#d97706', '#db2777', '#2563eb'];
    $appColor = fn ($app) => $palette[crc32(strtolower($app ?? '')) % count($palette)];
    $money = fn ($amount, $cur) => $amount === null ? '—'
        : trim(($cur ?? '').' '.number_format($amount, $cur === 'IDR' ? 0 : 2, $cur === 'IDR' ? ',' : '.', $cur === 'IDR' ? '.' : ','));
@endphp

<a href="{{ route('accounts.index') }}" class="btn btn-sm btn-light-primary px-3 fw-bold mb-4 d-inline-flex align-items-center gap-2 rounded-pill">
    <i class="bi bi-arrow-left"></i> Back to accounts
</a>

<div class="acc-grid mb-5">
    {{-- Identity + numbers --}}
    <div class="tile card tile-hero tile-xl-8">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="acc-icon" style="background: {{ $provider[1] }};"><i class="bi {{ $provider[0] }}"></i></div>
            <div class="min-w-0">
                <div class="tile-label mb-1">{{ $provider[2] }}</div>
                <h2 class="acc-email">{{ $account->email }}</h2>
                <div class="text-muted fw-semibold">{{ $account->purpose ?? 'No purpose noted' }}</div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat h-100">
                    <div class="tile-label">Active licenses</div>
                    <div class="stat-num">{{ $active->count() }}</div>
                    <small class="text-muted">{{ $account->subscriptions->count() - $active->count() }} inactive</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat h-100">
                    <div class="tile-label">Used by</div>
                    <div class="stat-num">{{ $account->users->count() }}</div>
                    <small class="text-muted">{{ \Illuminate\Support\Str::plural('employee', $account->users->count()) }}</small>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="stat h-100">
                    <div class="tile-label">Monthly spend (yearly ÷ 12)</div>
                    <div class="d-flex flex-wrap gap-3">
                        @forelse($monthlySpend as $cur => $total)
                            <div class="stat-num">{{ $money($total, $cur) }}</div>
                        @empty
                            <div class="stat-num">—</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- People on this login --}}
    <div class="tile card tile-xl-4">
        <div class="tile-label"><i class="bi bi-people"></i> Used by</div>
        <h5 class="fw-bold mb-3">Employees on this login</h5>
        @forelse($account->users as $u)
            <a href="{{ route('employees.show', $u) }}" class="person">
                <span class="avatar-sm">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                <span class="min-w-0">
                    <span class="fw-bold d-block text-truncate">{{ $u->name }}</span>
                    <small class="text-muted">{{ $u->department->name ?? 'No department' }}</small>
                </span>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </a>
        @empty
            <div class="text-center text-muted py-4">
                <i class="bi bi-person-plus fs-3 d-block mb-2 opacity-50"></i>
                No employees linked yet.<br><small>Link this email from an employee's profile.</small>
            </div>
        @endforelse
    </div>

    {{-- Licenses on this login --}}
    <div class="tile card">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <div class="tile-label"><i class="bi bi-cloud-check"></i> Licenses</div>
                <h5 class="fw-bold mb-0">Apps signed in with this email</h5>
            </div>
            <a href="{{ route('subscriptions.index') }}" class="btn btn-sm btn-light-primary fw-bold rounded-pill">All licenses</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover lic-table mb-0">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>App & plan</th>
                        <th>Seats</th>
                        <th>Cost</th>
                        <th class="text-end">Renewal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($account->subscriptions as $sub)
                        @php
                            $seatUsers = $sub->allocations->where('assignable_type', \App\Models\User::class)->map->assignable->filter();
                            $days = $sub->days_remaining;
                        @endphp
                        <tr class="{{ $sub->status === 'Inactive' ? 'is-inactive' : '' }}">
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="app-dot" style="background: {{ $appColor($sub->app) }};">{{ strtoupper(substr($sub->app ?? '?', 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('subscriptions.show', $sub) }}" class="fw-bold text-heading d-block">{{ $sub->label }}</a>
                                        <span class="badge {{ $sub->status === 'Active' ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">{{ $sub->status }}</span>
                                        @if($sub->login_with_google)<span class="badge bg-light-info text-info"><i class="bi bi-google"></i> Google login</span>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($seatUsers as $u)
                                        <a href="{{ route('employees.show', $u) }}" class="user-chip">{{ \Illuminate\Support\Str::words($u->name, 1, '') }}</a>
                                    @empty
                                        <span class="text-muted small">{{ $sub->shared_with ?: '—' }}</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 text-nowrap">
                                <div class="fw-bold">{{ $money($sub->bill, $sub->currency) }}</div>
                                <small class="text-muted">{{ $sub->period }}</small>
                            </td>
                            <td class="py-3 text-end text-nowrap">
                                @if($sub->status === 'Active' && $days !== null)
                                    <span class="badge {{ $days < 0 ? 'bg-danger' : ($days <= 7 ? 'bg-light-danger text-danger' : ($days <= 30 ? 'bg-light-warning text-warning' : 'bg-light-success text-success')) }} rounded-pill px-3">
                                        {{ $days < 0 ? abs($days).' days overdue' : ($days === 0 ? 'Today' : $days.' days') }}
                                    </span>
                                    <div class="text-muted small mt-1">{{ $sub->next_payment_date->format('d M Y') }}</div>
                                @else
                                    <span class="text-muted small">{{ $sub->renewal_label }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No licenses use this email.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
