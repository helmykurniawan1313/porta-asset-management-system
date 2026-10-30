{{--
    Shared shell for every Brand Health screen (ported from Brand Matrix).
    Pages extend this and fill: bh-content, and optionally bh-styles,
    bh-scripts, bh-modals. Provides Brand Matrix's status-tone colours (light +
    dark), common card/table/filter styles, Chart.js, and window.BH helpers.
--}}
@extends('layouts.app')

@section('styles')
<style>
    :root {
        --bh-surface: #ffffff; --bh-raised: #f8fafc; --bh-border: #e5e9f0; --bh-border-strong: #cfd6e0;
        --bh-ink: #1f2937; --bh-ink-muted: #5b6474; --bh-ink-faint: #8a94a6; --bh-accent: var(--porta-accent); --bh-accent-soft: var(--porta-accent-soft);
        --status-sip-bg: #e2f3ec; --status-sip-ink: #146c48;
        --status-bagus-bg: #eaf3d9; --status-bagus-ink: #4d6b17;
        --status-cukup-bg: #fdf1d7; --status-cukup-ink: #92650a;
        --status-kurang-bg: #fbe6d8; --status-kurang-ink: #9a4816;
        --status-parah-bg: #fbe2e2; --status-parah-ink: #a1282a;
    }
    [data-bs-theme="dark"] {
        --bh-surface: #1e1e2d; --bh-raised: #232336; --bh-border: rgba(255, 255, 255, .08); --bh-border-strong: rgba(255, 255, 255, .16);
        --bh-ink: #e5e7eb; --bh-ink-muted: #a1a8b8; --bh-ink-faint: #6f7790; --bh-accent: var(--porta-accent); --bh-accent-soft: var(--porta-accent-soft);
        --status-sip-bg: #123528; --status-sip-ink: #7fe0b3;
        --status-bagus-bg: #2a350f; --status-bagus-ink: #c3e079;
        --status-cukup-bg: #3a2c0c; --status-cukup-ink: #f0c869;
        --status-kurang-bg: #3a220f; --status-kurang-ink: #f3a877;
        --status-parah-bg: #3a1517; --status-parah-ink: #f29b9d;
    }

    .bh-status { display: inline-flex; align-items: center; gap: .4rem; border-radius: 999px; padding: .2rem .65rem; font-size: .75rem; font-weight: 600; white-space: nowrap; }
    .bh-status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

    .bh-card { background: var(--bh-surface); border: 1px solid var(--bh-border); border-radius: 16px; padding: 1.25rem; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
    .bh-card + .bh-card { margin-top: 1rem; }
    .bh-stat-label { font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--bh-ink-faint); }
    .bh-stat-value { font-size: 1.6rem; font-weight: 800; line-height: 1.15; color: var(--bh-ink); font-variant-numeric: tabular-nums; margin-top: .25rem; }
    .bh-stat-hint { font-size: .75rem; color: var(--bh-ink-muted); margin-top: .15rem; }
    .bh-muted { color: var(--bh-ink-muted) !important; }
    .bh-faint { color: var(--bh-ink-faint) !important; }
    .bh-num { font-variant-numeric: tabular-nums; }

    .bh-page-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 1.25rem; }
    .bh-page-head h3 { margin: 0; font-weight: 800; }
    .bh-page-head p { margin: .2rem 0 0; color: var(--bh-ink-muted); }

    .bh-filters { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .bh-filters .form-select, .bh-filters .form-control { width: auto; min-width: 150px; }
    .bh-filters .choices { min-width: 180px; }

    .bh-table { width: 100%; font-size: .875rem; }
    .bh-table thead th { font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--bh-ink-faint); border-bottom: 1px solid var(--bh-border); padding: .65rem .75rem; white-space: nowrap; background: transparent; }
    .bh-table tbody td { padding: .7rem .75rem; border-bottom: 1px solid var(--bh-border); vertical-align: middle; color: var(--bh-ink); }
    .bh-table tbody tr:hover td { background: var(--bh-accent-soft); }
    .bh-table th.sortable { cursor: pointer; user-select: none; }
    .bh-table th.sortable:hover { color: var(--bh-ink); }
    .bh-table th.sorted { color: var(--bh-accent); }

    .bh-pill { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; padding: .15rem .6rem; font-size: .72rem; font-weight: 600; background: var(--bh-accent-soft); color: var(--bh-accent); white-space: nowrap; }
    .bh-delta-up { color: var(--status-sip-ink); font-weight: 600; }
    .bh-delta-down { color: var(--status-parah-ink); font-weight: 600; }
    .bh-chart { position: relative; width: 100%; }
    .bh-empty { text-align: center; color: var(--bh-ink-faint); padding: 2.5rem 1rem; }
    .bh-empty .bi { display: block; font-size: 2rem; opacity: .4; margin-bottom: .4rem; }
    .bh-section-title { font-weight: 700; font-size: 1rem; margin: 0 0 .75rem; }
    /* Status banner (e.g. "90 cycles need attention"); colours come from inline status-tone vars. */
    .bh-alert-tile { display: flex; align-items: center; gap: .9rem; border-radius: 14px; padding: .9rem 1.1rem; border: 1px solid; margin-top: .75rem; text-decoration: none; transition: filter .15s ease; }
    .bh-alert-tile > .bi { font-size: 1.6rem; line-height: 1; flex-shrink: 0; }
    .bh-alert-tile > .bi::before { vertical-align: 0; }
    a.bh-alert-tile:hover { filter: brightness(.97); }
    .bi::before { vertical-align: -.1em; }

    [data-bs-theme="dark"] .bg-light-primary,   [data-bs-theme="dark"] .btn-light-primary   { background-color: rgba(129, 140, 248, .16) !important; color: #a5b4fc !important; }
    [data-bs-theme="dark"] .bg-light-success,   [data-bs-theme="dark"] .btn-light-success   { background-color: rgba(34, 197, 94, .16) !important;  color: #4ade80 !important; }
    [data-bs-theme="dark"] .bg-light-warning,   [data-bs-theme="dark"] .btn-light-warning   { background-color: rgba(245, 158, 11, .16) !important; color: #fbbf24 !important; }
    [data-bs-theme="dark"] .bg-light-danger,    [data-bs-theme="dark"] .btn-light-danger    { background-color: rgba(239, 68, 68, .16) !important;  color: #f87171 !important; }
    [data-bs-theme="dark"] .bg-light-secondary, [data-bs-theme="dark"] .btn-light-secondary { background-color: rgba(148, 163, 184, .14) !important; color: #cbd5e1 !important; }
</style>
@yield('bh-styles')
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-light-success color-success d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('ig_success'))
        <div class="alert alert-light-success color-success d-flex align-items-center gap-2 mb-4"><i class="bi bi-instagram"></i> {{ session('ig_success') }}</div>
    @endif
    @if(session('ig_error'))
        <div class="alert alert-light-danger color-danger d-flex align-items-center gap-2 mb-4"><i class="bi bi-instagram"></i> {{ session('ig_error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-light-danger color-danger mb-4">
            <i class="bi bi-exclamation-triangle"></i> {{ $errors->first() }}
        </div>
    @endif
    @yield('bh-content')
@endsection

@section('modals')
    @yield('bh-modals')
@endsection

@section('scripts')
<script src="{{ asset('assets/extensions/chart.js/chart.umd.js') }}"></script>
<script>
    /** Shared helpers for Brand Health screens (mirrors Brand Matrix's JS conventions). */
    window.BH = (function () {
        const nf = new Intl.NumberFormat('en-US');
        const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

        return {
            canEdit: @json(auth()->user()?->can('edit-brand-health') ?? false),
            labelBuckets: @json($bhLabelBuckets ?? []),

            num(v) { return v === null || v === undefined || v === '' ? '—' : nf.format(v); },
            dec(v, d = 2) { return v === null || v === undefined || v === '' ? '—' : Number(v).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d }); },
            pct(v, d = 2) { return v === null || v === undefined || v === '' ? '—' : Number(v).toFixed(d) + '%'; },
            date(v) { return v ? new Date(v).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'; },
            month(ym) { if (!ym) return '—'; const [y, m] = String(ym).split('-'); return new Date(+y, +m - 1, 1).toLocaleString('en-US', { month: 'short', year: 'numeric' }); },
            esc(s) { return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); },

            /** Colour {bg, ink} for a status label — same rules as the bh.status Blade component. */
            statusTone(label) {
                const tones = ['sip', 'bagus', 'cukup', 'kurang', 'parah'];
                if (!label) return { bg: 'var(--bh-border)', ink: 'var(--bh-ink-muted)' };
                const up = String(label).toUpperCase();
                const match = this.labelBuckets.find((b) => String(b.label || '').toUpperCase() === up);
                if (!match) return { bg: 'var(--bh-border)', ink: 'var(--bh-ink-muted)' };
                if (match.color === 'custom' && match.custom_color_bg && match.custom_color_ink) return { bg: match.custom_color_bg, ink: match.custom_color_ink };
                const rank = this.labelBuckets.filter((b) => b.metric === match.metric)
                    .sort((a, b) => (b.min_score ?? -Infinity) - (a.min_score ?? -Infinity))
                    .findIndex((b) => b.id === match.id);
                const tone = tones.includes(match.color) ? match.color : tones[Math.min(Math.max(rank, 0), 4)];
                return { bg: `var(--status-${tone}-bg)`, ink: `var(--status-${tone}-ink)` };
            },
            statusBadge(label) {
                const t = this.statusTone(label);
                return `<span class="bh-status" style="background-color:${t.bg};color:${t.ink}"><span class="bh-status-dot" style="background-color:${t.ink}"></span>${this.esc(label ?? 'No data')}</span>`;
            },

            /** fetch() returning JSON, with CSRF + Laravel validation errors surfaced. */
            async request(url, { method = 'GET', body = null } = {}) {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        ...(body && !(body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
                    },
                    body: body ? (body instanceof FormData ? body : JSON.stringify(body)) : null,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const msg = data.errors ? Object.values(data.errors).flat()[0] : (data.message || `Request failed (${res.status})`);
                    throw new Error(msg);
                }
                return data;
            },

            /** Theme-aware Chart.js defaults; returns the Chart instance. */
            chart(canvas, config) {
                Chart.defaults.color = css('--bh-ink-muted');
                Chart.defaults.borderColor = css('--bh-border');
                Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
                const existing = Chart.getChart(canvas);
                if (existing) existing.destroy();
                return new Chart(canvas, config);
            },
            palette: ['#435ebe', '#16a34a', '#f59e0b', '#e11d48', '#0891b2', '#7c3aed', '#db2777', '#65a30d'],

            /** Build a query string from a plain object, dropping empty values. */
            qs(params) {
                const u = new URLSearchParams();
                Object.entries(params).forEach(([k, v]) => {
                    if (Array.isArray(v)) v.forEach((x) => u.append(k + '[]', x));
                    else if (v !== null && v !== undefined && v !== '') u.append(k, v);
                });
                const s = u.toString();
                return s ? '?' + s : '';
            },
        };
    })();

    // Stacked modals (e.g. a drill-down over a detail modal): lift each newly opened
    // modal and its backdrop above the ones already open, and keep the body locked
    // while any modal is still showing.
    document.addEventListener('show.bs.modal', (e) => {
        const open = document.querySelectorAll('.modal.show').length;
        if (!open) return;
        e.target.style.zIndex = 1055 + open * 20;
        setTimeout(() => { const b = [...document.querySelectorAll('.modal-backdrop')].pop(); if (b) b.style.zIndex = 1050 + open * 20; });
    });
    document.addEventListener('hidden.bs.modal', (e) => {
        e.target.style.zIndex = '';
        if (document.querySelector('.modal.show')) document.body.classList.add('modal-open');
    });
</script>
@yield('bh-scripts')
@endsection
