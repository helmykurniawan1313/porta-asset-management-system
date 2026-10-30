@extends('brand-health.layout')

@section('title', 'Views Trend | Brand Health')
@section('header_title', 'Brand Health')

@section('bh-styles')
<style>
    .vt-tabs { display: inline-flex; gap: .25rem; border: 1px solid var(--bh-border); border-radius: 10px; padding: .25rem; background: var(--bh-surface); flex-wrap: wrap; }
    .vt-tabs > * { border: 0; background: transparent; border-radius: 7px; padding: .3rem .75rem; font-size: .85rem; font-weight: 500; color: var(--bh-ink-muted); text-decoration: none; }
    .vt-tabs > .active { background: var(--bh-accent); color: #fff; }
    .vt-kpi { display: block; text-decoration: none; border: 1px solid var(--bh-border); border-radius: 12px; padding: 1rem; background: var(--bh-surface); height: 100%; }
    .vt-kpi:hover { border-color: var(--bh-border-strong); }
    .vt-trend { display: inline-flex; align-items: center; gap: .4rem; border-radius: 999px; padding: .2rem .6rem; font-size: .75rem; font-weight: 600; white-space: nowrap; }
    .vt-trend::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .vt-plat { display: inline-block; border-radius: 999px; padding: .1rem .5rem; font-size: .7rem; font-weight: 700; color: #fff; }
    .vt-plat.instagram { background: #d62976; } .vt-plat.tiktok { background: #111827; }
    [data-bs-theme="dark"] .vt-plat.tiktok { background: #4b5563; }
    .vt-row { cursor: pointer; }
    .vt-sort { color: inherit; text-decoration: none; white-space: nowrap; }
    .vt-bar-row { display: flex; align-items: center; gap: .75rem; cursor: pointer; padding: .15rem 0; }
    .vt-bar-row:hover { opacity: .8; }
    .vt-bar-name { width: 10rem; flex-shrink: 0; text-align: right; font-size: .78rem; font-weight: 500; color: var(--bh-ink-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vt-bar-track { position: relative; flex: 1; height: 1.4rem; }
    .vt-bar-track::before { content: ''; position: absolute; top: 0; bottom: 0; left: 50%; width: 1px; background: var(--bh-border-strong); }
    .vt-bar { position: absolute; top: .2rem; height: 1rem; border-radius: 3px; }
    .vt-bar-val { width: 4rem; flex-shrink: 0; text-align: right; font-size: .78rem; font-weight: 600; font-variant-numeric: tabular-nums; }
    .vt-rank { display: inline-flex; width: 1.6rem; height: 1.6rem; align-items: center; justify-content: center; border-radius: 50%; font-size: .75rem; font-weight: 700; }
    .vt-clickable tbody tr { cursor: pointer; }
    .vt-clickable th[data-sort] { cursor: pointer; user-select: none; }
</style>
@endsection

@section('bh-content')
@php
    $f = $filters;
    $trendMeta = [
        'increasing' => ['Increasing', 'background: var(--status-sip-bg); color: var(--status-sip-ink)', 'views trending up vs. prior cycle'],
        'decreasing' => ['Decreasing', 'background: var(--status-parah-bg); color: var(--status-parah-ink)', 'views trending down vs. prior cycle'],
        'insufficient_data' => ['Not enough data', 'background: var(--bh-border); color: var(--bh-ink-muted)', 'not enough cycle history to classify'],
    ];
    $group = fn ($t) => in_array($t, ['growing', 'stable']) ? 'increasing' : (in_array($t, ['setback', 'stagnant']) ? 'decreasing' : 'insufficient_data');
    // Same query convention as Brand Matrix: defaults are left out of the URL.
    $base = array_filter(['search' => $f['search'], 'platform' => $f['platform'] !== 'all' ? $f['platform'] : null, 'trend' => $f['trend'], 'month_from' => $f['month_from'], 'month_to' => $f['month_to'],
        'sort' => $f['sort'] !== 'trend' ? $f['sort'] : null, 'direction' => $f['direction'] !== 'asc' ? $f['direction'] : null], fn ($v) => filled($v));
    $url = fn (array $over) => route('brand-health.views-trend.index', array_filter(array_merge($base, $over), fn ($v) => filled($v)));
    $sortUrl = fn ($k) => $url(['sort' => $k === 'trend' ? null : $k, 'direction' => $f['sort'] === $k && $f['direction'] === 'asc' ? 'desc' : null, 'page' => null]);
    $arrow = fn ($k) => $f['sort'] === $k ? ($f['direction'] === 'asc' ? '▲' : '▼') : '';
    $mLabel = fn ($ym) => $ym ? \Carbon\Carbon::createFromFormat('Y-m-d', $ym.'-01')->format('M Y') : '';
    $num = fn ($v) => $v === null ? '—' : number_format($v);
    $deltaColor = fn ($v) => $v === null ? 'var(--bh-ink-faint)' : ($v <= -15 ? 'var(--status-parah-ink)' : ($v >= 15 ? 'var(--status-sip-ink)' : 'var(--bh-ink-muted)'));
    $filterCount = ($f['platform'] !== 'all' ? 1 : 0) + ($f['trend'] ? 1 : 0) + ($f['month_from'] || $f['month_to'] ? 1 : 0);
    $exportQuery = array_diff_key($base, ['page' => 1]);
    $withDelta = $chartRows->whereNotNull('delta_pct')->count();
@endphp
<div class="bh-page-head">
    <div>
        <h3>Account</h3>
        <p>Which accounts are dropping off, flat, or growing in post views — compared cycle over cycle.
            @if($f['month_from'] || $f['month_to'])
                <span class="bh-pill ms-1" style="background: var(--bh-accent-soft); color: var(--bh-accent)">
                    @if($f['month_from'] && $f['month_to']){{ $mLabel($f['month_from']) }} vs {{ $mLabel($f['month_to']) }}@elseif($f['month_to'])As of {{ $mLabel($f['month_to']) }}@else From {{ $mLabel($f['month_from']) }}@endif
                </span>
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-light-secondary" onclick="vtRanking.open()"><i class="bi bi-trophy"></i> Best PM &amp; Conceptor</button>
        <div class="vt-tabs">
            @foreach(['all' => 'All Platforms', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'] as $v => $l)
                <a href="{{ $url(['platform' => $v === 'all' ? null : $v, 'page' => null]) }}" class="{{ $f['platform'] === $v ? 'active' : '' }}">{{ $l }}</a>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-md-3"><div class="vt-kpi"><div class="bh-stat-label">Total</div><div class="bh-stat-value">{{ $counts['total'] }}</div><div class="bh-stat-hint">accounts matching current filters</div></div></div>
    @foreach($trendMeta as $k => [$label, $style, $hint])
        <div class="col-6 col-md-3">
            <a href="{{ $url(['trend' => $f['trend'] === $k ? null : $k, 'page' => null]) }}" class="vt-kpi" @if($f['trend'] === $k) style="{{ str_replace('background', 'background-color', explode(';', $style)[0]) }}; border-color: currentColor" @endif>
                <div class="bh-stat-label">{{ $label === 'Not enough data' ? 'No Data' : $label }}</div>
                <div class="bh-stat-value" style="{{ explode(';', $style)[1] }}">{{ $counts[$k] }}</div>
                <div class="bh-stat-hint">{{ $hint }}</div>
            </a>
        </div>
    @endforeach
</div>

<button type="button" class="bh-card w-100 text-start mt-3 d-flex flex-wrap justify-content-between align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#vt-chart-modal">
    <div>
        <div class="fw-semibold">Views change vs prior cycle</div>
        <small class="bh-muted">{{ $withDelta }} filtered account{{ $withDelta === 1 ? '' : 's' }}, ranked best to worst — click to view chart</small>
    </div>
    <div class="d-flex align-items-center gap-3 small bh-muted">
        <span><span class="d-inline-block rounded-1 me-1" style="width: 10px; height: 10px; background: var(--status-parah-ink)"></span>Down</span>
        <span><span class="d-inline-block rounded-1 me-1" style="width: 10px; height: 10px; background: var(--status-sip-ink)"></span>Up</span>
        <i class="bi bi-arrows-angle-expand"></i>
    </div>
</button>

<form method="GET" class="d-flex flex-wrap align-items-center gap-2 mt-3" id="vt-toolbar">
    @foreach(array_diff_key($base, ['search' => 1]) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <div class="position-relative" style="width: 16rem">
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" name="search" value="{{ $f['search'] }}" class="form-control ps-5" placeholder="Search by account…" autocomplete="off" data-debounce>
    </div>
    <button type="button" class="btn btn-light-secondary" data-bs-toggle="modal" data-bs-target="#vt-filter-modal"><i class="bi bi-funnel"></i> Filter @if($filterCount)<span class="badge bg-primary rounded-pill ms-1">{{ $filterCount }}</span>@endif</button>
    <div class="vt-tabs">
        @foreach(['' => 'All accounts', 'increasing' => 'Increasing', 'decreasing' => 'Decreasing', 'insufficient_data' => 'No Data'] as $v => $l)
            <a href="{{ $url(['trend' => $v ?: null, 'page' => null]) }}" class="{{ ($f['trend'] ?? '') === $v ? 'active' : '' }}">{{ $l }}</a>
        @endforeach
    </div>
    @if($filterCount || $f['search'])<a href="{{ route('brand-health.views-trend.index') }}" class="btn btn-link text-decoration-none bh-muted">Clear filters</a>@endif
    <div class="ms-auto d-flex gap-2">
        <a class="btn btn-light-secondary" href="{{ route('brand-health.views-trend.excel', $exportQuery) }}"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        <a class="btn btn-light-secondary" href="{{ route('brand-health.views-trend.pdf', $exportQuery) }}"><i class="bi bi-download"></i> Download PDF</a>
    </div>
</form>

<div class="bh-card p-0 mt-3">
    <div class="table-responsive">
        <table class="bh-table">
            <thead><tr>
                <th><a class="vt-sort" href="{{ $sortUrl('account') }}">Account {{ $arrow('account') }}</a></th>
                @if($f['platform'] === 'all')<th>Platform</th>@endif
                <th><a class="vt-sort" href="{{ $sortUrl('trend') }}">Trend {{ $arrow('trend') }}</a></th>
                @foreach(['last_avg_views' => 'Median (last)', 'prior_avg_views' => 'Median (prior)', 'last_max_views' => 'Max', 'last_min_views' => 'Min', 'last_post_count' => 'Total Reels', 'delta_pct' => 'Δ%'] as $k => $l)
                    <th class="text-end"><a class="vt-sort" href="{{ $sortUrl($k) }}">{{ $l }} {{ $arrow($k) }}</a></th>
                @endforeach
                <th>As of</th>
            </tr></thead>
            <tbody>
                @forelse($rows->items() as $r)
                    @php [$tl, $ts] = $trendMeta[$group($r['trend'])]; @endphp
                    <tr class="vt-row" onclick='vtDetail.open(@json(['id' => $r['account_id'], 'name' => $r['account_name'], 'platform' => $r['platform']]))'>
                        <td class="fw-semibold">{{ $r['account_name'] }}</td>
                        @if($f['platform'] === 'all')<td><span class="vt-plat {{ $r['platform'] }}">{{ $r['platform'] === 'tiktok' ? 'TT' : 'IG' }}</span></td>@endif
                        <td><span class="vt-trend" style="{{ $ts }}" @if($r['trend'] === 'stagnant') title="Flat for {{ $r['stagnant_streak'] }} cycles" @endif>{{ $tl }}</span></td>
                        <td class="text-end bh-num">{{ $num($r['last_avg_views']) }}</td>
                        <td class="text-end bh-num bh-muted">{{ $num($r['prior_avg_views']) }}</td>
                        <td class="text-end bh-num">{{ $num($r['last_max_views']) }}</td>
                        <td class="text-end bh-num">{{ $num($r['last_min_views']) }}</td>
                        <td class="text-end bh-num bh-muted">{{ $num($r['last_post_count']) }}</td>
                        <td class="text-end bh-num fw-semibold" style="color: {{ $deltaColor($r['delta_pct']) }}">{{ $r['delta_pct'] === null ? 'New' : ($r['delta_pct'] > 0 ? '+' : '').$r['delta_pct'].'%' }}</td>
                        <td class="bh-muted text-nowrap">{{ $r['last_cycle_label'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $f['platform'] === 'all' ? 10 : 9 }}" class="bh-empty">No accounts match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->lastPage() > 1)
        <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 border-top gap-2" style="border-color: var(--bh-border) !important">
            <small class="bh-muted">Showing <b class="bh-num">{{ $rows->firstItem() }}–{{ $rows->lastItem() }}</b> of <b class="bh-num">{{ $rows->total() }}</b></small>
            {{ $rows->onEachSide(1)->links() }}
        </div>
    @endif
</div>
@endsection

@section('bh-modals')
@php $f = $filters; $mLabel = fn ($ym) => \Carbon\Carbon::createFromFormat('Y-m-d', $ym.'-01')->format('M Y'); @endphp
<div class="modal fade" id="vt-filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="GET" class="modal-content border-0 shadow" id="vt-filter-form">
            <input type="hidden" name="search" value="{{ $f['search'] }}">
            @if($f['sort'] !== 'trend')<input type="hidden" name="sort" value="{{ $f['sort'] }}">@endif
            @if($f['direction'] !== 'asc')<input type="hidden" name="direction" value="{{ $f['direction'] }}">@endif
            <div class="modal-header"><h5 class="modal-title fw-bold">Filter</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <label class="form-label small fw-semibold bh-muted">Platform</label>
                <select name="platform" class="form-select mb-3" data-no-search>
                    @foreach(['all' => 'All Platforms', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'] as $v => $l)<option value="{{ $v }}" @selected($f['platform'] === $v)>{{ $l }}</option>@endforeach
                </select>
                <label class="form-label small fw-semibold bh-muted">Trend</label>
                <select name="trend" class="form-select mb-3" data-no-search>
                    @foreach(['' => 'All accounts', 'increasing' => 'Increasing', 'decreasing' => 'Decreasing', 'insufficient_data' => 'No Data'] as $v => $l)<option value="{{ $v }}" @selected(($f['trend'] ?? '') === $v)>{{ $l }}</option>@endforeach
                </select>
                <label class="form-label small fw-semibold bh-muted">Compare months</label>
                <div class="d-flex gap-2 align-items-center">
                    <select name="month_from" class="form-select" data-no-search><option value="">Prior</option>@foreach($months as $m)<option value="{{ $m }}" @selected($f['month_from'] === $m)>{{ $mLabel($m) }}</option>@endforeach</select>
                    <span class="bh-faint small fw-semibold">with</span>
                    <select name="month_to" class="form-select" data-no-search><option value="">Latest</option>@foreach($months as $m)<option value="{{ $m }}" @selected($f['month_to'] === $m)>{{ $mLabel($m) }}</option>@endforeach</select>
                </div>
                <small class="bh-faint d-block mt-2">Leave both as default to compare the true latest cycle against the one right before it.</small>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-decoration-none bh-muted" data-vt-reset>Reset</button>
                <div class="d-flex gap-2"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Apply</button></div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="vt-chart-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow" style="height: 85vh">
            <div class="modal-header align-items-start">
                <div><h5 class="modal-title fw-bold">Views change vs prior cycle</h5><small class="bh-muted" data-vc-count></small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="px-3 py-2 border-bottom d-flex flex-wrap gap-2" style="border-color: var(--bh-border) !important">
                <div class="vt-tabs" data-vc-sort><button type="button" data-v="best" class="active">Best to worst</button><button type="button" data-v="worst">Worst to best</button></div>
                <div class="vt-tabs" data-vc-trend>
                    @foreach(['all' => 'All accounts', 'increasing' => 'Increasing', 'decreasing' => 'Decreasing', 'insufficient_data' => 'No Data'] as $v => $l)<button type="button" data-v="{{ $v }}" class="{{ $v === 'all' ? 'active' : '' }}">{{ $l }}</button>@endforeach
                </div>
            </div>
            <div class="modal-body" data-vc-body></div>
        </div>
    </div>
</div>

<div class="modal fade" id="vt-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header align-items-start">
                <div>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 mb-1 bh-muted d-none" data-vd-back><i class="bi bi-arrow-left"></i> Back to chart</button>
                    <h5 class="modal-title fw-bold" data-vd-name></h5>
                    <small class="bh-muted" data-vd-sub></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-vd-body></div>
        </div>
    </div>
</div>

<div class="modal fade" id="vt-posts-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow" style="height: 70vh">
            <div class="modal-header align-items-start">
                <div><h5 class="modal-title fw-bold" data-vp-title></h5><small class="bh-muted" data-vp-sub></small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-vp-body></div>
        </div>
    </div>
</div>

<div class="modal fade" id="vt-ranking-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 shadow" style="height: 85vh">
            <div class="modal-header align-items-start">
                <div>
                    <h5 class="modal-title fw-bold">Best PM &amp; Conceptor</h5>
                    <small class="bh-muted" data-vr-sub></small>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <a class="btn btn-sm btn-light-secondary" data-vr-pdf title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                    <a class="btn btn-sm btn-light-secondary" data-vr-excel title="Download Excel"><i class="bi bi-file-earmark-excel"></i></a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="px-3 pt-2">
                <ul class="nav nav-tabs" data-vr-tabs>
                    <li class="nav-item"><button type="button" class="nav-link active" data-v="pm">Project Managers</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-v="conceptor">Conceptors</button></li>
                </ul>
                <div class="border rounded-3 p-2 mt-2 d-flex flex-wrap align-items-center gap-2" style="border-color: var(--bh-border) !important">
                    <div class="vt-tabs" data-vr-mode><button type="button" data-v="month" class="active">By month</button><button type="button" data-v="cycle">By cycle</button></div>
                    <div class="d-flex align-items-center gap-1" data-vr-range></div>
                    <span class="vr mx-1"></span>
                    <small class="bh-faint">Ads</small>
                    <div class="vt-tabs" data-vr-ads><button type="button" data-v="all" class="active">All</button><button type="button" data-v="yes">Ads</button><button type="button" data-v="no">No ads</button></div>
                    <small class="ms-auto bh-faint" data-vr-summary></small>
                </div>
            </div>
            <div class="modal-body" data-vr-body></div>
        </div>
    </div>
</div>

<div class="modal fade" id="vt-person-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow" style="height: 75vh">
            <div class="modal-header align-items-start">
                <div><h5 class="modal-title fw-bold" data-vpp-name></h5><small class="bh-muted" data-vpp-role></small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Back to leaderboard"></button>
            </div>
            <div class="px-3 pt-2">
                <ul class="nav nav-tabs" data-vpp-tabs>
                    <li class="nav-item"><button type="button" class="nav-link active" data-v="content">Content</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-v="trend">Trend</button></li>
                </ul>
            </div>
            <div class="modal-body" data-vpp-body></div>
        </div>
    </div>
</div>
@endsection

@section('bh-scripts')
<script>
const VT = {
    chartRows: @json($chartRows),
    platform: @json($filters['platform']),
    urls: {
        detail: (id, p) => @json(route('brand-health.views-trend.detail', '__ID__')).replace('__ID__', id) + BH.qs({ platform: p }),
        cyclePosts: (id, c) => @json(route('brand-health.views-trend.cycle-posts', ['__ID__', '__C__'])).replace('__ID__', id).replace('__C__', c),
        ranking: @json(route('brand-health.views-trend.ranking')),
        rankingPdf: @json(route('brand-health.views-trend.ranking.pdf')),
        rankingExcel: @json(route('brand-health.views-trend.ranking.excel')),
        person: (id) => @json(route('brand-health.views-trend.ranking.person', '__ID__')).replace('__ID__', id),
        personPosts: (id) => @json(route('brand-health.views-trend.ranking.person-posts', '__ID__')).replace('__ID__', id),
    },
    group: (t) => (t === 'growing' || t === 'stable' ? 'increasing' : t === 'setback' || t === 'stagnant' ? 'decreasing' : 'insufficient_data'),
    delta: (v) => (v === null || v === undefined ? '—' : `${v > 0 ? '+' : ''}${v}%`),
    deltaColor: (v) => (v === null || v === undefined ? 'var(--bh-ink-faint)' : v < 0 ? 'var(--status-parah-ink)' : v > 0 ? 'var(--status-sip-ink)' : 'var(--bh-ink-muted)'),
    num: (v) => (v === null || v === undefined ? '—' : Number(v).toLocaleString('en-US')),
    compact: (v) => new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 }).format(v),
    date: (d) => (d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'),
    plat: (p) => (p === 'tiktok' ? 'TikTok' : 'Instagram'),
    accent: () => getComputedStyle(document.documentElement).getPropertyValue('--bh-accent').trim() || '#435ebe',
    switcher(el, cb) { el.addEventListener('click', (e) => { const b = e.target.closest('[data-v]'); if (!b) return; el.querySelectorAll('[data-v]').forEach((x) => x.classList.toggle('active', x === b)); cb(b.dataset.v); }); },
    postsTable(posts, withAccount) {
        return `<table class="bh-table"><thead><tr>${withAccount ? '<th>Account</th>' : ''}<th>Platform</th><th>Date</th><th class="text-end">Views</th><th>Ads</th><th></th></tr></thead><tbody>${posts.map((p) => `<tr>
            ${withAccount ? `<td class="fw-semibold">${BH.esc(p.account_name || '—')}</td>` : ''}<td class="bh-muted">${VT.plat(p.platform)}</td><td class="bh-muted bh-num text-nowrap">${VT.date(p.post_date)}</td>
            <td class="text-end fw-semibold bh-num">${VT.num(p.views)}</td><td class="bh-muted">${p.ads ? 'Yes' : 'No'}</td>
            <td class="text-end">${p.link ? `<a href="${BH.esc(p.link)}" target="_blank" rel="noopener" class="small fw-semibold">View <i class="bi bi-box-arrow-up-right"></i></a>` : ''}</td></tr>`).join('')}</tbody></table>`;
    },
    lineChart(canvas, labels, data, tooltip) {
        const accent = VT.accent();
        return BH.chart(canvas, { type: 'line',
            data: { labels, datasets: [{ label: 'Median', data, borderColor: accent, backgroundColor: accent + '1a', pointBackgroundColor: accent, pointRadius: 4, borderWidth: 2, tension: .25 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: tooltip } } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { callback: (v) => VT.compact(v) } } } } });
    },
};

(function () {
    const input = document.querySelector('[data-debounce]');
    let t; input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => input.form.submit(), 300); });
    // Default values stay out of the URL, same as Brand Matrix.
    const strip = (form) => form.addEventListener('submit', () => form.querySelectorAll('input, select').forEach((el) => { if (!el.value || (el.name === 'platform' && el.value === 'all')) el.disabled = true; }));
    strip(document.getElementById('vt-toolbar')); strip(document.getElementById('vt-filter-form'));
    document.querySelector('[data-vt-reset]').addEventListener('click', () => {
        const form = document.getElementById('vt-filter-form');
        form.platform.value = 'all'; form.trend.value = ''; form.month_from.value = ''; form.month_to.value = '';
    });
})();

/* ---- diverging bar chart (log-scaled bars, same as Brand Matrix) ---- */
const vtChart = (function () {
    const el = document.getElementById('vt-chart-modal');
    const body = el.querySelector('[data-vc-body]');
    let sort = 'best', trend = 'all';
    const log = (d) => Math.log10(1 + Math.abs(d));
    function render() {
        const rows = VT.chartRows.filter((r) => r.delta_pct !== null && (trend === 'all' || VT.group(r.trend) === trend))
            .sort((a, b) => (sort === 'best' ? b.delta_pct - a.delta_pct : a.delta_pct - b.delta_pct));
        el.querySelector('[data-vc-count]').textContent = `${rows.length} account${rows.length === 1 ? '' : 's'}`;
        const max = Math.max(log(10), ...rows.map((r) => log(r.delta_pct)));
        body.innerHTML = rows.length ? rows.map((r, i) => {
            const w = (log(r.delta_pct) / max) * 50;
            const bar = r.delta_pct < 0 ? `<div class="vt-bar" style="right: 50%; width: ${w}%; background: var(--status-parah-ink)"></div>` : r.delta_pct > 0 ? `<div class="vt-bar" style="left: 50%; width: ${w}%; background: var(--status-sip-ink)"></div>` : '';
            return `<div class="vt-bar-row" data-i="${i}"><span class="vt-bar-name" title="${BH.esc(r.account_name)}">${VT.platform === 'all' ? `<span class="vt-plat ${r.platform} me-1">${r.platform === 'tiktok' ? 'TT' : 'IG'}</span>` : ''}${BH.esc(r.account_name)}</span>
                <div class="vt-bar-track">${bar}</div><span class="vt-bar-val" style="color: ${VT.deltaColor(r.delta_pct)}">${VT.delta(r.delta_pct)}</span></div>`;
        }).join('') : '<p class="bh-empty">No accounts match this chart filter.</p>';
        body._rows = rows;
    }
    VT.switcher(el.querySelector('[data-vc-sort]'), (v) => { sort = v; render(); });
    VT.switcher(el.querySelector('[data-vc-trend]'), (v) => { trend = v; render(); });
    body.addEventListener('click', (e) => {
        const row = e.target.closest('[data-i]'); if (!row) return;
        const r = body._rows[+row.dataset.i];
        bootstrap.Modal.getInstance(el).hide();
        vtDetail.open({ id: r.account_id, name: r.account_name, platform: r.platform }, true);
    });
    render();
    return { show() { bootstrap.Modal.getOrCreateInstance(el).show(); } };
})();

/* ---- account detail ---- */
const vtDetail = (function () {
    const el = document.getElementById('vt-detail-modal');
    const body = el.querySelector('[data-vd-body]');
    const back = el.querySelector('[data-vd-back]');
    let chart = null, current = null, fromChart = false;

    async function open(acc, cameFromChart = false) {
        current = acc; fromChart = cameFromChart;
        back.classList.toggle('d-none', !fromChart);
        el.querySelector('[data-vd-name]').textContent = acc.name;
        el.querySelector('[data-vd-sub]').textContent = `Views trend — ${VT.plat(acc.platform)}`;
        body.innerHTML = '<p class="bh-empty">Loading…</p>';
        bootstrap.Modal.getOrCreateInstance(el).show();
        let series;
        try { series = (await BH.request(VT.urls.detail(acc.id, acc.platform))).series ?? []; } catch (e) { body.innerHTML = `<p class="bh-empty">${BH.esc(e.message || 'Failed to load views trend.')}</p>`; return; }
        const withData = series.filter((p) => p.avg_views !== null);
        if (!withData.length) { body.innerHTML = '<p class="bh-empty">No views data recorded for this account/platform yet.</p>'; return; }
        const latest = withData[withData.length - 1], prev = withData.length > 1 ? withData[withData.length - 2] : null;
        const overall = prev && prev.avg_views > 0 ? Math.round(((latest.avg_views - prev.avg_views) / prev.avg_views) * 1000) / 10 : null;
        const peak = withData.reduce((m, p) => (p.max_views > m.max_views ? p : m), withData[0]);
        const tile = (label, value, hint, hintStyle = '') => `<div class="col-6 col-md-3"><div class="bh-card h-100"><div class="bh-stat-label">${label}</div><div class="fs-4 fw-bold bh-num">${value}</div><small class="bh-muted" style="${hintStyle}">${hint}</small></div></div>`;
        body.innerHTML = `<div class="row g-3">
                ${tile('Latest cycle median views', VT.num(latest.avg_views), `${VT.delta(overall)} vs prior cycle`, `color: ${VT.deltaColor(overall)}; font-weight: 600`)}
                ${tile('Latest cycle total views', VT.num(latest.total_views), BH.esc(latest.label))}
                ${tile('Peak cycle (max)', VT.num(peak.max_views), BH.esc(peak.label))}
                ${tile('Posts, latest cycle', VT.num(latest.post_count), BH.esc(latest.label))}
            </div>
            <div class="bh-card mt-3"><h6 class="fw-bold">Median views, by cycle</h6><div class="bh-chart" style="height: 280px"><canvas></canvas></div></div>
            <div class="bh-card p-0 mt-3"><div class="table-responsive"><table class="bh-table vt-clickable"><thead><tr><th>Cycle</th><th class="text-end">Total</th><th class="text-end">Min</th><th class="text-end">Max</th><th class="text-end">Median</th><th class="text-end">Δ vs prior</th><th class="text-end">Reels</th></tr></thead>
            <tbody>${[...series].reverse().map((p) => `<tr data-cycle="${p.cycle_id}" data-label="${BH.esc(p.label)}" title="See this cycle's posts">
                <td class="fw-semibold">${BH.esc(p.label)}</td><td class="text-end bh-num bh-muted">${VT.num(p.total_views)}</td><td class="text-end bh-num bh-muted">${VT.num(p.min_views)}</td>
                <td class="text-end bh-num bh-muted">${VT.num(p.max_views)}</td><td class="text-end bh-num">${VT.num(p.avg_views)}</td>
                <td class="text-end bh-num fw-semibold" style="color: ${VT.deltaColor(p.delta_pct)}">${VT.delta(p.delta_pct)}</td><td class="text-end bh-num bh-muted">${p.post_count}</td></tr>`).join('')}</tbody></table></div></div>`;
        chart?.destroy();
        requestAnimationFrame(() => { chart = VT.lineChart(body.querySelector('canvas'), withData.map((p) => p.label), withData.map((p) => p.avg_views), (c) => `Median: ${VT.num(c.parsed.y)}`); });
    }

    body.addEventListener('click', (e) => { const tr = e.target.closest('[data-cycle]'); if (tr) vtPosts.open(current, tr.dataset.cycle, tr.dataset.label); });
    back.addEventListener('click', () => { bootstrap.Modal.getInstance(el).hide(); });
    el.addEventListener('hidden.bs.modal', () => { chart?.destroy(); chart = null; if (fromChart) { fromChart = false; vtChart.show(); } });
    return { open };
})();

const vtPosts = (function () {
    const el = document.getElementById('vt-posts-modal');
    const body = el.querySelector('[data-vp-body]');
    return {
        async open(acc, cycleId, label) {
            el.querySelector('[data-vp-title]').textContent = label;
            el.querySelector('[data-vp-sub]').textContent = `${acc.name} · ${VT.plat(acc.platform)}`;
            body.innerHTML = '<p class="bh-empty">Loading…</p>';
            bootstrap.Modal.getOrCreateInstance(el).show();
            try {
                const posts = (await BH.request(VT.urls.cyclePosts(acc.id, cycleId))).posts ?? [];
                body.innerHTML = posts.length ? VT.postsTable(posts, false) : '<p class="bh-empty">No posts recorded for this cycle.</p>';
            } catch (e) { body.innerHTML = `<p class="bh-empty">${BH.esc(e.message || 'Failed to load content.')}</p>`; }
        },
    };
})();

/* ---- PM & Conceptor ranking ---- */
const vtRanking = (function () {
    const el = document.getElementById('vt-ranking-modal');
    const body = el.querySelector('[data-vr-body]');
    const rangeEl = el.querySelector('[data-vr-range]');
    const S = { tab: 'pm', mode: 'month', from: '', to: '', ads: 'all', sort: 'avg_views', dir: 'desc' };
    let data = { projectManagers: [], conceptors: [], cycleMonths: [] }, loaded = false, seq = 0;
    const mLabel = (ym) => (ym ? BH.month(ym) : '');

    const params = () => {
        const p = { platform: VT.platform, range_mode: S.mode };
        if (S.from) { p.month_from = S.from; p.month_to = S.to || S.from; }
        if (S.ads !== 'all') p.ads = S.ads;
        return p;
    };

    function renderRange() {
        // "By month" is an open month range; "By cycle" only offers months a cycle actually starts in.
        if (S.mode === 'month') {
            rangeEl.innerHTML = `<input type="month" class="form-control form-control-sm" style="width: 9.5rem" data-r="from" value="${S.from}"><span class="bh-faint">→</span><input type="month" class="form-control form-control-sm" style="width: 9.5rem" data-r="to" value="${S.to}">`;
        } else {
            const opts = (v, ph) => `<option value="">${ph}</option>` + data.cycleMonths.map((m) => `<option value="${m}" ${m === v ? 'selected' : ''}>${mLabel(m)}</option>`).join('');
            rangeEl.innerHTML = `<select class="form-select form-select-sm" style="width: 9.5rem" data-r="from" data-no-search>${opts(S.from, 'From cycle…')}</select><span class="bh-faint">→</span><select class="form-select form-select-sm" style="width: 9.5rem" data-r="to" data-no-search>${opts(S.to, 'To cycle…')}</select>`;
        }
        if (S.from || S.to) rangeEl.insertAdjacentHTML('beforeend', '<button type="button" class="btn btn-sm btn-link text-decoration-none" data-r-reset>Reset</button>');
        const from = mLabel(S.from) || 'earliest', to = mLabel(S.to || S.from);
        el.querySelector('[data-vr-summary]').textContent = !(S.from || S.to) ? (S.mode === 'cycle' ? 'All cycles' : 'All time') : from === to ? from : `${from} – ${to}`;
        el.querySelector('[data-vr-sub]').textContent = `By median views per post · ${S.mode === 'cycle' ? 'grouped by cycle month' : 'grouped by post month'}`;
        el.querySelector('[data-vr-pdf]').href = VT.urls.rankingPdf + BH.qs(params());
        el.querySelector('[data-vr-excel]').href = VT.urls.rankingExcel + BH.qs(params());
    }

    function renderTable() {
        const list = [...(S.tab === 'pm' ? data.projectManagers : data.conceptors)].sort((a, b) => (S.dir === 'asc' ? 1 : -1) * (a[S.sort] - b[S.sort]));
        if (!data.projectManagers.length && !data.conceptors.length) {
            body.innerHTML = `<p class="bh-empty">${S.mode === 'cycle' ? 'No cycle-assigned posts with recorded views for this range.' : 'No posts with recorded views for this range.'}</p>`; return;
        }
        const ranked = S.sort === 'avg_views' && S.dir === 'desc';
        const medal = ['background: var(--status-cukup-bg); color: var(--status-cukup-ink)', 'background: var(--bh-surface); color: var(--bh-ink-muted); border: 1px solid var(--bh-border)', 'background: var(--status-kurang-bg); color: var(--status-kurang-ink)'];
        const th = (k, l) => `<th class="text-end" data-sort="${k}">${l}${S.sort === k ? (S.dir === 'asc' ? ' ↑' : ' ↓') : ''}</th>`;
        body.innerHTML = `<table class="bh-table vt-clickable"><thead><tr><th style="width: 3rem"></th><th>${S.tab === 'pm' ? 'Project Manager' : 'Conceptor'}</th>
            ${th('avg_views', 'Median Views')}${th('total_views', 'Total Views')}${th('min_views', 'Min Views')}${th('max_views', 'Max Views')}${th('post_count', 'Total Reels')}</tr></thead>
            <tbody>${list.map((p, i) => `<tr data-person="${p.employee_id}" data-name="${BH.esc(p.employee_name)}" ${ranked && i === 0 ? 'style="background: var(--bh-accent-soft)"' : ''}>
                <td>${ranked && i < 3 ? `<span class="vt-rank" style="${medal[i]}">${i + 1}</span>` : `<span class="bh-faint bh-num ps-2">${i + 1}</span>`}</td>
                <td class="fw-semibold">${BH.esc(p.employee_name)}</td><td class="text-end fw-semibold bh-num">${VT.num(p.avg_views)}</td>
                <td class="text-end bh-num bh-muted">${VT.num(p.total_views)}</td><td class="text-end bh-num bh-muted">${VT.num(p.min_views)}</td>
                <td class="text-end bh-num bh-muted">${VT.num(p.max_views)}</td><td class="text-end bh-num bh-muted">${p.post_count}</td></tr>`).join('')
                || `<tr><td colspan="7" class="bh-empty">No ${S.tab === 'pm' ? 'project managers' : 'conceptors'} to rank for this range.</td></tr>`}</tbody></table>`;
    }

    async function load() {
        const mine = ++seq;
        renderRange();
        body.innerHTML = '<p class="bh-empty">Loading…</p>';
        try {
            const res = await BH.request(VT.urls.ranking + BH.qs(params()));
            if (mine !== seq) return;
            data = { projectManagers: res.projectManagers ?? [], conceptors: res.conceptors ?? [], cycleMonths: res.cycleMonths ?? [] };
            loaded = true;
            renderRange(); renderTable();
        } catch (e) { if (mine === seq) body.innerHTML = `<p class="bh-empty">${BH.esc(e.message || 'Failed to load ranking.')}</p>`; }
    }

    el.querySelector('[data-vr-tabs]').addEventListener('click', (e) => { const b = e.target.closest('[data-v]'); if (!b) return; el.querySelectorAll('[data-vr-tabs] .nav-link').forEach((x) => x.classList.toggle('active', x === b)); S.tab = b.dataset.v; renderTable(); });
    VT.switcher(el.querySelector('[data-vr-mode]'), (v) => { if (S.mode === v) return; S.mode = v; S.from = ''; S.to = ''; load(); });
    VT.switcher(el.querySelector('[data-vr-ads]'), (v) => { S.ads = v; load(); });
    rangeEl.addEventListener('change', (e) => {
        const r = e.target.dataset.r; if (!r) return;
        S[r] = e.target.value;
        if (S.from && S.to && S.to < S.from) [S.from, S.to] = [S.to, S.from];
        load();
    });
    rangeEl.addEventListener('click', (e) => { if (e.target.closest('[data-r-reset]')) { S.from = ''; S.to = ''; load(); } });
    body.addEventListener('click', (e) => {
        const th = e.target.closest('[data-sort]');
        if (th) { const k = th.dataset.sort; if (S.sort === k) S.dir = S.dir === 'asc' ? 'desc' : 'asc'; else { S.sort = k; S.dir = 'desc'; } return renderTable(); }
        const tr = e.target.closest('[data-person]');
        if (tr) vtPerson.open({ id: tr.dataset.person, name: tr.dataset.name, role: S.tab === 'pm' ? 'project_manager_id' : 'conceptor_id' }, params());
    });

    return { open() { bootstrap.Modal.getOrCreateInstance(el).show(); if (!loaded) load(); } };
})();

const vtPerson = (function () {
    const el = document.getElementById('vt-person-modal');
    const body = el.querySelector('[data-vpp-body]');
    let person = null, base = {}, tab = 'content', chart = null, cache = {};

    async function render() {
        chart?.destroy(); chart = null;
        const p = { ...base, role: person.role };
        if (tab === 'content') {
            if (!cache.posts) {
                body.innerHTML = '<p class="bh-empty">Loading…</p>';
                try { cache.posts = (await BH.request(VT.urls.personPosts(person.id) + BH.qs(p))).posts ?? []; } catch (e) { body.innerHTML = `<p class="bh-empty">${BH.esc(e.message || 'Failed to load content.')}</p>`; return; }
            }
            if (tab !== 'content') return;
            body.innerHTML = cache.posts.length ? VT.postsTable(cache.posts, true) : '<p class="bh-empty">No posts for this range.</p>';
            return;
        }
        if (!cache.series) {
            body.innerHTML = '<p class="bh-empty">Loading…</p>';
            try { cache.series = (await BH.request(VT.urls.person(person.id) + BH.qs(p))).series ?? []; } catch (e) { body.innerHTML = `<p class="bh-empty">${BH.esc(e.message || 'Failed to load chart.')}</p>`; return; }
        }
        if (tab !== 'trend') return;
        if (!cache.series.length) { body.innerHTML = '<p class="bh-empty">No monthly data to chart yet.</p>'; return; }
        body.innerHTML = '<div class="bh-chart h-100" style="min-height: 300px"><canvas></canvas></div>';
        const s = cache.series;
        requestAnimationFrame(() => { chart = VT.lineChart(body.querySelector('canvas'), s.map((x) => x.label), s.map((x) => x.avg_views), (c) => [`Median Views: ${VT.num(s[c.dataIndex].avg_views)}`, `Total Views: ${VT.num(s[c.dataIndex].total_views)}`, `Posts: ${s[c.dataIndex].post_count}`]); });
    }

    el.querySelector('[data-vpp-tabs]').addEventListener('click', (e) => { const b = e.target.closest('[data-v]'); if (!b) return; el.querySelectorAll('[data-vpp-tabs] .nav-link').forEach((x) => x.classList.toggle('active', x === b)); tab = b.dataset.v; render(); });
    el.addEventListener('hidden.bs.modal', () => { chart?.destroy(); chart = null; });
    return {
        open(p, filterParams) {
            person = p; base = filterParams; tab = 'content'; cache = {};
            el.querySelectorAll('[data-vpp-tabs] .nav-link').forEach((x) => x.classList.toggle('active', x.dataset.v === 'content'));
            el.querySelector('[data-vpp-name]').textContent = p.name;
            el.querySelector('[data-vpp-role]').textContent = p.role === 'project_manager_id' ? 'as Project Manager' : 'as Conceptor';
            bootstrap.Modal.getOrCreateInstance(el).show();
            render();
        },
    };
})();
</script>
@endsection
