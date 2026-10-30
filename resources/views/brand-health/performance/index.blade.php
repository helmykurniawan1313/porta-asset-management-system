@extends('brand-health.layout')

@section('title', 'Reels / Video | Brand Health')

@section('bh-styles')
<style>
    .bh-click-card { cursor: pointer; transition: transform .15s ease, box-shadow .15s ease; }
    .bh-click-card:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(16, 24, 40, .08); }
    .bh-row-click { cursor: pointer; }
    .bh-sort-link { color: inherit; text-decoration: none; }
    .bh-label-check { display: inline-flex; align-items: center; gap: .35rem; margin: 0 .5rem .4rem 0; cursor: pointer; }
    .pd-chip { border: 1px solid var(--bh-border); border-radius: 10px; padding: .55rem .75rem; background: var(--bh-raised); height: 100%; }
    .pd-chip .lbl { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--bh-ink-faint); font-weight: 600; }
    .pd-chip .val { font-weight: 700; font-variant-numeric: tabular-nums; margin-top: .1rem; }
    .pd-proof { max-height: 260px; border-radius: 10px; border: 1px solid var(--bh-border); cursor: zoom-in; }
    .pf-proof-drop { border: 2px dashed var(--bh-border-strong); border-radius: 12px; padding: 1.25rem; text-align: center; cursor: pointer; color: var(--bh-ink-muted); }
    .pf-proof-drop.drag { border-color: var(--bh-accent); background: var(--bh-accent-soft); }
    .ig-pick { border: 1px solid var(--bh-border); border-radius: 10px; padding: .55rem .7rem; text-align: left; background: transparent; width: 100%; font-size: .85rem; }
    .ig-pick:hover { border-color: var(--bh-accent); }
</style>
@endsection

@section('bh-content')
@php
    $canEdit = auth()->user()->can('edit-brand-health');
    $query = array_filter(request()->except('page'), fn ($v) => $v !== null && $v !== '');
    $activeFilterCount = collect(['account_id', 'project_manager_id', 'conceptor_id', 'views_status', 'ads', 'post_date_from', 'post_date_to', 'views_h7', 'platform'])
        ->filter(fn ($k) => filled(request($k)))->count();
    $hasFilters = filled($filters['search']) || $activeFilterCount > 0;
    $sortUrl = function ($col) use ($filters, $query) {
        $dir = $filters['sort'] === $col && $filters['direction'] === 'asc' ? 'desc' : 'asc';
        return route('brand-health.performances.index', array_merge($query, ['sort' => $col, 'direction' => $dir]));
    };
    $arrow = fn ($col) => $filters['sort'] === $col ? (($filters['direction'] ?? 'asc') === 'asc' ? '▲' : '▼') : '';
    $rows = collect($performances->items());
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('M j, Y') : '—';
    $selectedStatuses = $filters['views_status'] ? explode(',', $filters['views_status']) : [];
    $adsValue = $filters['ads'] === null ? '' : ($filters['ads'] === 'null' ? 'null' : ($filters['ads'] ? '1' : '0'));
@endphp

<div class="bh-page-head">
    <div>
        <h3>Reels / Video</h3>
        <p>Per-post performance — Views H+7 status, crew credits, and Instagram-linked metrics.</p>
    </div>
    @if($canEdit)<button type="button" class="btn btn-primary" onclick="perfForm.open()"><i class="bi bi-plus-lg"></i> Add Performance</button>@endif
</div>

@if($summary['total'] > 0)
    <div class="row g-3">
        <div class="col-6 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Records Tracked</div><div class="bh-stat-value">{{ number_format($summary['total']) }}</div></div></div>
        <div class="col-6 col-md-4"><div class="bh-card h-100"><div class="bh-stat-label">Avg Views H+7</div><div class="bh-stat-value">{{ $summary['avg_views'] === null ? '—' : number_format($summary['avg_views']) }}</div></div></div>
        <div class="col-12 col-md-4">
            <a href="{{ route('brand-health.performances.index', array_merge($query, ['views_status' => $topTierLabels->implode(',')])) }}#performance-table" class="bh-card h-100 d-block text-reset bh-click-card">
                <div class="bh-stat-label">Top Performing</div>
                <div class="bh-stat-value">{{ $summary['top_performing_count'] }}</div>
                <div class="bh-stat-hint">{{ $topTierLabels->implode(' or ') ?: '—' }}</div>
            </a>
        </div>
    </div>
    @if($summary['at_risk_count'] > 0)
        <a href="{{ route('brand-health.performances.index', array_merge($query, ['views_status' => $bottomTierLabels->implode(',')])) }}#performance-table" class="bh-alert-tile" style="border-color: var(--status-parah-ink); background: var(--status-parah-bg); color: var(--status-parah-ink)">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                <div class="fw-bold fs-5">{{ $summary['at_risk_count'] }} {{ \Illuminate\Support\Str::plural('record', $summary['at_risk_count']) }} {{ $summary['at_risk_count'] === 1 ? 'needs' : 'need' }} attention</div>
                <small>Views H+7 status is {{ $bottomTierLabels->implode(' or ') }} — click to filter the table below.</small>
            </div>
        </a>
    @endif
    <div class="bh-card mt-3">
        <h6 class="fw-bold mb-0">Views H+7 Status Distribution</h6>
        <small class="bh-muted">How many matching records landed on each Views H+7 status label.</small>
        <div class="bh-chart mt-3" style="height: 240px"><canvas id="status-chart"></canvas></div>
    </div>
@endif

<div class="d-flex flex-wrap align-items-center gap-2 mt-4">
    <form method="GET" class="position-relative" style="width: 16rem">
        @foreach(request()->except(['search', 'page']) as $k => $v)
            @if(is_scalar($v) && $v !== '')<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <i class="bi bi-search position-absolute bh-faint" style="left: 12px; top: 50%; transform: translateY(-50%)"></i>
        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control ps-5" placeholder="Search by account…" autocomplete="off" data-debounce>
    </form>
    <button type="button" class="btn btn-light-secondary" data-bs-toggle="modal" data-bs-target="#filter-modal">
        <i class="bi bi-funnel"></i> Filter @if($activeFilterCount)<span class="badge bg-primary rounded-pill ms-1">{{ $activeFilterCount }}</span>@endif
    </button>
    @if($hasFilters)<a href="{{ route('brand-health.performances.index') }}" class="btn btn-link text-decoration-none bh-muted">Clear filters</a>@endif
    <div class="ms-auto d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-primary" id="summarize-btn"><i class="bi bi-stars"></i> Summarize with AI</button>
        <a href="{{ route('brand-health.performances.excel', $query) }}" class="btn btn-light-secondary"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        <a href="{{ route('brand-health.performances.pdf-filtered', $query) }}" class="btn btn-light-secondary" id="pdf-link"><i class="bi bi-download"></i> Download PDF</a>
    </div>
</div>

<div class="bh-card mt-3 d-none" id="ai-banner" style="border-color: var(--bh-accent)">
    <div class="d-flex justify-content-between gap-3">
        <div>
            <div class="bh-stat-label" style="color: var(--bh-accent)">AI Summary</div>
            <p class="mb-1 mt-1" style="white-space: pre-line" data-ai-text></p>
            <small class="bh-faint" data-ai-meta></small>
        </div>
        <button type="button" class="btn-close" aria-label="Dismiss summary" id="ai-dismiss"></button>
    </div>
</div>

<div class="bh-card p-0 mt-3" id="performance-table" style="scroll-margin-top: 1rem">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" style="border-color: var(--bh-border) !important">
        <span class="fw-semibold">Posts <span class="bh-muted fw-normal small">· {{ number_format($performances->total()) }} {{ \Illuminate\Support\Str::plural('result', $performances->total()) }}</span></span>
        <button type="button" class="btn btn-sm btn-link bh-muted text-decoration-none" data-bs-toggle="modal" data-bs-target="#scoring-info"><i class="bi bi-info-circle"></i> How scoring works</button>
    </div>
    <div class="table-responsive">
        <table class="bh-table" style="min-width: 1000px">
            <thead><tr>
                <th><a href="{{ $sortUrl('account') }}" class="bh-sort-link">Account {{ $arrow('account') }}</a></th>
                <th>Platform</th><th>Account Category</th>
                <th><a href="{{ $sortUrl('post_date') }}" class="bh-sort-link">Post Date {{ $arrow('post_date') }}</a></th>
                <th>Ads</th><th>PM Name</th><th>Conceptor Name</th>
                <th><a href="{{ $sortUrl('views_h7') }}" class="bh-sort-link">Views H+7 {{ $arrow('views_h7') }}</a></th>
                <th><a href="{{ $sortUrl('views_status') }}" class="bh-sort-link">Performa {{ $arrow('views_status') }}</a></th>
                <th class="text-end">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($rows as $i => $p)
                    <tr class="bh-row-click" data-detail="{{ $i }}">
                        <td class="fw-semibold">{{ $p->account->name ?? '—' }}</td>
                        <td class="bh-muted text-nowrap">{{ ['instagram' => 'Instagram', 'tiktok' => 'TikTok'][$p->platform] ?? '—' }}</td>
                        <td><x-bh.status :status="$p->follower_category" /></td>
                        <td class="bh-muted text-nowrap">{{ $fmtDate($p->post_date) }}</td>
                        <td class="bh-muted">{{ $p->ads === null ? '-' : ($p->ads ? 'Yes' : 'No') }}</td>
                        <td class="bh-muted">{{ $p->projectManager->name ?? '—' }}</td>
                        <td class="bh-muted">{{ $p->conceptor->name ?? '—' }}</td>
                        <td class="bh-num bh-muted">{{ $p->total_views_h7 === null ? '—' : number_format($p->total_views_h7) }}</td>
                        <td><x-bh.status :status="$p->views_status" /></td>
                        <td class="text-end" onclick="event.stopPropagation()">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light-secondary" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="perfDetail.open({{ $i }}); return false;">View</a></li>
                                    @if($canEdit)
                                        <li><a class="dropdown-item" href="#" onclick="perfForm.open({{ $i }}); return false;">Edit</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('brand-health.performances.destroy', $p->id) }}" onsubmit="return confirmDelete(this, 'This will permanently remove the Reels / Video record for {{ addslashes($p->account->name ?? '') }} posted {{ $fmtDate($p->post_date) }}. This cannot be undone.', 'Delete this record?')">
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
                    <tr><td colspan="10" class="bh-empty">{{ $hasFilters ? 'No records match the current filters.' : 'No Reels / Video records yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($performances->lastPage() > 1)
        <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 border-top gap-2" style="border-color: var(--bh-border) !important">
            <small class="bh-muted">Showing <b class="bh-num">{{ $performances->firstItem() }}–{{ $performances->lastItem() }}</b> of <b class="bh-num">{{ $performances->total() }}</b></small>
            {{ $performances->onEachSide(1)->links('brand-health.partials.pager') }}
        </div>
    @endif
</div>
@endsection

@section('bh-modals')
@php $canEdit = auth()->user()->can('edit-brand-health'); @endphp

{{-- Filter --}}
<div class="modal fade" id="filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="GET" action="{{ route('brand-health.performances.index') }}" class="modal-content border-0 shadow" id="filter-form">
            <div class="modal-header"><h5 class="modal-title fw-bold">Filter</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                @foreach(['search', 'sort', 'direction'] as $keep)
                    @if($filters[$keep])<input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">@endif
                @endforeach
                <label class="form-label small fw-semibold bh-muted">Post Date</label>
                <div class="d-flex gap-2 align-items-center mb-3">
                    <input type="date" name="post_date_from" value="{{ $filters['post_date_from'] }}" class="form-control">
                    <span class="bh-faint">–</span>
                    <input type="date" name="post_date_to" value="{{ $filters['post_date_to'] }}" class="form-control">
                </div>
                <label class="form-label small fw-semibold bh-muted">Brand (Account)</label>
                <select name="account_id" class="form-select mb-3">
                    <option value="">All accounts</option>
                    @foreach($accounts as $a)<option value="{{ $a->id }}" @selected($filters['account_id'] == $a->id)>{{ $a->name }}</option>@endforeach
                </select>
                <label class="form-label small fw-semibold bh-muted">Platform</label>
                <select name="platform" class="form-select mb-3" data-no-search>
                    <option value="">All platforms</option>
                    <option value="instagram" @selected($filters['platform'] === 'instagram')>Instagram</option>
                    <option value="tiktok" @selected($filters['platform'] === 'tiktok')>TikTok</option>
                </select>
                <label class="form-label small fw-semibold bh-muted">Project Manager</label>
                <select name="project_manager_id" class="form-select mb-3">
                    <option value="">All project managers</option>
                    @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}" @selected($filters['project_manager_id'] == $e->id)>{{ $e->name }}</option>@endforeach
                </select>
                <label class="form-label small fw-semibold bh-muted">Conceptor</label>
                <select name="conceptor_id" class="form-select mb-3">
                    <option value="">All conceptors</option>
                    @foreach($employees as $e)<option value="{{ $e->id }}" @selected($filters['conceptor_id'] == $e->id)>{{ $e->name }}</option>@endforeach
                </select>
                <div class="mb-3" data-label-group>
                    <label class="form-label small fw-semibold bh-muted d-block">Performa (Status)</label>
                    @foreach($viewsStatusOptions as $label)
                        <label class="bh-label-check"><input type="checkbox" class="form-check-input m-0" value="{{ $label }}" @checked(in_array($label, $selectedStatuses))> <x-bh.status :status="$label" /></label>
                    @endforeach
                    <input type="hidden" name="views_status" value="{{ $filters['views_status'] }}">
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-semibold bh-muted">Ads</label>
                        <select name="ads" class="form-select" data-no-search>
                            <option value="">All</option>
                            <option value="1" @selected($adsValue === '1')>Yes</option>
                            <option value="0" @selected($adsValue === '0')>No</option>
                            <option value="null" @selected($adsValue === 'null')>-</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold bh-muted">Views H+7</label>
                        <select name="views_h7" class="form-select" data-no-search>
                            <option value="">All</option>
                            <option value="not_null" @selected($filters['views_h7'] === 'not_null')>Has value</option>
                            <option value="null" @selected($filters['views_h7'] === 'null')>Empty</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-decoration-none bh-muted" id="filter-reset">Reset</button>
                <div class="d-flex gap-2"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Apply</button></div>
            </div>
        </form>
    </div>
</div>

{{-- Create / edit --}}
@if($canEdit)
<div class="modal fade" id="perf-form-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow" id="perf-form" action="{{ route('brand-health.performances.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="_perf_mode" value="">
            <input type="hidden" name="ig_media_id">
            <input type="hidden" name="ig_media_product_type">
            <input type="hidden" name="remove_proof" value="0">
            <div class="modal-header"><h5 class="modal-title fw-bold" data-pf-title>Add Performance</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
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
                        <select name="platform" class="form-select" data-no-search>
                            <option value="">Select a platform</option><option value="instagram">Instagram</option><option value="tiktok">TikTok</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label small fw-semibold bh-muted">Post Date</label><input type="date" name="post_date" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold bh-muted">Preview Date</label><input type="date" name="preview_date" class="form-control"></div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold bh-muted">Video Links</label>
                        <div data-pf-links></div>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none px-0" data-pf-add-link><i class="bi bi-plus"></i> Add another link</button>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted d-block">Ads</label>
                        <input type="hidden" name="ads" value="">
                        @foreach(['1' => 'Yes', '0' => 'No', '' => '-'] as $val => $label)
                            <label class="form-check form-check-inline"><input type="radio" class="form-check-input" name="_ads_choice" value="{{ $val }}"> {{ $label }}</label>
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted d-block">Note</label>
                        {{-- Sent first so unticking every note clears them (checked boxes below override it). --}}
                        <input type="hidden" name="notes" value="">
                        @foreach($noteOptions as $note)
                            <label class="form-check form-check-inline small"><input type="checkbox" class="form-check-input" name="notes[]" value="{{ $note }}"> {{ $note }}</label>
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Project Manager</label>
                        <select name="project_manager_id" class="form-select">
                            <option value="">Select a project manager</option>
                            @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Conceptor</label>
                        <select name="conceptor_id" class="form-select">
                            <option value="">Select a conceptor</option>
                            @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                        </select>
                    </div>
                    <input type="hidden" name="editor_id" value="">

                    <div class="col-12 d-none" data-pf-ig>
                        <label class="form-label small fw-semibold bh-muted">Instagram Post</label>
                        <div class="d-flex justify-content-between align-items-center gap-2 rounded-3 border p-2 d-none" style="border-color: var(--bh-border) !important; background: var(--bh-raised)" data-pf-ig-linked>
                            <span class="small text-truncate"><i class="bi bi-instagram"></i> <span data-pf-ig-caption></span> <span class="bh-faint" data-pf-ig-type></span></span>
                            <span class="d-flex gap-2"><button type="button" class="btn btn-sm btn-link text-decoration-none p-0" data-pf-ig-change>Change</button><button type="button" class="btn btn-sm btn-link text-decoration-none text-danger p-0" data-pf-ig-unlink>Unlink</button></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary" data-pf-ig-pick><i class="bi bi-link-45deg"></i> Link Instagram post</button>
                        <small class="bh-faint d-block mt-1 d-none" data-pf-ig-pending>Will be linked when you save — metrics fetched automatically.</small>
                        <div class="border rounded-3 p-2 mt-2 d-none" style="border-color: var(--bh-border) !important; max-height: 16rem; overflow-y: auto" data-pf-ig-list></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Followers</label>
                        <input type="hidden" name="followers"><input type="text" inputmode="numeric" class="form-control" data-thousands="followers">
                        <small class="bh-faint d-none" data-pf-followers-date></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold bh-muted">Total Views H+7</label>
                        <input type="hidden" name="total_views_h7"><input type="text" inputmode="numeric" class="form-control" data-thousands="total_views_h7">
                        <small class="bh-faint d-none" data-pf-views-hint>Auto-filled from Instagram (current total, not a day-7 checkpoint).</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold bh-muted">Upload Proof (max 700KB)</label>
                        <div class="d-none mb-2" data-pf-proof-current>
                            <img class="pd-proof" alt="Current proof"><br>
                            <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none px-0" data-pf-proof-remove>Remove current proof</button>
                        </div>
                        <small class="d-none d-block mb-2" style="color: var(--status-parah-ink)" data-pf-proof-removed>Current proof will be removed when you save.</small>
                        <div class="pf-proof-drop" data-pf-drop>
                            <i class="bi bi-cloud-arrow-up fs-4 d-block"></i>
                            <span data-pf-drop-label>Drop an image here or click to choose (PNG, JPG, WEBP)</span>
                            <input type="file" name="proof" accept="image/png,image/jpeg,image/webp" class="d-none">
                        </div>
                        <small class="d-none" style="color: var(--status-parah-ink)" data-pf-proof-error></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" data-pf-submit>Add Performance</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Detail --}}
<div class="modal fade" id="perf-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header align-items-start">
                <div>
                    <h5 class="modal-title fw-bold" data-pd-name></h5>
                    <small class="bh-muted" data-pd-sub></small>
                </div>
                <div class="d-flex gap-1 ms-auto">
                    @if($canEdit)<button type="button" class="btn btn-sm btn-light-secondary" data-pd-edit title="Edit"><i class="bi bi-pencil"></i></button>@endif
                    <a class="btn btn-sm btn-light-secondary" data-pd-pdf title="Download PDF"><i class="bi bi-download"></i></a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body" data-pd-body></div>
        </div>
    </div>
</div>

@include('brand-health.partials.scoring-info-modal', ['metrics' => ['views', 'followers'], 'id' => 'scoring-info'])
@include('brand-health.partials.filter-summary-modal', ['endpoint' => route('brand-health.performances.summarize-filtered'), 'countKey' => 'performance_count', 'countNoun' => 'record'])
@endsection

@section('bh-scripts')
<script>
const PERFS = @json($rows->values());
const P_URLS = {
    store: @json(route('brand-health.performances.store')),
    update: (id) => @json(route('brand-health.performances.update', '__ID__')).replace('__ID__', id),
    pdf: (id) => @json(route('brand-health.performances.pdf', '__ID__')).replace('__ID__', id),
    igLink: (id) => @json(route('brand-health.performances.instagram.link', '__ID__')).replace('__ID__', id),
    igMedia: (id) => @json(route('brand-health.accounts.instagram.media', '__ID__')).replace('__ID__', id),
};
const ACCOUNT_IG = @json($accounts->mapWithKeys(fn ($a) => [$a->id => (bool) $a->ig_connected_at]));
const VIEWS_BUCKETS = @json($viewsBuckets);
const FOLLOWER_BUCKETS = @json($followerBuckets);
const fmtDate = (v) => (v ? new Date(v).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—');

/* ---- chart, search, filters ---- */
(function () {
    const dist = @json($statusDistribution);
    const canvas = document.getElementById('status-chart');
    if (canvas && dist.tiers) {
        const css = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
        const tone = (t) => { const k = { SIP: 'sip', BAGUS: 'bagus', CUKUP: 'cukup', KURANG: 'kurang', PARAH: 'parah' }[String(t).toUpperCase()]; return k ? css(`--status-${k}-ink`) : css('--bh-accent'); };
        BH.chart(canvas, { type: 'bar', data: { labels: dist.tiers.map(String), datasets: [{ label: 'Views H+7 Status', data: dist.tiers.map((t) => dist.counts[t] ?? 0), backgroundColor: dist.tiers.map(tone), borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => `${c.parsed.y} record${c.parsed.y === 1 ? '' : 's'}` } } },
                scales: { x: { grid: { display: false }, title: { display: true, text: 'Views H+7 Status' } }, y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Record Count' } } } } });
    }
    const input = document.querySelector('[data-debounce]');
    let t; input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => input.form.submit(), 400); });
    document.querySelectorAll('[data-detail]').forEach((tr) => tr.addEventListener('click', () => perfDetail.open(+tr.dataset.detail)));

    const ff = document.getElementById('filter-form');
    ff.addEventListener('submit', () => {
        ff.querySelectorAll('[data-label-group]').forEach((g) => { g.querySelector('input[type=hidden]').value = [...g.querySelectorAll('input:checked')].map((c) => c.value).join(','); });
        ff.querySelectorAll('input[name], select[name]').forEach((f) => { if (!f.value) f.disabled = true; });
    });
    document.getElementById('filter-reset').addEventListener('click', () => {
        ff.querySelectorAll('input[type=checkbox]').forEach((c) => c.checked = false);
        ff.querySelectorAll('input[type=date]').forEach((d) => d.value = '');
        ff.querySelectorAll('select').forEach((s) => { s.value = ''; if (s._choices) s._choices.setChoiceByValue(''); });
    });

    // AI summary of the filtered set (PDF export embeds it).
    const banner = document.getElementById('ai-banner'), pdf = document.getElementById('pdf-link');
    document.getElementById('summarize-btn').addEventListener('click', () => {
        const f = @json($filters), parts = [];
        const accounts = @json($accounts->pluck('name', 'id'));
        if (f.account_id) parts.push(`account: ${accounts[f.account_id]}`);
        if (f.search) parts.push(`search: "${f.search}"`);
        if (f.platform) parts.push(`platform: ${f.platform === 'instagram' ? 'Instagram' : 'TikTok'}`);
        if (f.views_status) parts.push(`status: ${f.views_status.split(',').join(', ')}`);
        if (f.ads !== null && f.ads !== undefined) parts.push(`ads: ${f.ads === 'null' ? '-' : f.ads ? 'Yes' : 'No'}`);
        if (f.post_date_from) parts.push(`post date: ${f.post_date_from}${f.post_date_to ? ' to ' + f.post_date_to : '+'}`);
        if (f.views_h7) parts.push(`views H+7: ${f.views_h7 === 'null' ? 'empty' : 'not empty'}`);
        BH.openFilterSummary(@json($query), parts.length ? parts.join(', ') : 'none (all records)', (data) => {
            banner.querySelector('[data-ai-text]').textContent = data.summary;
            banner.querySelector('[data-ai-meta]').textContent = `Based on ${data.performance_count} record${data.performance_count === 1 ? '' : 's'} · ${data.filter_summary}`;
            banner.classList.remove('d-none');
            const u = new URL(pdf.href); u.searchParams.set('ai_summary', data.summary); pdf.href = u;
            bootstrap.Modal.getInstance(document.getElementById('filter-summary-modal'))?.hide();
        });
    });
    document.getElementById('ai-dismiss').addEventListener('click', () => { banner.classList.add('d-none'); const u = new URL(pdf.href); u.searchParams.delete('ai_summary'); pdf.href = u; });
})();

/* ---- detail ---- */
const perfDetail = (function () {
    const el = document.getElementById('perf-detail-modal');
    const body = el.querySelector('[data-pd-body]');
    let perf = null;
    const chip = (lbl, val, extra = '') => `<div class="col-6 col-md-3"><div class="pd-chip"><div class="lbl">${lbl}</div><div class="val">${val}</div>${extra}</div></div>`;
    const bucketTip = (buckets) => buckets.map((b) => `≥ ${Number(b.min_score ?? 0).toLocaleString('en-US')} = ${b.label}`).join('\n');
    const igFields = [['reach', 'Reach'], ['likes', 'Likes'], ['comments', 'Comments'], ['shares', 'Shares'], ['saved', 'Saved'], ['total_interactions', 'Total Interactions'], ['views', 'Views'], ['ig_reels_avg_watch_time', 'Avg Watch Time'], ['ig_reels_video_view_total_time', 'Total Watch Time']];
    const scripts = {};
    const loadScript = (src) => scripts[src] ??= new Promise((res, rej) => { const s = document.createElement('script'); s.src = src; s.async = true; s.onload = res; s.onerror = rej; document.body.appendChild(s); });

    function render() {
        el.querySelector('[data-pd-name]').textContent = perf.account?.name ?? '—';
        el.querySelector('[data-pd-sub]').textContent = `Posted ${fmtDate(perf.post_date)}${perf.platform ? ' · ' + ({ instagram: 'Instagram', tiktok: 'TikTok' }[perf.platform] ?? perf.platform) : ''}`;
        el.querySelector('[data-pd-pdf]').href = P_URLS.pdf(perf.id);
        const snap = perf.ig_snapshot;
        const snapFields = snap ? igFields.filter(([k]) => snap[k] !== null && snap[k] !== undefined) : [];
        body.innerHTML = `
            <div class="bh-stat-label mb-2">Details</div>
            <div class="row g-2">
                ${chip('Preview Date', fmtDate(perf.preview_date))}
                ${chip('Ads', perf.ads === null ? '-' : perf.ads ? 'Yes' : 'No')}
                ${chip('Cycle', perf.cycle ? `${fmtDate(perf.cycle.cycle_start_date)} – ${fmtDate(perf.cycle.cycle_end_date)}` : '<span class="bh-faint">No matching cycle</span>')}
                ${chip('Performa', BH.statusBadge(perf.views_status))}
                ${perf.notes?.length ? `<div class="col-12"><div class="pd-chip"><div class="lbl">Notes</div><div class="d-flex flex-wrap gap-1 mt-1">${perf.notes.map((n) => `<span class="bh-pill">${BH.esc(n)}</span>`).join('')}</div></div></div>` : ''}
            </div>
            <div class="bh-stat-label mt-4 mb-2">Performance</div>
            <div class="row g-2">
                ${chip('Followers', BH.num(perf.followers), perf.followers_captured_date ? `<div class="bh-faint" style="font-size:.65rem">From ${fmtDate(perf.followers_captured_date)}</div>` : '')}
                ${chip('Account Category', `<span title="${BH.esc(bucketTip(FOLLOWER_BUCKETS))}">${BH.statusBadge(perf.follower_category)}</span>`)}
                ${chip('Total Views H+7', BH.num(perf.total_views_h7))}
                ${chip('Views Status', `<span title="${BH.esc(bucketTip(VIEWS_BUCKETS))}">${BH.statusBadge(perf.views_status)}</span>`)}
            </div>
            ${snapFields.length ? `<div class="bh-stat-label mt-4 mb-2">Instagram Insights <span class="text-lowercase fw-normal">— as of ${new Date(snap.fetched_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })}</span></div>
                <div class="row g-2">${snapFields.map(([k, l]) => chip(l, BH.num(snap[k]))).join('')}</div>` : ''}
            <div class="bh-stat-label mt-4 mb-2">Crew</div>
            <div class="row g-2">${chip('Project Manager', BH.esc(perf.project_manager?.name ?? '—'))}${chip('Conceptor', BH.esc(perf.conceptor?.name ?? '—'))}</div>
            <div class="bh-stat-label mt-4 mb-2">Video Links</div>
            ${(perf.video_links ?? []).length ? (perf.video_links).map((link, i) => `<div class="pd-chip mb-2" data-link="${i}">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <a href="${BH.esc(link.url)}" target="_blank" rel="noopener" class="text-truncate small"><i class="bi ${link.platform === 'tiktok' ? 'bi-tiktok' : link.platform === 'instagram' ? 'bi-instagram' : 'bi-link-45deg'}"></i> ${BH.esc(link.url)}</a>
                    ${link.embed_html ? `<button type="button" class="btn btn-sm btn-light-primary text-nowrap" data-embed="${i}">View live post</button>` : ''}
                </div><div data-embed-box="${i}"></div></div>`).join('') : '<p class="bh-faint small">No video links.</p>'}
            <div class="bh-stat-label mt-4 mb-2">Upload Proof</div>
            ${perf.proof_path ? `<a href="/storage/${BH.esc(perf.proof_path)}" target="_blank" rel="noopener"><img src="/storage/${BH.esc(perf.proof_path)}" class="pd-proof" alt="Proof"></a>` : '<p class="bh-faint small">No proof uploaded.</p>'}`;
    }

    // Live embeds load only on request (keeps Instagram/TikTok embed traffic low).
    body.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-embed]');
        if (!btn) return;
        const link = perf.video_links[+btn.dataset.embed];
        const box = body.querySelector(`[data-embed-box="${btn.dataset.embed}"]`);
        box.innerHTML = `<div class="mt-2">${link.embed_html}</div>`;
        btn.remove();
        try {
            if (link.platform === 'instagram') { await loadScript('https://www.instagram.com/embed.js'); window.instgrm?.Embeds?.process(); }
            else if (link.platform === 'tiktok') { await loadScript('https://www.tiktok.com/embed.js'); }
        } catch { box.innerHTML += '<small class="bh-faint">Could not load the live embed — open the link instead.</small>'; }
    });
    el.querySelector('[data-pd-edit]')?.addEventListener('click', () => { bootstrap.Modal.getInstance(el).hide(); perfForm.open(PERFS.indexOf(perf)); });

    return { open(i) { perf = PERFS[i]; render(); bootstrap.Modal.getOrCreateInstance(el).show(); } };
})();

/* ---- create / edit ---- */
const perfForm = (function () {
    const modal = document.getElementById('perf-form-modal');
    if (!modal) return { open() {} };
    const form = document.getElementById('perf-form');
    const q = (s) => form.querySelector(s);
    const f = (n) => form.querySelector(`[name="${n}"]`);
    let editing = null, media = [], linked = null;

    const setSelect = (name, v) => { const s = f(name); s.value = v ?? ''; if (s._choices) s._choices.setChoiceByValue(String(v ?? '')); };
    const thousands = (v) => (v === '' || v === null || v === undefined ? '' : Number(v).toLocaleString('en-US'));
    const setThousands = (name, v) => { f(name).value = v ?? ''; q(`[data-thousands="${name}"]`).value = thousands(v); };
    form.querySelectorAll('[data-thousands]').forEach((inp) => inp.addEventListener('input', () => {
        const d = inp.value.replace(/[^\d]/g, ''); f(inp.dataset.thousands).value = d; inp.value = thousands(d === '' ? '' : Number(d));
    }));

    // Video link rows
    const links = q('[data-pf-links]');
    const addLink = (url = '') => {
        const row = document.createElement('div');
        row.className = 'd-flex gap-2 mb-2';
        row.innerHTML = `<input type="url" name="video_links[]" class="form-control" placeholder="https://instagram.com/... or https://tiktok.com/..." maxlength="2048"><button type="button" class="btn btn-light-secondary" aria-label="Remove link"><i class="bi bi-x"></i></button>`;
        row.querySelector('input').value = url;
        row.querySelector('button').addEventListener('click', () => { row.remove(); if (!links.children.length) addLink(); });
        links.appendChild(row);
    };
    q('[data-pf-add-link]').addEventListener('click', () => addLink());

    // Ads tri-state radio -> hidden field ("" means unknown)
    form.querySelectorAll('[name=_ads_choice]').forEach((r) => r.addEventListener('change', () => f('ads').value = r.value));

    // Proof upload (client-side type/size check mirrors the server's 700KB rule)
    const drop = q('[data-pf-drop]'), fileInput = f('proof'), err = q('[data-pf-proof-error]');
    drop.addEventListener('click', () => fileInput.click());
    ['dragover', 'dragenter'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove('drag')));
    drop.addEventListener('drop', (e) => { e.preventDefault(); if (e.dataTransfer.files[0]) { const dt = new DataTransfer(); dt.items.add(e.dataTransfer.files[0]); fileInput.files = dt.files; fileInput.dispatchEvent(new Event('change')); } });
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        err.classList.add('d-none');
        if (!file) { q('[data-pf-drop-label]').textContent = 'Drop an image here or click to choose (PNG, JPG, WEBP)'; return; }
        let msg = null;
        if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) msg = 'Only PNG, JPG, or WEBP images are allowed.';
        else if (file.size > 700 * 1024) msg = 'File exceeds 700KB — please choose a smaller image.';
        if (msg) { err.textContent = msg; err.classList.remove('d-none'); fileInput.value = ''; return; }
        f('remove_proof').value = '0';
        q('[data-pf-drop-label]').textContent = file.name;
    });
    q('[data-pf-proof-remove]').addEventListener('click', () => { f('remove_proof').value = '1'; q('[data-pf-proof-current]').classList.add('d-none'); q('[data-pf-proof-removed]').classList.remove('d-none'); });

    // Instagram post linking
    const igBox = q('[data-pf-ig]'), igList = q('[data-pf-ig-list]');
    function syncIg() {
        const connected = !!ACCOUNT_IG[f('account_id').value];
        igBox.classList.toggle('d-none', !connected);
        q('[data-pf-ig-linked]').classList.toggle('d-none', !linked);
        q('[data-pf-ig-pick]').classList.toggle('d-none', !!linked);
        q('[data-pf-ig-pending]').classList.toggle('d-none', !(linked && !editing));
        q('[data-pf-views-hint]').classList.toggle('d-none', !linked);
        if (linked) { q('[data-pf-ig-caption]').textContent = media.find((m) => m.id === linked.id)?.caption ?? linked.id; q('[data-pf-ig-type]').textContent = `(${linked.type ?? ''})`; }
    }
    async function pick() {
        igList.classList.remove('d-none');
        if (!media.length) {
            igList.innerHTML = '<div class="bh-faint small p-2">Loading posts…</div>';
            try { media = (await BH.request(P_URLS.igMedia(f('account_id').value))).media ?? []; }
            catch (e) { igList.innerHTML = `<div class="small p-2" style="color: var(--status-parah-ink)">${BH.esc(e.message)}</div>`; return; }
        }
        igList.innerHTML = media.length ? media.map((m, i) => `<button type="button" class="ig-pick mb-1" data-media="${i}"><b>${BH.esc(m.media_product_type)}</b> <span class="bh-faint">${BH.esc(m.timestamp)}</span><div class="text-truncate bh-muted">${BH.esc(m.caption ?? '—')}</div></button>`).join('') : '<div class="bh-faint small p-2">No posts found.</div>';
    }
    igList.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-media]'); if (!b) return;
        const m = media[+b.dataset.media];
        if (!editing) {
            linked = { id: m.id, type: m.media_product_type };
            f('ig_media_id').value = m.id; f('ig_media_product_type').value = m.media_product_type;
            if (!f('post_date').value && m.timestamp) f('post_date').value = m.timestamp.slice(0, 10);
        } else {
            try {
                const r = await BH.request(P_URLS.igLink(editing.id), { method: 'POST', body: { ig_media_id: m.id, ig_media_product_type: m.media_product_type } });
                linked = { id: m.id, type: m.media_product_type };
                if (r.followers != null) setThousands('followers', r.followers);
                const fd = q('[data-pf-followers-date]'); fd.textContent = r.followers_captured_date ? `From ${r.followers_captured_date} snapshot.` : ''; fd.classList.toggle('d-none', !r.followers_captured_date);
                window.showToast?.('Linked to Instagram post — metrics will refresh automatically.');
            } catch (err2) { window.showToast?.(err2.message, 'danger'); return; }
        }
        igList.classList.add('d-none'); syncIg();
    });
    q('[data-pf-ig-pick]').addEventListener('click', pick);
    q('[data-pf-ig-change]').addEventListener('click', pick);
    q('[data-pf-ig-unlink]').addEventListener('click', async () => {
        if (editing) {
            try { await BH.request(P_URLS.igLink(editing.id), { method: 'DELETE' }); window.showToast?.('Unlinked from Instagram post.'); }
            catch (e) { window.showToast?.(e.message, 'danger'); return; }
        }
        linked = null; f('ig_media_id').value = ''; f('ig_media_product_type').value = '';
        q('[data-pf-followers-date]').classList.add('d-none'); syncIg();
    });
    f('account_id').addEventListener('change', () => { media = []; igList.classList.add('d-none'); syncIg(); });

    function load(p) {
        editing = p; media = []; linked = p?.ig_media_id ? { id: p.ig_media_id, type: p.ig_media_product_type } : null;
        form.action = p ? P_URLS.update(p.id) : P_URLS.store;
        f('_method').value = p ? 'PUT' : 'POST';
        f('_perf_mode').value = p ? 'edit:' + p.id : 'create';
        q('[data-pf-title]').textContent = p ? 'Edit Performance' : 'Add Performance';
        q('[data-pf-submit]').textContent = p ? 'Save Changes' : 'Add Performance';
        setSelect('account_id', p?.account_id); setSelect('platform', p?.platform);
        setSelect('project_manager_id', p?.project_manager_id); setSelect('conceptor_id', p?.conceptor_id);
        f('editor_id').value = p?.editor_id ?? '';
        f('post_date').value = p?.post_date ? String(p.post_date).slice(0, 10) : '';
        f('preview_date').value = p?.preview_date ? String(p.preview_date).slice(0, 10) : '';
        links.innerHTML = ''; (p?.video_links?.length ? p.video_links.map((l) => l.url) : ['']).forEach(addLink);
        const ads = p ? (p.ads === null ? '' : p.ads ? '1' : '0') : '';
        f('ads').value = ads; form.querySelectorAll('[name=_ads_choice]').forEach((r) => r.checked = r.value === ads);
        form.querySelectorAll('[name="notes[]"]').forEach((c) => c.checked = (p?.notes ?? []).includes(c.value));
        setThousands('followers', p?.followers ?? ''); setThousands('total_views_h7', p?.total_views_h7 ?? '');
        const fd = q('[data-pf-followers-date]'); fd.textContent = p?.followers_captured_date && linked ? `From ${String(p.followers_captured_date).slice(0, 10)} snapshot.` : ''; fd.classList.toggle('d-none', !fd.textContent);
        f('ig_media_id').value = p?.ig_media_id ?? ''; f('ig_media_product_type').value = p?.ig_media_product_type ?? '';
        f('remove_proof').value = '0'; fileInput.value = ''; err.classList.add('d-none');
        q('[data-pf-drop-label]').textContent = 'Drop an image here or click to choose (PNG, JPG, WEBP)';
        q('[data-pf-proof-removed]').classList.add('d-none');
        const cur = q('[data-pf-proof-current]');
        cur.classList.toggle('d-none', !p?.proof_path);
        if (p?.proof_path) cur.querySelector('img').src = '/storage/' + p.proof_path;
        igList.classList.add('d-none');
        syncIg();
    }

    // On create, the IG link fields are only meaningful when a post was picked.
    form.addEventListener('submit', () => { if (!f('ig_media_id').value) { f('ig_media_id').disabled = true; f('ig_media_product_type').disabled = true; } });

    const old = @json(old());
    if (old._perf_mode) {
        const id = old._perf_mode.startsWith('edit:') ? +old._perf_mode.slice(5) : null;
        load(id ? PERFS.find((p) => p.id === id) ?? null : null);
        ['post_date', 'preview_date'].forEach((k) => { if (old[k]) f(k).value = old[k]; });
        ['account_id', 'platform', 'project_manager_id', 'conceptor_id'].forEach((k) => { if (old[k] !== undefined) setSelect(k, old[k]); });
        if (old.followers !== undefined) setThousands('followers', old.followers);
        if (old.total_views_h7 !== undefined) setThousands('total_views_h7', old.total_views_h7);
        if (Array.isArray(old.video_links)) { links.innerHTML = ''; old.video_links.forEach((u) => addLink(u ?? '')); }
        form.querySelectorAll('[name="notes[]"]').forEach((c) => c.checked = (old.notes ?? []).includes(c.value));
        syncIg();
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    return { open(i) { load(i === undefined ? null : PERFS[i]); bootstrap.Modal.getOrCreateInstance(modal).show(); } };
})();
</script>
@endsection
