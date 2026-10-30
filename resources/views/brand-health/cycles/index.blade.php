@extends('brand-health.layout')

@section('title', 'Cycles | Brand Health')

@section('bh-styles')
<style>
    .bh-click-card { cursor: pointer; transition: transform .15s ease, box-shadow .15s ease; }
    .bh-click-card:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(16, 24, 40, .08); }
    .bh-row-click { cursor: pointer; }
    .bh-input-chip { border: 1px solid var(--bh-border); border-radius: 10px; padding: .55rem .75rem; background: var(--bh-raised); }
    .bh-input-chip .bh-stat-label { font-size: .65rem; }
    .bh-input-chip .val { font-weight: 800; font-size: 1.05rem; font-variant-numeric: tabular-nums; }
    .bh-score { display: inline-flex; justify-content: center; min-width: 3rem; border-radius: 8px; padding: .2rem .5rem; font-weight: 700; font-variant-numeric: tabular-nums; cursor: default; }
    .bh-agg { border: 1px solid var(--bh-border); border-radius: 12px; padding: .8rem; background: var(--bh-raised); display: flex; flex-direction: column; }
    .bh-flag { display: inline-flex; width: fit-content; gap: .25rem; border-radius: 999px; padding: .1rem .55rem; font-size: .7rem; font-weight: 700; background: var(--status-kurang-bg); color: var(--status-kurang-ink); margin-top: .4rem; }
    .bh-tip { position: fixed; z-index: 2000; width: 190px; pointer-events: none; background: var(--bh-surface); border: 1px solid var(--bh-border); border-radius: 10px; padding: .6rem; box-shadow: 0 10px 24px rgba(0,0,0,.15); font-size: .78rem; }
    .bh-label-check { display: inline-flex; align-items: center; gap: .35rem; margin: 0 .5rem .4rem 0; cursor: pointer; }
    .bh-rank { display: inline-flex; width: 24px; height: 24px; border-radius: 50%; align-items: center; justify-content: center; font-weight: 700; font-size: .75rem; }
</style>
@endsection

@section('bh-content')
@php
    $canEdit = auth()->user()->can('edit-brand-health');
    $query = request()->except('page');
    $hasFilters = collect($filters)->filter(fn ($v) => filled($v))->isNotEmpty();
    $activeFilterCount = collect(['account_id', 'project_manager_id', 'platform', 'growth_label', 'visibility_label', 'engagement_label', 'health_label', 'month_from'])
        ->filter(fn ($k) => filled($filters[$k] ?? null))->count();
    $fmtDate = fn ($d) => \Carbon\Carbon::parse($d)->format('M j, Y');
    $rows = collect($cycles->items());
@endphp

<div class="bh-page-head">
    <div>
        <h3>Account Performance</h3>
        <p>Growth, visibility, and engagement scored per reporting cycle.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light-secondary" data-bs-toggle="modal" data-bs-target="#ads-ranking-modal"><i class="bi bi-trophy"></i> Ads Nominal Ranking</button>
        @if($canEdit)
            <button type="button" class="btn btn-primary" onclick="cycleForm.open()"><i class="bi bi-plus-lg"></i> Add Cycle</button>
        @endif
    </div>
</div>

@if($summary['total'] > 0)
    <div class="row g-3">
        <div class="col-6 col-md-4"><div class="bh-card h-100">
            <div class="bh-stat-label">Cycles Tracked</div>
            <div class="bh-stat-value">{{ number_format($summary['total']) }}</div>
        </div></div>
        <div class="col-6 col-md-4"><div class="bh-card h-100">
            <div class="bh-stat-label">Avg Health Rate</div>
            <div class="bh-stat-value">{{ $summary['avg_health_rate'] }}</div>
            <div class="bh-stat-hint">across {{ $summary['total'] }} {{ \Illuminate\Support\Str::plural('cycle', $summary['total']) }}</div>
        </div></div>
        <div class="col-12 col-md-4">
            <a href="{{ route('brand-health.cycles.index', array_merge($query, ['health_label' => 'SIP'])) }}#cycles-table" class="bh-card h-100 d-block text-reset bh-click-card" title="Show healthy (SIP) cycles">
                <div class="bh-stat-label">Healthy (SIP)</div>
                <div class="bh-stat-value">{{ $summary['healthy_count'] }}</div>
                <div class="bh-stat-hint">cycles at top tier</div>
            </a>
        </div>
    </div>

    @if($summary['at_risk_count'] > 0)
        <a href="{{ route('brand-health.cycles.index', array_merge($query, ['health_label' => 'KURANG,PARAH'])) }}#cycles-table" class="bh-alert-tile"
           style="border-color: var(--status-parah-ink); background: var(--status-parah-bg); color: var(--status-parah-ink)" title="Show cycles that need attention">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                <div class="fw-bold fs-5">{{ $summary['at_risk_count'] }} {{ \Illuminate\Support\Str::plural('cycle', $summary['at_risk_count']) }} {{ $summary['at_risk_count'] === 1 ? 'needs' : 'need' }} attention</div>
                <small>Health label is KURANG or PARAH — click to filter the table below.</small>
            </div>
        </a>
    @else
        <div class="bh-alert-tile" style="border-color: var(--status-sip-ink); background: var(--status-sip-bg); color: var(--status-sip-ink)">
            <i class="bi bi-check-circle"></i><div class="fw-semibold">All cycles are in good health — nothing needs attention right now.</div>
        </div>
    @endif

    <div class="bh-card mt-3">
        <h6 class="fw-bold mb-0">Score Distribution</h6>
        <small class="bh-muted">How many matching cycles landed on each label — Growth Rate, Visibility, Engagement &amp; Health.</small>
        <div class="bh-chart mt-3" style="height: 260px"><canvas id="score-distribution-chart"></canvas></div>
    </div>
@endif

{{-- Toolbar --}}
<div class="d-flex flex-wrap align-items-center gap-2 mt-4">
    <form method="GET" action="{{ route('brand-health.cycles.index') }}" class="position-relative" style="min-width: 16rem; max-width: 20rem; flex: 1" id="search-form">
        @foreach(request()->except(['search', 'page']) as $k => $v)
            @if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control ps-5" placeholder="Search by account…" autocomplete="off" id="search-input">
    </form>
    <button type="button" class="btn btn-light-secondary position-relative" data-bs-toggle="modal" data-bs-target="#filter-modal">
        <i class="bi bi-funnel"></i> Filter
        @if($activeFilterCount)<span class="badge bg-primary rounded-pill ms-1">{{ $activeFilterCount }}</span>@endif
    </button>
    @if($hasFilters)
        <a href="{{ route('brand-health.cycles.index') }}" class="btn btn-link text-decoration-none bh-muted">Clear filters</a>
    @endif
    <div class="ms-auto d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-primary" id="summarize-btn"><i class="bi bi-stars"></i> Summarize with AI</button>
        <a href="{{ route('brand-health.cycles.excel', $query) }}" class="btn btn-light-secondary"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        <a href="{{ route('brand-health.cycles.pdf', $query) }}" class="btn btn-light-secondary" id="pdf-link"><i class="bi bi-download"></i> Download PDF</a>
    </div>
</div>

<div class="bh-card mt-3 d-none" id="ai-banner" style="border-color: var(--bh-accent)">
    <div class="d-flex justify-content-between gap-3">
        <div>
            <div class="bh-stat-label" style="color: var(--bh-accent)">AI Summary</div>
            <p class="mb-1 mt-1" style="white-space: pre-line" data-ai-text></p>
            <small class="bh-faint" data-ai-meta></small>
        </div>
        <button type="button" class="btn-close" aria-label="Dismiss summary" onclick="aiBanner.dismiss()"></button>
    </div>
</div>

{{-- Table --}}
<div class="bh-card mt-3 p-0" id="cycles-table" style="scroll-margin-top: 1rem">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" style="border-color: var(--bh-border) !important">
        <span class="fw-semibold">Cycles <span class="bh-muted fw-normal small">· {{ number_format($cycles->total()) }} {{ \Illuminate\Support\Str::plural('result', $cycles->total()) }}</span></span>
        <button type="button" class="btn btn-sm btn-link bh-muted text-decoration-none" data-bs-toggle="modal" data-bs-target="#scoring-info"><i class="bi bi-info-circle"></i> How scoring works</button>
    </div>
    <div class="table-responsive">
        <table class="bh-table" style="min-width: 900px">
            <thead>
                <tr>
                    @foreach(['account' => 'Account', 'platform' => 'Platform', 'cycle_start_date' => 'Period', 'growth_rate' => 'Growth', 'visibility_rate' => 'Visibility', 'engagement_score' => 'Engagement', 'health_rate' => 'Health Rate', 'health_label' => 'Health'] as $key => $label)
                        <th class="sortable" data-sort="{{ $key }}">{{ $label }} <span class="sort-arrow"></span></th>
                    @endforeach
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $c)
                    @php $s = $c['scores']; @endphp
                    <tr class="bh-row-click" data-index="{{ $i }}"
                        data-account="{{ strtolower($c['account']['name'] ?? '') }}" data-platform="{{ $c['platform'] }}"
                        data-cycle_start_date="{{ $c['cycle_start_date'] }}" data-growth_rate="{{ $s['growth_rate'] }}"
                        data-visibility_rate="{{ $s['visibility_rate'] }}" data-engagement_score="{{ $s['engagement_score'] }}"
                        data-health_rate="{{ $s['health_rate'] }}" data-health_label="{{ $s['health_label'] }}">
                        <td class="fw-semibold text-truncate" style="max-width: 160px" title="{{ $c['account']['name'] ?? '' }}">{{ $c['account']['name'] ?? '—' }}</td>
                        <td class="bh-muted">{{ $c['platform'] === 'tiktok' ? 'TikTok' : 'Instagram' }}</td>
                        <td class="bh-muted text-nowrap">{{ $fmtDate($c['cycle_start_date']) }} – {{ $fmtDate($c['cycle_end_date']) }}</td>
                        <td class="bh-num fw-semibold" style="{{ $s['growth_rate'] < 0 ? 'color: var(--status-parah-ink)' : '' }}">{{ round($s['growth_rate'], 2) }}%</td>
                        <td class="bh-num">{{ round($s['visibility_rate'], 2) }}</td>
                        <td class="bh-num">{{ round($s['engagement_score'], 2) }}</td>
                        <td class="bh-num fw-bold">{{ round($s['health_rate'], 2) }}</td>
                        <td><x-bh.status :status="$s['health_label']" /></td>
                        <td class="text-end" onclick="event.stopPropagation()">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light-secondary" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="cycleDetail.open({{ $i }}); return false;">View</a></li>
                                    @if($canEdit)
                                        <li><a class="dropdown-item" href="#" onclick="cycleForm.open({{ $i }}); return false;">Edit</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('brand-health.cycles.destroy', $c['id']) }}"
                                                  onsubmit="return confirmDelete(this, 'This will permanently remove the cycle for {{ addslashes($c['account']['name'] ?? '') }} ({{ $fmtDate($c['cycle_start_date']) }} – {{ $fmtDate($c['cycle_end_date']) }}). This cannot be undone.', 'Delete this cycle?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">Delete</button>
                                            </form>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="bh-empty">{{ $hasFilters ? 'No cycles match the current filters.' : 'No cycles yet. Add your first cycle to see health scores.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cycles->lastPage() > 1)
        <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 border-top gap-2" style="border-color: var(--bh-border) !important">
            <small class="bh-muted">Showing <b class="bh-num">{{ $cycles->firstItem() }}–{{ $cycles->lastItem() }}</b> of <b class="bh-num">{{ $cycles->total() }}</b></small>
            {{ $cycles->onEachSide(1)->links('brand-health.partials.pager') }}
        </div>
    @endif
</div>
@endsection

@section('bh-modals')
@php $canEdit = auth()->user()->can('edit-brand-health'); @endphp

{{-- Filter --}}
<div class="modal fade" id="filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="GET" action="{{ route('brand-health.cycles.index') }}" class="modal-content border-0 shadow" id="filter-form">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Filter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($filters['search'])<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                <label class="form-label small fw-semibold bh-muted">Account</label>
                <select name="account_id" class="form-select mb-3">
                    <option value="">All accounts</option>
                    @foreach($accounts as $a)<option value="{{ $a->id }}" @selected($filters['account_id'] == $a->id)>{{ $a->name }}</option>@endforeach
                </select>
                <label class="form-label small fw-semibold bh-muted">Project Manager</label>
                <select name="project_manager_id" class="form-select mb-3">
                    <option value="">All project managers</option>
                    @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}" @selected($filters['project_manager_id'] == $e->id)>{{ $e->name }}</option>@endforeach
                </select>
                <label class="form-label small fw-semibold bh-muted">Platform</label>
                <select name="platform" class="form-select mb-3" data-no-search>
                    <option value="">All platforms</option>
                    <option value="instagram" @selected($filters['platform'] === 'instagram')>Instagram</option>
                    <option value="tiktok" @selected($filters['platform'] === 'tiktok')>TikTok</option>
                </select>
                @foreach(['growth_label' => ['Growth', $growthLabels], 'visibility_label' => ['Visibility', $visibilityLabels], 'engagement_label' => ['Engagement', $engagementLabels], 'health_label' => ['Health Status', $healthLabels]] as $field => [$title, $labels])
                    <div class="mb-3" data-label-group="{{ $field }}">
                        <label class="form-label small fw-semibold bh-muted d-block">{{ $title }}</label>
                        @foreach($labels as $label)
                            <label class="bh-label-check"><input type="checkbox" class="form-check-input m-0" value="{{ $label }}" @checked(in_array($label, $filters[$field] ?? []))> <x-bh.status :status="$label" /></label>
                        @endforeach
                        <input type="hidden" name="{{ $field }}" value="{{ implode(',', $filters[$field] ?? []) }}">
                    </div>
                @endforeach
                <label class="form-label small fw-semibold bh-muted">Cycle Month Range</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="month" name="month_from" value="{{ $filters['month_from'] }}" class="form-control">
                    <span class="bh-faint">–</span>
                    <input type="month" name="month_to" value="{{ $filters['month_to'] }}" class="form-control">
                </div>
                <small class="bh-faint">Filters by the cycle's start date. Leave "to" empty for a single month.</small>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-decoration-none bh-muted" onclick="filterForm.reset()">Reset</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Create / edit --}}
@if($canEdit)
<div class="modal fade" id="cycle-form-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form method="POST" class="modal-content border-0 shadow" id="cycle-form" action="{{ route('brand-health.cycles.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="_cycle_mode" value="">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" data-cf-title>Add Cycle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Account</label>
                        <select name="account_id" class="form-select" required>
                            <option value="">Select an account</option>
                            @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Platform</label>
                        <select name="platform" class="form-select" required data-no-search>
                            <option value="">Select platform</option>
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold bh-muted">Project Manager</label>
                        <select name="project_manager_id" class="form-select">
                            <option value="">Select a project manager</option>
                            @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Cycle Start</label>
                        <input type="date" name="cycle_start_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Cycle End</label>
                        <input type="date" name="cycle_end_date" class="form-control" required>
                    </div>
                    <div class="col-12 d-none" data-cf-prefill-hint>
                        <small class="bh-faint"><i class="bi bi-magic"></i> Followers, reach, views &amp; engagement auto-fill from connected Instagram data, or a neighboring cycle, if available.</small>
                    </div>
                    <div class="col-md-6"><label class="form-label small fw-semibold bh-muted">Start Followers</label><input type="number" min="0" name="start_follower" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold bh-muted">End Followers</label><input type="number" min="0" name="end_follower" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold bh-muted" data-cf-reach-label>Reach</label><input type="number" min="0" name="reach" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold bh-muted">Views</label><input type="number" min="0" name="views" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold bh-muted">Engagement</label><input type="number" min="0" name="engagement" class="form-control" required></div>
                    <div class="col-12"><label class="form-label small fw-semibold bh-muted">Story Performance</label><input type="number" min="0" name="story_performance" class="form-control"></div>
                    <div class="col-12">
                        <div class="border rounded-3 p-3" style="border-color: var(--bh-border) !important">
                            <div class="small fw-semibold bh-muted mb-2">Ads Spend</div>
                            <div class="mb-2 d-none" data-cf-currency>
                                <label class="form-label small bh-muted">Currency</label>
                                <select name="ads_currency" class="form-select" style="max-width: 10rem" data-no-search>
                                    <option>IDR</option><option>USD</option><option>EUR</option>
                                </select>
                            </div>
                            @foreach(['reach_views' => 'Reach & Views', 'engagement' => 'Engagement'] as $metric => $label)
                                <div class="mb-2">
                                    <input type="hidden" name="{{ $metric }}_ads_used" value="0">
                                    <label class="form-check d-flex align-items-center gap-2 mb-1">
                                        <input type="checkbox" class="form-check-input m-0" name="{{ $metric }}_ads_used" value="1" data-ads-toggle="{{ $metric }}"> Used ads for {{ $label }}?
                                    </label>
                                    <input type="hidden" name="{{ $metric }}_ads_spend" value="0">
                                    <input type="text" inputmode="decimal" class="form-control d-none" placeholder="Amount spent" data-ads-amount="{{ $metric }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" data-cf-submit>Add Cycle</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Detail --}}
<div class="modal fade" id="cycle-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header align-items-start">
                <div>
                    <h5 class="modal-title fw-bold" data-cd-name></h5>
                    <div class="bh-muted small" data-cd-period></div>
                    <div class="bh-faint small" data-cd-pm></div>
                </div>
                <div class="d-flex gap-1 ms-auto">
                    @if($canEdit)<button type="button" class="btn btn-sm btn-light-secondary" data-cd-edit title="Edit"><i class="bi bi-pencil"></i></button>@endif
                    <a class="btn btn-sm btn-light-secondary" data-cd-pdf title="Download PDF"><i class="bi bi-download"></i></a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="row g-2" data-cd-inputs></div>
                <div class="bh-stat-label mt-4 mb-2">Rate Metrics</div>
                <div class="border rounded-3" style="border-color: var(--bh-border) !important"><table class="w-100"><tbody data-cd-rates></tbody></table></div>
                <div class="bh-stat-label mt-4 mb-2">Aggregate Scores</div>
                <div class="row g-2" data-cd-aggregates></div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="bh-stat-label">AI Summary</div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="bh-faint">Provider</small>
                        <select class="form-select form-select-sm" style="width: auto" data-cd-provider data-no-search><option value="groq">Groq</option><option value="gemini">Gemini</option></select>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <input type="text" maxlength="500" class="form-control" data-cd-prompt placeholder="Optional: add custom instructions (e.g. focus on engagement, write for a client email)…">
                    <button type="button" class="btn btn-primary text-nowrap" data-cd-summarize><i class="bi bi-stars"></i> Summarize by AI</button>
                </div>
                <p class="small mt-2 mb-0 d-none" style="color: var(--status-parah-ink)" data-cd-error></p>
                <div class="rounded-3 border p-3 mt-2 d-none" style="background: var(--bh-raised)" data-cd-summary-box>
                    <p class="mb-1" style="white-space: pre-line" data-cd-summary></p>
                    <small class="bh-faint" data-cd-summary-at></small>
                </div>
                <p class="small bh-faint mt-2 mb-0" data-cd-no-summary>No summary yet. Click "Summarize by AI" to generate one.</p>
            </div>
        </div>
    </div>
</div>
<div class="bh-tip d-none" id="bucket-tip"></div>

{{-- Ads ranking --}}
<div class="modal fade" id="ads-ranking-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow" style="height: 82vh">
            <div class="modal-header align-items-start">
                <div>
                    <h5 class="modal-title fw-bold">Ads Nominal Ranking</h5>
                    <small class="bh-muted">Accounts ranked by total ads spend</small>
                </div>
                <div class="d-flex gap-1 ms-auto align-items-center">
                    <a class="btn btn-sm btn-light-secondary" data-ar-pdf title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                    <a class="btn btn-sm btn-light-secondary" data-ar-excel title="Download Excel"><i class="bi bi-file-earmark-excel"></i></a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="px-3 py-2 border-bottom d-flex flex-wrap gap-2 align-items-center" style="border-color: var(--bh-border) !important">
                <span class="small bh-muted">Cycle month range</span>
                <input type="month" class="form-control form-control-sm" style="width: 10rem" data-ar-from>
                <span class="bh-faint">–</span>
                <input type="month" class="form-control form-control-sm" style="width: 10rem" data-ar-to>
                <button type="button" class="btn btn-sm btn-link text-decoration-none d-none" data-ar-reset>Reset</button>
                <small class="ms-auto bh-faint" data-ar-range>All time</small>
            </div>
            <div class="px-3 pt-2 d-none" data-ar-currencies></div>
            <div class="modal-body pt-2">
                <div class="bh-empty" data-ar-status>Loading…</div>
                <table class="bh-table d-none" data-ar-table>
                    <thead><tr>
                        <th style="width: 3rem"></th><th>Account</th>
                        <th class="text-end sortable" data-ar-sort="reach_views_ads_spend">Reach/Views Ads <span data-ar-cur></span></th>
                        <th class="text-end sortable" data-ar-sort="engagement_ads_spend">Engagement Ads <span data-ar-cur></span></th>
                        <th class="text-end sortable" data-ar-sort="total_ads_spend">Total Ads Spend <span data-ar-cur></span></th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('brand-health.partials.scoring-info-modal', ['metrics' => ['health'], 'id' => 'scoring-info'])
@include('brand-health.partials.filter-summary-modal', ['endpoint' => route('brand-health.cycles.summarize-filtered'), 'countKey' => 'cycle_count', 'countNoun' => 'cycle'])
@endsection

@section('bh-scripts')
<script>
const CYCLES = @json($rows->values());
const SCORE_BUCKETS = @json($scoreBuckets);
const FILTER_QUERY = @json(array_filter($query, fn ($v) => $v !== null && $v !== ''));
const URLS = {
    show: (id) => @json(route('brand-health.cycles.pdf-single', '__ID__')).replace('__ID__', id),
    summarize: (id) => @json(route('brand-health.cycles.summarize', '__ID__')).replace('__ID__', id),
    update: (id) => @json(route('brand-health.cycles.update', '__ID__')).replace('__ID__', id),
    store: @json(route('brand-health.cycles.store')),
    neighboring: (accountId) => @json(route('brand-health.cycles.neighboring', '__ID__')).replace('__ID__', accountId),
    adsRanking: @json(route('brand-health.cycles.ads-ranking')),
    adsPdf: @json(route('brand-health.cycles.ads-ranking.pdf')),
    adsExcel: @json(route('brand-health.cycles.ads-ranking.excel')),
};
const fmtDate = (v) => new Date(v).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
const round = (v, d = 2) => Math.round(Number(v) * 10 ** d) / 10 ** d;

/* ---------- Score distribution chart ---------- */
(function () {
    const dist = @json($scoreDistribution);
    const canvas = document.getElementById('score-distribution-chart');
    if (!canvas || !dist.tiers) return;
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const colors = dark ? ['#3987e5', '#d95926', '#199e70', '#c98500'] : ['#2a78d6', '#eb6834', '#1baf7a', '#eda100'];
    // Grouped bars: the labels are separate categories, so a line between them would imply a trend that isn't there.
    BH.chart(canvas, {
        type: 'bar',
        data: { labels: dist.tiers.map(String), datasets: (dist.series || []).map((s, i) => ({ label: s.label, data: dist.tiers.map((t) => s.counts[t] ?? 0),
            backgroundColor: colors[i % 4], borderRadius: 4, maxBarThickness: 22, categoryPercentage: .7, barPercentage: .9 })) },
        options: { responsive: true, maintainAspectRatio: false,
            plugins: { tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${c.parsed.y} cycle${c.parsed.y === 1 ? '' : 's'}` } } },
            scales: { x: { grid: { display: false }, title: { display: true, text: 'Label' } }, y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Cycles' } } } },
    });
})();

/* ---------- Search (debounced) + client-side sort ---------- */
(function () {
    const input = document.getElementById('search-input');
    let t;
    input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => document.getElementById('search-form').submit(), 400); });

    const tbody = document.querySelector('#cycles-table tbody');
    let key = 'cycle_start_date', dir = 'desc';
    document.querySelectorAll('#cycles-table th[data-sort]').forEach((th) => th.addEventListener('click', () => {
        dir = key === th.dataset.sort ? (dir === 'asc' ? 'desc' : 'asc') : 'asc';
        key = th.dataset.sort;
        const rows = [...tbody.querySelectorAll('tr[data-index]')];
        const num = !['account', 'platform', 'cycle_start_date', 'health_label'].includes(key);
        rows.sort((a, b) => {
            let x = a.dataset[key], y = b.dataset[key];
            if (num) { x = Number(x); y = Number(y); }
            return (x < y ? -1 : x > y ? 1 : 0) * (dir === 'asc' ? 1 : -1);
        }).forEach((r) => tbody.appendChild(r));
        document.querySelectorAll('#cycles-table th[data-sort]').forEach((h) => {
            h.classList.toggle('sorted', h === th);
            h.querySelector('.sort-arrow').textContent = h === th ? (dir === 'asc' ? '↑' : '↓') : '';
        });
    }));
    tbody.querySelectorAll('tr[data-index]').forEach((tr) => tr.addEventListener('click', () => cycleDetail.open(+tr.dataset.index)));
})();

/* ---------- Filter modal: label checkboxes -> comma lists ---------- */
const filterForm = {
    el: document.getElementById('filter-form'),
    sync() {
        this.el.querySelectorAll('[data-label-group]').forEach((g) => {
            g.querySelector('input[type=hidden]').value = [...g.querySelectorAll('input[type=checkbox]:checked')].map((c) => c.value).join(',');
        });
    },
    reset() {
        this.el.querySelectorAll('select').forEach((s) => { s.value = ''; if (s._choices) s._choices.setChoiceByValue(''); });
        this.el.querySelectorAll('input[type=checkbox]').forEach((c) => c.checked = false);
        this.el.querySelectorAll('input[type=month]').forEach((m) => m.value = '');
    },
};
filterForm.el.addEventListener('submit', () => {
    filterForm.sync();
    // Drop empty params so the URL stays readable.
    filterForm.el.querySelectorAll('input[name], select[name]').forEach((f) => { if (!f.value) f.disabled = true; });
});

/* ---------- AI summary of the filtered set ---------- */
const aiBanner = {
    el: document.getElementById('ai-banner'),
    show(data) {
        this.el.querySelector('[data-ai-text]').textContent = data.summary;
        this.el.querySelector('[data-ai-meta]').textContent = `Based on ${data.cycle_count} cycle${data.cycle_count === 1 ? '' : 's'} · ${data.filter_summary}`;
        this.el.classList.remove('d-none');
        // The PDF export embeds the current AI summary.
        const pdf = new URL(document.getElementById('pdf-link').href);
        pdf.searchParams.set('ai_summary', data.summary);
        document.getElementById('pdf-link').href = pdf;
        bootstrap.Modal.getInstance(document.getElementById('filter-summary-modal'))?.hide();
    },
    dismiss() {
        this.el.classList.add('d-none');
        const pdf = new URL(document.getElementById('pdf-link').href);
        pdf.searchParams.delete('ai_summary');
        document.getElementById('pdf-link').href = pdf;
    },
};
document.getElementById('summarize-btn').addEventListener('click', () => {
    const parts = [];
    const f = @json($filters);
    const accounts = @json($accounts->pluck('name', 'id'));
    const pms = @json($accountDepartmentEmployees->pluck('name', 'id'));
    if (f.account_id) parts.push(`account: ${accounts[f.account_id]}`);
    if (f.project_manager_id) parts.push(`PM: ${pms[f.project_manager_id]}`);
    if (f.search) parts.push(`search: "${f.search}"`);
    if (f.platform) parts.push(`platform: ${f.platform === 'instagram' ? 'Instagram' : 'TikTok'}`);
    ['growth', 'visibility', 'engagement', 'health'].forEach((m) => { if (f[m + '_label']?.length) parts.push(`${m}: ${f[m + '_label'].join(', ')}`); });
    if (f.month_from) parts.push(`period: ${BH.month(f.month_from)}${f.month_to && f.month_to !== f.month_from ? ' - ' + BH.month(f.month_to) : ''}`);
    BH.openFilterSummary(FILTER_QUERY, parts.length ? parts.join(', ') : 'none (all cycles)', (data) => aiBanner.show(data));
});

/* ---------- Cycle detail ---------- */
const cycleDetail = (function () {
    const el = document.getElementById('cycle-detail-modal');
    const $ = (s) => el.querySelector(s);
    const tip = document.getElementById('bucket-tip');
    let cycle = null;
    const platformLabels = { instagram: 'Instagram', tiktok: 'TikTok' };

    const scoreStyle = (score) => {
        if (score === null || score === undefined) return 'background: var(--bh-border); color: var(--bh-ink-faint)';
        if (score <= 25) return 'background: var(--status-parah-bg); color: var(--status-parah-ink)';
        if (score <= 50) return 'background: var(--status-kurang-bg); color: var(--status-kurang-ink)';
        return 'background: var(--status-sip-bg); color: var(--status-sip-ink)';
    };
    // Mirrors MetricCalculator::resolveScore(): (platform, tier) -> (platform, none) -> (none, none).
    const bucketsFor = (metric) => {
        const all = SCORE_BUCKETS.filter((b) => b.metric === metric);
        for (const [p, t] of [[cycle.platform ?? null, cycle.tier ?? null], [cycle.platform ?? null, null], [null, null]]) {
            const m = all.filter((b) => (b.platform ?? null) === p && (b.tier ?? null) === t);
            if (m.length) return m;
        }
        return [];
    };
    const scopeLabel = (metric) => {
        const [b] = bucketsFor(metric);
        if (!b) return '';
        if (b.tier) return `${b.platform ? platformLabels[b.platform] ?? b.platform : 'All platforms'} — ${b.tier}`;
        if (b.platform) return `${platformLabels[b.platform] ?? b.platform} (no tier match — platform default)`;
        return 'Global default';
    };
    const divisor = (m) => (m === 'reach' || m === 'view' ? 100 : 1);
    const suffix = (m) => (['growth', 'er_reach', 'er_follower', 'er_view'].includes(m) ? '%' : '');
    const money = (amount, cur) => { try { return new Intl.NumberFormat('en-US', { style: 'currency', currency: cur || 'IDR' }).format(amount); } catch { return `${amount} ${cur}`; } };
    const adsLine = (used, spend) => (used ? `Ads: Yes — ${money(spend ?? 0, cycle.ads_currency)}` : 'Ads: No');

    function render() {
        const s = cycle.scores, tiktok = cycle.platform === 'tiktok';
        $('[data-cd-name]').textContent = cycle.account?.name ?? '';
        $('[data-cd-period]').textContent = `${fmtDate(cycle.cycle_start_date)} – ${fmtDate(cycle.cycle_end_date)}`;
        $('[data-cd-pm]').textContent = cycle.project_manager ? `PM: ${cycle.project_manager.name}` : '';
        $('[data-cd-pdf]').href = URLS.show(cycle.id);

        $('[data-cd-inputs]').innerHTML = [
            ['Start Followers', cycle.start_follower], ['End Followers', cycle.end_follower],
            [tiktok ? 'Profile Views' : 'Reach', cycle.reach], ['Views', cycle.views], ['Engagement', cycle.engagement],
        ].map(([l, v]) => `<div class="col-6 col-md"><div class="bh-input-chip"><div class="bh-stat-label">${l}</div><div class="val">${BH.num(v)}</div></div></div>`).join('');

        const rows = [
            { label: 'Growth', metric: 'growth', formula: 'end − start', rate: `${s.growth > 0 ? '+' : ''}${s.growth}`, score: s.growth_score },
            { label: 'Growth Rate', metric: 'growth', formula: '(end − start) / start × 100', rate: `${round(s.growth_rate)}%`, score: s.growth_score },
        ];
        if (tiktok) rows.push(
            { label: 'Profile Rate', metric: 'profile', formula: 'profile views / views × 1,000', rate: `${round(s.profile_rate)}`, score: s.profile_score },
            { label: 'View Rate', metric: 'view', formula: 'views / end followers', rate: `${round(s.view_rate / 100)}`, score: s.view_score },
            { label: 'ER (of Views)', metric: 'er_view', formula: 'engagement / views × 100', rate: `${round(s.er_view_rate)}%`, score: s.er_view_score, ads: adsLine(cycle.engagement_ads_used, cycle.engagement_ads_spend) },
        ); else rows.push(
            { label: 'Reach Rate', metric: 'reach', formula: 'reach / end followers', rate: `${round(s.reach_rate / 100)}`, score: s.reach_score, ads: adsLine(cycle.reach_views_ads_used, cycle.reach_views_ads_spend) },
            { label: 'View Rate', metric: 'view', formula: 'views / end followers', rate: `${round(s.view_rate / 100)}`, score: s.view_score, ads: adsLine(cycle.reach_views_ads_used, cycle.reach_views_ads_spend) },
            { label: 'ER (of Reach)', metric: 'er_reach', formula: 'engagement / reach × 100', rate: `${round(s.er_reach_rate)}%`, score: s.er_reach_score, ads: adsLine(cycle.engagement_ads_used, cycle.engagement_ads_spend) },
            { label: 'ER (of Followers)', metric: 'er_follower', formula: 'engagement / end followers × 100', rate: `${round(s.er_follower_rate)}%`, score: s.er_follower_score, ads: adsLine(cycle.engagement_ads_used, cycle.engagement_ads_spend) },
        );
        const totalAds = [[cycle.reach_views_ads_used, cycle.reach_views_ads_spend], [cycle.engagement_ads_used, cycle.engagement_ads_spend]].reduce((t, [u, sp]) => t + (u ? Number(sp ?? 0) : 0), 0);
        rows.push(
            { label: 'Story Performance', metric: null, formula: 'manual input', rate: BH.num(cycle.story_performance ?? 0), score: null },
            { label: 'Total Ads Spend', metric: null, formula: 'reach & views + engagement ads spend', rate: money(totalAds, cycle.ads_currency), score: null },
        );
        $('[data-cd-rates]').innerHTML = rows.map((r, i) => `<tr style="${i ? 'border-top: 1px solid var(--bh-border)' : ''}">
            <td class="px-3 py-2"><div class="fw-semibold small">${r.label}</div>${r.ads ? `<div class="small fw-semibold" style="color: var(--bh-accent)">${r.ads}</div>` : ''}<div class="font-monospace bh-faint" style="font-size: .7rem">${r.formula}</div></td>
            <td class="px-3 py-2 text-end bh-muted bh-num small">${BH.esc(r.rate)}</td>
            <td class="px-3 py-2 text-end"><span class="bh-score" style="${scoreStyle(r.score)}" ${r.metric ? `data-metric="${r.metric}"` : ''}>${r.score === null ? '-' : r.score}</span></td></tr>`).join('');

        const nc = !!cycle.needs_check, nc3 = !!cycle.health_3c_needs_check;
        const aggs = [
            { label: 'Growth Rate', formula: 'score based on growth rate', rate: nc ? '—' : round(s.growth_score), badge: nc ? null : s.growth_label, flag: nc },
            { label: 'Visibility', formula: tiktok ? 'weighted average of Profile Score + View Score' : 'weighted average of Reach Score + View Score', rate: round(s.visibility_rate), badge: s.visibility_label },
            { label: 'Engagement', formula: tiktok ? 'ER-View Score' : 'weighted average of ER-Reach Score + ER-Follower Score', rate: round(s.engagement_score), badge: s.engagement_label },
            { label: 'Health', formula: 'weighted average of Growth + Visibility + Engagement', rate: nc ? '—' : round(s.health_rate), badge: nc ? null : s.health_label, flag: nc },
            { label: 'Health (3-cycle avg)', formula: 'average Health of this cycle + up to 2 previous (same account & platform)', rate: nc3 ? '—' : (cycle.health_3c != null ? round(cycle.health_3c) : '—'), badge: nc3 ? null : cycle.health_3c_label, flag: nc3 },
        ];
        $('[data-cd-aggregates]').innerHTML = aggs.map((a) => `<div class="col-md-6 col-lg"><div class="bh-agg h-100">
            <div class="d-flex justify-content-between align-items-center gap-2"><span class="fw-semibold small">${a.label}</span><span class="fw-bold fs-5 bh-num">${a.rate}</span></div>
            ${a.badge ? `<div class="mt-1">${BH.statusBadge(a.badge)}</div>` : ''}${a.flag ? '<span class="bh-flag">⚠ Data perlu dicek</span>' : ''}
            <div class="bh-faint mt-auto pt-2" style="font-size: .68rem">${a.formula}</div></div></div>`).join('');

        setSummary(cycle.ai_summary, cycle.ai_summary_generated_at);
        $('[data-cd-error]').classList.add('d-none');
        $('[data-cd-prompt]').value = '';
        $('[data-cd-provider]').value = @json($defaultAiProvider);
    }

    function setSummary(text, at) {
        $('[data-cd-summary-box]').classList.toggle('d-none', !text);
        $('[data-cd-no-summary]').classList.toggle('d-none', !!text);
        $('[data-cd-summary]').textContent = text ?? '';
        $('[data-cd-summary-at]').textContent = at ? 'Generated ' + new Date(at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '';
        $('[data-cd-summarize]').innerHTML = `<i class="bi bi-stars"></i> ${text ? 'Regenerate' : 'Summarize by AI'}`;
    }

    // Hovering a score shows the thresholds of the bucket set this cycle actually uses.
    el.addEventListener('mouseover', (e) => {
        const badge = e.target.closest('[data-metric]');
        if (!badge) return;
        const m = badge.dataset.metric, buckets = bucketsFor(m), rect = badge.getBoundingClientRect();
        tip.innerHTML = `<div class="bh-stat-label">Scoring Buckets</div>${scopeLabel(m) ? `<div class="bh-muted" style="font-size: .68rem">${scopeLabel(m)}</div>` : ''}` +
            (buckets.length ? buckets.map((b) => `<div class="d-flex align-items-center gap-1 mt-1"><span class="bh-muted">≥ ${round(Number(b.min_rate) / divisor(m))}${suffix(m)}</span><span class="bh-faint">=</span><span class="bh-score ms-auto" style="min-width: 2.25rem; ${scoreStyle(Number(b.score))}">${round(Number(b.score))}</span></div>`).join('')
                : '<div class="bh-faint mt-1">No buckets configured.</div>');
        tip.style.bottom = `${window.innerHeight - rect.top + 4}px`;
        tip.style.left = `${Math.max(8, rect.right - 190)}px`;
        tip.classList.remove('d-none');
    });
    el.addEventListener('mouseout', (e) => { if (e.target.closest('[data-metric]')) tip.classList.add('d-none'); });
    el.addEventListener('hide.bs.modal', () => tip.classList.add('d-none'));

    $('[data-cd-summarize]').addEventListener('click', async function () {
        this.disabled = true; this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Summarizing…';
        $('[data-cd-error]').classList.add('d-none');
        try {
            const data = await BH.request(URLS.summarize(cycle.id), { method: 'POST', body: { prompt: $('[data-cd-prompt]').value || undefined, provider: $('[data-cd-provider]').value } });
            cycle.ai_summary = data.ai_summary; cycle.ai_summary_generated_at = data.ai_summary_generated_at;
            setSummary(data.ai_summary, data.ai_summary_generated_at);
        } catch (e) {
            $('[data-cd-error]').textContent = e.message; $('[data-cd-error]').classList.remove('d-none');
            setSummary(cycle.ai_summary, cycle.ai_summary_generated_at);
        } finally { this.disabled = false; }
    });
    $('[data-cd-prompt]').addEventListener('keydown', (e) => { if (e.key === 'Enter') $('[data-cd-summarize]').click(); });
    $('[data-cd-edit]')?.addEventListener('click', () => {
        bootstrap.Modal.getInstance(el).hide();
        cycleForm.open(CYCLES.indexOf(cycle));
    });

    return { open(i) { cycle = CYCLES[i]; render(); bootstrap.Modal.getOrCreateInstance(el).show(); } };
})();

/* ---------- Create / edit form ---------- */
const cycleForm = (function () {
    const modalEl = document.getElementById('cycle-form-modal');
    if (!modalEl) return { open() {} };
    const form = document.getElementById('cycle-form');
    const f = (n) => form.querySelector(`[name="${n}"]:not([type=hidden])`) || form.querySelector(`[name="${n}"]`);
    let editing = null;

    // Selects may be wrapped by the app-wide searchable picker; set the value and notify it.
    const setVal = (name, v) => {
        const field = f(name);
        field.value = v ?? '';
        if (field._choices) field._choices.setChoiceByValue(String(v ?? ''));
    };
    const fmtAmount = (v) => (v === '' || v == null ? '' : new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(v));

    function syncAds() {
        const any = ['reach_views', 'engagement'].some((m) => form.querySelector(`[data-ads-toggle="${m}"]`).checked);
        form.querySelector('[data-cf-currency]').classList.toggle('d-none', !any);
        ['reach_views', 'engagement'].forEach((m) => form.querySelector(`[data-ads-amount="${m}"]`).classList.toggle('d-none', !form.querySelector(`[data-ads-toggle="${m}"]`).checked));
    }
    function syncLabels() {
        form.querySelector('[data-cf-reach-label]').textContent = f('platform').value === 'tiktok' ? 'Profile Views' : 'Reach';
        form.querySelector('[data-cf-prefill-hint]').classList.toggle('d-none', !!editing || !f('account_id').value || !(f('cycle_start_date').value || f('cycle_end_date').value));
    }

    async function prefill(which) {
        if (editing || !f('account_id').value) return;
        const start = f('cycle_start_date').value, end = f('cycle_end_date').value;
        if ((which === 'start' && !start) || (which === 'end' && !end)) return;
        const params = { platform: f('platform').value || 'instagram' };
        if (start) params.start_date = start;
        if (end) params.end_date = end;
        try {
            const data = await BH.request(URLS.neighboring(f('account_id').value) + BH.qs(params));
            ['start_follower', 'end_follower', 'reach', 'views', 'engagement'].forEach((k) => { if (data[k] != null) f(k).value = data[k]; });
        } catch { /* user can still type values */ }
    }

    form.querySelectorAll('[data-ads-toggle]').forEach((c) => c.addEventListener('change', syncAds));
    form.querySelectorAll('[data-ads-amount]').forEach((inp) => inp.addEventListener('input', () => {
        const digits = inp.value.replace(/[^0-9.]/g, '');
        form.querySelector(`input[type=hidden][name="${inp.dataset.adsAmount}_ads_spend"]`).value = digits === '' ? '' : Number(digits);
        inp.value = digits === '' ? '' : fmtAmount(Number(digits));
    }));
    f('cycle_start_date').addEventListener('change', () => { syncLabels(); prefill('start'); });
    f('cycle_end_date').addEventListener('change', () => { syncLabels(); prefill('end'); });
    f('platform').addEventListener('change', () => { syncLabels(); prefill('start'); prefill('end'); });
    f('account_id').addEventListener('change', syncLabels);

    function load(c) {
        editing = c;
        form.action = c ? URLS.update(c.id) : URLS.store;
        form.querySelector('[name=_method]').value = c ? 'PUT' : 'POST';
        form.querySelector('[name=_cycle_mode]').value = c ? 'edit:' + c.id : 'create';
        form.querySelector('[data-cf-title]').textContent = c ? 'Edit Cycle' : 'Add Cycle';
        form.querySelector('[data-cf-submit]').textContent = c ? 'Save Changes' : 'Add Cycle';
        setVal('account_id', c?.account_id);
        setVal('platform', c ? (c.platform ?? 'instagram') : '');
        setVal('project_manager_id', c?.project_manager_id);
        f('cycle_start_date').value = c ? String(c.cycle_start_date).slice(0, 10) : '';
        f('cycle_end_date').value = c ? String(c.cycle_end_date).slice(0, 10) : '';
        ['start_follower', 'end_follower', 'reach', 'views', 'engagement'].forEach((k) => f(k).value = c ? c[k] : 0);
        f('story_performance').value = c ? (c.story_performance ?? 0) : 0;
        setVal('ads_currency', c?.ads_currency ?? 'IDR');
        ['reach_views', 'engagement'].forEach((m) => {
            form.querySelector(`[data-ads-toggle="${m}"]`).checked = !!(c && c[m + '_ads_used']);
            const spend = c ? (c[m + '_ads_spend'] ?? 0) : 0;
            form.querySelector(`input[type=hidden][name="${m}_ads_spend"]`).value = spend;
            form.querySelector(`[data-ads-amount="${m}"]`).value = fmtAmount(Number(spend));
        });
        syncAds(); syncLabels();
    }

    // After a failed save the page reloads with errors: reopen the form with what was typed.
    const old = @json(old());
    if (old._cycle_mode) {
        const id = old._cycle_mode.startsWith('edit:') ? +old._cycle_mode.slice(5) : null;
        load(id ? CYCLES.find((c) => c.id === id) ?? null : null);
        Object.entries(old).forEach(([k, v]) => { const el = f(k); if (el && !k.startsWith('_') && typeof v !== 'object') { el.value = v; if (el.tagName === 'SELECT') el.dispatchEvent(new Event('change')); } });
        ['reach_views', 'engagement'].forEach((m) => { form.querySelector(`[data-ads-toggle="${m}"]`).checked = old[m + '_ads_used'] === '1'; form.querySelector(`[data-ads-amount="${m}"]`).value = fmtAmount(Number(old[m + '_ads_spend'] || 0)); });
        syncAds(); syncLabels();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    return { open(i) { load(i === undefined ? null : CYCLES[i]); bootstrap.Modal.getOrCreateInstance(modalEl).show(); } };
})();

/* ---------- Ads ranking ---------- */
(function () {
    const el = document.getElementById('ads-ranking-modal');
    const $ = (s) => el.querySelector(s);
    let rows = [], currency = null, key = 'total_ads_spend', dir = 'desc', loaded = false;
    const params = () => { const from = $('[data-ar-from]').value, to = $('[data-ar-to]').value; return { month_from: from || undefined, month_to: from ? (to || from) : undefined }; };

    function draw() {
        const currencies = [...new Set(rows.map((r) => r.currency))].sort();
        if (!currencies.includes(currency)) currency = currencies[0] ?? null;
        const tabs = $('[data-ar-currencies]');
        tabs.classList.toggle('d-none', currencies.length < 2);
        tabs.innerHTML = currencies.map((c) => `<button type="button" class="btn btn-sm ${c === currency ? 'btn-primary' : 'btn-light-secondary'} me-1" data-cur="${c}">${c}</button>`).join('');
        el.querySelectorAll('[data-ar-cur]').forEach((s) => s.textContent = currency ? `(${currency})` : '');
        const list = rows.filter((r) => r.currency === currency).sort((a, b) => (a[key] - b[key]) * (dir === 'asc' ? 1 : -1));
        const ranked = key === 'total_ads_spend' && dir === 'desc';
        const medal = ['background: var(--status-cukup-bg); color: var(--status-cukup-ink)', 'background: var(--bh-raised); color: var(--bh-ink-muted)', 'background: var(--status-kurang-bg); color: var(--status-kurang-ink)'];
        $('[data-ar-table] tbody').innerHTML = list.map((r, i) => `<tr style="${ranked && i === 0 ? 'background: var(--bh-accent-soft)' : ''}">
            <td>${ranked && i < 3 ? `<span class="bh-rank" style="${medal[i]}">${i + 1}</span>` : `<span class="bh-faint bh-num ps-2">${i + 1}</span>`}</td>
            <td class="fw-semibold">${BH.esc(r.account_name)}</td>
            <td class="text-end bh-muted bh-num">${BH.num(r.reach_views_ads_spend)}</td>
            <td class="text-end bh-muted bh-num">${BH.num(r.engagement_ads_spend)}</td>
            <td class="text-end fw-bold bh-num">${BH.num(r.total_ads_spend)}</td></tr>`).join('') || '<tr><td colspan="5" class="bh-empty">No accounts with ads spend recorded for this range.</td></tr>';
        el.querySelectorAll('[data-ar-sort]').forEach((th) => th.classList.toggle('sorted', th.dataset.arSort === key));
    }

    async function load() {
        const p = params();
        $('[data-ar-pdf]').href = URLS.adsPdf + BH.qs(p);
        $('[data-ar-excel]').href = URLS.adsExcel + BH.qs(p);
        $('[data-ar-reset]').classList.toggle('d-none', !p.month_from);
        $('[data-ar-range]').textContent = p.month_from ? `${BH.month(p.month_from)} – ${BH.month(p.month_to)}` : 'All time';
        $('[data-ar-status]').textContent = 'Loading…'; $('[data-ar-status]').classList.remove('d-none'); $('[data-ar-table]').classList.add('d-none');
        try {
            const data = await BH.request(URLS.adsRanking + BH.qs(p));
            rows = data.rows ?? [];
            if (!rows.length) { $('[data-ar-status]').textContent = 'No accounts with ads spend recorded for this range.'; return; }
            $('[data-ar-status]').classList.add('d-none'); $('[data-ar-table]').classList.remove('d-none');
            draw();
        } catch (e) { $('[data-ar-status]').textContent = e.message; }
    }

    el.addEventListener('show.bs.modal', () => { if (!loaded) { loaded = true; load(); } });
    $('[data-ar-from]').addEventListener('change', load);
    $('[data-ar-to]').addEventListener('change', load);
    $('[data-ar-reset]').addEventListener('click', () => { $('[data-ar-from]').value = ''; $('[data-ar-to]').value = ''; load(); });
    $('[data-ar-currencies]').addEventListener('click', (e) => { const b = e.target.closest('[data-cur]'); if (b) { currency = b.dataset.cur; draw(); } });
    el.querySelectorAll('[data-ar-sort]').forEach((th) => th.addEventListener('click', () => { dir = key === th.dataset.arSort && dir === 'desc' ? 'asc' : 'desc'; key = th.dataset.arSort; draw(); }));
})();
</script>
@endsection
