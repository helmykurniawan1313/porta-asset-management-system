@extends('layouts.app')

@section('title', 'License Control | ' . $subscription->label)
@section('header_title', 'License Control')

@section('styles')
    <style>
        .table-assignments thead th {
            background-color: transparent !important;
            border-bottom: 2px solid rgba(67, 94, 190, 0.15) !important;
            color: #435ebe !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 1px;
            padding: 1.2rem 1rem !important;
        }
        [data-bs-theme="dark"] .table-assignments thead th { color: #a5b4fc !important; border-bottom-color: rgba(255, 255, 255, 0.1) !important; }

        .license-avatar {
            width: 76px; height: 76px; border-radius: 22px; margin: 0 auto; display: grid; place-items: center;
            font-size: 2rem; font-weight: 800; color: #fff; box-shadow: 0 10px 24px rgba(0, 0, 0, .12);
        }
        .detail-list .item { display: flex; justify-content: space-between; gap: 1rem; padding: .75rem 0; border-bottom: 1px dashed rgba(67, 94, 190, .12); }
        .detail-list .item:last-child { border-bottom: 0; }
        .detail-list .lbl { color: var(--porta-text-faint); font-size: .8rem; font-weight: 600; display: flex; align-items: center; gap: .45rem; white-space: nowrap; }
        .detail-list .val { font-weight: 700; text-align: right; overflow-wrap: anywhere; }
        [data-bs-theme="dark"] .detail-list .item { border-bottom-color: rgba(255, 255, 255, .06); }
        .pw-mask { letter-spacing: .15em; }

    </style>
@endsection

@section('content')
@php
    $s = $subscription;
    $palette = ['#435ebe', '#e11d48', '#0891b2', '#7c3aed', '#059669', '#d97706', '#db2777', '#2563eb'];
    $color = $palette[crc32(strtolower($s->app ?? '')) % count($palette)];
    $days = $s->days_remaining;
    $cost = $s->bill === null ? '—'
        : trim(($s->currency ?? '').' '.number_format($s->bill, $s->currency === 'IDR' ? 0 : 2, $s->currency === 'IDR' ? ',' : '.', $s->currency === 'IDR' ? '.' : ','));
@endphp

<a href="{{ route('subscriptions.index') }}" class="btn btn-sm btn-light-primary px-3 fw-bold mb-4 d-inline-flex align-items-center gap-2 rounded-pill">
    <i class="bi bi-arrow-left"></i> Back to licenses
</a>

@if(session('success'))
    <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i> {{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-12 col-xl-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center p-4 pt-5 border-bottom">
                <div class="license-avatar mb-3" style="background: {{ $color }};">{{ strtoupper(substr($s->app ?? '?', 0, 1)) }}</div>
                <h4 class="fw-bold mb-1">{{ $s->app ?? 'Untitled' }}</h4>
                <p class="text-muted fw-semibold mb-3">{{ $s->plan ?? 'No plan' }}</p>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    <span class="badge {{ $s->status === 'Active' ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }} px-3 py-2">{{ $s->status }}</span>
                    @if($s->status === 'Active' && $days !== null)
                        <span class="badge {{ $days < 0 ? 'bg-danger' : ($days <= 7 ? 'bg-light-danger text-danger' : ($days <= 30 ? 'bg-light-warning text-warning' : 'bg-light-primary text-primary')) }} px-3 py-2">
                            {{ $days < 0 ? abs($days).' days overdue' : ($days === 0 ? 'Renews today' : 'Renews in '.$days.' days') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="card-body p-4 detail-list">
                <div class="item">
                    <span class="lbl"><i class="bi bi-envelope"></i> Login</span>
                    <span class="val">
                        @if($s->account)
                            <a href="{{ route('accounts.show', $s->account) }}">{{ $s->account->email }}</a>
                        @else — @endif
                        @if($s->login_with_google)<br><span class="badge bg-light-info text-info mt-1"><i class="bi bi-google"></i> Login with Google</span>@endif
                    </span>
                </div>
                <div class="item">
                    <span class="lbl"><i class="bi bi-key"></i> Password</span>
                    <span class="val d-flex align-items-center justify-content-end gap-2">
                        @if($s->password)
                            <span id="pw-value" class="pw-mask" data-pw="{{ $s->password }}">••••••••</span>
                            <button type="button" class="btn btn-sm btn-light-secondary rounded-pill px-2 py-0" onclick="togglePassword(this)" title="Show / hide"><i class="bi bi-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-light-secondary rounded-pill px-2 py-0" onclick="copyPassword(this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                        @else
                            <span class="text-muted fw-normal">{{ $s->login_with_google ? 'Uses Google' : 'Not set' }}</span>
                        @endif
                    </span>
                </div>
                <div class="item"><span class="lbl"><i class="bi bi-cash-stack"></i> Cost</span><span class="val">{{ $cost }} <small class="text-muted fw-semibold">/ {{ $s->period }}</small></span></div>
                <div class="item"><span class="lbl"><i class="bi bi-calendar-event"></i> Renewal</span><span class="val">{{ $s->renewal_label }}</span></div>
                @if($s->status === 'Active' && $s->isRecurring())
                    <form action="{{ route('subscriptions.renew', $s->id) }}" method="POST" class="py-3"
                          onsubmit="return confirm('Mark as paid?\n\nRenewal: {{ $s->next_payment_date?->format('d M Y') ?? 'not set' }} → {{ $s->nextRenewalDate()->format('d M Y') }}')">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold">
                            <i class="bi bi-arrow-repeat me-1"></i> Paid — renew to {{ $s->nextRenewalDate()->format('d M Y') }}
                        </button>
                    </form>
                @endif
                <div class="item"><span class="lbl"><i class="bi bi-credit-card"></i> Card</span><span class="val">{{ $s->card_info ? '•••• '.$s->card_info : '—' }}</span></div>
                <div class="item"><span class="lbl"><i class="bi bi-people"></i> Other users</span><span class="val">{{ $s->shared_with ?: '—' }}</span></div>
                <div class="item"><span class="lbl"><i class="bi bi-diagram-3"></i> Division</span><span class="val">{{ $s->division ?: '—' }}</span></div>
                @if($s->notes)
                    <div class="item flex-column gap-1"><span class="lbl"><i class="bi bi-sticky"></i> Note</span><span class="fw-semibold">{{ $s->notes }}</span></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent border-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center py-4">
                <div>
                    <h5 class="card-title mb-1 fw-bold">Authorized Users & Devices</h5>
                    <p class="text-muted small mb-0">Found <span class="text-primary fw-bold">{{ $subscription->allocations->count() }}</span> active assignments.</p>
                </div>
                <button data-bs-toggle="modal" data-bs-target="#assign-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm mt-3 mt-sm-0">
                    <i class="bi bi-shield-check me-1"></i> Grant Access
                </button>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-assignments mb-0">
                        <thead>
                            <tr>
                                <th class="px-4">Target Name</th>
                                <th>Category</th>
                                <th class="text-end px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscription->allocations as $alloc)
                                <tr>
                                    <td class="px-4 fw-bold text-heading py-3">
                                        {{ $alloc->assignable->name ?? 'Deleted Asset' }}
                                    </td>
                                    <td class="py-3">
                                        @php $isStaff = str_contains($alloc->assignable_type, 'User'); @endphp
                                        <span class="badge {{ $isStaff ? 'bg-light-warning text-warning' : 'bg-light-success text-success' }} px-3" style="font-size: 0.65rem;">
                                            {{ $isStaff ? 'STAFF MEMBER' : 'REGISTERED DEVICE' }}
                                        </span>
                                    </td>
                                    <td class="text-end px-4 py-3">
                                        <form action="{{ route('subscriptions.revoke', $alloc->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Revoke this access?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger px-3 fw-bold text-uppercase" style="font-size: 0.65rem;">
                                                Revoke Access
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-5 text-center text-muted fst-italic">
                                        No access granted yet. Click "Grant Access" to assign this software.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
<div class="modal fade" id="assign-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg hr-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-uppercase" style="font-size: 0.9rem;">Assign Software License</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subscriptions.allocate', $subscription->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-2">
                        <label class="form-label">Target Assignment</label>
                        <select name="assignable_target" class="form-select" required>
                            <option value="">-- Choose User or Device --</option>
                            <optgroup label="Staff Members">
                                @foreach($employees as $emp) 
                                    <option value="App\Models\User,{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Registered Devices">
                                @foreach($devices as $dev) 
                                    <option value="App\Models\Device,{{ $dev->id }}">{{ $dev->name }}</option> 
                                @endforeach
                            </optgroup>
                        </select>
                        <small class="text-muted d-block mt-2">
                            The selected target will be granted a license seat for {{ $subscription->label }}.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Grant Access</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    function togglePassword(btn) {
        const el = document.getElementById('pw-value');
        const hidden = el.classList.toggle('pw-mask');
        el.textContent = hidden ? '••••••••' : el.dataset.pw;
        btn.querySelector('i').className = hidden ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
    function copyPassword(btn) {
        navigator.clipboard.writeText(document.getElementById('pw-value').dataset.pw).then(() => {
            const icon = btn.querySelector('i');
            icon.className = 'bi bi-check2';
            setTimeout(() => icon.className = 'bi bi-clipboard', 1500);
        });
    }
</script>
@endsection
