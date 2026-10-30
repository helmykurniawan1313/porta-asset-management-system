@extends('brand-health.layout')

@section('title', 'Weekly Reports | Brand Health')
@section('header_title', 'Brand Health')

@section('bh-content')
@php
    $canEdit = auth()->user()->can('edit-brand-health');
    $hasFilters = filled($filters['search']) || filled($filters['account_id']) || filled($filters['platform']);
    $range = fn ($s, $e) => \Carbon\Carbon::parse($s)->format('M j').' – '.\Carbon\Carbon::parse($e)->format('M j, Y');
    $n = fn ($v) => $v === null ? '—' : number_format($v);
    $rows = collect($reports->items());
@endphp
<div class="bh-page-head">
    <div>
        <h3>Weekly Reports</h3>
        <p>Raw weekly numbers per account — Monday through Sunday. No scoring, just the metrics.</p>
    </div>
    @if($canEdit)<button type="button" class="btn btn-primary" onclick="weeklyForm.open()"><i class="bi bi-plus-lg"></i> Add Weekly Report</button>@endif
</div>

<form method="GET" class="d-flex flex-wrap align-items-center gap-2">
    <div class="position-relative" style="width: 16rem">
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control ps-5" placeholder="Search account…" autocomplete="off" data-debounce>
    </div>
    <select name="account_id" class="form-select" style="width: 14rem" onchange="this.form.submit()">
        <option value="">All accounts</option>
        @foreach($accounts as $a)<option value="{{ $a->id }}" @selected($filters['account_id'] == $a->id)>{{ $a->name }}</option>@endforeach
    </select>
    <select name="platform" class="form-select" style="width: 11rem" onchange="this.form.submit()" data-no-search>
        <option value="">All platforms</option>
        <option value="instagram" @selected($filters['platform'] === 'instagram')>Instagram</option>
        <option value="tiktok" @selected($filters['platform'] === 'tiktok')>TikTok</option>
    </select>
    @if($hasFilters)<a href="{{ route('brand-health.weekly-reports.index') }}" class="btn btn-link text-decoration-none bh-muted">Clear filters</a>@endif
</form>

<div class="bh-card p-0 mt-3">
    <div class="table-responsive">
        <table class="bh-table">
            <thead><tr>
                <th>Account</th><th>Platform</th><th>Week</th>
                <th class="text-end">Followers</th><th class="text-end">Reach</th><th class="text-end">Views</th>
                <th class="text-end">Visits</th><th class="text-end">Link clicks</th><th class="text-end">Engagement</th>
                @if($canEdit)<th></th>@endif
            </tr></thead>
            <tbody>
                @forelse($rows as $i => $r)
                    <tr>
                        <td class="fw-semibold">{{ $r->account->name ?? '—' }}</td>
                        <td class="bh-muted text-capitalize">{{ $r->platform }}</td>
                        <td class="bh-muted text-nowrap">{{ $range($r->week_start_date, $r->week_end_date) }}</td>
                        @foreach(['follower_count', 'reach', 'views', 'profile_visits', 'link_clicks', 'engagement'] as $k)
                            <td class="text-end bh-num">{{ $n($r->$k) }}</td>
                        @endforeach
                        @if($canEdit)
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light-secondary" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" onclick="weeklyForm.open({{ $i }}); return false;">Edit</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('brand-health.weekly-reports.destroy', $r->id) }}" onsubmit="return confirmDelete(this, 'This will permanently remove the weekly report for {{ addslashes($r->account->name ?? '') }} ({{ $range($r->week_start_date, $r->week_end_date) }}).', 'Delete this weekly report?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="10" class="bh-empty">{{ $hasFilters ? 'No weekly reports match these filters.' : 'No weekly reports yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->lastPage() > 1)
        <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 border-top gap-2" style="border-color: var(--bh-border) !important">
            <small class="bh-muted">Showing <b class="bh-num">{{ $reports->firstItem() }}–{{ $reports->lastItem() }}</b> of <b class="bh-num">{{ $reports->total() }}</b></small>
            {{ $reports->onEachSide(1)->links() }}
        </div>
    @endif
</div>
@endsection

@section('bh-modals')
@if(auth()->user()->can('edit-brand-health'))
<div class="modal fade" id="weekly-form-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow" id="weekly-form" action="{{ route('brand-health.weekly-reports.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="_weekly_mode" value="">
            <div class="modal-header"><h5 class="modal-title fw-bold" data-wf-title>Add Weekly Report</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold bh-muted">Account</label>
                        <select name="account_id" class="form-select" required>
                            <option value="">Select account</option>
                            @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Platform</label>
                        <select name="platform" class="form-select" required data-no-search>
                            <option value="instagram">Instagram</option><option value="tiktok">TikTok</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Week starting (Monday)</label>
                        <input type="date" name="week_start_date" class="form-control" required>
                        <small class="bh-faint" data-wf-range></small>
                    </div>
                    <div class="col-12"><small class="bh-faint"><i class="bi bi-magic"></i> Numbers auto-fill from connected Instagram daily data for that week, if any.</small></div>
                    @foreach(['follower_count' => 'Followers (end of week)', 'reach' => 'Reach', 'views' => 'Views', 'profile_visits' => 'Profile visits', 'link_clicks' => 'Link clicks', 'engagement' => 'Engagement'] as $k => $label)
                        <div class="col-md-6"><label class="form-label small fw-semibold bh-muted">{{ $label }}</label><input type="number" name="{{ $k }}" class="form-control" @if($k !== 'engagement') min="0" @endif></div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" data-wf-submit>Add Weekly Report</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('bh-scripts')
<script>
const REPORTS = @json($rows->values());
(function () {
    const input = document.querySelector('[data-debounce]');
    let t; input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => input.form.submit(), 400); });
})();

const weeklyForm = (function () {
    const modal = document.getElementById('weekly-form-modal');
    if (!modal) return { open() {} };
    const form = document.getElementById('weekly-form');
    const f = (n) => form.querySelector(`[name="${n}"]`);
    const URLS = {
        store: @json(route('brand-health.weekly-reports.store')),
        update: (id) => @json(route('brand-health.weekly-reports.update', '__ID__')).replace('__ID__', id),
        neighboring: (id) => @json(route('brand-health.weekly-reports.neighboring', '__ID__')).replace('__ID__', id),
    };
    const fields = ['follower_count', 'reach', 'views', 'profile_visits', 'link_clicks', 'engagement'];
    let editing = null;

    // A week is always Monday through Sunday: snap any picked date back to its Monday.
    const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const mondayOf = (s) => { const d = new Date(s + 'T00:00:00'); d.setDate(d.getDate() - (d.getDay() === 0 ? 6 : d.getDay() - 1)); return ymd(d); };
    const sundayFor = (m) => { const d = new Date(m + 'T00:00:00'); d.setDate(d.getDate() + 6); return ymd(d); };
    const lastCompleteMonday = () => { const m = mondayOf(ymd(new Date())); if (sundayFor(m) >= ymd(new Date())) { const d = new Date(m + 'T00:00:00'); d.setDate(d.getDate() - 7); return ymd(d); } return m; };
    const label = () => {
        const m = f('week_start_date').value;
        form.querySelector('[data-wf-range]').textContent = m ? `${new Date(m + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${new Date(sundayFor(m) + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}` : '';
    };
    const setSelect = (n, v) => { const s = f(n); s.value = v ?? ''; if (s._choices) s._choices.setChoiceByValue(String(v ?? '')); };

    async function prefill() {
        if (editing || !f('account_id').value || !f('week_start_date').value) return;
        try {
            const m = f('week_start_date').value;
            const data = await BH.request(URLS.neighboring(f('account_id').value) + BH.qs({ week_start_date: m, week_end_date: sundayFor(m), platform: f('platform').value }));
            fields.forEach((k) => { if (data[k] != null) f(k).value = data[k]; });
        } catch { /* manual entry still works */ }
    }
    f('week_start_date').addEventListener('change', () => { if (f('week_start_date').value) f('week_start_date').value = mondayOf(f('week_start_date').value); label(); prefill(); });
    f('account_id').addEventListener('change', prefill);
    f('platform').addEventListener('change', prefill);

    function load(r) {
        editing = r;
        form.action = r ? URLS.update(r.id) : URLS.store;
        f('_method').value = r ? 'PUT' : 'POST';
        f('_weekly_mode').value = r ? 'edit:' + r.id : 'create';
        form.querySelector('[data-wf-title]').textContent = r ? 'Edit Weekly Report' : 'Add Weekly Report';
        form.querySelector('[data-wf-submit]').textContent = r ? 'Save Changes' : 'Add Weekly Report';
        setSelect('account_id', r?.account_id); setSelect('platform', r?.platform ?? 'instagram');
        f('week_start_date').value = r ? String(r.week_start_date).slice(0, 10) : lastCompleteMonday();
        fields.forEach((k) => f(k).value = r ? (r[k] ?? '') : '');
        label();
    }

    const old = @json(old());
    if (old._weekly_mode) {
        const id = old._weekly_mode.startsWith('edit:') ? +old._weekly_mode.slice(5) : null;
        load(id ? REPORTS.find((r) => r.id === id) ?? null : null);
        ['account_id', 'platform'].forEach((k) => { if (old[k]) setSelect(k, old[k]); });
        ['week_start_date', ...fields].forEach((k) => { if (old[k] !== undefined && old[k] !== null) f(k).value = old[k]; });
        label();
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
    return { open(i) { load(i === undefined ? null : REPORTS[i]); bootstrap.Modal.getOrCreateInstance(modal).show(); } };
})();
</script>
@endsection
