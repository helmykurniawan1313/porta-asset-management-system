@extends('layouts.app')

@section('title', ($employee->name ?? 'Employee') . ' | Porta IT')
@section('header_title', 'Employee Profile')

@section('styles')
    <style>
        /* 1. Floating Header Table Style */
        .table-custom thead th {
            background-color: transparent !important;
            border-bottom: 2px solid rgba(67, 94, 190, 0.15) !important;
            color: #435ebe !important; /* Brand Blue text */
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            padding: 1.2rem 1rem !important;
        }

        .theme-dark .table-custom thead th {
            color: #8c9eff !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* 2. Profile Avatar Styling */
        .profile-avatar {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 2rem;
        }

        /* 3. Company account cards */
        .account-card { border: 1px solid rgba(67, 94, 190, .12); border-radius: 14px; padding: 1rem; transition: border-color .15s ease; }
        .account-card:hover { border-color: rgba(67, 94, 190, .35); }
        .account-icon { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; flex-shrink: 0; background: rgba(67, 94, 190, .08); color: #435ebe; }
        .account-icon .bi::before { vertical-align: 0; line-height: 1; }
        [data-bs-theme="dark"] .account-card { border-color: rgba(255, 255, 255, .08); }
        [data-bs-theme="dark"] .account-icon { background: rgba(129, 140, 248, .12); color: #a5b4fc; }
        [data-bs-theme="dark"] .bg-light-primary { background-color: rgba(129, 140, 248, .16) !important; color: #a5b4fc !important; }
        [data-bs-theme="dark"] .btn-light-secondary { background-color: rgba(148, 163, 184, .14) !important; color: #cbd5e1 !important; }
    </style>
@endsection

@section('content')
    <div class="mb-4">
        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-light-primary px-3 fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="profile-avatar bg-light-primary text-primary rounded-circle shadow-sm">
                        {{ strtoupper(substr($employee->name ?? 'E', 0, 1)) }}
                    </div>
                </div>
                <div class="col mt-3 mt-md-0">
                    <h2 class="fw-bold mb-1">{{ $employee->name ?? 'Unnamed Employee' }}</h2>
                    <p class="text-muted fw-semibold mb-3">{{ $employee->email ?? 'No email provided' }}</p>
                    <div class="d-flex gap-2 flex-wrap">
                        @if($employee->resigned_at)
                            <span class="badge bg-light-danger text-danger px-3 rounded-pill fw-bold">RESIGNED {{ $employee->resigned_at->format('d/m/Y') }}</span>
                        @else
                            <span class="badge bg-light-success text-success px-3 rounded-pill fw-bold">ACTIVE</span>
                        @endif
                        <span class="badge bg-light-primary text-primary px-3 rounded-pill fw-bold">
                            {{ $employee->department->name ?? 'NO DEPARTMENT' }}
                        </span>
                        @if($employee->joined_at)
                            <span class="badge bg-light-secondary text-secondary px-3 rounded-pill fw-bold">JOINED {{ $employee->joined_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-auto mt-4 mt-md-0 text-md-end border-start-md ps-md-5">
                    <h6 class="text-muted fw-bold small text-uppercase tracking-wider mb-1">Assigned Hardware</h6>
                    <h1 class="display-5 fw-black text-primary mb-0">{{ $employee->devices->count() }}</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <h5 class="card-title fw-bold">Hardware Checked Out</h5>
            <p class="text-muted small mb-0">Assets currently in possession of this employee.</p>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom" id="table-hardware">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Asset Name</th>
                            <th>Details</th>
                            <th class="text-center">Health</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->devices as $asset)
                        <tr>
                            <td class="py-3">
                                <span class="badge bg-light-secondary text-secondary px-2" style="font-size: 0.65rem;">
                                    {{ strtoupper($asset->category) }}
                                </span>
                            </td>
                            <td class="fw-bold py-3 text-heading">{{ $asset->name }}</td>
                            <td class="py-3">
                                <span class="fw-semibold">{{ $asset->manufacturer }} {{ $asset->model }}</span>
                                @if($asset->device_group == 'Peripheral')
                                    <small class="d-block text-muted fst-italic" style="font-size: 0.7rem;">Installed inside parent device</small>
                                @endif
                            </td>
                            <td class="text-center py-3">
                                <span class="badge {{ $asset->current_health < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-3">
                                    {{ $asset->current_health ?? 100 }}%
                                </span>
                            </td>
                            <td class="text-end py-3">
                                <a href="{{ route('inventory.show', $asset->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3" title="View Asset">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted fst-italic">No hardware is currently assigned to this employee.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="card-title fw-bold mb-1">Company Accounts <span class="badge bg-light-primary text-primary ms-1">{{ $employee->accounts->count() }}</span></h5>
                <p class="text-muted small mb-0">Company emails this person logs in with.</p>
            </div>
            <form action="{{ route('employees.accounts.attach', $employee) }}" method="POST" class="d-flex gap-2" style="min-width: min(100%, 420px);">
                @csrf
                <input type="email" name="email" list="company-accounts" class="form-control form-control-sm" placeholder="Pick or type an email…" required>
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold text-nowrap"><i class="bi bi-plus-lg"></i> Link</button>
            </form>
            <datalist id="company-accounts">
                @foreach($allAccounts->diff($employee->accounts->pluck('email')) as $email)
                    <option value="{{ $email }}">
                @endforeach
            </datalist>
        </div>
        <div class="card-body py-4">
            <div class="row g-3">
                @forelse($employee->accounts as $acc)
                    @php
                        $apps = $acc->subscriptions->where('status', 'Active')->map->label->unique()->values();
                        $others = $acc->users->where('id', '!=', $employee->id);
                    @endphp
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="account-card h-100">
                            <div class="d-flex align-items-start gap-3">
                                <div class="account-icon"><i class="bi {{ str_ends_with($acc->email, 'gmail.com') ? 'bi-google' : (str_contains($acc->email, 'outlook') ? 'bi-microsoft' : 'bi-envelope') }}"></i></div>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('accounts.show', $acc) }}" class="fw-bold text-heading d-block text-truncate" title="{{ $acc->email }}">{{ $acc->email }}</a>
                                    @if($acc->purpose)<small class="text-muted d-block">{{ $acc->purpose }}</small>@endif
                                </div>
                                <form action="{{ route('employees.accounts.detach', [$employee, $acc]) }}" method="POST" class="m-0"
                                      onsubmit="return confirmDelete(this, 'Unlink {{ $acc->email }} from {{ addslashes($employee->name) }}? The account itself is kept.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-secondary rounded-pill px-2 py-0" title="Unlink"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-3">
                                @forelse($apps as $app)
                                    <span class="badge bg-light-primary text-primary">{{ $app }}</span>
                                @empty
                                    <small class="text-muted">No active licenses on this email</small>
                                @endforelse
                            </div>
                            @if($others->count())
                                <small class="text-muted d-block mt-2"><i class="bi bi-people"></i> Shared with {{ $others->map(fn ($u) => \Illuminate\Support\Str::words($u->name, 1, ''))->implode(', ') }}</small>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted fst-italic py-4">No company accounts linked yet. Pick or type an email above.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title fw-bold">Software & Licenses</h5>
                <p class="text-muted small mb-0">Subscription seats assigned to this user.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#link-software-modal">
                <i class="bi bi-link-45deg me-1"></i> LINK SOFTWARE
            </button>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom" id="table-software">
                    <thead>
                        <tr>
                            <th>Software Plan</th>
                            <th>Master Account</th>
                            <th>Cycle</th>
                            <th class="text-end">Next Payment</th>
                            <th class="text-end" style="width: 1%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->softwareAllocations as $allocation)
                            @if($allocation->subscription)
                                <tr>
                                    <td class="py-3">
                                        <a href="{{ route('subscriptions.show', $allocation->subscription) }}" class="fw-bold text-primary d-block">{{ $allocation->subscription->label }}</a>
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Software License</small>
                                    </td>
                                    <td class="py-3 fw-semibold small">{{ $allocation->subscription->account->email ?? 'Unlinked Account' }}</td>
                                    <td class="py-3">
                                        <span class="badge bg-light-secondary text-secondary px-2 fw-bold" style="font-size: 0.65rem;">
                                            {{ strtoupper($allocation->subscription->period) }}
                                        </span>
                                    </td>
                                    <td class="text-end py-3 fw-bold small">
                                        {{ $allocation->subscription->next_payment_date?->format('M d, Y') ?? $allocation->subscription->renewal_label }}
                                    </td>
                                    <td class="text-end py-3">
                                        <form action="{{ route('subscriptions.revoke', $allocation) }}" method="POST" class="m-0"
                                              onsubmit="return confirmDelete(this, 'Remove {{ addslashes($allocation->subscription->label) }} from {{ addslashes($employee->name) }}? The license itself is kept.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-2" title="Remove access"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted fst-italic">No software subscriptions directly assigned.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('modals')
<div class="modal fade" id="link-software-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="link-software-form" class="modal-content border-0 shadow-sm hr-modal">
            @csrf
            <input type="hidden" name="assignable_target" value="App\Models\User,{{ $employee->id }}">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Give {{ \Illuminate\Support\Str::words($employee->name, 2, '') }} a license</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <label class="form-label text-muted fw-bold small">Active license</label>
                <select id="link-software-select" class="form-select" required>
                    <option value="">-- Choose a license --</option>
                    @foreach($availableLicenses->groupBy('app') as $app => $subs)
                        <optgroup label="{{ $app }}">
                            @foreach($subs as $sub)
                                <option value="{{ route('subscriptions.allocate', $sub) }}">{{ $sub->plan ?? $app }} — {{ $sub->account->email ?? 'no account' }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-2">Only active licenses this person doesn't already have are listed.</small>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Give access</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Each license has its own allocate URL, so point the form at the chosen one.
    document.getElementById('link-software-select')?.addEventListener('change', function () {
        document.getElementById('link-software-form').action = this.value;
    });
</script>
@endsection
