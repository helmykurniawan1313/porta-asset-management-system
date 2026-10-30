@extends('brand-health.layout')

@section('title', 'Scoring Buckets | Brand Health')

@section('bh-styles')
<style>
    .sb-range { position: relative; height: 10px; border-radius: 999px; background: var(--bh-border); overflow: hidden; }
    .sb-range > span { position: absolute; top: 0; bottom: 0; }
    .sb-swatch { display: inline-block; width: 14px; height: 14px; border-radius: 4px; vertical-align: middle; border: 1px solid rgba(0,0,0,.08); }
    .sb-table td, .sb-table th { vertical-align: middle; }
    .sb-table input.form-control, .sb-table select.form-select { min-width: 5.5rem; }
    .sb-cell { display: block; width: 100%; border: 1px solid var(--bh-border); border-radius: 10px; padding: .5rem .6rem; background: var(--bh-surface); text-align: left; color: var(--bh-ink); }
    .sb-cell:hover { border-color: var(--bh-border-strong); }
    .sb-cell[aria-expanded="true"] { border-color: var(--bh-accent); background: var(--bh-accent-soft); }
    .sb-example { font-size: .8rem; color: var(--bh-ink-muted); background: var(--bh-accent-soft); border-radius: 8px; padding: .4rem .7rem; display: inline-block; }
    .sb-dirty { box-shadow: inset 3px 0 0 var(--status-cukup-ink); }
    .sb-weight-bar { height: 8px; border-radius: 999px; overflow: hidden; display: flex; background: var(--bh-border); }
</style>
@endsection

@section('bh-content')
@php
    $canEdit = auth()->user()->can('edit-brand-health');
    $tones = ['sip', 'bagus', 'cukup', 'kurang', 'parah'];
    $toneFor = function ($b, int $rank) use ($tones) {
        if (($b['color'] ?? null) === 'custom' && ($b['custom_color_bg'] ?? null) && ($b['custom_color_ink'] ?? null)) return [$b['custom_color_bg'], $b['custom_color_ink']];
        $t = in_array($b['color'] ?? null, $tones, true) ? $b['color'] : $tones[min($rank, 4)];
        return ["var(--status-{$t}-bg)", "var(--status-{$t}-ink)"];
    };
    // Tiers come sorted high → low; bars are drawn low → high on a 0..(max × 1.25) axis.
    $segments = function ($tiers, string $minKey) use ($toneFor) {
        $tiers = collect($tiers)->values();
        $finite = $tiers->pluck($minKey)->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
        $axis = $finite->isNotEmpty() ? max($finite->max() * 1.25, 1) : 1;
        $asc = $tiers->reverse()->values();
        return $asc->map(function ($t, $i) use ($asc, $axis, $minKey, $toneFor, $tiers) {
            $min = $t[$minKey] === null ? 0 : (float) $t[$minKey];
            $next = $asc[$i + 1] ?? null;
            $max = $next ? ($next[$minKey] === null ? $axis : (float) $next[$minKey]) : $axis;
            return ['start' => max(0, $min / $axis * 100), 'width' => max(($max - $min) / $axis * 100, .5), 'tone' => $toneFor($t, $tiers->count() - 1 - $i)];
        });
    };
    $resolve = function ($tiers, string $minKey, string $valKey, $value) {
        $winner = null; $wMin = -INF;
        foreach ($tiers as $t) { $min = $t[$minKey] === null ? -INF : (float) $t[$minKey]; if ($min > $value) continue; if ($winner === null || $min > $wMin) { $winner = $t; $wMin = $min; } }
        return $winner[$valKey] ?? null;
    };
    $fmt = fn ($v) => $v === null ? '0' : ((float) $v >= 1000 ? \Illuminate\Support\Number::abbreviate((float) $v, maxPrecision: 1) : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'));
    $plain = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');

    $metricTitles = ['growth' => 'Growth Rate', 'reach' => 'Reach Rate (Instagram)', 'view' => 'View Rate', 'er_reach' => 'Engagement Rate of Reach (Instagram)', 'er_follower' => 'Engagement Rate of Followers',
        'profile' => 'Profile Rate (TikTok)', 'er_view' => 'Engagement Rate of Views (TikTok)', 'visibility' => 'Visibility', 'engagement' => 'Engagement', 'health' => 'Health', 'views' => 'Views (H+7) Status',
        'followers' => 'Follower Category', 'account_growth' => 'Accounts Table — Growth Rate', 'views_change' => 'Growth Analysis — Views Change', 'reach_change' => 'Growth Analysis — Reach Change',
        'engagement_change' => 'Growth Analysis — Engagement Change', 'visibility_tiktok' => 'Visibility (TikTok)', 'engagement_tiktok' => 'Engagement (TikTok)', 'account_tier' => 'Account Size Tier'];
    $metricHints = ['growth' => '(end − start) / start × 100', 'reach' => 'reach / end followers × 100 — Instagram only, feeds Visibility', 'view' => 'views / end followers × 100',
        'er_reach' => 'engagement / reach × 100 — Instagram only, feeds Engagement', 'er_follower' => 'engagement / end followers × 100 — display only, not scored',
        'profile' => 'profile_views / views × 1000 — TikTok\'s "reach" field is actually profile views; feeds Visibility in place of Reach Rate',
        'er_view' => 'engagement / views × 100 — feeds Engagement for TikTok in place of ER-Reach/ER-Follower',
        'visibility' => 'Instagram: weighted average of Reach Rate + View Rate — see Formula Weights tab', 'engagement' => 'Instagram: weighted average of ER-Reach + ER-Follower scores — see Formula Weights tab',
        'health' => 'weighted average of growth score + visibility rate + engagement score — see Formula Weights tab for current weights',
        'visibility_tiktok' => 'TikTok: weighted average of Profile Rate + View Rate', 'engagement_tiktok' => 'TikTok: ER-View score',
        'views' => 'raw Views H+7 count, from Performance records', 'followers' => 'raw follower count, from Performance records',
        'account_growth' => 'latest cycle\'s growth rate — shown on the Accounts table and Growth modal', 'views_change' => '% change in Views between the two picked months, Growth Analysis panel',
        'reach_change' => '% change in Reach between the two picked months, Growth Analysis panel', 'engagement_change' => '% change in Engagement between the two picked months, Growth Analysis panel',
        'account_tier' => 'resolved from a cycle\'s start_follower — decides which Score Tiers threshold set a cycle is scored against. Renaming a tier also retags its Score Tiers rows.'];
    $componentLabels = ['reach_score' => 'Reach Score', 'view_score' => 'View Score', 'er_reach_score' => 'ER-Reach Score', 'er_follower_score' => 'ER-Follower Score', 'profile_score' => 'Profile Score',
        'er_view_score' => 'ER-View Score', 'growth_score' => 'Growth Score', 'visibility_rate' => 'Visibility Rate', 'engagement_score' => 'Engagement Score'];
    $feedsInto = ['growth' => 'Health', 'reach' => 'Visibility', 'view' => 'Visibility', 'er_reach' => 'Engagement', 'er_follower' => 'Engagement', 'profile' => 'Visibility (TikTok)', 'er_view' => 'Engagement (TikTok)'];
    $exampleRate = ['growth' => 7, 'reach' => 120, 'view' => 45, 'er_reach' => 5, 'er_follower' => 8, 'profile' => 11, 'er_view' => 2];
    $exampleScore = ['visibility' => 82, 'engagement' => 70, 'health' => 75, 'growth' => 65, 'views' => 50000, 'followers' => 10000, 'account_growth' => 7, 'views_change' => 15, 'reach_change' => 10, 'engagement_change' => 12, 'account_tier' => 8000];
    $tabs = [
        'growth' => ['Growth', 'Growth rate → score → label', ['growth'], ['growth']],
        'visibility' => ['Visibility', 'Reach/View/Profile → Visibility', ['reach', 'view', 'profile'], ['visibility']],
        'engagement' => ['Engagement', 'ER-Reach/ER-Follower/ER-View → Engagement', ['er_reach', 'er_follower', 'er_view'], ['engagement']],
        'health' => ['Health', 'Terminal aggregate label', [], ['health']],
        'other' => ['Other', 'Standalone label-only metrics', [], ['account_growth', 'views', 'followers', 'views_change', 'reach_change', 'engagement_change', 'account_tier']],
        'weights' => ['Formula Weights', 'How aggregates combine', [], []],
    ];
    $platformLabels = ['instagram' => 'Instagram', 'tiktok' => 'TikTok'];
    $tierOrder = $accountTiers->values()->all();
    $tierIdx = fn ($t) => ($i = array_search($t, $tierOrder, true)) === false ? count($tierOrder) : $i;
    $score = $scoreBuckets->map->toArray();
    $labels = $labelBuckets->map->toArray();
    $scoreFor = fn ($m) => $score->where('metric', $m)->sortByDesc(fn ($b) => $b['min_rate'] === null ? -INF : (float) $b['min_rate'])->values();
    $labelFor = fn ($m) => $labels->where('metric', $m)->sortByDesc(fn ($b) => $b['min_score'] === null ? -INF : (float) $b['min_score'])->values();
    $groupsFor = fn ($m) => $scoreFor($m)->groupBy(fn ($b) => ($b['platform'] ?? '').':'.($b['tier'] ?? ''))
        ->map(fn ($bs, $k) => ['key' => $k, 'platform' => $bs[0]['platform'], 'tier' => $bs[0]['tier'], 'buckets' => $bs->values(),
            'label' => $bs[0]['tier'] ? ($platformLabels[$bs[0]['platform']] ?? 'All platforms').' — '.$bs[0]['tier'] : ($platformLabels[$bs[0]['platform']] ?? 'Global')])
        ->sortBy(fn ($g) => sprintf('%s-%02d', $g['platform'] ?? '', $tierIdx($g['tier'])))->values();
    $dis = $canEdit ? '' : 'disabled';
@endphp

<div class="bh-page-head">
    <div>
        <h3>Scoring Buckets</h3>
        <p>Thresholds that turn raw rates into scores, scores into labels, and how aggregates are weighted. Saving a tier does not rescore existing cycles — use Recalculate.</p>
    </div>
    @if($canEdit)
        <form method="POST" action="{{ route('brand-health.score-buckets.recalculate') }}" data-keep-place onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span class=\'spinner-border spinner-border-sm\'></span> Recalculating…'">
            @csrf
            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Recalculate all cycles</button>
        </form>
    @endif
</div>

@if($errors->any())
    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle"></i> {{ $errors->first() }}</div>
@endif

<ul class="nav nav-pills flex-wrap gap-1 mb-3" id="sb-tabs" role="tablist">
    @foreach($tabs as $key => [$label, $hint])
        <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#sb-{{ $key }}" type="button" title="{{ $hint }}">{{ $label }}</button></li>
    @endforeach
</ul>

<div class="tab-content">
@foreach($tabs as $key => [$label, $hint, $tabScore, $tabLabel])
    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="sb-{{ $key }}">
        <p class="bh-muted small mb-3">{{ $hint }}</p>

        {{-- ===== Score tiers: rate → score, per platform × account size ===== --}}
        @foreach(array_intersect($tabScore, $scoreMetrics) as $metric)
            @php
                $groups = $groupsFor($metric);
                $platforms = $groups->pluck('platform')->filter()->unique()->sort()->values();
                $tiers = $groups->pluck('tier')->filter()->unique()->sortBy($tierIdx)->values();
                $global = $groups->first(fn ($g) => ! $g['platform'] && ! $g['tier']);
                $find = fn ($p, $t) => $groups->first(fn ($g) => $g['platform'] === $p && $g['tier'] === $t);
                $cellId = fn ($g) => 'sbc-'.$metric.'-'.\Illuminate\Support\Str::slug($g['key'] ?: 'global');
                $exampleGroup = $groups->first(fn ($g) => $g['platform'] === 'instagram' && $g['tier']) ?? $global ?? $groups->first();
                $ex = $exampleGroup ? $resolve($exampleGroup['buckets'], 'min_rate', 'score', $exampleRate[$metric] ?? 10) : null;
            @endphp
            <div class="bh-card mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">{{ $metricTitles[$metric] ?? $metric }} <span class="bh-pill ms-1">Score tiers</span></h6>
                        <small class="bh-faint">{{ $metricHints[$metric] ?? '' }}</small>
                    </div>
                    @if($canEdit)<button class="btn btn-sm btn-light-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#sbg-new-{{ $metric }}"><i class="bi bi-plus-lg"></i> New group</button>@endif
                </div>
                @if($ex !== null)
                    <div class="sb-example mt-2">Example ({{ $exampleGroup['label'] }}): a {{ $exampleRate[$metric] ?? 10 }}{{ in_array($metric, ['growth', 'er_reach', 'er_follower', 'view', 'reach']) ? '%' : '' }} {{ strtolower($metricTitles[$metric]) }} → scores {{ $fmt($ex) }} → feeds into {{ $feedsInto[$metric] ?? '—' }}.</div>
                @endif

                @if($canEdit)
                    <div class="collapse" id="sbg-new-{{ $metric }}">
                        <form method="POST" action="{{ route('brand-health.score-buckets.store') }}" class="d-flex flex-wrap align-items-end gap-2 border rounded-3 p-2 mt-3" style="border-color: var(--bh-border) !important" data-keep-place>
                            @csrf
                            <input type="hidden" name="metric" value="{{ $metric }}"><input type="hidden" name="min_rate" value=""><input type="hidden" name="score" value="0">
                            <div><label class="form-label small bh-muted mb-1">Platform</label><select name="platform" class="form-select form-select-sm" data-no-search><option value="">All platforms</option><option value="instagram">Instagram</option><option value="tiktok">TikTok</option></select></div>
                            <div><label class="form-label small bh-muted mb-1">Account tier</label><select name="tier" class="form-select form-select-sm" data-no-search><option value="">All tiers</option>@foreach($accountTiers as $t)<option>{{ $t }}</option>@endforeach</select></div>
                            <button class="btn btn-sm btn-primary">Create group</button>
                            <small class="bh-faint w-100">Starts with a floor tier (no minimum → 0); add the remaining tiers in the group's editor.</small>
                        </form>
                    </div>
                @endif

                <div class="table-responsive mt-3">
                    <table class="table table-borderless align-middle mb-0" style="min-width: 28rem">
                        @if($platforms->isNotEmpty() && $tiers->isNotEmpty())
                            <thead><tr><th class="small bh-faint fw-semibold" style="width: 8rem">Tier</th>@foreach($platforms as $p)<th class="small bh-faint fw-semibold">{{ $platformLabels[$p] ?? $p }}</th>@endforeach</tr></thead>
                        @endif
                        <tbody>
                            @foreach($tiers as $t)
                                <tr>
                                    <td class="fw-semibold small">{{ $t }}</td>
                                    @foreach($platforms as $p)
                                        @php $g = $find($p, $t); @endphp
                                        <td>@if($g)@include('brand-health.settings._bucket-cell', ['g' => $g, 'target' => $cellId($g), 'metric' => $metric])@else<span class="bh-faint small">—</span>@endif</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            @foreach($groups->filter(fn ($g) => ! $g['tier']) as $g)
                                <tr><td class="fw-semibold small">{{ $g['label'] }}</td><td colspan="{{ max(1, $platforms->count()) }}">@include('brand-health.settings._bucket-cell', ['g' => $g, 'target' => $cellId($g), 'metric' => $metric])</td></tr>
                            @endforeach
                            @foreach($groups->filter(fn ($g) => $g['tier'] && ! $g['platform']) as $g)
                                <tr><td class="fw-semibold small">{{ $g['label'] }}</td><td colspan="{{ max(1, $platforms->count()) }}">@include('brand-health.settings._bucket-cell', ['g' => $g, 'target' => $cellId($g), 'metric' => $metric])</td></tr>
                            @endforeach
                            @if($groups->isEmpty())<tr><td class="bh-empty">No score tiers yet.</td></tr>@endif
                        </tbody>
                    </table>
                </div>

                <div id="sbm-{{ $metric }}">
                {{-- Editors are built on first open from this data (see scripts): rendering every group's
                     editor up front made the page ~900KB. --}}
                @foreach($groups as $g)
                    <div class="collapse" id="{{ $cellId($g) }}" data-bs-parent="#sbm-{{ $metric }}"
                         data-sb-editor="{{ json_encode([
                             'title' => $metricTitles[$metric].' — '.$g['label'],
                             'metric' => $metric, 'platform' => $g['platform'], 'tier' => $g['tier'], 'cell' => $cellId($g),
                             'buckets' => collect($g['buckets'])->values()->map(fn ($b, $rank) => [
                                 'id' => $b['id'], 'min_rate' => $plain($b['min_rate']), 'score' => $plain($b['score']),
                                 'color' => $b['color'] ?? '', 'bg' => $b['custom_color_bg'] ?? '#e8f5e9', 'ink' => $b['custom_color_ink'] ?? '#1b5e20',
                                 'auto' => $tones[min($rank, 4)],
                             ]),
                         ]) }}"></div>
                @endforeach
                </div>
            </div>
        @endforeach

        {{-- ===== Label tiers: score → label ===== --}}
        @foreach(array_intersect($tabLabel, $labelMetrics) as $metric)
            @php
                $rows = $labelFor($metric);
                $lx = $rows->isNotEmpty() ? $resolve($rows, 'min_score', 'label', $exampleScore[$metric] ?? 50) : null;
                $noun = $metric === 'account_tier' ? 'start_follower' : (in_array($metric, ['views', 'followers']) ? 'value' : 'score');
            @endphp
            <div class="bh-card mb-3">
                <h6 class="fw-bold mb-0">{{ $metricTitles[$metric] ?? $metric }} <span class="bh-pill ms-1">{{ $metric === 'account_tier' ? 'Size tiers' : 'Labels' }}</span></h6>
                <small class="bh-faint">{{ $metricHints[$metric] ?? '' }}</small>
                @if($rows->isNotEmpty())
                    <div class="sb-range mt-3">@foreach($segments($rows, 'min_score') as $s)<span style="left: {{ $s['start'] }}%; width: {{ $s['width'] }}%; background: {{ $s['tone'][1] }}"></span>@endforeach</div>
                @endif
                @if($lx !== null)<div class="sb-example mt-2">Example: a {{ $noun }} of {{ number_format($exampleScore[$metric] ?? 50) }} → labeled {{ $lx }}.</div>@endif
                <div class="table-responsive mt-2">
                    <table class="table table-sm sb-table mb-0">
                        <thead><tr><th class="small bh-faint">Min {{ $noun }} (≥)</th><th class="small bh-faint">Label</th>@if($metric !== 'account_tier')<th class="small bh-faint">Color</th>@endif<th></th></tr></thead>
                        <tbody>
                            @foreach($rows as $rank => $b)
                                <tr data-sb-row>
                                    <td>
                                        <form method="POST" action="{{ route('brand-health.label-buckets.update', $b['id']) }}" id="lbf-{{ $b['id'] }}" data-keep-place>@csrf @method('PUT')</form>
                                        <input type="number" step="any" name="min_score" form="lbf-{{ $b['id'] }}" class="form-control form-control-sm" value="{{ $plain($b['min_score']) }}" placeholder="No minimum" {{ $dis }}>
                                    </td>
                                    <td><input type="text" name="label" maxlength="50" form="lbf-{{ $b['id'] }}" class="form-control form-control-sm" value="{{ $b['label'] }}" required {{ $dis }}></td>
                                    @if($metric !== 'account_tier')<td>@include('brand-health.settings._color-picker', ['b' => $b, 'formId' => 'lbf-'.$b['id'], 'rank' => $rank])</td>@endif
                                    <td class="text-end text-nowrap">
                                        @if($canEdit)
                                            <button type="submit" form="lbf-{{ $b['id'] }}" class="btn btn-sm btn-primary">Save</button>
                                            <form method="POST" action="{{ route('brand-health.label-buckets.destroy', $b['id']) }}" class="d-inline" data-keep-place onsubmit="return confirmDelete(this, 'The {{ addslashes($b['label']) }} tier will be removed.', 'Delete this tier?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light-danger" title="Delete"><i class="bi bi-trash"></i></button></form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if($canEdit)
                                <tr>
                                    <td>
                                        <form method="POST" action="{{ route('brand-health.label-buckets.store') }}" id="lbn-{{ $metric }}" data-keep-place>@csrf<input type="hidden" name="metric" value="{{ $metric }}"></form>
                                        <input type="number" step="any" name="min_score" form="lbn-{{ $metric }}" class="form-control form-control-sm" placeholder="New minimum">
                                    </td>
                                    <td><input type="text" name="label" maxlength="50" form="lbn-{{ $metric }}" class="form-control form-control-sm" placeholder="Label" required></td>
                                    @if($metric !== 'account_tier')<td></td>@endif
                                    <td class="text-end"><button type="submit" form="lbn-{{ $metric }}" class="btn btn-sm btn-light-secondary"><i class="bi bi-plus-lg"></i> Add tier</button></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        {{-- ===== Formula weights ===== --}}
        @if($key === 'weights')
            <div class="row g-3">
                @foreach($formulaComponents as $aggregate => $components)
                    @php
                        $w = collect($components)->mapWithKeys(fn ($c) => [$c => (float) ($formulaWeights->first(fn ($x) => $x->aggregate === $aggregate && $x->component === $c)?->weight ?? round(1 / count($components), 4))]);
                    @endphp
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('brand-health.formula-weights.update', $aggregate) }}" class="bh-card h-100" data-keep-place data-weights>
                            @csrf @method('PUT')
                            <h6 class="fw-bold mb-0">{{ $metricTitles[$aggregate] ?? ucfirst($aggregate) }}</h6>
                            <small class="bh-faint">{{ $metricHints[$aggregate] ?? '' }}</small>
                            <div class="sb-weight-bar mt-3" data-bar></div>
                            @foreach($components as $i => $c)
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <span class="sb-swatch" style="background: var(--status-{{ $tones[min($i, 4)] }}-ink)"></span>
                                    <label class="flex-grow-1 small fw-semibold">{{ $componentLabels[$c] ?? $c }}</label>
                                    <input type="number" step="0.0001" min="0" name="weights[{{ $c }}]" value="{{ $plain($w[$c]) }}" class="form-control form-control-sm" style="width: 7rem" data-tone="var(--status-{{ $tones[min($i, 4)] }}-ink)" required {{ $dis }}>
                                    <span class="small bh-muted bh-num text-end" style="width: 3.5rem" data-pct></span>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="bh-faint">Weights are normalized — they don't need to add up to 1.</small>
                                @if($canEdit)<button class="btn btn-sm btn-primary">Save weights</button>@endif
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach
</div>
@endsection

@section('bh-scripts')
<script>
(function () {
    // Every save is a normal form post that redirects back here: remember the tab,
    // the open group editor and the scroll position so the page reopens in place.
    const KEY = 'bh-buckets-place';
    document.addEventListener('submit', (e) => {
        if (!e.target.matches('form[data-keep-place]')) return;
        const open = document.querySelector('.collapse.show[id^="sbc-"]');
        try { sessionStorage.setItem(KEY, JSON.stringify({ tab: document.querySelector('#sb-tabs .nav-link.active')?.dataset.bsTarget, open: open?.id, y: window.scrollY })); } catch {}
    });

    // ---- Score-tier group editors, built the first time a cell is opened ----
    const CAN_EDIT = @json($canEdit);
    const CSRF = @json(csrf_token());
    const URL_STORE = @json(route('brand-health.score-buckets.store'));
    const URL_ITEM = (id) => @json(route('brand-health.score-buckets.update', '__ID__')).replace('__ID__', id);
    const COLORS = [['', 'Auto'], ['sip', 'SIP'], ['bagus', 'Bagus'], ['cukup', 'Cukup'], ['kurang', 'Kurang'], ['parah', 'Parah'], ['custom', 'Custom']];
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const dis = CAN_EDIT ? '' : 'disabled';
    const hidden = (name, value) => `<input type="hidden" name="${name}" value="${esc(value)}">`;

    function pickerHtml(b, formId) {
        return `<div class="d-flex align-items-center gap-1" data-color-picker data-auto="${b.auto}">
            <span class="sb-swatch"></span>
            <select name="color" form="${formId}" class="form-select form-select-sm" style="width: 7rem" data-no-search ${dis}>
                ${COLORS.map(([v, l]) => `<option value="${v}" ${b.color === v ? 'selected' : ''}>${l}</option>`).join('')}
            </select>
            <span class="d-flex gap-1 d-none" data-custom>
                <input type="color" name="custom_color_bg" form="${formId}" value="${esc(b.bg)}" class="form-control form-control-sm form-control-color p-0" title="Background">
                <input type="color" name="custom_color_ink" form="${formId}" value="${esc(b.ink)}" class="form-control form-control-sm form-control-color p-0" title="Text">
            </span>
        </div>`;
    }

    function editorHtml(g) {
        const rows = g.buckets.map((b) => {
            const f = `sbf-${b.id}`;
            return `<tr data-sb-row>
                <td><form method="POST" action="${URL_ITEM(b.id)}" id="${f}" data-keep-place>${hidden('_token', CSRF)}${hidden('_method', 'PUT')}</form>
                    <input type="number" step="any" name="min_rate" max="999999.99" form="${f}" class="form-control form-control-sm" value="${esc(b.min_rate)}" placeholder="No minimum" ${dis}></td>
                <td><input type="number" step="any" min="0" name="score" max="999.99" form="${f}" class="form-control form-control-sm" value="${esc(b.score)}" required ${dis}></td>
                <td>${pickerHtml(b, f)}</td>
                <td class="text-end text-nowrap">${CAN_EDIT ? `
                    <button type="submit" form="${f}" class="btn btn-sm btn-primary">Save</button>
                    <form method="POST" action="${URL_ITEM(b.id)}" class="d-inline" data-keep-place onsubmit="return confirmDelete(this, 'This score tier will be removed. Existing cycles keep their scores until you recalculate.', 'Delete this tier?')">${hidden('_token', CSRF)}${hidden('_method', 'DELETE')}<button class="btn btn-sm btn-light-danger" title="Delete"><i class="bi bi-trash"></i></button></form>` : ''}</td>
            </tr>`;
        }).join('');
        const n = `sbn-${g.cell}`;
        const addRow = CAN_EDIT ? `<tr>
            <td><form method="POST" action="${URL_STORE}" id="${n}" data-keep-place>${hidden('_token', CSRF)}${hidden('metric', g.metric)}${hidden('platform', g.platform ?? '')}${hidden('tier', g.tier ?? '')}</form>
                <input type="number" step="any" name="min_rate" max="999999.99" form="${n}" class="form-control form-control-sm" placeholder="New min rate"></td>
            <td><input type="number" step="any" min="0" name="score" max="999.99" form="${n}" class="form-control form-control-sm" placeholder="Score" required></td>
            <td></td>
            <td class="text-end"><button type="submit" form="${n}" class="btn btn-sm btn-light-secondary"><i class="bi bi-plus-lg"></i> Add tier</button></td>
        </tr>` : '';
        return `<div class="border rounded-3 p-3 mt-2" style="border-color: var(--bh-border) !important">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">${esc(g.title)}</div>
                <button type="button" class="btn-close small" data-bs-toggle="collapse" data-bs-target="#${g.cell}" aria-label="Close"></button>
            </div>
            <table class="table table-sm sb-table mb-0">
                <thead><tr><th class="small bh-faint">Min rate (≥)</th><th class="small bh-faint">Score</th><th class="small bh-faint">Color</th><th></th></tr></thead>
                <tbody>${rows}${addRow}</tbody>
            </table>
            ${g.metric === 'growth' ? '<small class="bh-faint">Growth thresholds must be 0% or higher, and the floor tier (no minimum) must score 0.</small>' : ''}
        </div>`;
    }

    function buildEditor(el) {
        if (el.dataset.built) return;
        el.innerHTML = editorHtml(JSON.parse(el.dataset.sbEditor));
        el.dataset.built = '1';
        initRows(el);
    }
    document.addEventListener('show.bs.collapse', (e) => { if (e.target.dataset.sbEditor) buildEditor(e.target); });
    let place = null;
    try { place = JSON.parse(sessionStorage.getItem(KEY) || 'null'); sessionStorage.removeItem(KEY); } catch {}
    const tab = place?.tab ?? (location.hash ? '#sb-' + location.hash.slice(1) : null);
    const btn = tab && document.querySelector(`#sb-tabs [data-bs-target="${tab}"]`);
    if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    if (place?.open && document.getElementById(place.open)) {
        const el = document.getElementById(place.open);
        if (el.dataset.sbEditor) buildEditor(el);
        el.classList.add('show');
        document.querySelector(`[data-bs-target="#${place.open}"].sb-cell`)?.setAttribute('aria-expanded', 'true');
    }
    if (place?.y) requestAnimationFrame(() => window.scrollTo(0, place.y));

    // Unsaved-change markers and colour pickers, for the page and for each editor once built.
    function initRows(root) {
        root.querySelectorAll('[data-sb-row]').forEach((row) => row.addEventListener('input', () => row.classList.add('sb-dirty')));
        root.querySelectorAll('[data-color-picker]').forEach(initPicker);
    }

    // Colour pickers: show the custom colour inputs only for "custom", and preview the swatch.
    function initPicker(wrap) {
        const sel = wrap.querySelector('select'), custom = wrap.querySelector('[data-custom]'), sw = wrap.querySelector('.sb-swatch');
        const [bg, ink] = custom.querySelectorAll('input');
        const sync = () => {
            const v = sel.value;
            custom.classList.toggle('d-none', v !== 'custom');
            bg.disabled = ink.disabled = v !== 'custom' || sel.disabled;
            sw.style.background = v === 'custom' ? ink.value : v ? `var(--status-${v}-ink)` : `var(--status-${wrap.dataset.auto}-ink)`;
        };
        sel.addEventListener('change', sync); bg.addEventListener('input', sync); ink.addEventListener('input', sync);
        sync();
    }
    initRows(document);

    // Formula weights: live normalized share per component.
    document.querySelectorAll('form[data-weights]').forEach((form) => {
        const inputs = [...form.querySelectorAll('input[type=number]')];
        const sync = () => {
            const sum = inputs.reduce((t, i) => t + (Number(i.value) || 0), 0);
            form.querySelector('[data-bar]').innerHTML = inputs.map((i) => `<span style="width: ${sum > 0 ? (Number(i.value) || 0) / sum * 100 : 0}%; background: ${i.dataset.tone}"></span>`).join('');
            inputs.forEach((i) => { i.closest('.d-flex').querySelector('[data-pct]').textContent = sum > 0 ? `${Math.round((Number(i.value) || 0) / sum * 1000) / 10}%` : '—'; });
        };
        form.addEventListener('input', sync); sync();
    });
})();
</script>
@endsection
