@extends('brand-health.layout')

@section('title', 'Account Growth | Brand Health')
@section('header_title', 'Brand Health')

@section('bh-styles')
<style>
    .bh-row-click { cursor: pointer; }
    .bh-label-check { display: inline-flex; align-items: center; gap: .35rem; margin: 0 .5rem .4rem 0; cursor: pointer; }
</style>
@endsection

@section('bh-content')
@php
    $activeFilterCount = collect(['growth_label', 'reach_status', 'views_status', 'engagement_status'])->sum(fn ($k) => count($filters[$k] ?? [])) + ($filters['project_manager_id'] ? 1 : 0);
    $hasFilters = filled($filters['search']) || $activeFilterCount > 0;
    $rows = collect($accounts->items());
@endphp
<div class="bh-page-head">
    <div>
        <h3>Account Growth</h3>
        <p>Whether each account is improving or declining versus its previous cycle — a TREND, not a level.
            For each account's current health, see <a href="{{ route('brand-health.account-health.index') }}" class="fw-semibold">Account Health</a>.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Total Accounts</div><div class="bh-stat-value">{{ $summary['total'] }}</div></div></div>
    <div class="col-6 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Improving</div><div class="bh-stat-value" style="color: var(--status-sip-ink)">{{ $summary['improving'] }}</div><div class="bh-stat-hint">at least one of Reach / Views / Engagement not declining</div></div></div>
    <div class="col-12 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Declining</div><div class="bh-stat-value" style="color: var(--status-parah-ink)">{{ $summary['declining'] }}</div><div class="bh-stat-hint">vs. previous cycle</div></div></div>
</div>

<form method="GET" class="d-flex flex-wrap align-items-center gap-2 mt-4">
    @foreach(request()->except(['search', 'page']) as $k => $v)
        @if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <div class="position-relative" style="width: 16rem">
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control ps-5" placeholder="Search accounts…" autocomplete="off" data-debounce>
    </div>
    <button type="button" class="btn btn-light-secondary" data-bs-toggle="modal" data-bs-target="#filter-modal">
        <i class="bi bi-funnel"></i> Filter @if($activeFilterCount)<span class="badge bg-primary rounded-pill ms-1">{{ $activeFilterCount }}</span>@endif
    </button>
    @if($hasFilters)<a href="{{ route('brand-health.account-growth.index') }}" class="btn btn-link text-decoration-none bh-muted">Clear filters</a>@endif
</form>

<div class="bh-card p-0 mt-3">
    <div class="d-flex justify-content-end px-3 py-2 border-bottom" style="border-color: var(--bh-border) !important">
        <button type="button" class="btn btn-sm btn-link bh-muted" data-bs-toggle="modal" data-bs-target="#scoring-info" title="How statuses are scored"><i class="bi bi-info-circle"></i></button>
    </div>
    <div class="table-responsive">
        <table class="bh-table">
            <thead>
                <tr>
                    <th rowspan="2" class="align-bottom">Account</th>
                    <th rowspan="2" class="align-bottom">Latest Cycle</th>
                    <th rowspan="2" class="align-bottom">Follower Growth</th>
                    <th colspan="3" class="text-center">vs. previous cycle</th>
                    <th rowspan="2" class="align-bottom">PM</th>
                </tr>
                <tr><th>Reach</th><th>Views</th><th>Engagement</th></tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $a)
                    <tr class="bh-row-click" data-growth="{{ $i }}">
                        <td class="fw-semibold">{{ $a['name'] }}</td>
                        <td class="bh-muted">{{ $a['latest_cycle_label'] ?? '—' }}</td>
                        <td>@if($a['growth_label'])<x-bh.status :status="$a['growth_label']" />@else<span class="bh-faint">No data</span>@endif</td>
                        @foreach(['reach_status', 'views_status', 'engagement_status'] as $k)
                            <td>@if($a[$k])<x-bh.status :status="$a[$k]" />@else<span class="bh-faint">No prior cycle</span>@endif</td>
                        @endforeach
                        <td class="bh-muted">{{ $a['project_manager']['name'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="bh-empty">{{ $hasFilters ? 'No accounts match these filters.' : 'No accounts yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($accounts->lastPage() > 1)
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
        <small class="bh-muted">Showing <b class="bh-num">{{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}</b> of <b class="bh-num">{{ $accounts->total() }}</b></small>
        {{ $accounts->onEachSide(1)->links() }}
    </div>
@endif
@endsection

@section('bh-modals')
<div class="modal fade" id="filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="GET" action="{{ route('brand-health.account-growth.index') }}" class="modal-content border-0 shadow" id="filter-form">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Filter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($filters['search'])<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                <label class="form-label small fw-semibold bh-muted">Project Manager</label>
                <select name="project_manager_id" class="form-select mb-3">
                    <option value="">All PMs</option>
                    @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}" @selected($filters['project_manager_id'] == $e->id)>{{ $e->name }}</option>@endforeach
                </select>
                @foreach(['growth_label' => ['Follower Growth', $growthLabels], 'reach_status' => ['Reach (vs. last cycle)', $reachLabels], 'views_status' => ['Views (vs. last cycle)', $viewsLabels], 'engagement_status' => ['Engagement (vs. last cycle)', $engagementLabels]] as $field => [$title, $labels])
                    <div class="mb-3 pt-3 border-top" style="border-color: var(--bh-border) !important" data-label-group>
                        <label class="form-label small fw-semibold bh-muted d-block">{{ $title }}</label>
                        @forelse($labels as $label)
                            <label class="bh-label-check"><input type="checkbox" class="form-check-input m-0" value="{{ $label }}" @checked(in_array($label, $filters[$field] ?? []))> <x-bh.status :status="$label" /></label>
                        @empty <small class="bh-faint">No labels configured.</small> @endforelse
                        <input type="hidden" name="{{ $field }}" value="{{ implode(',', $filters[$field] ?? []) }}">
                    </div>
                @endforeach
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-decoration-none bh-muted" id="filter-clear">Clear all</button>
                <button type="submit" class="btn btn-primary">Apply filters</button>
            </div>
        </form>
    </div>
</div>
@include('brand-health.partials.scoring-info-modal', ['metrics' => ['account_growth', 'reach_change', 'views_change', 'engagement_change'], 'id' => 'scoring-info'])
@include('brand-health.partials.account-growth-modal')
@endsection

@section('bh-scripts')
<script>
(function () {
    const ROWS = @json($rows->values());
    const input = document.querySelector('[data-debounce]');
    let t; input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => input.form.submit(), 400); });
    document.querySelectorAll('[data-growth]').forEach((r) => r.addEventListener('click', () => BH.openAccountGrowth(ROWS[+r.dataset.growth])));

    const ff = document.getElementById('filter-form');
    ff.addEventListener('submit', () => {
        ff.querySelectorAll('[data-label-group]').forEach((g) => { g.querySelector('input[type=hidden]').value = [...g.querySelectorAll('input:checked')].map((c) => c.value).join(','); });
        ff.querySelectorAll('input[name], select[name]').forEach((f) => { if (!f.value) f.disabled = true; });
    });
    document.getElementById('filter-clear').addEventListener('click', () => {
        ff.querySelectorAll('input[type=checkbox]').forEach((c) => c.checked = false);
        const pm = ff.querySelector('[name=project_manager_id]'); pm.value = ''; if (pm._choices) pm._choices.setChoiceByValue('');
    });
})();
</script>
@endsection
