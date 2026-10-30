@extends('brand-health.layout')

@section('title', 'Content Insights | Brand Health')
@section('header_title', 'Brand Health')

@section('bh-styles')
<style>
    .ci-row { cursor: pointer; }
    .ci-score { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ci-trend { border: 1px solid var(--bh-border); border-radius: 999px; padding: .3rem .8rem; font-size: .8rem; font-weight: 600; background: transparent; color: var(--bh-ink-muted); margin: 0 .35rem .35rem 0; }
    .ci-trend.active { background: var(--bh-accent); border-color: var(--bh-accent); color: #fff; }
    .ci-tile { border: 1px solid var(--bh-border); border-radius: 12px; padding: .9rem; background: var(--bh-raised); height: 100%; }
    .ci-share { height: 4px; border-radius: 4px; background: var(--bh-accent-soft); overflow: hidden; }
    .ci-share > span { display: block; height: 100%; background: var(--bh-accent); }
    .bh-sortable { cursor: pointer; user-select: none; }
</style>
@endsection

@section('bh-content')
@php $canEdit = auth()->user()->can('edit-brand-health'); @endphp
<div class="bh-page-head">
    <div>
        <h3>Content Insights</h3>
        <p>Instagram Insights "Overview" numbers per cycle — Viewers and Interactions by content type — scored as a Weighted Content Score.</p>
    </div>
    @if($canEdit)<button type="button" class="btn btn-primary" onclick="ciForm.open()"><i class="bi bi-plus-lg"></i> Add Content Insight</button>@endif
</div>

<div class="row g-3">
    <div class="col-4"><div class="bh-card h-100"><div class="bh-stat-label">Accounts Shown</div><div class="bh-stat-value" data-stat-accounts>0</div></div></div>
    <div class="col-4"><div class="bh-card h-100"><div class="bh-stat-label">Months Tracked</div><div class="bh-stat-value">{{ $months->count() }}</div></div></div>
    <div class="col-4"><div class="bh-card h-100"><div class="bh-stat-label">Platforms</div><div class="bh-stat-value">{{ $allInsights->pluck('platform')->filter()->unique()->count() }}</div><div class="bh-stat-hint">with recorded insights</div></div></div>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mt-4">
    <div class="position-relative" style="width: 16rem">
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" class="form-control ps-5" placeholder="Search accounts…" data-ci-search autocomplete="off">
    </div>
    <button type="button" class="btn btn-light-secondary" data-bs-toggle="modal" data-bs-target="#ci-filter-modal"><i class="bi bi-funnel"></i> Filter <span class="badge bg-primary rounded-pill ms-1 d-none" data-ci-filter-count></span></button>
    <button type="button" class="btn btn-link text-decoration-none bh-muted d-none" data-ci-clear>Clear filters</button>
    <small class="bh-faint" data-ci-scope></small>
    <div class="ms-auto d-flex gap-2">
        <a class="btn btn-light-secondary" data-ci-excel><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        <a class="btn btn-light-secondary" data-ci-pdf><i class="bi bi-download"></i> Download PDF</a>
    </div>
</div>
<p class="small mt-2 mb-0 d-none" style="color: var(--status-cukup-ink)" data-ci-range-empty></p>

<div class="bh-card p-0 mt-3">
    <div class="table-responsive">
        <table class="bh-table">
            <thead><tr>
                <th class="bh-sortable" data-ci-sort="account">Account <span></span></th>
                <th class="bh-sortable text-end" data-ci-sort="posts">Posts <span></span></th>
                <th class="bh-sortable text-end" data-ci-sort="reels">Reels <span></span></th>
                <th class="bh-sortable text-end" data-ci-sort="story">Story <span></span></th>
                <th></th>
            </tr></thead>
            <tbody data-ci-body></tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 border-top gap-2" style="border-color: var(--bh-border) !important" data-ci-pager></div>
</div>
@endsection

@section('bh-modals')
@php $canEdit = auth()->user()->can('edit-brand-health'); @endphp
<div class="modal fade" id="ci-filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header"><h5 class="modal-title fw-bold">Filter</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <label class="form-label small fw-semibold bh-muted">Project Manager</label>
                <select class="form-select mb-3" data-ci-pm>
                    <option value="">All PMs</option>
                    @foreach($projectManagers as $pm)<option value="{{ $pm->id }}">{{ $pm->name }}</option>@endforeach
                </select>
                <label class="form-label small fw-semibold bh-muted d-block">Trend</label>
                <div class="mb-3" data-ci-trends>
                    @foreach(['up' => 'Trending up', 'flat' => 'Flat', 'down' => 'Trending down', 'no_data' => 'No prior cycle'] as $v => $l)
                        <button type="button" class="ci-trend" data-trend="{{ $v }}">{{ $l }}</button>
                    @endforeach
                </div>
                <label class="form-label small fw-semibold bh-muted">Compare months</label>
                <small class="bh-muted d-block mb-2">Score each account's cycle in month A against its cycle in month B, instead of latest vs. previous.</small>
                <div class="d-flex gap-2 align-items-center">
                    <select class="form-select" data-ci-from data-no-search><option value="">Month A…</option>@foreach($months as $m)<option value="{{ $m }}">{{ \Carbon\Carbon::createFromFormat('Y-m-d', $m.'-01')->format('M Y') }}</option>@endforeach</select>
                    <span class="bh-faint">→</span>
                    <select class="form-select" data-ci-to data-no-search><option value="">Month B…</option>@foreach($months as $m)<option value="{{ $m }}">{{ \Carbon\Carbon::createFromFormat('Y-m-d', $m.'-01')->format('M Y') }}</option>@endforeach</select>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-decoration-none bh-muted" data-ci-filter-reset>Clear all</button>
                <button type="button" class="btn btn-primary" data-ci-filter-apply>Apply filters</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ci-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header align-items-start">
                <div>
                    <h5 class="modal-title fw-bold" data-cd-name></h5>
                    <small class="bh-muted" data-cd-sub></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-cd-body></div>
        </div>
    </div>
</div>

@if($canEdit)
<div class="modal fade" id="ci-form-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form method="POST" class="modal-content border-0 shadow" id="ci-form" action="{{ route('brand-health.content-insights.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header"><h5 class="modal-title fw-bold" data-cf-title>Add Content Insight</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Account</label>
                        <select name="account_id" class="form-select" required>
                            <option value="">Select account</option>
                            @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Cycle</label>
                        <select name="cycle_id" class="form-select" required data-no-search><option value="">Select an account first</option></select>
                    </div>
                </div>
                @foreach(['viewers' => ['Viewers', 'Accounts that saw your content, by content type.'], 'interactions' => ['Interactions', 'Likes, comments, shares and saves, by content type.']] as $prefix => [$title, $hint])
                    <div class="border rounded-3 p-3 mt-3" style="border-color: var(--bh-border) !important" data-cf-section="{{ $prefix }}">
                        <div class="d-flex justify-content-between align-items-baseline">
                            <div><h6 class="fw-bold mb-0">{{ $title }}</h6><small class="bh-faint">{{ $hint }}</small></div>
                            <div class="fw-bold bh-num" data-cf-total></div>
                        </div>
                        <div class="row g-2 mt-1">
                            @foreach(['posts' => 'Posts', 'reels' => 'Reels', 'story' => 'Story'] as $type => $label)
                                <div class="col-md-4">
                                    <label class="form-label small bh-muted mb-1">{{ $label }}</label>
                                    <input type="number" min="0" name="{{ $prefix }}_{{ $type }}" class="form-control" value="0" required>
                                    <div class="ci-share mt-1"><span data-cf-share style="width: 0"></span></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" data-cf-submit>Save</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('bh-scripts')
<script>
const CI = @json($allInsights);
const CI_URLS = {
    excel: @json(route('brand-health.content-insights.excel')),
    pdf: @json(route('brand-health.content-insights.pdf')),
    cycles: (id) => @json(route('brand-health.content-insights.cycles', '__ID__')).replace('__ID__', id),
    store: @json(route('brand-health.content-insights.store')),
    update: (id) => @json(route('brand-health.content-insights.update', '__ID__')).replace('__ID__', id),
    destroy: (id) => @json(route('brand-health.content-insights.destroy', '__ID__')).replace('__ID__', id),
};
const CAN_EDIT = @json($canEdit);
const ciCsrf = document.querySelector('meta[name="csrf-token"]').content;

/* Same weighting as Brand Matrix (and the server-side exports). */
const ci = {
    weights: { posts: [['views', .5], ['interactions', .2], ['growth', .3]], reels: [['views', .5], ['interactions', .2], ['growth', .3]], story: [['views', .8], ['interactions', .2]] },
    labels: { posts: 'Posts', reels: 'Reels', story: 'Story' },
    pct(f, t) { return f == null || t == null || f === 0 ? null : ((t - f) / f) * 100; },
    fmtPct(v) { return v === null || v === undefined ? '—' : `${v > 0 ? '+' : ''}${Math.round(v * 10) / 10}%`; },
    tone(v) { if (v === null || v === undefined) return 'color: var(--bh-ink-faint)'; if (v >= 0) return 'color: var(--status-sip-ink)'; return v > -10 ? 'color: var(--status-cukup-ink)' : 'color: var(--status-parah-ink)'; },
    month(ym) { return BH.month(ym); },
    range(s, e) { if (!s) return '—'; const f = (d) => new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); return `${f(s)} – ${f(e)}`; },
    scores(from, to) {
        const growth = this.pct(from.end_follower, to.end_follower);
        return Object.entries(this.weights).map(([type, comps]) => {
            const values = { views: this.pct(from[`viewers_${type}`], to[`viewers_${type}`]), interactions: this.pct(from[`interactions_${type}`], to[`interactions_${type}`]), growth };
            const avail = comps.filter(([s]) => values[s] !== null), sum = avail.reduce((t, [, w]) => t + w, 0);
            return { key: type, label: this.labels[type], score: avail.length ? avail.reduce((t, [s, w]) => t + (values[s] * w) / sum, 0) : null, components: comps.map(([source, weight]) => ({ source, weight, value: values[source] })) };
        });
    },
    trend(row) { const s = (row.weightedScores ?? []).map((x) => x.score).filter((x) => x !== null); if (!s.length) return 'no_data'; const a = s.reduce((t, x) => t + x, 0) / s.length; return a <= -1 ? 'down' : a >= 1 ? 'up' : 'flat'; },
};

/* ---- table state ---- */
const ciTable = (function () {
    const S = { search: '', pm: '', trends: [], from: '', to: '', sort: 'account', dir: 'asc', page: 1 };
    const PAGE = 15;
    const hasRange = () => !!S.from && !!S.to;
    const $ = (s) => document.querySelector(s);

    function accountRows() {
        if (hasRange()) {
            const by = new Map();
            CI.forEach((i) => { if (i.month !== S.from && i.month !== S.to) return; if (!by.has(i.account_id)) by.set(i.account_id, {}); by.get(i.account_id)[i.month] = i; });
            return [...by.values()].filter((p) => p[S.from] && p[S.to]).map((p) => ({ current: p[S.to], previous: p[S.from], weightedScores: ci.scores(p[S.from], p[S.to]) }));
        }
        const latest = new Map();
        CI.forEach((i) => { if (!i.cycle_start_date) return; const e = latest.get(i.account_id); if (!e || i.cycle_start_date > e.cycle_start_date) latest.set(i.account_id, i); });
        return [...latest.values()].map((ins) => {
            const prev = CI.filter((i) => i.account_id === ins.account_id && i.cycle_start_date && i.cycle_start_date < ins.cycle_start_date).sort((a, b) => (a.cycle_start_date < b.cycle_start_date ? 1 : -1))[0] ?? null;
            return { current: ins, previous: prev, weightedScores: prev ? ci.scores(prev, ins) : null };
        });
    }
    const scoreFor = (row, type) => row.weightedScores?.find((s) => s.key === type)?.score ?? null;

    function rows() {
        const q = S.search.trim().toLowerCase();
        let list = accountRows().filter((r) => (!q || (r.current.account_name || '').toLowerCase().includes(q))
            && (!S.pm || String(r.current.project_manager_id) === String(S.pm))
            && (!S.trends.length || S.trends.includes(ci.trend(r))));
        if (S.sort === 'account') {
            list.sort((a, b) => (a.current.account_name || '').localeCompare(b.current.account_name || ''));
            if (S.dir === 'desc') list.reverse();
            return list;
        }
        const withV = list.filter((r) => scoreFor(r, S.sort) !== null).sort((a, b) => scoreFor(a, S.sort) - scoreFor(b, S.sort));
        if (S.dir === 'desc') withV.reverse();
        return [...withV, ...list.filter((r) => scoreFor(r, S.sort) === null)];
    }

    function exportQuery() { return { search: S.search || undefined, pm: S.pm || undefined, trend: S.trends.length ? S.trends.join(',') : undefined, month_from: hasRange() ? S.from : undefined, month_to: hasRange() ? S.to : undefined, sort: S.sort, direction: S.dir }; }

    let current = [];
    function render() {
        const all = rows();
        current = all;
        const pages = Math.max(1, Math.ceil(all.length / PAGE));
        S.page = Math.min(S.page, pages);
        const slice = all.slice((S.page - 1) * PAGE, S.page * PAGE);
        $('[data-stat-accounts]').textContent = accountRows().length;
        $('[data-ci-scope]').textContent = hasRange() ? 'Scored against the picked months' : "Scored against each account's previous cycle";
        const empty = $('[data-ci-range-empty]');
        empty.classList.toggle('d-none', !(hasRange() && !accountRows().length));
        empty.textContent = `No account has a Content Insight in both ${ci.month(S.from)} and ${ci.month(S.to)}.`;
        const count = (S.pm ? 1 : 0) + (S.trends.length ? 1 : 0) + (hasRange() ? 1 : 0);
        $('[data-ci-filter-count]').textContent = count; $('[data-ci-filter-count]').classList.toggle('d-none', !count);
        $('[data-ci-clear]').classList.toggle('d-none', !(count || S.search));
        $('[data-ci-excel]').href = CI_URLS.excel + BH.qs(exportQuery());
        $('[data-ci-pdf]').href = CI_URLS.pdf + BH.qs(exportQuery());
        document.querySelectorAll('[data-ci-sort]').forEach((th) => { th.classList.toggle('sorted', th.dataset.ciSort === S.sort); th.querySelector('span').textContent = th.dataset.ciSort === S.sort ? (S.dir === 'asc' ? '↑' : '↓') : ''; });

        $('[data-ci-body]').innerHTML = slice.map((r, i) => {
            const idx = (S.page - 1) * PAGE + i;
            return `<tr class="ci-row" data-row="${idx}">
                <td><div class="fw-semibold text-truncate" style="max-width: 18rem">${BH.esc(r.current.account_name || '—')}</div>
                    <small class="bh-faint"><span class="bh-pill me-1">${BH.esc((r.current.platform || '').replace(/^./, (c) => c.toUpperCase()))}</span><span class="bh-num">${ci.range(r.current.cycle_start_date, r.current.cycle_end_date)}</span></small></td>
                ${['posts', 'reels', 'story'].map((t) => { const s = scoreFor(r, t); return `<td class="text-end"><span class="ci-score" style="${ci.tone(s)}" title="${s === null ? 'Not enough data' : ci.labels[t] + ': ' + ci.fmtPct(s)}">${s === null ? '—' : (s >= 0 ? '▲ ' : '▼ ') + ci.fmtPct(s)}</span></td>`; }).join('')}
                <td class="text-end" onclick="event.stopPropagation()">${CAN_EDIT ? `<div class="dropdown"><button class="btn btn-sm btn-light-secondary" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#" data-edit="${idx}">Edit</a></li>
                    <li><form method="POST" action="${CI_URLS.destroy(r.current.id)}" onsubmit="return confirmDelete(this, 'This will permanently remove this content insight for ${BH.esc((r.current.account_name || '').replace(/'/g, '\\&#39;'))}.', 'Delete this content insight?')"><input type="hidden" name="_token" value="${ciCsrf}"><input type="hidden" name="_method" value="DELETE"><button type="submit" class="dropdown-item text-danger">Delete</button></form></li></ul></div>` : ''}</td></tr>`;
        }).join('') || `<tr><td colspan="5" class="bh-empty">${CI.length ? 'No accounts match these filters.' : 'No content insights yet.'}</td></tr>`;

        $('[data-ci-pager]').innerHTML = `<small class="bh-muted">Page <b>${S.page}</b> of <b>${pages}</b> (${all.length} account${all.length === 1 ? '' : 's'})</small>` +
            (pages > 1 ? `<div class="btn-group btn-group-sm">${Array.from({ length: pages }, (_, n) => `<button type="button" class="btn ${n + 1 === S.page ? 'btn-primary' : 'btn-light-secondary'}" data-page="${n + 1}">${n + 1}</button>`).join('')}</div>` : '');
    }

    let t;
    $('[data-ci-search]').addEventListener('input', (e) => { clearTimeout(t); t = setTimeout(() => { S.search = e.target.value; S.page = 1; render(); }, 250); });
    $('[data-ci-clear]').addEventListener('click', () => { Object.assign(S, { search: '', pm: '', trends: [], from: '', to: '', page: 1 }); $('[data-ci-search]').value = ''; render(); });
    document.querySelectorAll('[data-ci-sort]').forEach((th) => th.addEventListener('click', () => {
        const k = th.dataset.ciSort;
        if (S.sort === k) S.dir = S.dir === 'asc' ? 'desc' : 'asc'; else { S.sort = k; S.dir = k === 'account' ? 'asc' : 'desc'; }
        S.page = 1; render();
    }));
    $('[data-ci-pager]').addEventListener('click', (e) => { const b = e.target.closest('[data-page]'); if (b) { S.page = +b.dataset.page; render(); } });
    $('[data-ci-body]').addEventListener('click', (e) => {
        const edit = e.target.closest('[data-edit]');
        if (edit) { e.preventDefault(); return ciForm.open(current[+edit.dataset.edit].current); }
        const tr = e.target.closest('[data-row]');
        if (tr) ciDetail.open(current[+tr.dataset.row], hasRange());
    });

    // Filter modal (draft until Apply)
    const fm = document.getElementById('ci-filter-modal');
    let draftTrends = [];
    const syncTrendButtons = () => fm.querySelectorAll('[data-trend]').forEach((b) => b.classList.toggle('active', draftTrends.includes(b.dataset.trend)));
    const setSel = (sel, v) => { sel.value = v; if (sel._choices) sel._choices.setChoiceByValue(String(v)); };
    fm.addEventListener('show.bs.modal', () => { setSel(fm.querySelector('[data-ci-pm]'), S.pm); fm.querySelector('[data-ci-from]').value = S.from; fm.querySelector('[data-ci-to]').value = S.to; draftTrends = [...S.trends]; syncTrendButtons(); });
    fm.querySelectorAll('[data-trend]').forEach((b) => b.addEventListener('click', () => { const v = b.dataset.trend; draftTrends = draftTrends.includes(v) ? draftTrends.filter((x) => x !== v) : [...draftTrends, v]; syncTrendButtons(); }));
    fm.querySelector('[data-ci-filter-reset]').addEventListener('click', () => { setSel(fm.querySelector('[data-ci-pm]'), ''); fm.querySelector('[data-ci-from]').value = ''; fm.querySelector('[data-ci-to]').value = ''; draftTrends = []; syncTrendButtons(); });
    fm.querySelector('[data-ci-filter-apply]').addEventListener('click', () => {
        Object.assign(S, { pm: fm.querySelector('[data-ci-pm]').value, trends: draftTrends, from: fm.querySelector('[data-ci-from]').value, to: fm.querySelector('[data-ci-to]').value, page: 1 });
        bootstrap.Modal.getInstance(fm).hide(); render();
    });

    render();
    return { render };
})();

/* ---- detail ---- */
const ciDetail = (function () {
    const el = document.getElementById('ci-detail-modal');
    const body = el.querySelector('[data-cd-body]');
    let row, history, rangeFrom = '', rangeTo = '', chart = null;
    const num = (v) => Number(v || 0).toLocaleString('en-US');
    const forMonth = (ym) => history.find((h) => h.month === ym) ?? null;
    const picked = () => !!rangeFrom && !!rangeTo;

    function render() {
        const to = picked() ? forMonth(rangeTo) : row.current;
        const from = picked() ? forMonth(rangeFrom) : row.previous;
        const scores = from && to ? ci.scores(from, to) : null;
        const months = [...new Set(history.map((h) => h.month).filter(Boolean))].sort();
        const ranged = picked() ? history.filter((h) => h.month >= rangeFrom && h.month <= rangeTo) : history;
        const growth = from && to ? ci.pct(from.end_follower, to.end_follower) : null;
        el.querySelector('[data-cd-name]').textContent = row.current.account_name || '—';
        el.querySelector('[data-cd-sub]').textContent = `${(row.current.platform || '').replace(/^./, (c) => c.toUpperCase())} · ${ci.range(to?.cycle_start_date, to?.cycle_end_date)}`;
        const breakdown = (prefix) => ['posts', 'reels', 'story'].map((t) => { const c = to?.[`${prefix}_${t}`] ?? 0, p = from ? (from[`${prefix}_${t}`] ?? 0) : null; return { label: t[0].toUpperCase() + t.slice(1), c, p, d: p === null ? null : ci.pct(p, c) }; });

        body.innerHTML = `
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <small class="bh-muted">Compare</small>
                <select class="form-select form-select-sm" style="width: auto" data-cd-from data-no-search><option value="">Month A</option>${months.map((m) => `<option value="${m}" ${m === rangeFrom ? 'selected' : ''}>${ci.month(m)}</option>`).join('')}</select>
                <span class="bh-faint">→</span>
                <select class="form-select form-select-sm" style="width: auto" data-cd-to data-no-search><option value="">Month B</option>${months.map((m) => `<option value="${m}" ${m === rangeTo ? 'selected' : ''}>${ci.month(m)}</option>`).join('')}</select>
                ${picked() ? '<button type="button" class="btn btn-sm btn-link text-decoration-none" data-cd-clear>Clear</button>' : ''}
                <span class="ms-auto small"><span class="bh-faint">Follower growth</span> <b style="${ci.tone(growth)}">${growth === null ? '—' : (growth >= 0 ? '▲ ' : '▼ ') + ci.fmtPct(growth)}</b></span>
            </div>
            ${scores ? `<div class="row g-3">${scores.map((s) => `<div class="col-md-4"><div class="ci-tile">
                <div class="bh-stat-label">${s.label}</div>
                <div class="fs-3 fw-bold bh-num" style="${ci.tone(s.score)}">${s.score === null ? '—' : (s.score >= 0 ? '▲ ' : '▼ ') + ci.fmtPct(s.score)}</div>
                <div class="border-top pt-2 mt-2" style="border-color: var(--bh-border) !important">${s.components.map((c) => `<div class="d-flex justify-content-between small"><span class="bh-muted">${{ views: 'Views', interactions: 'Interactions', growth: 'Growth' }[c.source]} <span class="bh-faint">(${Math.round(c.weight * 100)}%)</span></span><span class="fw-semibold bh-num" style="${ci.tone(c.value)}">${c.value === null ? 'n/a' : ci.fmtPct(c.value)}</span></div>`).join('')}</div>
            </div></div>`).join('')}</div>` : '<p class="bh-empty">No earlier cycle to compare against.</p>'}
            <div class="bh-card mt-3">
                <h6 class="fw-bold mb-0">Weighted Score Trend</h6>
                <small class="bh-faint">Posts, Reels, and Story, cycle over cycle${picked() ? ', within the picked range' : ''}.</small>
                <div class="bh-chart mt-3" style="height: 240px"><canvas></canvas></div>
            </div>
            <div class="row g-3 mt-1">${[['viewers', 'Viewers'], ['interactions', 'Interactions']].map(([k, l]) => `<div class="col-md-6"><div class="ci-tile">
                <div class="bh-stat-label mb-2">${l}</div>
                ${breakdown(k).map((p) => `<div class="d-flex justify-content-between small py-1"><span class="bh-muted">${p.label}</span><span class="bh-num">${p.p !== null ? `<span class="bh-faint">${num(p.p)} →</span> ` : ''}<b>${num(p.c)}</b>${p.d !== null ? ` <span style="${ci.tone(p.d)}">(${ci.fmtPct(p.d)})</span>` : ''}</span></div>`).join('')}
            </div></div>`).join('')}</div>`;

        const labels = [], series = { posts: [], reels: [], story: [] };
        for (let i = 1; i < ranged.length; i++) {
            const sc = Object.fromEntries(ci.scores(ranged[i - 1], ranged[i]).map((s) => [s.key, s.score]));
            labels.push(ci.month(ranged[i].month));
            Object.keys(series).forEach((k) => series[k].push(sc[k] === null ? null : Math.round(sc[k] * 10) / 10));
        }
        chart?.destroy();
        const css = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
        const colors = [css('--bh-accent') || '#435ebe', '#c2410c', '#7c3aed'];
        requestAnimationFrame(() => {
            chart = BH.chart(body.querySelector('canvas'), { type: 'line',
                data: { labels, datasets: ['posts', 'reels', 'story'].map((k, i) => ({ label: ci.labels[k], data: series[k], borderColor: colors[i], backgroundColor: colors[i], pointRadius: 3, tension: .3, spanGaps: true, fill: false })) },
                options: { responsive: true, maintainAspectRatio: false, plugins: { tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${ci.fmtPct(c.parsed.y)}` } } }, scales: { y: { ticks: { callback: (v) => v + '%' } } } } });
        });
    }

    body.addEventListener('change', (e) => { if (e.target.matches('[data-cd-from]')) { rangeFrom = e.target.value; render(); } if (e.target.matches('[data-cd-to]')) { rangeTo = e.target.value; render(); } });
    body.addEventListener('click', (e) => { if (e.target.closest('[data-cd-clear]')) { rangeFrom = ''; rangeTo = ''; render(); } });
    el.addEventListener('hidden.bs.modal', () => chart?.destroy());

    return {
        open(r, tableRange) {
            row = r;
            history = CI.filter((i) => i.account_id === r.current.account_id && i.cycle_start_date).sort((a, b) => (a.cycle_start_date < b.cycle_start_date ? -1 : 1));
            rangeFrom = tableRange ? r.previous?.month ?? '' : ''; rangeTo = tableRange ? r.current?.month ?? '' : '';
            bootstrap.Modal.getOrCreateInstance(el).show();
            render();
        },
    };
})();

/* ---- add / edit ---- */
const ciForm = (function () {
    const modal = document.getElementById('ci-form-modal');
    if (!modal) return { open() {} };
    const form = document.getElementById('ci-form');
    const f = (n) => form.querySelector(`[name="${n}"]`);
    const fields = ['viewers_posts', 'viewers_reels', 'viewers_story', 'interactions_posts', 'interactions_reels', 'interactions_story'];
    let cycles = [], editing = null;

    function totals() {
        form.querySelectorAll('[data-cf-section]').forEach((sec) => {
            const inputs = [...sec.querySelectorAll('input[type=number]')];
            const total = inputs.reduce((t, i) => t + Math.max(0, Number(i.value) || 0), 0);
            sec.querySelector('[data-cf-total]').textContent = total.toLocaleString('en-US');
            inputs.forEach((i) => i.parentElement.querySelector('[data-cf-share]').style.width = total ? `${(Math.max(0, Number(i.value) || 0) / total) * 100}%` : '0');
        });
    }
    form.addEventListener('input', totals);

    async function loadCycles(accountId, selectId = null) {
        const sel = f('cycle_id');
        sel.innerHTML = '<option value="">Loading cycles…</option>';
        if (!accountId) { sel.innerHTML = '<option value="">Select an account first</option>'; return; }
        try {
            cycles = await BH.request(CI_URLS.cycles(accountId));
            const mixed = new Set(cycles.map((c) => c.platform)).size > 1;
            sel.innerHTML = '<option value="">Select a cycle</option>' + cycles.map((c) => `<option value="${c.id}">${BH.esc(c.label)}${mixed ? '' : ''}${c.insight ? ' ✓' : ''}</option>`).join('');
            if (selectId) sel.value = selectId;
        } catch { sel.innerHTML = '<option value="">Could not load cycles</option>'; }
    }
    f('account_id').addEventListener('change', () => loadCycles(f('account_id').value));
    // Picking a cycle that already has data fills it in (saving then updates that record).
    f('cycle_id').addEventListener('change', () => {
        const c = cycles.find((x) => String(x.id) === f('cycle_id').value);
        if (c?.insight && !editing) fields.forEach((k) => f(k).value = c.insight[k]);
        totals();
    });

    function open(ins) {
        editing = ins ?? null;
        form.action = ins ? CI_URLS.update(ins.id) : CI_URLS.store;
        f('_method').value = ins ? 'PUT' : 'POST';
        form.querySelector('[data-cf-title]').textContent = ins ? 'Edit Content Insight' : 'Add Content Insight';
        const acc = f('account_id'); acc.value = ins?.account_id ?? ''; if (acc._choices) acc._choices.setChoiceByValue(String(ins?.account_id ?? ''));
        fields.forEach((k) => f(k).value = ins ? (ins[k] ?? 0) : 0);
        loadCycles(ins?.account_id, ins?.cycle_id);
        totals();
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
    return { open };
})();
</script>
@endsection
