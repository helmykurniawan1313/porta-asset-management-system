@extends('brand-health.layout')

@section('title', 'Accounts | Brand Health')

@section('bh-styles')
<style>
    .bh-plat-dot { display: inline-flex; width: 24px; height: 24px; border-radius: 50%; align-items: center; justify-content: center; font-size: .62rem; font-weight: 800; color: #fff; }
    .bh-row-click { cursor: pointer; }
    .bh-acc-card { cursor: pointer; transition: transform .15s ease, box-shadow .15s ease; height: 100%; display: flex; flex-direction: column; }
    .bh-acc-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 24, 40, .08); }
    .bh-sort-link { color: inherit; text-decoration: none; }
    .bh-label-check { display: inline-flex; align-items: center; gap: .35rem; margin: 0 .5rem .4rem 0; cursor: pointer; }
    .ig-metric { border: 1px solid var(--bh-border); border-radius: 10px; padding: .6rem; text-align: center; }
    .ig-metric .lbl { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--bh-ink-faint); }
    .ig-metric .val { font-weight: 800; font-size: 1.15rem; font-variant-numeric: tabular-nums; }
    .ig-media { border: 1px solid var(--bh-border); border-radius: 10px; padding: .55rem .7rem; font-size: .8rem; cursor: pointer; }
</style>
@endsection

@section('bh-content')
@php
    $canEdit = auth()->user()->can('edit-brand-health');
    $query = request()->except('page');
    $activeFilterCount = collect(['health_label', 'growth_label', 'reach_status', 'views_status', 'engagement_status'])->sum(fn ($k) => count($filters[$k] ?? []));
    $hasFilters = filled($filters['search']) || filled($filters['project_manager_id']) || $activeFilterCount > 0;
    $platformMeta = ['instagram' => ['IG', '#e1306c'], 'tiktok' => ['TT', '#111827']];
    $sortUrl = function ($col) use ($filters, $query) {
        $dir = $filters['sort'] === $col && $filters['direction'] === 'asc' ? 'desc' : 'asc';
        return route('brand-health.accounts.index', array_merge($query, ['sort' => $col, 'direction' => $dir]));
    };
    $sortArrow = fn ($col) => $filters['sort'] === $col ? ($filters['direction'] === 'asc' ? '▲' : '▼') : '';
    $rows = collect($accounts->items());
@endphp

<div class="bh-page-head">
    <div>
        <h3>Accounts</h3>
        <p>Manage the brand accounts being tracked across cycles.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('brand-health.accounts.excel', $query) }}" class="btn btn-light-secondary"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        @if($canEdit)<button type="button" class="btn btn-primary" onclick="accountForm.open()"><i class="bi bi-plus-lg"></i> Add Account</button>@endif
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Total Accounts</div><div class="bh-stat-value">{{ $summary['total'] }}</div></div></div>
    <div class="col-6 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Instagram Connected</div><div class="bh-stat-value">{{ $summary['instagram_connected'] }}</div><div class="bh-stat-hint">of {{ $summary['total'] }} accounts</div></div></div>
    <div class="col-12 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Needs Attention</div><div class="bh-stat-value">{{ $summary['at_risk'] }}</div><div class="bh-stat-hint">latest cycle is KURANG or PARAH</div></div></div>
</div>

<form method="GET" class="d-flex flex-wrap align-items-center gap-2 mt-4" id="toolbar">
    @foreach(request()->except(['search', 'project_manager_id', 'page']) as $k => $v)
        @if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <div class="position-relative" style="width: 16rem">
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control ps-5" placeholder="Search accounts…" autocomplete="off" data-debounce>
    </div>
    <select name="project_manager_id" class="form-select" style="width: 14rem" onchange="this.form.submit()">
        <option value="">All PMs</option>
        @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}" @selected($filters['project_manager_id'] == $e->id)>{{ $e->name }}</option>@endforeach
    </select>
    <button type="button" class="btn btn-light-secondary" data-bs-toggle="modal" data-bs-target="#filter-modal">
        <i class="bi bi-funnel"></i> Filter @if($activeFilterCount)<span class="badge bg-primary rounded-pill ms-1">{{ $activeFilterCount }}</span>@endif
    </button>
    @if($hasFilters)<a href="{{ route('brand-health.accounts.index') }}" class="btn btn-link text-decoration-none bh-muted">Clear filters</a>@endif

    <div class="ms-auto d-flex align-items-center gap-2">
        <select class="form-select form-select-sm d-none" style="width: auto" data-card-sort data-no-search>
            @foreach(['name' => 'Name', 'health' => 'Health', 'pm' => 'PM', 'cycles' => 'Cycles'] as $col => $label)
                <optgroup label="{{ $label }}">
                    <option value="{{ $col }}:asc" @selected($filters['sort'] === $col && $filters['direction'] === 'asc')>{{ $label }} (A–Z / Low–High)</option>
                    <option value="{{ $col }}:desc" @selected($filters['sort'] === $col && $filters['direction'] === 'desc')>{{ $label }} (Z–A / High–Low)</option>
                </optgroup>
            @endforeach
        </select>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-light-secondary" data-view="table" title="Table view"><i class="bi bi-table"></i></button>
            <button type="button" class="btn btn-sm btn-light-secondary" data-view="cards" title="Card view"><i class="bi bi-grid"></i></button>
        </div>
    </div>
</form>

{{-- Table view --}}
<div class="bh-card p-0 mt-3" data-pane="table">
    <div class="table-responsive">
        <table class="bh-table">
            <thead><tr>
                <th><a href="{{ $sortUrl('name') }}" class="bh-sort-link">Name {{ $sortArrow('name') }}</a></th>
                <th>Platforms</th>
                <th><a href="{{ $sortUrl('pm') }}" class="bh-sort-link">PM {{ $sortArrow('pm') }}</a></th>
                <th class="text-end"><a href="{{ $sortUrl('cycles') }}" class="bh-sort-link">Cycles {{ $sortArrow('cycles') }}</a></th>
                <th class="text-end">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($rows as $i => $a)
                    <tr class="bh-row-click" data-growth="{{ $i }}">
                        <td class="fw-semibold">{{ $a['name'] }}</td>
                        <td>
                            @forelse($a['platforms'] ?? [] as $p)
                                <span class="bh-plat-dot" style="background: {{ $platformMeta[$p][1] ?? '#9ca3af' }}" title="{{ $platformMeta[$p][0] ?? $p }}">{{ $platformMeta[$p][0] ?? '?' }}</span>
                            @empty <span class="bh-faint">—</span> @endforelse
                        </td>
                        <td class="bh-muted">{{ $a['project_manager']['name'] ?? '—' }}</td>
                        <td class="text-end bh-num bh-muted">{{ $a['cycles_count'] }}</td>
                        <td class="text-end" onclick="event.stopPropagation()">@include('brand-health.accounts._actions', ['a' => $a, 'i' => $i])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="bh-empty">{{ $hasFilters ? 'No accounts match these filters.' : 'No accounts yet. Add one to start tracking cycles.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Card view --}}
<div class="mt-3 d-none" data-pane="cards">
    @if($rows->isEmpty())
        <div class="bh-card bh-empty">{{ $hasFilters ? 'No accounts match these filters.' : 'No accounts yet. Add one to start tracking cycles.' }}</div>
    @else
        <div class="row g-3">
            @foreach($rows as $i => $a)
                <div class="col-sm-6 col-lg-4">
                    <div class="bh-card bh-acc-card" data-growth="{{ $i }}">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="min-w-0">
                                <h6 class="fw-bold text-truncate mb-0" title="{{ $a['name'] }}">{{ $a['name'] }}</h6>
                                <small class="bh-muted">{{ $a['project_manager']['name'] ?? 'No PM assigned' }}</small>
                            </div>
                            <div class="d-flex align-items-start gap-1" onclick="event.stopPropagation()">
                                @foreach($a['platforms'] ?? [] as $p)
                                    <span class="bh-plat-dot" style="background: {{ $platformMeta[$p][1] ?? '#9ca3af' }}">{{ $platformMeta[$p][0] ?? '?' }}</span>
                                @endforeach
                                @include('brand-health.accounts._actions', ['a' => $a, 'i' => $i])
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 mt-auto small" style="border-color: var(--bh-border) !important; margin-top: .75rem !important">
                            <span class="bh-faint">{{ $a['cycles_count'] }} {{ \Illuminate\Support\Str::plural('cycle', $a['cycles_count']) }} tracked</span>
                            <span class="fw-semibold" style="color: var(--bh-accent)">View details →</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if($accounts->lastPage() > 1)
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
        <small class="bh-muted">Showing <b class="bh-num">{{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}</b> of <b class="bh-num">{{ $accounts->total() }}</b></small>
        {{ $accounts->onEachSide(1)->links('brand-health.partials.pager') }}
    </div>
@endif
@endsection

@section('bh-modals')
@php $canEdit = auth()->user()->can('edit-brand-health'); @endphp

<div class="modal fade" id="filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="GET" action="{{ route('brand-health.accounts.index') }}" class="modal-content border-0 shadow" id="filter-form">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Filter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @foreach(['search', 'project_manager_id', 'sort', 'direction'] as $keep)
                    @if(request($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
                @endforeach
                @foreach(['health_label' => ['Health Status', $healthLabels], 'growth_label' => ['Growth Rate', $growthLabels], 'reach_status' => ['Reach Status', $reachStatusLabels], 'views_status' => ['Views Status', $viewsStatusLabels], 'engagement_status' => ['Engagement Status', $engagementStatusLabels]] as $field => [$title, $labels])
                    <div class="mb-3" data-label-group>
                        <label class="form-label small fw-semibold bh-muted d-block">{{ $title }}</label>
                        @forelse($labels as $label)
                            <label class="bh-label-check"><input type="checkbox" class="form-check-input m-0" value="{{ $label }}" @checked(in_array($label, $filters[$field] ?? []))> <x-bh.status :status="$label" /></label>
                        @empty <small class="bh-faint">No labels configured.</small> @endforelse
                        <input type="hidden" name="{{ $field }}" value="{{ implode(',', $filters[$field] ?? []) }}">
                    </div>
                @endforeach
                <small class="bh-faint">All filters use each account's latest cycle (Reach/Views/Engagement compare it to the one before it).</small>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-decoration-none bh-muted" onclick="document.querySelectorAll('#filter-form input[type=checkbox]').forEach((c) => c.checked = false)">Reset</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($canEdit)
<div class="modal fade" id="account-form-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow" id="account-form">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" data-af-title>Add Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-semibold bh-muted">Account Name</label>
                <input type="text" name="name" class="form-control" placeholder="Account name" required maxlength="255" value="{{ old('name') }}">
                <div class="mt-3 d-none" data-af-pm>
                    <label class="form-label small fw-semibold bh-muted">Project Manager</label>
                    <div class="fw-semibold" data-af-pm-name></div>
                    <small class="bh-faint">Derived from this account's most recent cycle — set or change it from the cycle itself, not here. Each past cycle keeps its own PM on record.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" data-af-submit>Add Account</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="account-delete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow" id="account-delete-form">
            @csrf @method('DELETE')
            <div class="modal-header"><h5 class="modal-title fw-bold">Delete this account?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <p class="mb-3" data-ad-message></p>
                <label class="form-check d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input m-0" data-ad-confirm> I understand this will also delete all cycles for this account.</label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" disabled data-ad-submit>Delete</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Instagram --}}
<div class="modal fade" id="instagram-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" data-ig-title></h5>
                    <small class="bh-muted" data-ig-sub></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div data-ig-disconnected>
                    @if($canEdit)
                        <a class="btn btn-primary" data-ig-oauth><i class="bi bi-instagram"></i> Login with Instagram</a>
                        <p class="small bh-faint mt-2">Redirects to Instagram to authorize read-only access, then returns here connected.</p>
                        <details class="mt-3">
                            <summary class="small fw-semibold bh-faint text-uppercase" style="cursor: pointer">Or connect manually with an existing token</summary>
                            <form method="POST" class="mt-3" data-ig-connect-form>
                                @csrf
                                <label class="form-label small fw-semibold bh-muted">Instagram Business ID</label>
                                <input type="text" name="ig_business_id" class="form-control mb-2" placeholder="e.g. 27350904627911625" required>
                                <label class="form-label small fw-semibold bh-muted">Access Token</label>
                                <input type="password" name="ig_access_token" class="form-control mb-3" placeholder="IGAAR..." required autocomplete="off">
                                <button type="submit" class="btn btn-light-secondary">Connect manually</button>
                            </form>
                        </details>
                    @else
                        <p class="small bh-faint mb-0">You don't have permission to connect Instagram for this account.</p>
                    @endif
                </div>
                <div class="d-none" data-ig-connected>
                    <div data-ig-data></div>
                    <div class="d-flex justify-content-between border-top pt-3 mt-3" style="border-color: var(--bh-border) !important">
                        <button type="button" class="btn btn-sm btn-link text-decoration-none bh-muted" data-ig-refresh>Refresh</button>
                        @if($canEdit)
                            <form method="POST" data-ig-disconnect-form onsubmit="return confirmDelete(this, 'This will disconnect Instagram from this account. You can reconnect it later.', 'Disconnect Instagram?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-decoration-none text-danger">Disconnect</button>
                            </form>
                        @endif
                    </div>
                </div>
                @if($canEdit)
                    <details class="border rounded-3 p-3 mt-4" style="border-color: var(--bh-border) !important">
                        <summary class="fw-semibold" style="cursor: pointer">Import Meta Export (bulk daily data)</summary>
                        <p class="small bh-faint mt-2">Upload the daily CSVs exported from Meta Business Suite ("Export data" on each Insights chart). Any subset works — each file is matched by metric, not filename. Rows are merged into this account's daily history, which Add Cycle's auto-fill already reads from.</p>
                        <form data-ig-import-form>
                            <div class="row g-2">
                                @foreach(['follows' => 'Follows', 'viewers' => 'Viewers', 'views' => 'Views', 'interactions' => 'Interactions', 'visits' => 'Visits', 'link_clicks' => 'Link clicks'] as $key => $label)
                                    <div class="col-md-6"><label class="form-label small fw-semibold bh-faint text-uppercase mb-1">{{ $label }}</label><input type="file" accept=".csv,text/csv" name="{{ $key }}" class="form-control form-control-sm"></div>
                                @endforeach
                            </div>
                            <label class="form-label small fw-semibold bh-faint text-uppercase mt-3 mb-1">Starting follower count (optional)</label>
                            <input type="number" min="0" name="starting_followers" class="form-control" placeholder="Leave blank to use the last known count">
                            <small class="bh-faint d-block mt-1">"Follows" in the export is the net change per day, not a running total — this seeds the count it accumulates onto for the first imported day. If left blank, the last recorded snapshot before the import range is used, if any.</small>
                            <p class="small mt-2 mb-0 d-none" data-ig-import-msg></p>
                            <button type="submit" class="btn btn-primary mt-3">Import</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    </div>
</div>

@include('brand-health.partials.account-growth-modal')
@endsection

@section('bh-scripts')
<script>
const ACCOUNTS = @json($rows->values());
const ACC_URLS = {
    store: @json(route('brand-health.accounts.store')),
    update: (id) => @json(route('brand-health.accounts.update', '__ID__')).replace('__ID__', id),
    destroy: (id) => @json(route('brand-health.accounts.destroy', '__ID__')).replace('__ID__', id),
    ig: (id) => @json(route('brand-health.accounts.instagram.show', '__ID__')).replace('__ID__', id),
    igMedia: (id) => @json(route('brand-health.accounts.instagram.media-insights', '__ID__')).replace('__ID__', id),
    igOauth: (id) => @json(route('brand-health.accounts.instagram.oauth.redirect', '__ID__')).replace('__ID__', id),
    igConnect: (id) => @json(route('brand-health.accounts.instagram.connect', '__ID__')).replace('__ID__', id),
    igDisconnect: (id) => @json(route('brand-health.accounts.instagram.disconnect', '__ID__')).replace('__ID__', id),
    igImport: (id) => @json(route('brand-health.accounts.instagram.import', '__ID__')).replace('__ID__', id),
};

/* ---- toolbar, view mode, filters ---- */
(function () {
    const input = document.querySelector('[data-debounce]');
    let t; input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => input.form.submit(), 400); });

    const setView = (mode) => {
        document.querySelectorAll('[data-pane]').forEach((p) => p.classList.toggle('d-none', p.dataset.pane !== mode));
        document.querySelectorAll('[data-view]').forEach((b) => { b.classList.toggle('btn-primary', b.dataset.view === mode); b.classList.toggle('btn-light-secondary', b.dataset.view !== mode); });
        document.querySelector('[data-card-sort]').classList.toggle('d-none', mode !== 'cards');
        try { localStorage.setItem('bh-accounts-view-mode', mode); } catch {}
    };
    let mode = 'table';
    try { const s = localStorage.getItem('bh-accounts-view-mode'); if (s === 'table' || s === 'cards') mode = s; } catch {}
    setView(mode);
    document.querySelectorAll('[data-view]').forEach((b) => b.addEventListener('click', () => setView(b.dataset.view)));
    document.querySelector('[data-card-sort]').addEventListener('change', (e) => {
        const [sort, direction] = e.target.value.split(':');
        const u = new URL(location.href); u.searchParams.set('sort', sort); u.searchParams.set('direction', direction); u.searchParams.delete('page'); location.href = u;
    });

    const ff = document.getElementById('filter-form');
    ff.addEventListener('submit', () => {
        ff.querySelectorAll('[data-label-group]').forEach((g) => { g.querySelector('input[type=hidden]').value = [...g.querySelectorAll('input:checked')].map((c) => c.value).join(','); });
        ff.querySelectorAll('input[name]').forEach((f) => { if (!f.value) f.disabled = true; });
    });

    document.querySelectorAll('[data-growth]').forEach((el) => el.addEventListener('click', () => BH.openAccountGrowth(ACCOUNTS[+el.dataset.growth])));
})();

/* ---- add / edit / delete ---- */
const accountForm = (function () {
    const modal = document.getElementById('account-form-modal');
    if (!modal) return { open() {}, del() {} };
    const form = document.getElementById('account-form');
    const open = (i) => {
        const a = i === undefined ? null : ACCOUNTS[i];
        form.action = a ? ACC_URLS.update(a.id) : ACC_URLS.store;
        form.querySelector('[name=_method]').value = a ? 'PUT' : 'POST';
        form.querySelector('[name=name]').value = a ? a.name : '';
        form.querySelector('[data-af-title]').textContent = a ? 'Edit Account' : 'Add Account';
        form.querySelector('[data-af-submit]').textContent = a ? 'Save' : 'Add Account';
        form.querySelector('[data-af-pm]').classList.toggle('d-none', !a);
        form.querySelector('[data-af-pm-name]').textContent = a?.project_manager?.name ?? 'No PM yet';
        bootstrap.Modal.getOrCreateInstance(modal).show();
    };
    const delModal = document.getElementById('account-delete-modal');
    const delForm = document.getElementById('account-delete-form');
    delForm.querySelector('[data-ad-confirm]').addEventListener('change', (e) => delForm.querySelector('[data-ad-submit]').disabled = !e.target.checked);
    const del = (i) => {
        const a = ACCOUNTS[i];
        delForm.action = ACC_URLS.destroy(a.id);
        delForm.querySelector('[data-ad-message]').textContent = `This will permanently remove “${a.name}” and all ${a.cycles_count} cycle${a.cycles_count === 1 ? '' : 's'} recorded for it. This cannot be undone.`;
        delForm.querySelector('[data-ad-confirm]').checked = false;
        delForm.querySelector('[data-ad-submit]').disabled = true;
        bootstrap.Modal.getOrCreateInstance(delModal).show();
    };
    @if($errors->has('name')) open(); @endif
    return { open, del };
})();

/* ---- Instagram ---- */
const instagram = (function () {
    const el = document.getElementById('instagram-modal');
    const $ = (s) => el.querySelector(s);
    let account = null;
    const metric = (lbl, val) => `<div class="col"><div class="ig-metric"><div class="lbl">${BH.esc(lbl)}</div><div class="val">${val}</div></div></div>`;

    async function load() {
        const box = $('[data-ig-data]');
        box.innerHTML = '<p class="bh-empty py-4">Loading Instagram data…</p>';
        try {
            const d = await BH.request(ACC_URLS.ig(account.id));
            $('[data-ig-sub]').textContent = `Connected as @${d.profile.username}`;
            box.innerHTML = `<div class="row g-2">${metric('Followers', BH.num(d.profile.followers_count))}${metric('Media Count', BH.num(d.profile.media_count))}${metric('Username', '<span class="fs-6">@' + BH.esc(d.profile.username) + '</span>')}</div>
                <h6 class="fw-bold mt-4">Today's Insights</h6>
                <div class="row g-2">${(d.daily_insights || []).map((m) => metric(m.name, m.total_value?.value ?? '—')).join('')}</div>
                <h6 class="fw-bold mt-4 mb-0">Post Analytics (${(d.media || []).length})</h6><small class="bh-faint">Click a post to load its insights.</small>
                <div class="d-flex flex-column gap-2 mt-2" style="max-height: 20rem; overflow-y: auto">${(d.media || []).map((m) => `<div class="ig-media" data-media="${BH.esc(m.id)}" data-type="${BH.esc(m.media_product_type)}">
                    <div class="d-flex justify-content-between"><span><b>${BH.esc(m.media_product_type)}</b> — ${BH.esc(m.timestamp)}</span><span class="bh-faint" data-arrow>▼</span></div>
                    <div class="text-truncate bh-faint">${BH.esc(m.caption ?? '')}</div><div class="d-none mt-2 pt-2 border-top" data-insights></div></div>`).join('')}</div>`;
        } catch (e) {
            box.innerHTML = `<div class="alert alert-light-danger mb-0">${BH.esc(e.message)}</div>`;
        }
    }

    el.addEventListener('click', async (e) => {
        if (e.target.closest('[data-ig-refresh]')) return load();
        const item = e.target.closest('[data-media]');
        if (!item || e.target.closest('[data-insights]')) return;
        const box = item.querySelector('[data-insights]');
        const open = box.classList.toggle('d-none') === false;
        item.querySelector('[data-arrow]').textContent = open ? '▲' : '▼';
        if (!open || box.dataset.loaded) return;
        box.innerHTML = '<span class="bh-faint">Loading insights…</span>';
        try {
            const d = await BH.request(ACC_URLS.igMedia(account.id) + BH.qs({ media_id: item.dataset.media, media_product_type: item.dataset.type }));
            box.dataset.loaded = '1';
            box.innerHTML = `<div class="row g-2">${(d.insights || []).map((m) => `<div class="col-4"><div class="ig-metric py-1"><div class="lbl">${BH.esc(m.name)}</div><div class="fw-bold bh-num">${m.values?.[0]?.value ?? m.total_value?.value ?? '—'}</div></div></div>`).join('')}</div>`;
        } catch (err) { box.innerHTML = `<span style="color: var(--status-parah-ink)">${BH.esc(err.message)}</span>`; }
    });

    $('[data-ig-import-form]')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target, msg = form.querySelector('[data-ig-import-msg]');
        const fd = new FormData();
        let files = 0;
        form.querySelectorAll('input[type=file]').forEach((f) => { if (f.files[0]) { fd.append(f.name, f.files[0]); files++; } });
        if (form.starting_followers.value !== '') fd.append('starting_followers', form.starting_followers.value);
        const show = (text, ok) => { msg.textContent = text; msg.style.color = ok ? 'var(--status-sip-ink)' : 'var(--status-parah-ink)'; msg.classList.remove('d-none'); };
        if (!files) return show('Choose at least one Meta export file.', false);
        const btn = form.querySelector('[type=submit]'); btn.disabled = true; btn.textContent = 'Importing…';
        try {
            const r = await BH.request(ACC_URLS.igImport(account.id), { method: 'POST', body: fd });
            show(`Imported ${r.dates_imported} day(s), ${r.date_from} to ${r.date_to}.`, true);
            form.querySelectorAll('input[type=file]').forEach((f) => f.value = '');
            if (account.ig_business_id) load();
        } catch (err) { show(err.message, false); }
        finally { btn.disabled = false; btn.textContent = 'Import'; }
    });

    return {
        open(i) {
            account = ACCOUNTS[i];
            const connected = !!account.ig_business_id;
            $('[data-ig-title]').textContent = `Instagram — ${account.name}`;
            $('[data-ig-sub]').textContent = connected ? `Connected as @${account.ig_username ?? '...'}` : 'Not connected yet';
            $('[data-ig-disconnected]').classList.toggle('d-none', connected);
            $('[data-ig-connected]').classList.toggle('d-none', !connected);
            if ($('[data-ig-oauth]')) $('[data-ig-oauth]').href = ACC_URLS.igOauth(account.id);
            if ($('[data-ig-connect-form]')) $('[data-ig-connect-form]').action = ACC_URLS.igConnect(account.id);
            if ($('[data-ig-disconnect-form]')) $('[data-ig-disconnect-form]').action = ACC_URLS.igDisconnect(account.id);
            const msg = $('[data-ig-import-msg]'); if (msg) msg.classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(el).show();
            if (connected) load();
        },
    };
})();
</script>
@endsection
