@extends('layouts.app')

@section('title', $device->name . ' | Asset Profile')
@section('header_title', 'Asset Profile')

@section('styles')
    <style>
        /* ---------- Bento grid ---------- */
        .bento {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1rem;
        }
        .bento > .tile { grid-column: span 12; }
        @media (min-width: 768px) {
            .tile-md-6 { grid-column: span 6 !important; }
        }
        @media (min-width: 1200px) {
            .tile-xl-3 { grid-column: span 3 !important; }
            .tile-xl-4 { grid-column: span 4 !important; }
            .tile-xl-5 { grid-column: span 5 !important; }
            .tile-xl-6 { grid-column: span 6 !important; }
            .tile-xl-7 { grid-column: span 7 !important; }
            .tile-xl-8 { grid-column: span 8 !important; }
            .tile-row-2 { grid-row: span 2; }
        }

        .tile {
            border-radius: 20px !important;
            border: 1px solid rgba(67, 94, 190, 0.08) !important;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 8px 24px rgba(67, 94, 190, 0.05) !important;
            margin-bottom: 0 !important;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: box-shadow .25s ease, transform .25s ease;
        }
        .tile:hover { box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 12px 32px rgba(67, 94, 190, 0.09) !important; }

        .tile-label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #8a94a6;
            display: flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .75rem;
        }
        .tile-head { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; margin-bottom: 1rem; }
        .tile-head .tile-label { margin-bottom: .15rem; }
        .tile-title { font-weight: 700; font-size: 1.05rem; margin: 0; }
        .tile-scroll { overflow-y: auto; max-height: 340px; margin: 0 -.5rem; padding: 0 .5rem; }
        .count-pill { font-size: .7rem; padding: .2rem .55rem; border-radius: 999px; background: rgba(67, 94, 190, .1); color: #435ebe; font-weight: 700; }

        /* ---------- Hero ---------- */
        .tile-hero {
            background: linear-gradient(145deg, rgba(67, 94, 190, .10), rgba(67, 94, 190, .02) 60%) !important;
            position: relative;
            overflow: hidden;
        }
        .tile-hero::after {
            content: ''; position: absolute; width: 260px; height: 260px; border-radius: 50%;
            right: -90px; top: -90px; background: radial-gradient(circle, rgba(67, 94, 190, .14), transparent 70%);
            pointer-events: none;
        }
        .hero-media {
            width: 100%; height: 190px; border-radius: 16px; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255, 255, 255, .55); margin-bottom: 1.25rem; position: relative; z-index: 1;
        }
        .hero-media img { width: 100%; height: 100%; object-fit: cover; cursor: zoom-in; transition: transform .4s ease; }
        .hero-media img:hover { transform: scale(1.04); }
        .hero-media .hero-icon { font-size: 4.5rem; color: #435ebe; opacity: .85; }
        .hero-name { font-size: 1.6rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; margin: 0; }
        .hero-sub { color: #6b7385; font-weight: 600; margin: .25rem 0 0; }
        .chip {
            display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; font-weight: 700;
            padding: .35rem .75rem; border-radius: 999px; white-space: nowrap;
        }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; box-shadow: 0 0 0 3px rgba(0, 0, 0, .04); }

        /* ---------- Health ring ---------- */
        .ring {
            --pct: 0; --ring-color: #22c55e;
            width: 132px; height: 132px; border-radius: 50%;
            background: conic-gradient(var(--ring-color) calc(var(--pct) * 1%), rgba(67, 94, 190, .09) 0);
            display: grid; place-items: center; margin: .25rem auto 1rem;
        }
        .ring-inner {
            width: 104px; height: 104px; border-radius: 50%; display: grid; place-items: center; text-align: center;
            background: var(--bs-card-bg, #fff);
        }
        .ring-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
        .ring-caption { font-size: .68rem; color: #8a94a6; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }

        /* ---------- Owner ---------- */
        .owner-avatar {
            width: 56px; height: 56px; border-radius: 16px; display: grid; place-items: center;
            font-size: 1.35rem; font-weight: 800; color: #fff;
            background: linear-gradient(135deg, #435ebe, #7c8ff0);
            flex-shrink: 0;
        }

        /* ---------- Spec cells ---------- */
        .spec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .75rem; }
        .spec-cell {
            border-radius: 14px; padding: .9rem 1rem; background: rgba(67, 94, 190, .04);
            border: 1px solid rgba(67, 94, 190, .06); min-width: 0;
        }
        .spec-cell i { font-size: 1.1rem; color: #435ebe; }
        .spec-cell .spec-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #8a94a6; margin: .45rem 0 .15rem; }
        .spec-cell .spec-value { font-weight: 700; font-size: .92rem; overflow-wrap: anywhere; }
        .spec-cell.is-empty .spec-value { color: #b4bccb; font-weight: 600; }

        /* ---------- Stat tiles ---------- */
        .stat-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; font-size: 1.15rem; }
        .stat-value { font-size: 1.3rem; font-weight: 800; letter-spacing: -.01em; margin-top: auto; overflow-wrap: anywhere; }
        .stat-foot { font-size: .75rem; color: #8a94a6; font-weight: 600; }
        .pw-mask { letter-spacing: .2em; }

        /* ---------- Lists ---------- */
        .row-item {
            display: flex; align-items: center; gap: .9rem; padding: .8rem .5rem; border-radius: 12px;
            transition: background-color .2s ease;
        }
        .row-item + .row-item { border-top: 1px dashed rgba(67, 94, 190, .1); }
        .row-item:hover { background: rgba(67, 94, 190, .035); }
        .row-icon { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; flex-shrink: 0; background: rgba(67, 94, 190, .08); color: #435ebe; }

        .timeline { position: relative; padding-left: 1.4rem; }
        .timeline::before { content: ''; position: absolute; left: .4rem; top: .3rem; bottom: .3rem; width: 2px; background: rgba(67, 94, 190, .12); border-radius: 2px; }
        .timeline-item { position: relative; padding-bottom: 1.1rem; }
        .timeline-item::before {
            content: ''; position: absolute; left: -1.28rem; top: .3rem; width: 10px; height: 10px; border-radius: 50%;
            background: #435ebe; box-shadow: 0 0 0 4px rgba(67, 94, 190, .15);
        }
        .timeline-item:last-child { padding-bottom: 0; }

        .empty-state { text-align: center; padding: 2rem 1rem; color: #8a94a6; font-size: .85rem; font-weight: 600; }
        .empty-state i { display: block; font-size: 2rem; color: rgba(67, 94, 190, .2); margin-bottom: .4rem; }

        .gallery-img { width: 100%; height: 180px; object-fit: cover; border-radius: 14px; cursor: zoom-in; transition: transform .3s ease; }
        .gallery-img:hover { transform: scale(1.03); }

        .icon-fix { display: inline-flex; align-items: center; justify-content: center; line-height: 0; }

        /* Bootstrap Icons shift glyphs down (vertical-align: -.125em), which pushes them
           off-centre inside the icon boxes and beside labels. Centre them properly. */
        .bento .bi, .bento .bi::before { line-height: 1; vertical-align: 0; }
        .bento .bi { display: inline-flex; align-items: center; justify-content: center; }
        .stat-icon .bi, .row-icon .bi, .owner-avatar .bi { font-size: inherit; }
        .stat-icon, .row-icon, .owner-avatar { line-height: 1; }
        .hero-media .hero-icon { display: flex; }
        .spec-cell .bi { display: flex; width: 1.1rem; height: 1.1rem; }
        .tile-label .bi { font-size: .85rem; }
        .stat-foot .bi, .text-muted.small .bi { margin-right: .15rem; }
        .btn .bi { vertical-align: 0; }
        .tile-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, .12), rgba(245, 158, 11, .03)) !important;
            border-color: rgba(245, 158, 11, .3) !important;
        }
        .issue-item {
            display: flex; align-items: center; gap: .75rem; padding: .7rem .9rem; border-radius: 12px;
            background: rgba(255, 255, 255, .65); border: 1px solid rgba(245, 158, 11, .18);
        }
        .issue-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: .6rem; }
        .comp-issue { color: #b45309; font-size: .78rem; font-weight: 600; }
        .bento .empty-state .bi { display: flex; margin: 0 auto .4rem; }
        .tile .pagination { margin-bottom: 0; }

        /* ---------- Dark mode (the template sets data-bs-theme="dark" on <html>) ----------
           Its pale "light-*" badges and buttons stay pastel in dark mode, which makes the
           coloured text on them unreadable, so swap them for translucent tints. */
        [data-bs-theme="dark"] .bg-light-primary,   [data-bs-theme="dark"] .btn-light-primary   { background-color: rgba(129, 140, 248, .16) !important; color: #a5b4fc !important; border-color: transparent !important; }
        [data-bs-theme="dark"] .bg-light-success,   [data-bs-theme="dark"] .btn-light-success   { background-color: rgba(34, 197, 94, .16) !important;  color: #4ade80 !important; border-color: transparent !important; }
        [data-bs-theme="dark"] .bg-light-warning,   [data-bs-theme="dark"] .btn-light-warning   { background-color: rgba(245, 158, 11, .16) !important; color: #fbbf24 !important; border-color: transparent !important; }
        [data-bs-theme="dark"] .bg-light-danger,    [data-bs-theme="dark"] .btn-light-danger    { background-color: rgba(239, 68, 68, .16) !important;  color: #f87171 !important; border-color: transparent !important; }
        [data-bs-theme="dark"] .bg-light-info,      [data-bs-theme="dark"] .btn-light-info      { background-color: rgba(56, 189, 248, .16) !important; color: #7dd3fc !important; border-color: transparent !important; }
        [data-bs-theme="dark"] .bg-light-secondary, [data-bs-theme="dark"] .btn-light-secondary { background-color: rgba(148, 163, 184, .14) !important; color: #cbd5e1 !important; border-color: transparent !important; }
        [data-bs-theme="dark"] [class*="btn-light-"]:hover { filter: brightness(1.25); }
        [data-bs-theme="dark"] .btn-warning { color: #1f1300 !important; }

        [data-bs-theme="dark"] .tile { border-color: rgba(255, 255, 255, .06) !important; box-shadow: 0 1px 2px rgba(0, 0, 0, .3) !important; }
        [data-bs-theme="dark"] .tile-hero { background: linear-gradient(145deg, rgba(99, 102, 241, .16), rgba(99, 102, 241, .03) 60%) !important; }
        [data-bs-theme="dark"] .tile-hero::after { background: radial-gradient(circle, rgba(129, 140, 248, .18), transparent 70%); }
        [data-bs-theme="dark"] .hero-media { background: rgba(255, 255, 255, .04); }
        [data-bs-theme="dark"] .hero-media .hero-icon,
        [data-bs-theme="dark"] .spec-cell .bi { color: #a5b4fc; }
        [data-bs-theme="dark"] .hero-sub { color: #9aa3b5; }
        [data-bs-theme="dark"] .ring { background: conic-gradient(var(--ring-color) calc(var(--pct) * 1%), rgba(255, 255, 255, .08) 0); }
        [data-bs-theme="dark"] .ring-inner { background: var(--bs-card-bg, #1e1e2d); }
        [data-bs-theme="dark"] .spec-cell { background: rgba(255, 255, 255, .03); border-color: rgba(255, 255, 255, .06); }
        [data-bs-theme="dark"] .spec-cell.is-empty .spec-value { color: #5b6477; }
        [data-bs-theme="dark"] .row-icon { background: rgba(129, 140, 248, .12); color: #a5b4fc; }
        [data-bs-theme="dark"] .row-item + .row-item { border-top-color: rgba(255, 255, 255, .06); }
        [data-bs-theme="dark"] .row-item:hover { background: rgba(255, 255, 255, .03); }
        [data-bs-theme="dark"] .count-pill { background: rgba(129, 140, 248, .16); color: #a5b4fc; }
        [data-bs-theme="dark"] .owner-avatar { background: linear-gradient(135deg, #4f46e5, #818cf8); }
        [data-bs-theme="dark"] .timeline::before { background: rgba(255, 255, 255, .08); }
        [data-bs-theme="dark"] .timeline-item::before { background: #818cf8; box-shadow: 0 0 0 4px rgba(129, 140, 248, .18); }
        [data-bs-theme="dark"] .empty-state .bi { color: rgba(165, 180, 252, .25); }
        [data-bs-theme="dark"] .tile-warning { background: linear-gradient(135deg, rgba(245, 158, 11, .10), rgba(245, 158, 11, .02)) !important; border-color: rgba(245, 158, 11, .25) !important; }
        [data-bs-theme="dark"] .tile-warning .tile-label { color: #fbbf24 !important; }
        [data-bs-theme="dark"] .issue-item { background: rgba(255, 255, 255, .03); border-color: rgba(245, 158, 11, .2); }
        [data-bs-theme="dark"] .issue-item .text-heading { color: #e5e7eb !important; }
        [data-bs-theme="dark"] .comp-issue { color: #fbbf24; }
        [data-bs-theme="dark"] #detach-modal .rounded-3 { background: rgba(239, 68, 68, .08) !important; }

        /* ---------- Company accounts ---------- */
        /* Bootstrap has no min-w-0; without it flex/grid children can't shrink, so text-truncate never kicks in. */
        .min-w-0 { min-width: 0 !important; }
        .account-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: .75rem; }
        .account-item {
            border: 1px solid rgba(67, 94, 190, .1); border-radius: 14px; padding: .9rem 1rem; min-width: 0;
            display: flex; flex-direction: column; gap: .65rem; overflow: hidden;
        }
        .account-item .row-icon { width: 36px; height: 36px; }
        .account-item .account-apps { display: flex; flex-wrap: wrap; gap: .35rem; }
        .account-item .account-apps .badge {
            white-space: normal; text-align: left; line-height: 1.3; max-width: 100%;
            font-weight: 600; padding: .35rem .6rem; border-radius: 8px;
        }
        .account-item:hover { border-color: rgba(67, 94, 190, .3); }
        [data-bs-theme="dark"] .account-item { border-color: rgba(255, 255, 255, .08); }

        /* ---------- Attach part picker ---------- */
        .part-filter {
            border: 1px solid rgba(67, 94, 190, .15); background: transparent; color: inherit;
            border-radius: 999px; padding: .3rem .8rem; font-size: .8rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: .35rem; transition: all .15s ease;
        }
        .part-filter span { font-size: .7rem; opacity: .6; }
        .part-filter:hover { border-color: #435ebe; }
        .part-filter.active { background: #435ebe; border-color: #435ebe; color: #fff; }
        .part-filter.active span { opacity: .85; }
        .part-option {
            display: flex; align-items: center; gap: .9rem; padding: .8rem 1rem; margin: 0; cursor: pointer;
            border: 1.5px solid rgba(67, 94, 190, .1); border-radius: 14px; transition: all .15s ease;
        }
        .part-option:hover { border-color: rgba(67, 94, 190, .35); background: rgba(67, 94, 190, .03); }
        .part-option.selected { border-color: #435ebe; background: rgba(67, 94, 190, .07); box-shadow: 0 0 0 3px rgba(67, 94, 190, .12); }
        .part-option.has-issue { border-left: 4px solid #f59e0b; }
        .part-icon { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; flex-shrink: 0; background: rgba(67, 94, 190, .08); color: #435ebe; font-size: 1.15rem; }
        .part-icon .bi, .part-check .bi, .part-filter .bi,
        .part-icon .bi::before, .part-check .bi::before, .part-filter .bi::before { line-height: 1; vertical-align: 0; display: block; }
        .part-side { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: .5rem; flex-shrink: 0; min-width: 64px; }
        .part-check {
            width: 22px; height: 22px; border-radius: 50%; border: 1.5px solid rgba(67, 94, 190, .25);
            display: flex; align-items: center; justify-content: center; color: transparent; font-size: .85rem; line-height: 1;
        }
        .part-option.selected .part-check { background: #435ebe; border-color: #435ebe; color: #fff; }
        #attach-modal .empty-state .bi { display: block; margin: 0 auto .4rem; }
        #attach-modal .modal-body { min-height: 320px; }
        [data-bs-theme="dark"] .part-filter { border-color: rgba(255, 255, 255, .12); }
        [data-bs-theme="dark"] .part-filter.active { background: #6366f1; border-color: #6366f1; }
        [data-bs-theme="dark"] .part-option { border-color: rgba(255, 255, 255, .08); }
        [data-bs-theme="dark"] .part-option:hover { background: rgba(255, 255, 255, .03); border-color: rgba(129, 140, 248, .4); }
        [data-bs-theme="dark"] .part-option.selected { background: rgba(129, 140, 248, .1); border-color: #818cf8; box-shadow: 0 0 0 3px rgba(129, 140, 248, .15); }
        [data-bs-theme="dark"] .part-icon { background: rgba(129, 140, 248, .12); color: #a5b4fc; }
        [data-bs-theme="dark"] .part-option.selected .part-check { background: #6366f1; border-color: #6366f1; }
    </style>
@endsection

@section('content')
    @php
        $services = $device->serviceHistories()->latest()->paginate(5, ['*'], 'services')->withQueryString();
        $logs = $device->activityLogs()->latest()->paginate(6, ['*'], 'logs')->withQueryString();

        $deviceAllocations = \App\Models\SubscriptionAllocation::with('subscription.account')
            ->where('assignable_type', 'App\Models\Device')
            ->where('assignable_id', $device->id)
            ->get();

        // Software on this device = licenses linked to the device itself, plus the active
        // licenses of whoever the device is assigned to (shown "via <name>"). A license
        // linked both ways is listed once, as a device license.
        $owner = $device->device_group === 'Device' ? $device->user : null;
        $ownerAllocations = $owner
            ? \App\Models\SubscriptionAllocation::with('subscription.account')
                ->where('assignable_type', 'App\Models\User')
                ->where('assignable_id', $owner->id)
                ->get()
                ->filter(fn ($a) => $a->subscription?->status === 'Active')
            : collect();
        $software = $deviceAllocations->filter(fn ($a) => $a->subscription)
            ->map(fn ($a) => ['sub' => $a->subscription, 'via' => null])
            ->concat($ownerAllocations->map(fn ($a) => ['sub' => $a->subscription, 'via' => $owner]))
            ->unique(fn ($row) => $row['sub']->id)
            ->sortBy(fn ($row) => strtolower($row['sub']->label))
            ->values();

        $isPhone = $device->category == 'Phone';

        // Warnings on this asset, plus those on its installed parts (shown with the part's name).
        $activeIssues = $device->issues->whereNull('resolved_at');
        $componentIssues = $device->components->flatMap(fn ($c) => $c->activeIssues->map(fn ($i) => [$c, $i]));
        $warningCount = $activeIssues->count() + $componentIssues->count();
        $isComputer = in_array($device->category, ['PC', 'Laptop']);
        $isNetwork = $device->isNetwork();
        // Network gear lives in a place, not with a person, so it tracks in-use explicitly.
        $inUse = $isNetwork ? (bool) $device->in_use : ($device->user_id || $device->parent_id);

        // Health: phone batteries degrade noticeably below 80%, other parts below 50%.
        $health = $device->current_health;
        $healthThreshold = $isPhone ? 80 : 50;
        $healthColor = is_null($health) ? '#b4bccb' : ($health < $healthThreshold ? '#ef4444' : ($health < $healthThreshold + 10 ? '#f59e0b' : '#22c55e'));

        $conditionClass = match (strtolower($device->condition ?? '')) {
            'sangat baik', 'baik' => 'bg-light-success text-success',
            'ok' => 'bg-light-primary text-primary',
            'cukup' => 'bg-light-warning text-warning',
            default => 'bg-light-secondary text-secondary',
        };

        $heroIcon = match (true) {
            $isPhone => strtolower($device->os ?? '') == 'ios' ? 'bi-apple' : 'bi-phone',
            $device->category == 'Router' => 'bi-router',
            $device->category == 'Access Point' => 'bi-broadcast-pin',
            $device->category == 'Switch' => 'bi-hdd-network',
            $device->category == 'Laptop' => 'bi-laptop',
            $device->category == 'PC' => 'bi-pc-display',
            $device->category == 'Monitor' => 'bi-display',
            in_array($device->category, ['SSD', 'HDD']) => 'bi-device-hdd',
            $device->category == 'RAM' => 'bi-memory',
            $device->category == 'GPU' => 'bi-gpu-card',
            $device->category == 'Battery' => 'bi-battery-half',
            $device->category == 'Keyboard' => 'bi-keyboard',
            $device->category == 'Mouse' => 'bi-mouse',
            default => 'bi-cpu',
        };

        // Spec cells differ per kind of asset. Computers summarise their attached parts.
        if ($isNetwork) {
            $specs = [
                ['bi-wifi', 'Wi-Fi SSID', $device->ssid],
                ['bi-shield-lock', 'Wi-Fi Password', $device->ssid_password],
                ['bi-person-gear', 'Admin Username', $device->admin_username],
                ['bi-diagram-2', 'IP Address', $device->ip_address],
                ['bi-palette', 'Color', $device->color],
                ['bi-box-seam', 'Accessories', $device->accessories],
            ];
            // Switches don't broadcast Wi-Fi.
            if ($device->category == 'Switch') {
                $specs = array_values(array_filter($specs, fn ($s) => ! str_starts_with($s[1], 'Wi-Fi')));
            }
        } elseif ($isPhone) {
            $specs = [
                ['bi-phone', 'Operating System', trim(($device->os ?? '').' '.($device->os_version ?? '')) ?: null],
                ['bi-cpu', 'Processor', $device->processor],
                ['bi-memory', 'RAM', $device->ram],
                ['bi-sd-card', 'Storage', $device->rom],
                ['bi-palette', 'Color', $device->color],
                ['bi-box-seam', 'Accessories', $device->accessories],
            ];
        } elseif ($isComputer) {
            $parts = $device->components->groupBy('category');
            $join = fn ($cat, $fn) => $parts->get($cat)?->map($fn)->filter()->unique()->implode(', ') ?: null;
            $ramTotal = $parts->get('RAM')?->sum(fn ($r) => (int) $r->size);
            $ramType = $parts->get('RAM')?->pluck('component_type')->filter()->first();
            $specs = [
                ['bi-cpu', 'Processor', $join('Processor', fn ($p) => $p->model)],
                ['bi-memory', 'RAM', $ramTotal ? trim("$ramTotal GB ".($ramType ?? '')) : null],
                ['bi-gpu-card', 'Graphics', $join('GPU', fn ($g) => $g->model)],
                ['bi-device-ssd', 'SSD', $join('SSD', fn ($s) => $s->size ?: $s->model)],
                ['bi-device-hdd', 'HDD', $join('HDD', fn ($h) => $h->size ?: $h->model)],
                ['bi-motherboard', 'Motherboard', $join('Motherboard', fn ($m) => $m->model)],
                ['bi-windows', 'Operating System', $join('OS', fn ($o) => $o->model)],
                ['bi-battery-half', 'Battery', $join('Battery', fn ($b) => $b->current_health !== null ? $b->current_health.'%' : null)],
            ];
            // A PC has no battery and a laptop has no separate HDD most of the time — hide cells that don't apply.
            $specs = array_values(array_filter($specs, fn ($s) => $s[2] !== null || in_array($s[1], ['Processor', 'RAM', 'Operating System'])));
        } else {
            $specs = [
                ['bi-tag', 'Category', $device->category],
                ['bi-diagram-3', 'Type', $device->component_type],
                ['bi-rulers', 'Capacity / Size', $device->size],
                ['bi-upc', 'Model', $device->model],
                ['bi-folder2-open', 'Used For', $device->area],
            ];
        }
    @endphp

    {{-- Page header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
        <div>
            <a href="{{ route('inventory.index', $device->category) }}" class="btn btn-sm btn-light-primary px-3 fw-bold mb-3 d-inline-flex align-items-center gap-2 rounded-pill">
                <i class="bi bi-arrow-left icon-fix"></i> Back to {{ $device->category }}s
            </a>
            <div class="text-muted small fw-semibold">{{ $device->device_group }} &middot; {{ $device->category }}</div>
        </div>
        <div class="d-flex gap-2">
            <button data-bs-toggle="modal" data-bs-target="#issue-modal" class="btn btn-light-warning fw-bold px-3 d-flex align-items-center gap-2 rounded-pill" title="Add warning">
                <i class="bi bi-exclamation-triangle icon-fix"></i> <span class="d-none d-sm-inline">Warning</span>
            </button>
            <button data-bs-toggle="modal" data-bs-target="#edit-modal" class="btn btn-primary fw-bold px-4 d-flex align-items-center gap-2 rounded-pill shadow-sm">
                <i class="bi bi-pencil-square icon-fix"></i> Edit
            </button>
            <form action="{{ route('inventory.destroy', $device->id) }}" method="POST" onsubmit="return confirmDelete(this, 'Permanently delete this asset?')" class="m-0">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-light-danger text-danger fw-bold px-3 d-flex align-items-center gap-2 h-100 rounded-pill" title="Delete asset">
                    <i class="bi bi-trash icon-fix"></i>
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-light-success color-success d-flex align-items-center gap-2 mb-4 rounded-4">
            <i class="bi bi-check-circle icon-fix fs-5"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center gap-2 mb-4 rounded-4">
            <i class="bi bi-exclamation-triangle icon-fix fs-5"></i> Action Failed: {{ $errors->first() }}
        </div>
    @endif

    <div class="bento mb-5">

        {{-- ===== WARNINGS ===== --}}
        @if($warningCount)
            <div class="tile card tile-warning">
                <div class="tile-head mb-3">
                    <div>
                        <div class="tile-label text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Known issues</div>
                        <h5 class="tile-title">{{ $warningCount }} active {{ \Illuminate\Support\Str::plural('warning', $warningCount) }}</h5>
                    </div>
                    <button data-bs-toggle="modal" data-bs-target="#issue-modal" class="btn btn-sm btn-warning fw-bold rounded-pill"><i class="bi bi-plus-lg"></i> Add</button>
                </div>
                <div class="issue-grid">
                    @foreach($activeIssues as $issue)
                        <div class="issue-item">
                            <i class="bi bi-exclamation-circle-fill text-warning fs-5"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-heading">{{ $issue->description }}</div>
                                <div class="text-muted small">Since {{ $issue->reported_at->format('d M Y') }}</div>
                            </div>
                            <form action="{{ route('inventory.issues.resolve', $issue) }}" method="POST" class="m-0">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-light-success rounded-pill px-2" title="Mark as fixed"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <form action="{{ route('inventory.issues.destroy', $issue) }}" method="POST" class="m-0" onsubmit="return confirmDelete(this, 'Remove this warning? Use the tick instead if it was fixed.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light-secondary rounded-pill px-2" title="Remove (entered by mistake)"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    @endforeach
                    @foreach($componentIssues as [$comp, $issue])
                        <div class="issue-item">
                            <i class="bi bi-cpu text-warning fs-5"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-heading">{{ $issue->description }}</div>
                                <div class="text-muted small">
                                    on <a href="{{ route('inventory.show', $comp->id) }}" class="fw-semibold">{{ $comp->name }}</a> ({{ $comp->category }}) &middot; since {{ $issue->reported_at->format('d M Y') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===== HERO ===== --}}
        <div class="tile card tile-hero tile-md-6 tile-xl-5 tile-row-2">
            <div class="hero-media">
                @if($device->image1)
                    <img src="{{ asset('storage/' . $device->image1) }}" alt="{{ $device->name }}"
                         data-bs-toggle="modal" data-bs-target="#image-lightbox-modal"
                         onclick="document.getElementById('lightbox-image').src=this.src">
                @else
                    <i class="bi {{ $heroIcon }} hero-icon"></i>
                @endif
            </div>
            <div class="position-relative" style="z-index: 1;">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="chip {{ $inUse ? 'bg-light-success text-success' : 'bg-light-primary text-primary' }}">
                        <span class="status-dot"></span> {{ $inUse ? 'In use' : 'In stock' }}
                    </span>
                    @if($warningCount)
                        <span class="chip bg-light-warning text-warning"><i class="bi bi-exclamation-triangle-fill"></i> {{ $warningCount }} {{ \Illuminate\Support\Str::plural('warning', $warningCount) }}</span>
                    @endif
                    @if($device->condition)
                        <span class="chip {{ $conditionClass }}"><i class="bi bi-stars"></i> {{ $device->condition }}</span>
                    @endif
                    @if($device->usability_tier)
                        <span class="chip bg-light-info text-info"><i class="bi bi-bar-chart-steps"></i> Tier {{ $device->usability_tier }}/10</span>
                    @endif
                </div>
                <h2 class="hero-name">{{ $device->name }}</h2>
                <p class="hero-sub">{{ trim(($device->manufacturer ?? '').' '.($device->model ?? '')) ?: 'Unknown model' }}</p>
            </div>
        </div>

        {{-- ===== OWNER / LOCATION ===== --}}
        @if($isNetwork)
        <div class="tile card tile-md-6 tile-xl-4">
            <div class="tile-label"><i class="bi bi-geo-alt"></i> Location</div>
            <div class="d-flex align-items-center gap-3 mt-auto">
                <div class="owner-avatar"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="min-w-0">
                    <div class="fw-bold fs-5 text-heading text-truncate">{{ $device->location ?? 'Not set' }}</div>
                    <span class="text-muted small fw-semibold"><i class="bi bi-people me-1"></i>Serves: {{ $device->area ?? '-' }}</span>
                </div>
            </div>
        </div>
        @else
        <div class="tile card tile-md-6 tile-xl-4">
            <div class="tile-label"><i class="bi bi-person-badge"></i> {{ $device->parentDevice ? 'Installed in' : 'Assigned to' }}</div>
            @if($device->device_group != 'Device' && $device->parentDevice)
                <div class="d-flex align-items-center gap-3 mt-auto">
                    <div class="owner-avatar"><i class="bi bi-pc-display"></i></div>
                    <div class="min-w-0">
                        <a href="{{ route('inventory.show', $device->parentDevice->id) }}" class="fw-bold fs-5 text-heading d-block text-truncate">{{ $device->parentDevice->name }}</a>
                        <span class="text-muted small fw-semibold">{{ $device->parentDevice->category }} &middot; installed component</span>
                    </div>
                </div>
            @elseif($device->user)
                <div class="d-flex align-items-center gap-3 mt-auto">
                    <div class="owner-avatar">{{ strtoupper(substr($device->user->name, 0, 1)) }}</div>
                    <div class="min-w-0">
                        <a href="{{ route('employees.show', $device->user->id) }}" class="fw-bold fs-5 text-heading d-block text-truncate">{{ $device->user->name }}</a>
                        <span class="text-muted small fw-semibold"><i class="bi bi-building me-1"></i>{{ $device->user->department->name ?? 'No department' }}</span>
                    </div>
                </div>
            @else
                <div class="d-flex align-items-center gap-3 mt-auto">
                    <div class="owner-avatar" style="background: rgba(67,94,190,.1); color: #435ebe;"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <div class="fw-bold fs-5 text-heading">Available in stock</div>
                        <button data-bs-toggle="modal" data-bs-target="#edit-modal" class="btn btn-link p-0 small fw-semibold text-decoration-none">Assign to an employee &rarr;</button>
                    </div>
                </div>
            @endif
        </div>
        @endif

        {{-- ===== HEALTH / PARTS / CONNECTION ===== --}}
        @if($isNetwork)
            <div class="tile card tile-md-6 tile-xl-3">
                <div class="tile-label"><i class="bi bi-diagram-2"></i> IP address</div>
                <div class="stat-value font-monospace" style="font-size: 1.2rem;">{{ $device->ip_address ?? '—' }}</div>
                <div class="stat-foot mb-3">{{ $device->ip_address ? 'Admin panel' : 'No IP recorded' }}</div>
                @if($device->ip_address)
                    <a href="http://{{ $device->ip_address }}" target="_blank" rel="noopener" class="btn btn-sm btn-light-primary fw-bold rounded-pill">
                        <i class="bi bi-box-arrow-up-right"></i> Open admin
                    </a>
                @endif
            </div>
        @elseif($isComputer)
            <div class="tile card tile-md-6 tile-xl-3">
                <div class="tile-label"><i class="bi bi-motherboard"></i> Components</div>
                <div class="stat-value" style="font-size: 2.4rem;">{{ $device->components->count() }}</div>
                <div class="stat-foot mb-3">parts installed</div>
                <button data-bs-toggle="modal" data-bs-target="#attach-modal" class="btn btn-sm btn-light-primary fw-bold rounded-pill">
                    <i class="bi bi-link-45deg"></i> Attach part
                </button>
            </div>
        @else
            <div class="tile card tile-md-6 tile-xl-3 text-center">
                <div class="tile-label justify-content-center"><i class="bi bi-heart-pulse"></i> {{ $isPhone ? 'Battery health' : 'Health' }}</div>
                <div class="ring" style="--pct: {{ $health ?? 0 }}; --ring-color: {{ $healthColor }};">
                    <div class="ring-inner">
                        <div>
                            <div class="ring-value" style="color: {{ $healthColor }};">{{ is_null($health) ? '—' : $health.'%' }}</div>
                            <div class="ring-caption">{{ is_null($health) ? 'not recorded' : ($health < $healthThreshold ? 'replace soon' : 'healthy') }}</div>
                        </div>
                    </div>
                </div>
                <button data-bs-toggle="modal" data-bs-target="#health-modal" class="btn btn-sm btn-light-success fw-bold rounded-pill mt-auto">
                    <i class="bi bi-plus-lg"></i> Log health
                </button>
            </div>
        @endif

        {{-- ===== QUICK STATS ===== --}}
        <div class="tile card tile-md-6 tile-xl-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon bg-light-warning text-warning"><i class="bi bi-key"></i></div>
                @if($device->device_password)
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-light-secondary rounded-pill px-2" onclick="togglePassword(this)" title="Show / hide"><i class="bi bi-eye"></i></button>
                        <button type="button" class="btn btn-sm btn-light-secondary rounded-pill px-2" onclick="copyPassword(this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                    </div>
                @endif
            </div>
            <div class="tile-label mb-1">{{ $isNetwork ? 'Admin password' : 'Password' }}</div>
            @if($isNetwork && $device->admin_username)
                <div class="stat-foot mb-1 text-truncate"><i class="bi bi-person"></i> {{ $device->admin_username }}</div>
            @endif
            @if($device->device_password)
                <div class="stat-value pw-mask" id="pw-value" data-pw="{{ $device->device_password }}">••••••••</div>
            @else
                <div class="stat-value text-muted" style="font-size: 1rem;">Not set</div>
            @endif
        </div>

        <div class="tile card tile-md-6 tile-xl-3">
            <div class="stat-icon bg-light-primary text-primary mb-3"><i class="bi bi-calendar-check"></i></div>
            <div class="tile-label mb-1">Purchased</div>
            <div class="stat-value" style="font-size: 1.1rem;">{{ $device->purchase_date?->format('d M Y') ?? 'Unknown' }}</div>
            @if($device->purchase_date)
                <div class="stat-foot">{{ $device->purchase_date->diffForHumans(null, true) }} old</div>
            @endif
        </div>

        {{-- ===== SPECS ===== --}}
        <div class="tile card tile-xl-7">
            <div class="tile-head">
                <div>
                    <div class="tile-label"><i class="bi bi-sliders"></i> Specifications</div>
                    <h5 class="tile-title">{{ $isComputer ? 'System overview' : ($isNetwork ? 'Network details' : 'Hardware details') }}</h5>
                </div>
            </div>
            <div class="spec-grid">
                @foreach($specs as [$icon, $label, $value])
                    <div class="spec-cell {{ $value ? '' : 'is-empty' }}">
                        <i class="bi {{ $icon }}"></i>
                        <div class="spec-label">{{ $label }}</div>
                        <div class="spec-value">{{ $value ?? 'Not set' }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== SOFTWARE ===== --}}
        <div class="tile card tile-md-6 tile-xl-5">
            <div class="tile-head">
                <div>
                    <div class="tile-label"><i class="bi bi-cloud-check"></i> Licenses</div>
                    <h5 class="tile-title">Software <span class="count-pill ms-1">{{ $software->count() }}</span></h5>
                    @if($owner)
                        <small class="text-muted">Includes licenses {{ \Illuminate\Support\Str::words($owner->name, 2, '') }} has access to</small>
                    @endif
                </div>
            </div>
            <div class="tile-scroll">
                @forelse($software as $row)
                    @php $sub = $row['sub']; @endphp
                    <div class="row-item">
                        <div class="row-icon"><i class="bi bi-app-indicator"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <a href="{{ route('subscriptions.show', $sub->id) }}" class="fw-bold text-heading d-block text-truncate">{{ $sub->label }}</a>
                            <div class="text-muted small text-truncate">{{ $sub->account->email ?? 'Unlinked account' }}</div>
                            <div class="small fw-semibold text-primary">
                                {{ $sub->period }} &middot; {{ $sub->renewal_label }}
                            </div>
                        </div>
                        @if($row['via'])
                            <a href="{{ route('employees.show', $row['via']->id) }}" class="badge bg-light-primary text-primary text-decoration-none flex-shrink-0" title="Comes from {{ $row['via']->name }}'s access">
                                <i class="bi bi-person"></i> via {{ \Illuminate\Support\Str::words($row['via']->name, 1, '') }}
                            </a>
                        @else
                            <span class="badge bg-light-success text-success flex-shrink-0" title="Linked to this device directly"><i class="bi bi-pc-display"></i> Device</span>
                        @endif
                    </div>
                @empty
                    <div class="empty-state"><i class="bi bi-cloud-slash"></i>{{ $owner ? \Illuminate\Support\Str::words($owner->name, 2, '').' has no software licenses yet.' : 'No software assigned.' }}</div>
                @endforelse
            </div>
        </div>

        {{-- ===== COMPANY ACCOUNTS (through the owner) ===== --}}
        @if($owner)
            @php
                $ownerAccounts = $owner->accounts()->with(['subscriptions', 'users'])->get();
                // Apps whose license this person actually holds, to tell "has the login" from "has the seat".
                $heldSubIds = $ownerAllocations->pluck('subscription_id');
            @endphp
            <div class="tile card">
                <div class="tile-head">
                    <div>
                        <div class="tile-label"><i class="bi bi-person-badge"></i> Logins</div>
                        <h5 class="tile-title">Company accounts <span class="count-pill ms-1">{{ $ownerAccounts->count() }}</span></h5>
                        <small class="text-muted">Emails {{ \Illuminate\Support\Str::words($owner->name, 2, '') }} signs in with on this device</small>
                    </div>
                    <a href="{{ route('employees.show', $owner) }}" class="btn btn-sm btn-light-primary fw-bold rounded-pill"><i class="bi bi-pencil"></i> Manage</a>
                </div>
                @if($ownerAccounts->count())
                    <div class="account-grid">
                        @foreach($ownerAccounts as $acc)
                            @php
                                $apps = $acc->subscriptions->where('status', 'Active');
                                $others = $acc->users->where('id', '!=', $owner->id);
                            @endphp
                            <div class="account-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="row-icon"><i class="bi {{ str_ends_with($acc->email, 'gmail.com') ? 'bi-google' : (str_contains($acc->email, 'outlook') ? 'bi-microsoft' : 'bi-envelope') }}"></i></div>
                                    <div class="min-w-0 flex-grow-1">
                                        <a href="{{ route('accounts.show', $acc) }}" class="fw-bold text-heading d-block text-truncate" title="{{ $acc->email }}">{{ $acc->email }}</a>
                                        <small class="text-muted d-block text-truncate" title="{{ $others->pluck('name')->implode(', ') }}">
                                            {{ $others->count() ? 'Shared with '.$others->map(fn ($u) => \Illuminate\Support\Str::words($u->name, 1, ''))->take(3)->implode(', ').($others->count() > 3 ? ' +'.($others->count() - 3) : '') : 'Only '.\Illuminate\Support\Str::words($owner->name, 1, '') }}
                                        </small>
                                    </div>
                                </div>
                                <div class="account-apps">
                                    @forelse($apps as $app)
                                        <a href="{{ route('subscriptions.show', $app) }}"
                                           class="badge text-decoration-none {{ $heldSubIds->contains($app->id) ? 'bg-light-primary text-primary' : 'bg-light-secondary text-secondary' }}"
                                           title="{{ $heldSubIds->contains($app->id) ? 'Has a seat on this license' : 'Login only — no seat on this license' }}">{{ $app->label }}</a>
                                    @empty
                                        <small class="text-muted">No active licenses on this email</small>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state"><i class="bi bi-envelope-slash"></i>No company accounts linked to {{ \Illuminate\Support\Str::words($owner->name, 2, '') }} yet.</div>
                @endif
            </div>
        @endif

        {{-- ===== COMPONENTS (computers) ===== --}}
        @if($isComputer)
            <div class="tile card tile-xl-8">
                <div class="tile-head">
                    <div>
                        <div class="tile-label"><i class="bi bi-motherboard"></i> Installed hardware</div>
                        <h5 class="tile-title">System components <span class="count-pill ms-1">{{ $device->components->count() }}</span></h5>
                    </div>
                    <button data-bs-toggle="modal" data-bs-target="#attach-modal" class="btn btn-sm btn-light-primary fw-bold rounded-pill"><i class="bi bi-link-45deg"></i> Attach</button>
                </div>
                <div class="tile-scroll">
                    @forelse($device->components->sortBy('category') as $comp)
                        <div class="row-item">
                            <div class="row-icon"><i class="bi bi-cpu"></i></div>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('inventory.show', $comp->id) }}" class="fw-bold text-heading">{{ $comp->name }}</a>
                                <span class="text-muted small fw-semibold ms-1">{{ $comp->category }}</span>
                                <div class="text-muted small text-truncate">
                                    {{ collect([$comp->manufacturer, $comp->model, $comp->component_type, $comp->size])->filter()->implode(' · ') ?: '—' }}
                                </div>
                                @foreach($comp->activeIssues as $issue)
                                    <div class="comp-issue"><i class="bi bi-exclamation-triangle-fill"></i> {{ $issue->description }}</div>
                                @endforeach
                            </div>
                            @if($comp->current_health !== null)
                                <span class="badge {{ $comp->current_health < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} rounded-pill px-3 py-2">{{ $comp->current_health }}%</span>
                            @endif
                            <button type="button" class="btn btn-sm btn-light-danger rounded-pill px-2" title="Detach"
                                    data-bs-toggle="modal" data-bs-target="#detach-modal"
                                    data-action="{{ route('inventory.detach', $comp->id) }}"
                                    data-name="{{ $comp->name }}"
                                    data-detail="{{ collect([$comp->category, $comp->manufacturer, $comp->model])->filter()->implode(' · ') }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    @empty
                        <div class="empty-state"><i class="bi bi-cpu"></i>No internal components registered.</div>
                    @endforelse
                </div>
            </div>
        @elseif(! $isNetwork)
            {{-- ===== HEALTH LOG (non-computers) ===== --}}
            <div class="tile card tile-xl-8">
                <div class="tile-head">
                    <div>
                        <div class="tile-label"><i class="bi bi-activity"></i> Diagnostics</div>
                        <h5 class="tile-title">Health history</h5>
                    </div>
                    <button data-bs-toggle="modal" data-bs-target="#health-modal" class="btn btn-sm btn-light-success fw-bold rounded-pill"><i class="bi bi-plus-lg"></i> Log</button>
                </div>
                <div class="tile-scroll">
                    @php $previousHealth = null; @endphp
                    @forelse($device->healthRecords ?? [] as $record)
                        <div class="row-item">
                            <div class="row-icon"><i class="bi bi-heart-pulse"></i></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-heading">{{ $record->recorded_date->format('d M Y') }}</div>
                                <div class="small fw-semibold">
                                    @if($previousHealth === null)
                                        <span class="text-muted">Initial record</span>
                                    @else
                                        @php $drop = $previousHealth - $record->health_percentage; @endphp
                                        @if($drop > 0)
                                            <span class="text-danger"><i class="bi bi-arrow-down"></i> {{ $drop }}% drop</span>
                                        @elseif($drop < 0)
                                            <span class="text-success"><i class="bi bi-arrow-up"></i> +{{ abs($drop) }}% (replaced)</span>
                                        @else
                                            <span class="text-muted">Stable</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="text-end" style="width: 120px;">
                                <div class="fw-bold">{{ $record->health_percentage }}%</div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar {{ $record->health_percentage < $healthThreshold ? 'bg-danger' : 'bg-success' }}" style="width: {{ $record->health_percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                        @php $previousHealth = $record->health_percentage; @endphp
                    @empty
                        <div class="empty-state"><i class="bi bi-heart-pulse"></i>No health checks have been logged yet.</div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- ===== VALUE / COST ===== --}}
        <div class="tile card {{ $isNetwork ? '' : 'tile-xl-4' }}">
            <div class="row g-3 h-100">
                <div class="col-6 d-flex flex-column">
                    <div class="stat-icon bg-light-success text-success mb-3"><i class="bi bi-cash-stack"></i></div>
                    <div class="tile-label mb-1">Asset value</div>
                    <div class="stat-value" style="font-size: 1.15rem;">{{ $device->price ? 'Rp '.number_format($device->price, 0, ',', '.') : '—' }}</div>
                </div>
                <div class="col-6 d-flex flex-column">
                    <div class="stat-icon bg-light-danger text-danger mb-3"><i class="bi bi-tools"></i></div>
                    <div class="tile-label mb-1">Service cost</div>
                    <div class="stat-value" style="font-size: 1.15rem;">Rp {{ number_format($device->serviceHistories->sum('cost'), 0, ',', '.') }}</div>
                    <div class="stat-foot">{{ $services->total() }} service {{ \Illuminate\Support\Str::plural('record', $services->total()) }}</div>
                </div>
            </div>
        </div>

        {{-- ===== SERVICE LOG ===== --}}
        <div class="tile card tile-xl-6">
            <div class="tile-head">
                <div>
                    <div class="tile-label"><i class="bi bi-wrench-adjustable"></i> Maintenance</div>
                    <h5 class="tile-title">Service log <span class="count-pill ms-1">{{ $services->total() }}</span></h5>
                </div>
                <button data-bs-toggle="modal" data-bs-target="#service-modal" class="btn btn-sm btn-light-warning fw-bold rounded-pill"><i class="bi bi-plus-lg"></i> Log service</button>
            </div>
            <div id="ajax-service-container">
                @forelse($services as $history)
                    <div class="row-item align-items-start">
                        <div class="row-icon bg-light-warning text-warning"><i class="bi bi-tools"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold text-heading">{{ \Carbon\Carbon::parse($history->service_date)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ $history->description }}</div>
                        </div>
                        <span class="badge {{ $history->cost ? 'bg-light-danger text-danger' : 'bg-light-secondary text-secondary' }} rounded-pill px-3 py-2">
                            {{ $history->cost ? 'Rp '.number_format($history->cost, 0, ',', '.') : 'No cost' }}
                        </span>
                    </div>
                @empty
                    <div class="empty-state"><i class="bi bi-wrench"></i>No service records yet.</div>
                @endforelse
                @if($services->hasPages())
                    <div class="pt-3 mt-2 border-top">{{ $services->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ===== LIFECYCLE ===== --}}
        <div class="tile card tile-xl-6">
            <div class="tile-head">
                <div>
                    <div class="tile-label"><i class="bi bi-clock-history"></i> Audit trail</div>
                    <h5 class="tile-title">Lifecycle history <span class="count-pill ms-1">{{ $logs->total() }}</span></h5>
                </div>
            </div>
            <div id="ajax-lifecycle-container">
                @if($logs->count())
                    <div class="timeline">
                        @foreach($logs as $log)
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="fw-bold text-heading small text-uppercase">{{ $log->action }}</span>
                                    <span class="text-muted small text-nowrap">{{ $log->created_at->format('d M Y') }}</span>
                                </div>
                                <div class="text-muted small">{{ $log->description }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state"><i class="bi bi-inbox"></i>No history records yet.</div>
                @endif
                @if($logs->hasPages())
                    <div class="pt-3 mt-3 border-top">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ===== GALLERY ===== --}}
        @if($device->image1 || $device->image2 || $device->image3)
            <div class="tile card">
                <div class="tile-label"><i class="bi bi-images"></i> Gallery</div>
                <div class="row g-3">
                    @foreach(['image1', 'image2', 'image3'] as $img)
                        @if($device->$img)
                            <div class="col-12 col-md-4">
                                <img src="{{ asset('storage/' . $device->$img) }}" class="gallery-img" alt="{{ $device->name }}"
                                     data-bs-toggle="modal" data-bs-target="#image-lightbox-modal"
                                     onclick="document.getElementById('lightbox-image').src=this.src">
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <script>
        function togglePassword(btn) {
            const el = document.getElementById('pw-value');
            const hidden = el.classList.toggle('pw-mask');
            el.textContent = hidden ? '••••••••' : el.dataset.pw;
            btn.querySelector('i').className = hidden ? 'bi bi-eye' : 'bi bi-eye-slash';
        }
        function copyPassword(btn) {
            navigator.clipboard.writeText(document.getElementById('pw-value').dataset.pw).then(() => {
                const icon = btn.querySelector('i');
                icon.className = 'bi bi-check2';
                setTimeout(() => icon.className = 'bi bi-clipboard', 1500);
            });
        }
    </script>
@endsection

@section('modals')

    <div class="modal fade" id="issue-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('inventory.issues.store', $device) }}" method="POST" class="modal-content border-0 shadow-sm hr-modal">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add warning to {{ $device->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small">What's wrong?</label>
                        <input type="text" name="description" maxlength="255" class="form-control" placeholder="e.g. Keyboard huruf L rusak, Speaker pelan" required>
                    </div>
                    <div>
                        <label class="form-label text-muted fw-bold small">Noticed on</label>
                        <input type="date" name="reported_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" class="form-control" required>
                    </div>
                    @if($device->issues->whereNotNull('resolved_at')->count())
                        <div class="mt-4 pt-3 border-top">
                            <div class="text-muted fw-bold small mb-2">Previously resolved</div>
                            @foreach($device->issues->whereNotNull('resolved_at')->take(5) as $old)
                                <div class="small text-muted d-flex justify-content-between gap-2">
                                    <span class="text-decoration-line-through">{{ $old->description }}</span>
                                    <span class="text-nowrap">fixed {{ $old->resolved_at->format('d M Y') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white rounded-pill px-4 fw-bold">Add warning</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="detach-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="detach-form" class="modal-content border-0 shadow-sm hr-modal">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Detach component?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-4" style="background: rgba(239, 68, 68, .06);">
                        <div class="stat-icon bg-light-danger text-danger flex-shrink-0"><i class="bi bi-plug"></i></div>
                        <div class="min-w-0">
                            <div class="fw-bold text-heading" id="detach-name"></div>
                            <div class="text-muted small" id="detach-detail"></div>
                        </div>
                    </div>
                    <p class="text-muted small mb-4">
                        It will be removed from <strong>{{ $device->name }}</strong> and returned to stock. This is recorded in both assets' history.
                    </p>
                    <label class="form-label text-muted fw-bold small">Detach date</label>
                    <input type="date" name="action_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold"><i class="bi bi-x-lg me-1"></i> Detach</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="image-lightbox-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0 shadow-none">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0 mt-2">
                    <img id="lightbox-image" src="" class="img-fluid rounded shadow-lg border border-secondary" style="max-height: 85vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
            <form action="{{ route('inventory.update', $device->id) }}" method="POST" enctype="multipart/form-data" class="w-100 h-100">
                <div class="modal-content border-0 shadow-sm h-100 hr-modal">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Edit {{ $device->category }} Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    @csrf @method('PUT')
                    
                    <div class="modal-body p-4 p-md-5">
                        
                        @if(in_array($device->category, ['PC', 'Laptop']))
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Name</label>
                                    <input type="text" name="name" value="{{ $device->name }}" class="form-control" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Device Password</label>
                                    <input type="text" name="device_password" value="{{ $device->device_password }}" class="form-control">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Manufacturer</label>
                                    <input type="text" name="manufacturer" value="{{ $device->manufacturer }}" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Model</label>
                                    <input type="text" name="model" value="{{ $device->model }}" class="form-control">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" {{ $device->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Purchase Date</label>
                                    <input type="date" name="purchase_date" value="{{ $device->purchase_date ? $device->purchase_date->format('Y-m-d') : '' }}" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span>
                                        <input type="text" value="{{ $device->price ? number_format($device->price, 0) : '' }}" oninput="formatPrice(this, 'edit_price_actual')" class="form-control">
                                        <input type="hidden" name="price" id="edit_price_actual" value="{{ $device->price }}">
                                    </div>
                                </div>
                            </div>

                        @elseif($device->isNetwork())
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" value="{{ $device->name }}" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">IP Address</label><input type="text" name="ip_address" value="{{ $device->ip_address }}" class="form-control font-monospace"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" value="{{ $device->manufacturer }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" value="{{ $device->model }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Color</label><input type="text" name="color" value="{{ $device->color }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Location</label><input type="text" name="location" value="{{ $device->location }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Area / Division Served</label><input type="text" name="area" value="{{ $device->area }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Wi-Fi SSID</label><input type="text" name="ssid" value="{{ $device->ssid }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Wi-Fi Password</label><input type="text" name="ssid_password" value="{{ $device->ssid_password }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Admin Username</label><input type="text" name="admin_username" value="{{ $device->admin_username }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Admin Password</label><input type="text" name="device_password" value="{{ $device->device_password }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Status</label>
                                    <select name="in_use" class="form-select">
                                        <option value="1" {{ $device->in_use !== false ? 'selected' : '' }}>In use</option>
                                        <option value="0" {{ $device->in_use === false ? 'selected' : '' }}>Not in use</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Condition</label>
                                    <select name="condition" class="form-select">
                                        <option value="">-</option>
                                        @foreach(['Sangat Baik', 'Baik', 'OK', 'Cukup', 'Tidak diketahui'] as $cond)<option {{ $device->condition == $cond ? 'selected' : '' }}>{{ $cond }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Accessories</label><input type="text" name="accessories" value="{{ $device->accessories }}" class="form-control"></div>
                            </div>
                        @elseif($device->category == 'Phone')
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" value="{{ $device->name }}" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Device Password</label><input type="text" name="device_password" value="{{ $device->device_password }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" value="{{ $device->manufacturer }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" value="{{ $device->model }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp) <option value="{{ $emp->id }}" {{ $device->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">RAM</label><input type="text" name="ram" value="{{ $device->ram }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Storage</label><input type="text" name="rom" value="{{ $device->rom }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Battery Health %</label><input type="number" name="current_health" min="0" max="100" value="{{ $device->current_health }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">OS</label>
                                    <select name="os" class="form-select">
                                        <option value="">-</option>
                                        @foreach(['IOS', 'Android'] as $osOption)<option {{ $device->os == $osOption ? 'selected' : '' }}>{{ $osOption }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">OS Version</label><input type="text" name="os_version" value="{{ $device->os_version }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Processor</label><input type="text" name="processor" value="{{ $device->processor }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Color</label><input type="text" name="color" value="{{ $device->color }}" class="form-control"></div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Condition</label>
                                    <select name="condition" class="form-select">
                                        <option value="">-</option>
                                        @foreach(['Sangat Baik', 'Baik', 'OK', 'Cukup', 'Tidak diketahui'] as $cond)<option {{ $device->condition == $cond ? 'selected' : '' }}>{{ $cond }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Usability Tier (1-10)</label><input type="number" name="usability_tier" min="1" max="10" value="{{ $device->usability_tier }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12"><label class="form-label text-muted fw-bold small">Accessories (Kelengkapan)</label><input type="text" name="accessories" value="{{ $device->accessories }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" value="{{ $device->purchase_date ? $device->purchase_date->format('Y-m-d') : '' }}" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" value="{{ $device->price ? number_format($device->price, 0) : '' }}" oninput="formatPrice(this, 'edit_price_actual')" class="form-control"><input type="hidden" name="price" id="edit_price_actual" value="{{ $device->price }}">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" value="{{ $device->name }}" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Component Type</label><input type="text" name="component_type" value="{{ $device->component_type }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" value="{{ $device->manufacturer }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" value="{{ $device->model }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Size / Specs</label><input type="text" name="size" value="{{ $device->size }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Health %</label><input type="number" name="current_health" value="{{ $device->current_health }}" class="form-control"></div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" {{ $device->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Attach to Device</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach(\App\Models\Device::whereIn('category', ['PC', 'Laptop', 'Phone'])->get() as $targetDevice)
                                            <option value="{{ $targetDevice->id }}" {{ $device->parent_id == $targetDevice->id ? 'selected' : '' }}>{{ $targetDevice->name }} ({{ $targetDevice->category }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" value="{{ $device->purchase_date ? $device->purchase_date->format('Y-m-d') : '' }}" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" value="{{ $device->price ? number_format($device->price, 0) : '' }}" oninput="formatPrice(this, 'edit_price_actual')" class="form-control"><input type="hidden" name="price" id="edit_price_actual" value="{{ $device->price }}">
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($device->image1 || $device->image2 || $device->image3)
                        <div class="row g-3 border-top pt-3 mt-1 mb-3">
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold small">Current Gallery (Click X to remove)</label>
                                <div class="d-flex gap-3 mt-2">
                                    @foreach(['image1', 'image2', 'image3'] as $imgField)
                                        @if($device->$imgField)
                                            <div class="position-relative" id="preview-{{ $device->id }}-{{ $imgField }}">
                                                <img src="{{ asset('storage/' . $device->$imgField) }}" class="rounded shadow-sm border" style="width: 75px; height: 75px; object-fit: cover;">
                                                <button type="button" onclick="markImageForDeletion('{{ $device->id }}', '{{ $imgField }}')" class="btn btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; margin-left: -5px; margin-top: 5px;">
                                                    <i class="bi bi-x" style="font-size: 14px; line-height: 1;"></i>
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <div id="hidden-removals-{{ $device->id }}"></div>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3 @if(!$device->image1 && !$device->image2 && !$device->image3) border-top pt-3 mt-1 @endif">
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold small">Upload New Images (Max 3 - Auto-optimized to WEBP)</label>
                                <input type="file" name="images[]" multiple accept="image/*" class="form-control" onchange="validateFiles(this)">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end w-100">
                            <button type="button" class="btn btn-light-secondary rounded-pill order-2 order-md-1" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm order-1 order-md-2">Save Changes</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $partIcon = fn ($cat) => match ($cat) {
            'SSD', 'HDD' => 'bi-device-hdd', 'SD Card' => 'bi-sd-card', 'RAM' => 'bi-memory', 'GPU' => 'bi-gpu-card',
            'Processor' => 'bi-cpu', 'Motherboard' => 'bi-motherboard', 'Monitor' => 'bi-display', 'Keyboard' => 'bi-keyboard',
            'Mouse' => 'bi-mouse', 'Battery' => 'bi-battery-half', 'UPS', 'Power Supply' => 'bi-lightning-charge', 'OS' => 'bi-windows',
            default => 'bi-box',
        };
        $stockByCategory = $stockItems->groupBy('category');
    @endphp
    <div class="modal fade" id="attach-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
            <form action="{{ route('inventory.attach') }}" method="POST" class="modal-content border-0 shadow-sm hr-modal" id="attach-form">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $device->id }}">
                <div class="modal-header border-0 pb-2 flex-column align-items-stretch gap-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Install a part into {{ $device->name }}</h5>
                            <small class="text-muted">{{ $stockItems->count() }} parts available in stock</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%);"></i>
                        <input type="search" id="part-search" class="form-control rounded-pill ps-5" placeholder="Search name, brand, model, size… e.g. 500 GB, Samsung, rusak" autocomplete="off">
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="part-filters">
                        <button type="button" class="part-filter active" data-cat="">All <span>{{ $stockItems->count() }}</span></button>
                        @foreach($stockByCategory as $cat => $items)
                            <button type="button" class="part-filter" data-cat="{{ $cat }}"><i class="bi {{ $partIcon($cat) }}"></i> {{ $cat }} <span>{{ $items->count() }}</span></button>
                        @endforeach
                    </div>
                </div>

                <div class="modal-body pt-2">
                    <div id="part-list" class="d-flex flex-column gap-2">
                        @forelse($stockItems as $stock)
                            @php
                                // Skip the brand when the model already starts with it ("Seagate FireCuda 530").
                                $meta = $stock->manufacturer && $stock->model && stripos($stock->model, $stock->manufacturer) === 0
                                    ? $stock->model
                                    : collect([$stock->manufacturer, $stock->model])->filter()->implode(' ');
                                $specs = collect([$stock->component_type, $stock->size, $stock->area ? 'for '.$stock->area : null])->filter();
                                $issues = $stock->activeIssues;
                                $search = strtolower(implode(' ', [$stock->name, $stock->category, $meta, $specs->implode(' '), $stock->condition, $issues->pluck('description')->implode(' ')]));
                            @endphp
                            <label class="part-option {{ $issues->count() ? 'has-issue' : '' }}" data-cat="{{ $stock->category }}" data-search="{{ $search }}">
                                <input type="radio" name="child_id" value="{{ $stock->id }}" class="d-none" data-name="{{ $stock->name }}" required>
                                <div class="part-icon"><i class="bi {{ $partIcon($stock->category) }}"></i></div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-bold text-heading">{{ $stock->name }}</span>
                                        <span class="badge bg-light-secondary text-secondary">{{ $stock->category }}</span>
                                    </div>
                                    <div class="small text-truncate">{{ $meta ?: 'No brand / model recorded' }}</div>
                                    @if($specs->count())<div class="small text-muted">{{ $specs->implode(' · ') }}</div>@endif
                                    @foreach($issues as $issue)
                                        <div class="small fw-semibold text-warning"><i class="bi bi-exclamation-triangle-fill"></i> {{ $issue->description }}</div>
                                    @endforeach
                                </div>
                                <div class="part-side">
                                    @if(!is_null($stock->current_health))
                                        <span class="badge {{ $stock->current_health < 50 ? 'bg-light-danger text-danger' : ($stock->current_health < 80 ? 'bg-light-warning text-warning' : 'bg-light-success text-success') }} px-2 py-1">{{ $stock->current_health }}%</span>
                                    @elseif($stock->condition)
                                        <span class="badge {{ strtolower($stock->condition) === 'rusak' ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-2 py-1">{{ $stock->condition }}</span>
                                    @endif
                                    <div class="part-check"><i class="bi bi-check-lg"></i></div>
                                </div>
                            </label>
                        @empty
                            <div class="empty-state"><i class="bi bi-box-seam"></i>No parts in stock. Register one first, or detach it from another device.</div>
                        @endforelse
                        <div class="empty-state d-none" id="part-empty"><i class="bi bi-search"></i>No parts match your search.</div>
                    </div>
                </div>

                <div class="modal-footer border-0 d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                    <div class="d-flex align-items-center gap-2 me-sm-auto">
                        <label class="form-label text-muted fw-bold small mb-0 text-nowrap" for="attach-date">Installed on</label>
                        <input type="date" id="attach-date" name="action_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
                    </div>
                    <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="attach-submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" disabled>Select a part</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="service-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Log Maintenance/Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('inventory.service', $device->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row mb-4">
                            <div class="col-6">
                                <label class="form-label text-muted fw-bold small">Service Date</label>
                                <input type="date" name="log_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted fw-bold small">Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp.</span>
                                    <input type="number" step="0.01" name="cost" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small">Description of Work</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="e.g., Replaced cracked screen..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning rounded-pill text-white px-4 fw-bold shadow-sm">Save Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="health-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Log Health Diagnostic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('inventory.health', $device->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold small">Diagnostic Date</label>
                            <input type="date" name="recorded_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small">New Health %</label>
                            <input type="number" name="health_percentage" value="{{ $device->current_health }}" class="form-control" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm text-white">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        function setupAjaxPagination(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            container.addEventListener('click', function (e) {
                const link = e.target.closest('a.page-link');
                if (link && link.href) {
                    e.preventDefault();
                    container.style.opacity = '0.5';
                    container.style.pointerEvents = 'none';

                    fetch(link.href)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            container.innerHTML = doc.getElementById(containerId).innerHTML;
                            container.style.opacity = '1';
                            container.style.pointerEvents = 'auto';
                            window.history.pushState(null, '', link.href);
                        })
                        .catch(error => {
                            console.error('AJAX Pagination Error:', error);
                            container.style.opacity = '1';
                            container.style.pointerEvents = 'auto';
                        });
                }
            });
        }

        // ---- Attach part picker: search + category chips + selectable cards ----
        const partSearch = document.getElementById('part-search');
        if (partSearch) {
            const options = [...document.querySelectorAll('.part-option')];
            const chips = [...document.querySelectorAll('.part-filter')];
            const submit = document.getElementById('attach-submit');
            let activeCat = '';

            const applyFilter = () => {
                const terms = partSearch.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
                let shown = 0;
                options.forEach(opt => {
                    const match = (!activeCat || opt.dataset.cat === activeCat)
                        && terms.every(t => opt.dataset.search.includes(t));
                    opt.classList.toggle('d-none', !match);
                    if (match) shown++;
                });
                document.getElementById('part-empty').classList.toggle('d-none', shown > 0 || options.length === 0);
            };

            partSearch.addEventListener('input', applyFilter);
            chips.forEach(chip => chip.addEventListener('click', () => {
                activeCat = chip.dataset.cat;
                chips.forEach(c => c.classList.toggle('active', c === chip));
                applyFilter();
            }));
            options.forEach(opt => opt.querySelector('input').addEventListener('change', e => {
                options.forEach(o => o.classList.toggle('selected', o === opt));
                submit.disabled = false;
                submit.textContent = 'Install ' + e.target.dataset.name;
            }));
            document.getElementById('attach-modal').addEventListener('shown.bs.modal', () => partSearch.focus());
        }

        // One shared detach modal: fill it from the clicked component's button.
        const detachModal = document.getElementById('detach-modal');
        if (detachModal) {
            detachModal.addEventListener('show.bs.modal', function (e) {
                const btn = e.relatedTarget;
                document.getElementById('detach-form').action = btn.dataset.action;
                document.getElementById('detach-name').textContent = btn.dataset.name;
                document.getElementById('detach-detail').textContent = btn.dataset.detail;
            });
        }

        setupAjaxPagination('ajax-service-container');
        setupAjaxPagination('ajax-lifecycle-container');
    });

    // Price formatting logic handling dynamic hidden field IDs
    function formatPrice(input, hiddenFieldId) {
        let value = input.value.replace(/\D/g, "");
        if (value !== "") {
            input.value = parseInt(value).toLocaleString('en-US');
            if(document.getElementById(hiddenFieldId)) {
                document.getElementById(hiddenFieldId).value = value;
            }
        } else {
            if(document.getElementById(hiddenFieldId)) {
                document.getElementById(hiddenFieldId).value = "";
            }
        }
    }

    // File count validation
    function validateFiles(input) {
        if (input.files.length > 3) {
            alert("Maximum 3 images allowed!");
            input.value = "";
        }
    }

    // Hide image and append to hidden removals list
    function markImageForDeletion(itemId, imageField) {
        document.getElementById('preview-' + itemId + '-' + imageField).style.display = 'none';
        const hiddenContainer = document.getElementById('hidden-removals-' + itemId);
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_images[]';
        input.value = imageField;
        hiddenContainer.appendChild(input);
    }
    </script>
@endsection