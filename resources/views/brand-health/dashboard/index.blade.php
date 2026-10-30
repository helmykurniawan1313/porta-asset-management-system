@extends('brand-health.layout')

@section('title', 'Brand Health Dashboard | Porta IT')

@section('bh-styles')
<style>
    .bh-tabs { display: flex; gap: .25rem; border-bottom: 1px solid var(--bh-border); margin-bottom: 1rem; }
    .bh-tabs button { border: 0; background: none; padding: .6rem .9rem; font-weight: 600; color: var(--bh-ink-muted); border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .bh-tabs button.active { color: var(--bh-accent); border-bottom-color: var(--bh-accent); }
    .bh-seg { display: inline-flex; gap: .2rem; padding: .2rem; border: 1px solid var(--bh-border); border-radius: 10px; background: var(--bh-surface); }
    .bh-seg a { padding: .35rem .8rem; border-radius: 8px; font-size: .85rem; font-weight: 600; color: var(--bh-ink-muted); text-decoration: none; }
    .bh-seg a.active { background: var(--bh-accent); color: #fff; }
    .bh-legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
</style>
@endsection

@section('bh-content')
@php
    $cycleData = value($cycleData);
    $performanceData = value($performanceData);
    $tab = request('tab') === 'performance' ? 'performance' : 'cycles';
    $platformOptions = ['all' => 'All Platforms', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'];
    $monthLabel = fn ($ym) => \Carbon\Carbon::createFromFormat('Y-m-d', $ym.'-01')->format('M Y');
@endphp

<div class="bh-page-head">
    <div>
        <h3>Dashboard</h3>
        <p>A snapshot of accounts, cycles, and performance across Brand Health.</p>
    </div>
</div>

<div class="bh-tabs" role="tablist">
    <button type="button" data-tab="cycles" class="{{ $tab === 'cycles' ? 'active' : '' }}">Cycles</button>
    <button type="button" data-tab="performance" class="{{ $tab === 'performance' ? 'active' : '' }}">Performance</button>
</div>

<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <span class="bh-stat-label">Filter</span>
    <div class="bh-seg">
        @foreach($platformOptions as $value => $label)
            <a href="{{ route('brand-health.dashboard', ['platform' => $value, 'range' => $filters['range'], 'tab' => $tab]) }}"
               class="{{ $filters['platform'] === $value ? 'active' : '' }}" data-keep-tab>{{ $label }}</a>
        @endforeach
    </div>
    @foreach(['cycles' => $cycleData['availableMonths'], 'performance' => $performanceData['availableMonths']] as $which => $months)
        <select class="form-select form-select-sm bh-range" data-for="{{ $which }}" style="width: 11rem; {{ $tab === $which ? '' : 'display:none' }}" data-no-search>
            <option value="all" {{ $filters['range'] === 'all' ? 'selected' : '' }}>All time</option>
            @foreach($months as $ym)
                <option value="{{ $ym }}" {{ $filters['range'] === $ym ? 'selected' : '' }}>{{ $monthLabel($ym) }}</option>
            @endforeach
        </select>
    @endforeach
</div>

{{-- ===== Cycles tab ===== --}}
<section data-pane="cycles" style="{{ $tab === 'cycles' ? '' : 'display:none' }}">
    @php $cs = $cycleData['summary']; @endphp
    <div class="row g-3">
        @foreach([
            ['Accounts', $cs['accounts_count'], null],
            ['Cycles Tracked', $cs['cycles_count'], null],
            ['Avg Health Rate', $cs['avg_health_rate'] ?? '—', null],
            ['Healthy (SIP)', $cs['healthy_cycles_count'], 'cycles at top tier'],
        ] as [$label, $value, $hint])
            <div class="col-6 col-lg-3"><div class="bh-card h-100">
                <div class="bh-stat-label">{{ $label }}</div>
                <div class="bh-stat-value">{{ is_numeric($value) ? number_format($value, is_float($value) ? 2 : 0) : $value }}</div>
                @if($hint)<div class="bh-stat-hint">{{ $hint }}</div>@endif
            </div></div>
        @endforeach
    </div>

    @if($cs['at_risk_cycles_count'] > 0)
        <div class="bh-alert-tile" style="border-color: var(--status-parah-ink); background: var(--status-parah-bg); color: var(--status-parah-ink)">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                <div class="fw-bold fs-5">{{ $cs['at_risk_cycles_count'] }} {{ \Illuminate\Support\Str::plural('cycle', $cs['at_risk_cycles_count']) }} {{ $cs['at_risk_cycles_count'] === 1 ? 'needs' : 'need' }} attention</div>
                <small>Health label is KURANG or PARAH.</small>
            </div>
        </div>
    @else
        <div class="bh-alert-tile" style="border-color: var(--status-sip-ink); background: var(--status-sip-bg); color: var(--status-sip-ink)">
            <i class="bi bi-check-circle"></i><div class="fw-bold">No cycles currently need attention.</div>
        </div>
    @endif

    <div class="row g-3 mt-1">
        <div class="col-lg-8"><div class="bh-card h-100">
            <div class="bh-stat-label">Health Distribution</div>
            <div class="bh-stat-hint">How many cycles landed on each Health label.</div>
            <div class="bh-chart mt-3" style="height: 220px"><canvas id="health-chart"></canvas></div>
        </div></div>
        <div class="col-lg-4"><div class="bh-card h-100 d-flex flex-column">
            <div class="bh-stat-label">Cycles by Platform</div>
            <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center py-2">
                <div style="height: 140px; width: 140px"><canvas id="cycle-platform-chart"></canvas></div>
                <div class="d-flex gap-4 mt-3 small">
                    <span><span class="bh-legend-dot" style="background:#e1306c"></span> IG <b class="bh-num">{{ $platformCounts['instagram'] ?? 0 }}</b></span>
                    <span><span class="bh-legend-dot" style="background:#6b7280"></span> TT <b class="bh-num">{{ $platformCounts['tiktok'] ?? 0 }}</b></span>
                </div>
            </div>
        </div></div>
    </div>

    <div class="bh-card mt-3">
        <div class="bh-stat-label">Cycles Added Per Month</div>
        <div class="bh-stat-hint">Last 6 months with recorded cycles.</div>
        <div class="bh-chart mt-3" style="height: 200px"><canvas id="cycle-trend-chart"></canvas></div>
    </div>
</section>

{{-- ===== Performance tab ===== --}}
<section data-pane="performance" style="{{ $tab === 'performance' ? '' : 'display:none' }}">
    @php $ps = $performanceData['summary']; @endphp
    <div class="row g-3">
        @foreach([
            ['Accounts', $ps['accounts_count'], null],
            ['Performance Posts', $ps['performances_count'], null],
            ['Avg Views (H+7)', $ps['avg_views'], null],
            ['Top Performing', $ps['top_performing_count'], 'top-tier Views status'],
        ] as [$label, $value, $hint])
            <div class="col-6 col-lg-3"><div class="bh-card h-100">
                <div class="bh-stat-label">{{ $label }}</div>
                <div class="bh-stat-value">{{ $value === null ? '—' : number_format($value) }}</div>
                @if($hint)<div class="bh-stat-hint">{{ $hint }}</div>@endif
            </div></div>
        @endforeach
    </div>

    @if($ps['at_risk_count'] > 0)
        <div class="bh-alert-tile" style="border-color: var(--status-parah-ink); background: var(--status-parah-bg); color: var(--status-parah-ink)">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                <div class="fw-bold fs-5">{{ $ps['at_risk_count'] }} {{ \Illuminate\Support\Str::plural('post', $ps['at_risk_count']) }} {{ $ps['at_risk_count'] === 1 ? 'needs' : 'need' }} attention</div>
                <small>Views H+7 status is bottom-tier.</small>
            </div>
        </div>
    @else
        <div class="bh-alert-tile" style="border-color: var(--status-sip-ink); background: var(--status-sip-bg); color: var(--status-sip-ink)">
            <i class="bi bi-check-circle"></i><div class="fw-bold">No posts currently need attention.</div>
        </div>
    @endif

    <div class="row g-3 mt-1">
        <div class="col-lg-8"><div class="bh-card h-100">
            <div class="bh-stat-label">Views Status Distribution</div>
            <div class="bh-stat-hint">How many posts landed on each Views H+7 status.</div>
            <div class="bh-chart mt-3" style="height: 220px"><canvas id="views-chart"></canvas></div>
        </div></div>
        <div class="col-lg-4"><div class="bh-card h-100 d-flex flex-column">
            <div class="bh-stat-label">Posts by Platform</div>
            <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center py-2">
                <div style="height: 140px; width: 140px"><canvas id="perf-platform-chart"></canvas></div>
                <div class="d-flex gap-4 mt-3 small">
                    <span><span class="bh-legend-dot" style="background:#e1306c"></span> IG <b class="bh-num">{{ $performanceData['platformCounts']['instagram'] ?? 0 }}</b></span>
                    <span><span class="bh-legend-dot" style="background:#6b7280"></span> TT <b class="bh-num">{{ $performanceData['platformCounts']['tiktok'] ?? 0 }}</b></span>
                </div>
            </div>
        </div></div>
    </div>

    <div class="bh-card mt-3">
        <div class="bh-stat-label">Posts Added Per Month</div>
        <div class="bh-stat-hint">Last 6 months with recorded posts.</div>
        <div class="bh-chart mt-3" style="height: 200px"><canvas id="perf-trend-chart"></canvas></div>
    </div>
</section>
@endsection

@section('bh-scripts')
<script>
(function () {
    const data = {
        cycles: @json($cycleData),
        performance: @json($performanceData),
        platformCounts: @json($platformCounts),
    };
    const css = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
    // Same PARAH..SIP ramp as the status badges, so bar colour carries severity.
    const tone = (tier) => {
        const key = { SIP: 'sip', BAGUS: 'bagus', CUKUP: 'cukup', KURANG: 'kurang', PARAH: 'parah' }[String(tier ?? '').toUpperCase()];
        return key ? css(`--status-${key}-ink`) : css('--bh-accent');
    };
    const plural = (n, w) => `${n} ${w}${n === 1 ? '' : 's'}`;

    const bar = (id, dist, noun) => BH.chart(document.getElementById(id), {
        type: 'bar',
        data: { labels: dist.tiers, datasets: [{ data: dist.tiers.map((t) => dist.counts[t] ?? 0), backgroundColor: dist.tiers.map(tone), borderRadius: 4, maxBarThickness: 40 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => plural(c.parsed.y, noun) } } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
    const line = (id, trend, noun) => BH.chart(document.getElementById(id), {
        type: 'line',
        data: { labels: trend.map((r) => r.label), datasets: [{ data: trend.map((r) => r.count), borderColor: css('--bh-accent'), backgroundColor: css('--bh-accent'),
            pointBorderColor: css('--bh-surface'), pointBorderWidth: 2, pointRadius: 4, borderWidth: 2, tension: .3, fill: false }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => plural(c.parsed.y, noun) } } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
    const pie = (id, counts, noun) => BH.chart(document.getElementById(id), {
        type: 'pie',
        data: { labels: ['Instagram', 'TikTok'], datasets: [{ data: [counts.instagram ?? 0, counts.tiktok ?? 0], backgroundColor: ['#e1306c', '#6b7280'], borderColor: css('--bh-surface'), borderWidth: 2 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => `${c.label}: ${plural(c.parsed, noun)}` } } } },
    });

    const rendered = {};
    function render(tab) {
        if (rendered[tab]) return;
        rendered[tab] = true;
        if (tab === 'cycles') {
            bar('health-chart', data.cycles.healthDistribution, 'cycle');
            pie('cycle-platform-chart', data.platformCounts, 'cycle');
            line('cycle-trend-chart', data.cycles.monthlyTrend, 'cycle');
        } else {
            bar('views-chart', data.performance.viewsDistribution, 'post');
            pie('perf-platform-chart', data.performance.platformCounts, 'post');
            line('perf-trend-chart', data.performance.monthlyTrend, 'post');
        }
    }

    let current = @json($tab);
    const months = { cycles: data.cycles.availableMonths, performance: data.performance.availableMonths };
    const go = (params) => { const u = new URL(location.href); Object.entries(params).forEach(([k, v]) => u.searchParams.set(k, v)); location.href = u; };

    document.querySelectorAll('[data-tab]').forEach((btn) => btn.addEventListener('click', () => {
        current = btn.dataset.tab;
        document.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('active', b === btn));
        document.querySelectorAll('[data-pane]').forEach((p) => p.style.display = p.dataset.pane === current ? '' : 'none');
        document.querySelectorAll('.bh-range').forEach((s) => s.style.display = s.dataset.for === current ? '' : 'none');
        history.replaceState(null, '', (() => { const u = new URL(location.href); u.searchParams.set('tab', current); return u; })());
        // A month picked on one tab may not exist on the other (each has its own months).
        const range = new URL(location.href).searchParams.get('range') || 'all';
        if (range !== 'all' && !months[current].includes(range)) return go({ range: 'all', tab: current });
        render(current);
    }));
    document.querySelectorAll('.bh-range').forEach((s) => s.addEventListener('change', () => go({ range: s.value, tab: current })));

    render(current);
})();
</script>
@endsection
