{{--
    Account Growth drill-down — Blade/JS port of AccountGrowthModal.vue, shared by
    Accounts, Account Health and Account Growth. Open with:
        BH.openAccountGrowth({ id, name }, 'instagram' | 'tiktok')
--}}
<style>
    #account-growth-modal .modal-dialog { max-width: min(1140px, 95vw); }
    #account-growth-modal .modal-content { height: 95vh; }
    .ag-tabs { display: flex; gap: 1.5rem; margin-top: 1rem; margin-bottom: -1rem; }
    .ag-tabs button { border: 0; background: none; padding: 0 0 .65rem; font-weight: 600; color: var(--bh-ink-muted); border-bottom: 2px solid transparent; }
    .ag-tabs button.active { color: var(--bh-accent); border-bottom-color: var(--bh-accent); }
    .ag-bar { background: var(--bh-raised); border: 1px solid var(--bh-border); border-radius: 12px; padding: .7rem 1rem; display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; align-items: center; font-size: .8rem; }
    .ag-chip { border: 2px solid var(--bh-border); background: var(--bh-raised); border-radius: 12px; padding: .7rem; text-align: left; width: 100%; height: 100%; }
    .ag-chip.active { border-color: var(--bh-accent); background: var(--bh-accent-soft); }
    .ag-chip .lbl { font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--bh-ink-faint); }
    .ag-chip.active .lbl { color: var(--bh-accent); }
    .ag-chip .num { font-size: 1.15rem; font-weight: 800; font-variant-numeric: tabular-nums; margin-top: .2rem; }
    .ag-tile { border: 1px solid var(--bh-border); border-radius: 12px; padding: 1rem; background: var(--bh-raised); height: 100%; }
    .ag-up { color: var(--status-sip-ink); } .ag-down { color: var(--status-parah-ink); }
    .ag-range { display: inline-flex; gap: .3rem; align-items: center; background: var(--bh-raised); border-radius: 10px; padding: .25rem; }
</style>

<div class="modal fade" id="account-growth-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header d-block border-bottom pb-3">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h5 class="modal-title fw-bold text-truncate" data-ag-name></h5>
                            <span data-ag-health></span>
                        </div>
                        <div class="small bh-muted" data-ag-sub>Performance trends over time</div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-light-secondary d-none" data-ag-pdf><i class="bi bi-download"></i> <span class="d-none d-sm-inline">Download PDF</span></button>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <p class="small mb-0 mt-2 d-none" style="color: var(--status-parah-ink)" data-ag-export-error></p>
                <div class="ag-tabs d-none" data-ag-tabs>
                    <button type="button" data-ag-tab="instagram">Instagram</button>
                    <button type="button" data-ag-tab="tiktok">TikTok</button>
                    <button type="button" data-ag-tab="ai-summary">AI Summary</button>
                </div>
            </div>
            <div class="modal-body" data-ag-body></div>
        </div>
    </div>
</div>

<script>
// Runs after the layout's BH helpers (defined later in the page) are available.
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('account-growth-modal');
    const $ = (s) => el.querySelector(s);
    const body = $('[data-ag-body]');
    const URLS = {
        growth: (id) => @json(route('brand-health.accounts.growth', '__ID__')).replace('__ID__', id),
        summarize: (id) => @json(route('brand-health.accounts.summarize', '__ID__')).replace('__ID__', id),
        pdf: (id) => @json(route('brand-health.accounts.pdf', '__ID__')).replace('__ID__', id),
    };
    const DEFAULT_PROVIDER = @json(config('services.brand_health.ai_summary.provider', 'groq'));
    const css = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
    const palette = () => [css('--bh-accent') || '#435ebe', '#c2410c', '#7c3aed'];
    const nf = new Intl.NumberFormat('en-US');
    const count = (v) => nf.format(v ?? 0);
    const compact = (v) => (v === null || v === undefined ? '—' : new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 }).format(v));
    const pctChange = (from, to) => (from == null || to == null || from === 0 ? null : ((to - from) / from) * 100);
    const fmtDelta = (d) => { if (d === null) return null; const r = Math.round(d * 10) / 10; return `${r > 0 ? '+' : ''}${r}%`; };
    const deltaHtml = (d, withArrow = true) => d === null ? '<span class="bh-faint">—</span>'
        : `<span class="${d >= 0 ? 'ag-up' : 'ag-down'} fw-semibold">${withArrow ? (d >= 0 ? '▲ ' : '▼ ') : ''}${fmtDelta(d)}</span>`;
    const fmtDate = (v) => (v ? new Date(v).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : null);
    const growthTone = (label) => {
        const t = { sip: 'sip', good: 'bagus', bagus: 'bagus', cukup: 'cukup', 'need attention': 'parah', 'perlu perhatian': 'parah' }[String(label ?? '').toLowerCase()];
        return t ? `background: var(--status-${t}-bg); color: var(--status-${t}-ink)` : 'background: var(--bh-border); color: var(--bh-ink-muted)';
    };

    const S = {};   // modal state
    const charts = {};

    function reset(account, platform) {
        Object.values(charts).forEach((c) => c?.destroy());
        Object.assign(S, {
            account, tab: platform === 'tiktok' ? 'tiktok' : 'instagram', range: 12, from: '', to: '',
            metric: 'end_follower', cycles: [], postSummary: {}, changeBuckets: { views: [], reach: [], engagement: [] },
            pmHistory: [], showPm: false, summary: null, loading: true, error: null,
        });
    }

    // ---- derived state (same rules as the Vue component) ----
    const allCycles = () => S.cycles.filter((c) => (c.platform ?? 'instagram') === S.tab);
    const hasRange = () => !!S.from && !!S.to;
    const cycleForMonth = (m) => { if (!m) return null; let best = null; for (const c of allCycles()) { const cm = c.cycle_start_date.slice(0, 7); if (cm <= m && (!best || cm > best.cycle_start_date.slice(0, 7))) best = c; } return best; };
    const fromCycle = () => cycleForMonth(S.from);
    const toCycle = () => cycleForMonth(S.to);
    const activeCycles = () => {
        const all = allCycles();
        if (hasRange() && fromCycle() && toCycle()) { const a = all.indexOf(fromCycle()), b = all.indexOf(toCycle()); return all.slice(Math.min(a, b), Math.max(a, b) + 1); }
        return S.range === 'all' ? all : all.slice(-S.range);
    };
    const latest = () => (hasRange() && toCycle()) ? toCycle() : (allCycles().at(-1) ?? null);
    const previous = () => (hasRange() && fromCycle()) ? fromCycle() : (allCycles().at(-2) ?? null);
    const resolveLabel = (buckets, rate) => { let w = null, wm = -Infinity; for (const b of buckets ?? []) { const m = b.min_score === null ? -Infinity : Number(b.min_score); if (m > rate) continue; if (w === null || m > wm) { w = b; wm = m; } } return w?.label ?? null; };
    const analysis = () => {
        const f = previous(), t = latest();
        if (!f || !t || f.end_follower === null || t.end_follower === null || f === t) return null;
        const v = pctChange(f.views, t.views), r = pctChange(f.reach, t.reach), e = pctChange(f.engagement, t.engagement);
        return { followerChangeRate: f.end_follower !== 0 ? ((t.end_follower - f.end_follower) / f.end_follower) * 100 : null,
            views: v === null ? null : resolveLabel(S.changeBuckets.views, v), reach: r === null ? null : resolveLabel(S.changeBuckets.reach, r), engagement: e === null ? null : resolveLabel(S.changeBuckets.engagement, e) };
    };
    const deltaFor = (key) => { const l = latest(), p = previous(); if (!l || !p) return null; const c = l[key], pr = p[key]; if (c == null || pr == null || pr === 0) return null; return ((c - pr) / pr) * 100; };
    const reachLabel = () => (S.tab === 'tiktok' ? 'Profile Views' : 'Reach');
    const metrics = () => [
        { key: 'end_follower', label: 'Followers' }, { key: 'views', label: 'Views', change: true },
        { key: 'reach', label: reachLabel(), change: true }, { key: 'engagement', label: 'Engagement', change: true },
        { key: 'ads-spend', label: 'Ads Spend', valueKey: 'total_ads_spend' },
    ];
    const chartConfigs = () => ({
        end_follower: { key: 'end_follower', label: 'Followers' }, views: { key: 'views', label: 'Views' },
        reach: { key: 'reach', label: reachLabel() }, engagement: { key: 'engagement', label: 'Engagement' },
        scores: { key: 'aggregate-scores', label: 'Visibility / Engagement / Health', series: [{ key: 'visibility_rate', label: 'Visibility' }, { key: 'engagement_score', label: 'Engagement' }, { key: 'health_rate', label: 'Health' }] },
        'ads-spend': { key: 'ads-spend', label: 'Ads Spend', series: [{ key: 'reach_views_ads_spend', label: 'Reach/Views Ads' }, { key: 'engagement_ads_spend', label: 'Engagement Ads' }] },
    });

    function chartConfigFor(config, cycles, forExport = false) {
        const colors = palette();
        const series = config.series ?? [{ key: config.key, label: config.label }];
        return {
            type: 'line',
            data: { labels: cycles.map((c) => c.label), datasets: series.map((s, i) => ({ label: s.label, data: cycles.map((c) => c[s.key]),
                borderColor: colors[i % 3], backgroundColor: colors[i % 3], pointBackgroundColor: colors[i % 3], pointBorderColor: forExport ? '#fff' : css('--bh-surface'),
                pointBorderWidth: 2, pointRadius: forExport ? 3 : 4, borderWidth: 2, tension: .3, fill: false })) },
            options: { responsive: !forExport, maintainAspectRatio: false, animation: forExport ? false : undefined,
                plugins: { legend: { display: series.length > 1, labels: { usePointStyle: true, color: forExport ? '#12181a' : undefined } },
                    tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${nf.format(c.parsed.y)}` } } },
                scales: { x: { grid: { display: forExport }, ticks: forExport ? { color: '#8b979b' } : {} }, y: { ticks: { callback: (v) => nf.format(v), ...(forExport ? { color: '#8b979b' } : {}) } } } },
        };
    }

    // ---- rendering ----
    function header() {
        $('[data-ag-name]').textContent = S.account.name;
        const showing = !S.loading && !S.error && S.tab !== 'ai-summary' && latest();
        $('[data-ag-health]').innerHTML = showing && latest().health_label ? BH.statusBadge(latest().health_label) : '';
        $('[data-ag-sub]').innerHTML = showing ? `${hasRange() ? 'Viewing' : 'Latest cycle'}: <b>${BH.esc(latest().label)}</b>` : 'Performance trends over time';
        $('[data-ag-pdf]').classList.toggle('d-none', S.loading || !!S.error || S.tab === 'ai-summary');
        $('[data-ag-tabs]').classList.toggle('d-none', S.loading || !!S.error);
        el.querySelectorAll('[data-ag-tab]').forEach((b) => b.classList.toggle('active', b.dataset.agTab === S.tab));
    }

    function render() {
        header();
        Object.values(charts).forEach((c) => c?.destroy());
        if (S.loading) { body.innerHTML = '<p class="bh-empty">Loading…</p>'; return; }
        if (S.error) { body.innerHTML = `<p class="bh-empty">${BH.esc(S.error)}</p>`; return; }
        if (S.tab === 'ai-summary') return renderSummaryTab();
        if (!allCycles().length) { body.innerHTML = `<p class="bh-empty">No ${S.tab === 'tiktok' ? 'TikTok' : 'Instagram'} cycles recorded for this account yet.</p>`; return; }

        const l = latest(), p = previous(), a = analysis(), cycles = activeCycles();
        const dateRange = hasRange() && p && l ? `${fmtDate(p.cycle_start_date)} – ${fmtDate(l.cycle_end_date)}` : (l?.cycle_start_date ? `${fmtDate(l.cycle_start_date)}${l.cycle_end_date ? ' – ' + fmtDate(l.cycle_end_date) : ''}` : '—');
        const m = metrics().find((x) => x.key === S.metric) ?? metrics()[0];
        const changeLabel = m.change && a ? a[m.key] : null;
        const ci = contentInsight(cycles, a);

        body.innerHTML = `
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="bh-faint">${hasRange() && !a ? 'No cycle data found at or before one of the selected months.' : ''}</small>
            <div class="ag-range">
                ${!hasRange() && allCycles().length > 6 ? `<select class="form-select form-select-sm border-0 bg-transparent" style="width:auto" data-ag-range data-no-search>
                    ${[[6, 'Last 6 cycles'], [12, 'Last 12 cycles'], ['all', 'All cycles']].map(([v, t]) => `<option value="${v}" ${String(S.range) === String(v) ? 'selected' : ''}>${t}</option>`).join('')}</select>` : ''}
                <i class="bi bi-calendar3 bh-faint ms-1"></i>
                <input type="month" class="form-control form-control-sm" style="width: 9.5rem" value="${S.from}" data-ag-from title="From month">
                <span class="bh-faint">→</span>
                <input type="month" class="form-control form-control-sm" style="width: 9.5rem" value="${S.to}" data-ag-to title="To month">
                ${S.from || S.to ? '<button type="button" class="btn btn-sm btn-link text-decoration-none bh-muted" data-ag-clear>Clear</button>' : ''}
            </div>
        </div>

        <div class="ag-bar mt-3">
            <span><i class="bi bi-calendar3 bh-faint"></i> <b class="bh-num">${dateRange}</b></span>
            <span class="d-flex align-items-center gap-2"><span class="bh-faint">Health</span>${l?.health_label ? BH.statusBadge(l.health_label) : '<span class="bh-faint">—</span>'}</span>
            <span><i class="bi bi-person bh-faint"></i> <span class="bh-faint">PM</span> <b>${BH.esc(l?.project_manager_name || '—')}</b>
                ${S.pmHistory.length > 1 ? `<a href="#" class="ms-1 fw-semibold" data-ag-pm>${S.showPm ? 'Hide history' : 'History'}</a>` : ''}</span>
        </div>
        ${S.showPm && S.pmHistory.length > 1 ? `<div class="ag-bar mt-2 d-block"><div class="bh-stat-label mb-2">PM History</div>
            ${[...S.pmHistory].reverse().map((r, i) => `<div class="d-flex align-items-center gap-2 small mb-1"><span class="bh-status-dot" style="background:${i === 0 ? 'var(--bh-accent)' : 'var(--bh-ink-faint)'}"></span><b>${BH.esc(r.project_manager_name || 'No PM')}</b><span class="bh-faint">${fmtDate(r.from)} – ${fmtDate(r.to)}</span></div>`).join('')}</div>` : ''}

        <div class="bh-card mt-3">
            <div class="row g-2">
                ${metrics().map((x) => { const d = deltaFor(x.key); return `<div class="col-6 col-md"><button type="button" class="ag-chip ${S.metric === x.key ? 'active' : ''}" data-ag-metric="${x.key}">
                    <div class="lbl">${BH.esc(x.label)}</div><div class="num">${compact(l?.[x.valueKey ?? x.key])}</div>
                    <div class="small">${d === null ? '<span class="bh-faint">—</span>' : `<span class="${d >= 0 ? 'ag-up' : 'ag-down'} fw-semibold">${d >= 0 ? '▲' : '▼'} ${Math.abs(Math.round(d * 10) / 10)}%</span>`}</div></button></div>`; }).join('')}
            </div>
            <div class="border-top pt-3 mt-3" style="border-color: var(--bh-border) !important">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="fw-bold mb-0">${BH.esc(m.label)}</h6>
                    ${changeLabel ? `<span class="badge rounded-pill" style="${growthTone(changeLabel)}">${BH.esc(changeLabel)}</span>` : ''}
                </div>
                <small class="bh-faint">${hasRange() ? 'Selected range, compared start to end.' : 'Cycle by cycle.'}</small>
                <div class="bh-chart mt-3" style="height: 260px"><canvas data-ag-canvas="metric"></canvas></div>
            </div>
        </div>

        <div class="bh-card mt-3">
            <h6 class="fw-bold mb-0">Scores</h6><small class="bh-faint">Visibility, Engagement, and Health scores side by side.</small>
            <div class="bh-chart mt-3" style="height: 260px"><canvas data-ag-canvas="scores"></canvas></div>
        </div>

        <div class="bh-card mt-3">
            <h6 class="fw-bold mb-0">Content Type Breakdown</h6>
            <small class="bh-faint">Viewers and Interactions by content type (Posts / Reels / Story), change across ${hasRange() ? 'the selected months' : 'the current window'}${ci.from && ci.to && ci.from !== ci.to ? ` (${BH.esc(ci.from.label)} → ${BH.esc(ci.to.label)})` : ''}.</small>
            ${!ci.has ? '<div class="bh-empty py-4">No Content Insights entered for the cycles in this range yet.</div>' : `<div class="row g-3 mt-1">${ci.cards.map((g) => `<div class="col-md-6"><div class="ag-tile">
                <div class="d-flex justify-content-between align-items-baseline"><span class="bh-stat-label">${g.label}</span><small>${deltaHtml(g.totalDelta)}</small></div>
                <div class="small bh-muted bh-num">${count(g.from)} → <b class="text-body">${count(g.to)}</b></div>
                <div class="border-top pt-2 mt-2" style="border-color: var(--bh-border) !important">${g.lines.map((ln) => `<div class="d-flex justify-content-between align-items-center gap-2 small py-1"><span class="bh-muted" style="width: 2.75rem">${ln.label}</span><span class="flex-grow-1 text-end bh-faint bh-num">${count(ln.from)} → <span class="text-body">${count(ln.to)}</span></span><span>${deltaHtml(ln.delta)}</span></div>`).join('')}</div>
            </div></div>`).join('')}</div>`}
        </div>

        <div class="bh-card mt-3">
            <h6 class="fw-bold mb-0">Weighted Content Score</h6>
            <small class="bh-faint">Views, Interactions, and account Growth blended per content type — Posts/Reels 50% Views + 20% Interactions + 30% Growth, Story 80% Views + 20% Interactions.</small>
            ${!ci.has ? '<div class="bh-empty py-4">No Content Insights entered for the cycles in this range yet.</div>' : `<div class="row g-3 mt-1">${ci.scores.map((sc) => `<div class="col-md-4"><div class="ag-tile">
                <div class="bh-stat-label">${sc.label}</div>
                <div class="fs-3 fw-bold bh-num ${sc.score === null ? 'bh-faint' : sc.score >= 0 ? 'ag-up' : 'ag-down'}">${sc.score === null ? '—' : (sc.score >= 0 ? '▲ ' : '▼ ') + fmtDelta(sc.score)}</div>
                <div class="border-top pt-2 mt-2" style="border-color: var(--bh-border) !important">${sc.components.map((c) => `<div class="d-flex justify-content-between small"><span class="bh-muted">${{ views: 'Views', interactions: 'Interactions', growth: 'Growth' }[c.source]} <span class="bh-faint">(${Math.round(c.weight * 100)}%)</span></span><span class="fw-semibold bh-num ${c.value === null ? 'bh-faint' : c.value >= 0 ? 'ag-up' : 'ag-down'}">${c.value === null ? 'n/a' : fmtDelta(c.value)}</span></div>`).join('')}</div>
            </div></div>`).join('')}</div>`}
        </div>`;

        const metricConfig = chartConfigs()[S.metric];
        charts.metric = BH.chart(body.querySelector('[data-ag-canvas="metric"]'), chartConfigFor(metricConfig, cycles));
        charts.scores = BH.chart(body.querySelector('[data-ag-canvas="scores"]'), chartConfigFor(chartConfigs().scores, cycles));
    }

    function contentInsight(cycles, a) {
        const from = cycles.at(0) ?? null, to = cycles.at(-1) ?? null;
        const has = cycles.some((c) => c.content_insight);
        const fi = from?.content_insight, ti = to?.content_insight;
        if (!fi || !ti) return { has, from, to, cards: [], scores: [] };
        const groups = [
            { key: 'viewers', label: 'Viewers', fields: [['viewers_posts', 'Posts'], ['viewers_reels', 'Reels'], ['viewers_story', 'Story']] },
            { key: 'interactions', label: 'Interactions', fields: [['interactions_posts', 'Posts'], ['interactions_reels', 'Reels'], ['interactions_story', 'Story']] },
        ];
        const total = (ins, g) => g.fields.reduce((s, [k]) => s + (ins[k] ?? 0), 0);
        const cards = groups.map((g) => ({ label: g.label, from: total(fi, g), to: total(ti, g), totalDelta: pctChange(total(fi, g), total(ti, g)),
            lines: g.fields.map(([k, l]) => ({ label: l, from: fi[k] ?? 0, to: ti[k] ?? 0, delta: pctChange(fi[k], ti[k]) })) }));
        const weights = { posts: [['views', .5], ['interactions', .2], ['growth', .3]], reels: [['views', .5], ['interactions', .2], ['growth', .3]], story: [['views', .8], ['interactions', .2]] };
        const growth = a?.followerChangeRate ?? null;
        const scores = Object.entries(weights).map(([type, comps]) => {
            const values = { views: pctChange(fi[`viewers_${type}`], ti[`viewers_${type}`]), interactions: pctChange(fi[`interactions_${type}`], ti[`interactions_${type}`]), growth };
            const avail = comps.filter(([src]) => values[src] !== null), sum = avail.reduce((s, [, w]) => s + w, 0);
            return { label: { posts: 'Posts', reels: 'Reels', story: 'Story' }[type],
                score: avail.length ? avail.reduce((s, [src, w]) => s + (values[src] * w) / sum, 0) : null,
                components: comps.map(([source, weight]) => ({ source, weight, value: values[source] })) };
        });
        return { has, from, to, cards, scores };
    }

    function renderSummaryTab() {
        const r = S.summary;
        body.innerHTML = `<div class="mx-auto" style="max-width: 36rem">
            <div class="bh-card"><h6 class="fw-bold mb-0">Generate AI Summary</h6><small class="bh-faint">A short written recap of this account's performance.</small>
                <label class="form-label small fw-semibold bh-muted mt-3">Provider</label>
                <select class="form-select" data-ag-provider data-no-search><option value="groq">Groq</option><option value="gemini">Gemini</option></select>
                <label class="form-label small fw-semibold bh-muted mt-3">Custom instructions (optional)</label>
                <textarea class="form-control" rows="2" maxlength="500" data-ag-prompt placeholder="e.g. focus on engagement trend, keep it short"></textarea>
                <button type="button" class="btn btn-primary w-100 mt-3" data-ag-run>${r ? 'Regenerate' : 'Summarize'}</button>
                <p class="small mt-2 mb-0 d-none" style="color: var(--status-parah-ink)" data-ag-run-error></p>
            </div>
            ${r ? `<div class="bh-card mt-3"><p class="mb-2" style="white-space: pre-line">${BH.esc(r.summary)}</p>
                <div class="small bh-faint border-top pt-2" style="border-color: var(--bh-border) !important">${r.cycle_count} cycle${r.cycle_count === 1 ? '' : 's'} · generated ${new Date(r.generated_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })}</div></div>` : ''}
        </div>`;
        body.querySelector('[data-ag-provider]').value = S.provider ?? DEFAULT_PROVIDER;
    }

    // ---- events ----
    el.addEventListener('click', async (e) => {
        const t = e.target;
        if (t.closest('[data-ag-tab]')) { S.tab = t.closest('[data-ag-tab]').dataset.agTab; S.from = ''; S.to = ''; return render(); }
        if (t.closest('[data-ag-metric]')) { S.metric = t.closest('[data-ag-metric]').dataset.agMetric; return render(); }
        if (t.closest('[data-ag-clear]')) { S.from = ''; S.to = ''; return render(); }
        if (t.closest('[data-ag-pm]')) { e.preventDefault(); S.showPm = !S.showPm; return render(); }
        if (t.closest('[data-ag-run]')) return summarize(t.closest('[data-ag-run]'));
        if (t.closest('[data-ag-pdf]')) return downloadPdf(t.closest('[data-ag-pdf]'));
    });
    el.addEventListener('change', (e) => {
        if (e.target.matches('[data-ag-range]')) { S.range = e.target.value === 'all' ? 'all' : +e.target.value; render(); }
        if (e.target.matches('[data-ag-from]')) { S.from = e.target.value; render(); }
        if (e.target.matches('[data-ag-to]')) { S.to = e.target.value; render(); }
        if (e.target.matches('[data-ag-provider]')) S.provider = e.target.value;
    });
    el.addEventListener('hidden.bs.modal', () => Object.values(charts).forEach((c) => c?.destroy()));

    async function summarize(btn) {
        btn.disabled = true; btn.textContent = 'Summarizing…';
        const err = body.querySelector('[data-ag-run-error]');
        try {
            S.provider = body.querySelector('[data-ag-provider]').value;
            S.summary = await BH.request(URLS.summarize(S.account.id), { method: 'POST', body: { prompt: body.querySelector('[data-ag-prompt]').value || undefined, provider: S.provider } });
            renderSummaryTab();
        } catch (e) {
            err.textContent = e.message; err.classList.remove('d-none');
            btn.disabled = false; btn.textContent = S.summary ? 'Regenerate' : 'Summarize';
        }
    }

    // Charts are drawn off-screen on white so the PDF looks the same in light or dark mode.
    function capture(config, cycles) {
        return new Promise((resolve) => {
            const c = document.createElement('canvas'); c.width = 900; c.height = 400;
            const cfg = chartConfigFor(config, cycles, true);
            cfg.plugins = [{ id: 'white', beforeDraw: (ch) => { const x = ch.canvas.getContext('2d'); x.save(); x.globalCompositeOperation = 'destination-over'; x.fillStyle = 'white'; x.fillRect(0, 0, ch.width, ch.height); x.restore(); } }];
            const chart = new Chart(c, cfg);
            requestAnimationFrame(() => { const img = c.toDataURL('image/png'); chart.destroy(); resolve(img); });
        });
    }

    async function downloadPdf(btn) {
        const errEl = $('[data-ag-export-error]');
        errEl.classList.add('d-none');
        btn.disabled = true;
        const label = btn.querySelector('span');
        label.textContent = 'Preparing…';
        try {
            const cycles = activeCycles(), l = latest(), ps = S.postSummary[S.tab] ?? {};
            const configs = Object.values(chartConfigs());
            const images = await Promise.all(configs.map(async (cfg) => ({ label: cfg.label, image: await capture(cfg, cycles) })));
            const res = await fetch(URLS.pdf(S.account.id), {
                method: 'POST',
                headers: { 'Accept': 'application/pdf', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({
                    charts: images,
                    ai_summary: S.summary?.summary ?? undefined,
                    summary: {
                        platform: S.tab === 'tiktok' ? 'TikTok' : 'Instagram', followers: l?.end_follower ?? null, followers_delta: deltaFor('end_follower'),
                        health_label: l?.health_label ?? null, health_rate: l?.health_rate ?? null, growth_rate: l?.growth_rate ?? null,
                        avg_views: ps.avg_views ?? null, median_views: ps.median_views ?? null, total_posts: ps.total_posts ?? null, latest_cycle_label: l?.label ?? null,
                        volume: [['reach', reachLabel()], ['views', 'Views'], ['engagement', 'Engagement'], ['story_performance', 'Story Performance']].map(([k, lab]) => ({ label: lab, value: l?.[k] ?? null, delta: deltaFor(k) })),
                    },
                }),
            });
            if (!res.ok) throw new Error('Failed to generate PDF.');
            const url = URL.createObjectURL(await res.blob());
            const a = document.createElement('a'); a.href = url; a.download = `${S.account.name}-growth.pdf`; a.click();
            URL.revokeObjectURL(url);
        } catch (e) {
            errEl.textContent = e.message; errEl.classList.remove('d-none');
        } finally {
            btn.disabled = false; label.textContent = 'Download PDF';
        }
    }

    BH.openAccountGrowth = async function (account, platform = 'instagram') {
        reset(account, platform);
        render();
        bootstrap.Modal.getOrCreateInstance(el).show();
        try {
            const data = await BH.request(URLS.growth(account.id));
            if (!data.cycles.length) { S.error = 'No cycles recorded for this account yet.'; S.loading = false; return render(); }
            S.cycles = data.cycles.map((c) => ({ ...c, total_ads_spend: (c.reach_views_ads_spend ?? 0) + (c.engagement_ads_spend ?? 0) }));
            S.postSummary = data.postSummary ?? {};
            S.changeBuckets = data.changeLabelBuckets ?? S.changeBuckets;
            S.pmHistory = data.pmHistory ?? [];
            if (data.ai_summary) S.summary = { summary: data.ai_summary, cycle_count: data.cycles.length, generated_at: data.ai_summary_generated_at };
            S.loading = false;
            // Wait for the modal to finish opening so charts measure their real size.
            requestAnimationFrame(render);
        } catch (e) { S.error = e.message || 'Failed to load growth data.'; S.loading = false; render(); }
    };
});
</script>
