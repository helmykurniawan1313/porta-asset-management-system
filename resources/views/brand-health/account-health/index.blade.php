@extends('brand-health.layout')

@section('title', 'Account Health | Brand Health')

@section('bh-styles')
<style>
    .bh-group-row td { background: var(--bh-raised); }
    .bh-plat { display: inline-block; min-width: 1.6rem; text-align: center; border-radius: 6px; padding: .05rem .3rem; font-size: .65rem; font-weight: 800; background: var(--bh-accent-soft); color: var(--bh-accent); }
    .bh-click { cursor: pointer; }
</style>
@endsection

@section('bh-content')
@php
    $hasFilters = filled($filters['search']) || filled($filters['project_manager_id']);
    $dash = '<span class="bh-faint">—</span>';
@endphp
<div class="bh-page-head">
    <div>
        <h3>Account Health</h3>
        <p>How healthy each account is right now, based on its latest cycle — a current LEVEL, not a trend.
            For whether accounts are improving or declining, see <a href="{{ route('brand-health.account-growth.index') }}" class="fw-semibold">Account Growth</a>.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Total Accounts</div><div class="bh-stat-value">{{ $summary['total'] }}</div></div></div>
    <div class="col-6 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Needs Attention</div><div class="bh-stat-value">{{ $summary['at_risk'] }}</div><div class="bh-stat-hint">Health is KURANG or PARAH</div></div></div>
    <div class="col-12 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">In Good Standing</div><div class="bh-stat-value">{{ $summary['total'] - $summary['at_risk'] }}</div><div class="bh-stat-hint">Health is SIP, BAGUS, or CUKUP</div></div></div>
</div>

<form method="GET" class="d-flex flex-wrap align-items-center gap-2 mt-4" id="filters">
    <div class="position-relative" style="width: 16rem">
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control ps-5" placeholder="Search accounts…" autocomplete="off" data-debounce>
    </div>
    <select name="project_manager_id" class="form-select" style="width: 14rem" onchange="this.form.submit()">
        <option value="">All PMs</option>
        @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}" @selected($filters['project_manager_id'] == $e->id)>{{ $e->name }}</option>@endforeach
    </select>
    @if($hasFilters)<a href="{{ route('brand-health.account-health.index') }}" class="btn btn-link text-decoration-none bh-muted">Clear filters</a>@endif
</form>

<div class="bh-card p-0 mt-3">
    <div class="d-flex justify-content-end px-3 py-2 border-bottom" style="border-color: var(--bh-border) !important">
        <button type="button" class="btn btn-sm btn-link bh-muted" data-bs-toggle="modal" data-bs-target="#scoring-info" title="How statuses are scored"><i class="bi bi-info-circle"></i></button>
    </div>
    <div class="table-responsive">
        <table class="bh-table">
            <thead><tr>
                <th>Account / Platform</th><th>Latest Cycle</th><th>Health Score</th><th>Health</th><th>Visibility</th><th>Engagement</th><th>PM</th>
            </tr></thead>
            @forelse($accounts->items() as $account)
                @php $multi = count($account['platforms']) > 1; $p = $account['platforms'][0] ?? null; @endphp
                <tbody>
                    <tr class="bh-group-row {{ $multi ? '' : 'bh-click' }}" @unless($multi) data-growth="{{ $account['id'] }}" data-name="{{ $account['name'] }}" @endunless>
                        <td class="fw-semibold" @if($multi) colspan="7" @endif>{{ $account['name'] }}</td>
                        @unless($multi)
                            <td class="bh-muted">{{ $p['latest_cycle_label'] ?? '—' }}</td>
                            <td class="bh-num">{!! isset($p['health_score']) ? e($p['health_score']) : $dash !!}</td>
                            <td>@if($p)<x-bh.status :status="$p['health_label']" />@else<span class="bh-faint">No data</span>@endif</td>
                            <td class="bh-num bh-muted">{!! isset($p['visibility_rate']) ? e($p['visibility_rate']) : $dash !!}</td>
                            <td class="bh-num bh-muted">{!! isset($p['engagement_score']) ? e($p['engagement_score']) : $dash !!}</td>
                            <td class="bh-muted">{{ $account['project_manager']['name'] ?? '—' }}</td>
                        @endunless
                    </tr>
                    @if($multi)
                        @foreach($account['platforms'] as $pl)
                            <tr class="bh-click" data-growth="{{ $account['id'] }}" data-name="{{ $account['name'] }}">
                                <td class="ps-5"><span class="bh-plat">{{ $pl['platform'] === 'tiktok' ? 'TT' : 'IG' }}</span></td>
                                <td class="bh-muted">{{ $pl['latest_cycle_label'] ?? '—' }}</td>
                                <td class="bh-num">{!! isset($pl['health_score']) ? e($pl['health_score']) : $dash !!}</td>
                                <td><x-bh.status :status="$pl['health_label']" /></td>
                                <td class="bh-num bh-muted">{!! isset($pl['visibility_rate']) ? e($pl['visibility_rate']) : $dash !!}</td>
                                <td class="bh-num bh-muted">{!! isset($pl['engagement_score']) ? e($pl['engagement_score']) : $dash !!}</td>
                                <td class="bh-muted">{{ $account['project_manager']['name'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            @empty
                <tbody><tr><td colspan="7" class="bh-empty">{{ $hasFilters ? 'No accounts match these filters.' : 'No accounts yet.' }}</td></tr></tbody>
            @endforelse
        </table>
    </div>
</div>
@if($accounts->lastPage() > 1)
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
        <small class="bh-muted">Showing <b class="bh-num">{{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}</b> of <b class="bh-num">{{ $accounts->total() }}</b></small>
        {{ $accounts->onEachSide(1)->links('brand-health.partials.pager') }}
    </div>
@endif
@endsection

@section('bh-modals')
@include('brand-health.partials.scoring-info-modal', ['metrics' => ['health'], 'id' => 'scoring-info'])
@include('brand-health.partials.account-growth-modal')
@endsection

@section('bh-scripts')
<script>
    (function () {
        const input = document.querySelector('[data-debounce]');
        let t; input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => input.form.submit(), 400); });
        document.querySelectorAll('[data-growth]').forEach((row) => row.addEventListener('click', () =>
            BH.openAccountGrowth({ id: +row.dataset.growth, name: row.dataset.name })));
    })();
</script>
@endsection
