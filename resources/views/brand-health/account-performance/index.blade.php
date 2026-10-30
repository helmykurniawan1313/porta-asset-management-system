@extends('brand-health.layout')

@section('title', 'Account Performance | Brand Health')

@section('bh-styles')
<style>
    .bh-row-click { cursor: pointer; }
    .bh-plat-tag { border-radius: 6px; padding: .1rem .4rem; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; background: var(--bh-accent-soft); color: var(--bh-accent); }
    .bh-label-check { display: inline-flex; align-items: center; gap: .35rem; margin: 0 .5rem .4rem 0; cursor: pointer; }
</style>
@endsection

@section('bh-content')
@php
    $query = request()->except('page');
    $activeFilterCount = count($filters['health_label'] ?? []) + ($filters['project_manager_id'] ? 1 : 0) + ($filters['month_to'] ? 1 : 0);
    $hasFilters = filled($filters['search']) || $activeFilterCount > 0;
    $list = collect($rows->items());
    $monthLabel = fn ($ym) => \Carbon\Carbon::createFromFormat('Y-m-d', $ym.'-01')->format('M Y');
    $r = fn ($v) => $v === null ? '—' : round($v, 2);
@endphp
<div class="bh-page-head">
    <div>
        <h3>Account Performance</h3>
        <p>Each account's most recent cycle — same Growth/Visibility/Engagement/Health breakdown as Cycles, grouped down to one row per account and platform. Click a row for its month-over-month trend.</p>
    </div>
</div>

<div class="d-flex flex-wrap align-items-center gap-2">
    <form method="GET" class="position-relative" style="width: 16rem">
        @foreach(request()->except(['search', 'page']) as $k => $v)
            @if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control ps-5" placeholder="Search accounts…" autocomplete="off" data-debounce>
    </form>
    <button type="button" class="btn btn-light-secondary" data-bs-toggle="modal" data-bs-target="#filter-modal">
        <i class="bi bi-funnel"></i> Filter @if($activeFilterCount)<span class="badge bg-primary rounded-pill ms-1">{{ $activeFilterCount }}</span>@endif
    </button>
    @if($hasFilters)<a href="{{ route('brand-health.account-performance.index') }}" class="btn btn-link text-decoration-none bh-muted">Clear filters</a>@endif
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('brand-health.account-performance.excel', $query) }}" class="btn btn-light-secondary"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        <a href="{{ route('brand-health.account-performance.pdf', $query) }}" class="btn btn-light-secondary"><i class="bi bi-download"></i> Download PDF</a>
    </div>
</div>
@if($filters['month_to'])
    <small class="bh-faint d-block mt-2">Showing each account's cycle as of {{ $monthLabel($filters['month_to']) }}, not necessarily its latest overall.</small>
@endif

<div class="bh-card p-0 mt-3">
    <div class="d-flex justify-content-end px-3 py-2 border-bottom" style="border-color: var(--bh-border) !important">
        <button type="button" class="btn btn-sm btn-link bh-muted" data-bs-toggle="modal" data-bs-target="#scoring-info" title="How statuses are scored"><i class="bi bi-info-circle"></i></button>
    </div>
    <div class="table-responsive">
        <table class="bh-table">
            <thead><tr><th>Account</th><th>Platform</th><th>Latest Cycle</th><th>Growth</th><th>Visibility</th><th>Engagement</th><th>Health Rate</th><th>Health</th><th>PM</th></tr></thead>
            <tbody>
                @forelse($list as $i => $row)
                    <tr class="bh-row-click" data-trend="{{ $i }}">
                        <td class="fw-semibold">{{ $row['account_name'] }}</td>
                        <td><span class="bh-plat-tag">{{ $row['platform'] === 'tiktok' ? 'TikTok' : 'Instagram' }}</span></td>
                        <td class="bh-muted">{{ $row['latest_cycle_label'] ?? '—' }}</td>
                        <td class="bh-num" style="{{ ($row['growth_rate'] ?? 0) < 0 ? 'color: var(--status-parah-ink)' : '' }}">{{ $r($row['growth_rate']) }}%</td>
                        <td class="bh-num bh-muted">{{ $r($row['visibility_rate']) }}</td>
                        <td class="bh-num bh-muted">{{ $r($row['engagement_score']) }}</td>
                        <td class="bh-num">{{ $r($row['health_rate']) }}</td>
                        <td><x-bh.status :status="$row['health_label']" /></td>
                        <td class="bh-muted">{{ $row['project_manager']['name'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="bh-empty">{{ $hasFilters ? 'No accounts match these filters.' : 'No accounts yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($rows->lastPage() > 1)
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
        <small class="bh-muted">Showing <b class="bh-num">{{ $rows->firstItem() }}–{{ $rows->lastItem() }}</b> of <b class="bh-num">{{ $rows->total() }}</b></small>
        {{ $rows->onEachSide(1)->links('brand-health.partials.pager') }}
    </div>
@endif
@endsection

@section('bh-modals')
<div class="modal fade" id="filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="GET" action="{{ route('brand-health.account-performance.index') }}" class="modal-content border-0 shadow" id="filter-form">
            <div class="modal-header"><h5 class="modal-title fw-bold">Filter</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                @if($filters['search'])<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                <label class="form-label small fw-semibold bh-muted">Project Manager</label>
                <select name="project_manager_id" class="form-select mb-3">
                    <option value="">All PMs</option>
                    @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}" @selected($filters['project_manager_id'] == $e->id)>{{ $e->name }}</option>@endforeach
                </select>
                <div class="pt-3 border-top mb-3" style="border-color: var(--bh-border) !important" data-label-group>
                    <label class="form-label small fw-semibold bh-muted d-block">Health</label>
                    @foreach($healthLabels as $label)
                        <label class="bh-label-check"><input type="checkbox" class="form-check-input m-0" value="{{ $label }}" @checked(in_array($label, $filters['health_label'] ?? []))> <x-bh.status :status="$label" /></label>
                    @endforeach
                    <input type="hidden" name="health_label" value="{{ implode(',', $filters['health_label'] ?? []) }}">
                </div>
                <div class="pt-3 border-top" style="border-color: var(--bh-border) !important">
                    <label class="form-label small fw-semibold bh-muted">As of month</label>
                    <small class="bh-muted d-block mb-2">Shows each account's cycle at/before the picked month instead of its true latest. Leave blank to use each account's actual latest cycle.</small>
                    <select name="month_to" class="form-select" data-no-search>
                        <option value="">Latest cycle</option>
                        @foreach($months->reverse() as $m)<option value="{{ $m }}" @selected($filters['month_to'] === $m)>{{ $monthLabel($m) }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-decoration-none bh-muted" id="filter-clear">Clear all</button>
                <button type="submit" class="btn btn-primary">Apply filters</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="trend-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header align-items-start">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h5 class="modal-title fw-bold" data-tr-name></h5>
                        <span class="bh-plat-tag" data-tr-platform></span>
                        <span data-tr-health></span>
                    </div>
                    <small class="bh-muted">Growth, Visibility, Engagement, and Health — one cycle per month, oldest to latest.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-tr-body></div>
        </div>
    </div>
</div>

@include('brand-health.partials.scoring-info-modal', ['metrics' => ['growth', 'visibility', 'engagement', 'health'], 'id' => 'scoring-info'])
@endsection

@section('bh-scripts')
<script>
(function () {
    const ROWS = @json($list->values());
    const trendUrl = (id) => @json(route('brand-health.account-performance.trend', '__ID__')).replace('__ID__', id);
    const input = document.querySelector('[data-debounce]');
    let t; input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => input.form.submit(), 400); });

    const ff = document.getElementById('filter-form');
    ff.addEventListener('submit', () => {
        ff.querySelectorAll('[data-label-group]').forEach((g) => { g.querySelector('input[type=hidden]').value = [...g.querySelectorAll('input:checked')].map((c) => c.value).join(','); });
        ff.querySelectorAll('input[name], select[name]').forEach((f) => { if (!f.value) f.disabled = true; });
    });
    document.getElementById('filter-clear').addEventListener('click', () => {
        ff.querySelectorAll('input[type=checkbox]').forEach((c) => c.checked = false);
        ff.querySelectorAll('select').forEach((s) => { s.value = ''; if (s._choices) s._choices.setChoiceByValue(''); });
    });

    const el = document.getElementById('trend-modal');
    const body = el.querySelector('[data-tr-body]');
    let chart = null;
    const r1 = (v) => (v === null || v === undefined ? '—' : Math.round(v * 10) / 10);
    const css = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim();

    document.querySelectorAll('[data-trend]').forEach((tr) => tr.addEventListener('click', async () => {
        const row = ROWS[+tr.dataset.trend];
        el.querySelector('[data-tr-name]').textContent = row.account_name;
        el.querySelector('[data-tr-platform]').textContent = row.platform === 'tiktok' ? 'TikTok' : 'Instagram';
        el.querySelector('[data-tr-health]').innerHTML = '';
        body.innerHTML = '<p class="bh-empty py-5">Loading trend…</p>';
        chart?.destroy();
        bootstrap.Modal.getOrCreateInstance(el).show();
        try {
            const data = await BH.request(trendUrl(row.account_id) + BH.qs({ platform: row.platform }));
            const series = data.series ?? [];
            if (!series.length) { body.innerHTML = '<p class="bh-empty py-5">No cycles recorded for this account on this platform.</p>'; return; }
            el.querySelector('[data-tr-health]').innerHTML = BH.statusBadge(series.at(-1).health_label);
            body.innerHTML = `<div class="bh-chart" style="height: 18rem"><canvas></canvas></div>
                <div class="border rounded-3 mt-4" style="border-color: var(--bh-border) !important"><table class="bh-table">
                <thead><tr><th>Month</th><th>Growth</th><th>Visibility</th><th>Engagement</th><th>Health Rate</th><th>Health</th></tr></thead>
                <tbody>${[...series].reverse().map((p) => `<tr><td>${BH.esc(p.month)}</td><td class="bh-num bh-muted">${r1(p.growth_rate)}%</td><td class="bh-num bh-muted">${r1(p.visibility_rate)}</td><td class="bh-num bh-muted">${r1(p.engagement_score)}</td><td class="bh-num">${r1(p.health_rate)}</td><td>${BH.statusBadge(p.health_label)}</td></tr>`).join('')}</tbody></table></div>`;
            if (series.length >= 2) {
                const colors = [css('--bh-accent') || '#435ebe', '#c2410c', '#7c3aed', '#1d4ed8'];
                const metrics = [['growth_rate', 'Growth'], ['visibility_rate', 'Visibility'], ['engagement_score', 'Engagement'], ['health_rate', 'Health']];
                requestAnimationFrame(() => {
                    chart = BH.chart(body.querySelector('canvas'), {
                        type: 'line',
                        data: { labels: series.map((p) => p.month), datasets: metrics.map(([k, l], i) => ({ label: l, data: series.map((p) => p[k]), borderColor: colors[i], backgroundColor: colors[i],
                            pointBackgroundColor: colors[i], pointBorderColor: css('--bh-surface'), pointBorderWidth: 1.5, pointRadius: 3, borderWidth: 2, fill: false, tension: .25 })) },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } },
                            scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } },
                    });
                });
            }
        } catch (e) { body.innerHTML = `<p class="bh-empty py-5">${BH.esc(e.message)}</p>`; }
    }));
    el.addEventListener('hidden.bs.modal', () => chart?.destroy());
})();
</script>
@endsection
