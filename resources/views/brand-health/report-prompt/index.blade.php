@extends('brand-health.layout')

@section('title', 'Report Prompt | Brand Health')
@section('header_title', 'Brand Health')

@section('bh-styles')
<style>
    .rp-source { display: flex; gap: .75rem; align-items: flex-start; border: 1px solid var(--bh-border); border-radius: 10px; padding: .65rem .8rem; cursor: pointer; margin-bottom: .5rem; }
    .rp-source:has(input:checked) { border-color: var(--bh-accent); background: var(--bh-accent-soft); }
    .rp-output { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; min-height: 28rem; resize: vertical; }
</style>
@endsection

@section('bh-content')
@php $mLabel = fn ($ym) => \Carbon\Carbon::createFromFormat('Y-m-d', $ym.'-01')->format('F Y'); $latest = $months->last(); @endphp
<div class="bh-page-head">
    <div>
        <h3>Report Prompt Generator</h3>
        <p>Builds a text prompt carrying real data from the picked scope — paste it into an AI chat (Claude, ChatGPT, etc.) to generate a PPTX report with charts and illustrations. No AI call happens here — this only assembles the data.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <form class="bh-card" id="rp-form">
            <h6 class="fw-bold">Scope</h6>
            <label class="form-label small fw-semibold bh-muted mt-2">Month (or range)</label>
            <div class="d-flex align-items-center gap-2">
                <select name="month_from" class="form-select" data-no-search required>
                    @forelse($months as $m)<option value="{{ $m }}" @selected($m === $latest)>{{ $mLabel($m) }}</option>@empty<option value="">No cycles yet</option>@endforelse
                </select>
                <span class="bh-faint">→</span>
                <select name="month_to" class="form-select" data-no-search>
                    @foreach($months as $m)<option value="{{ $m }}" @selected($m === $latest)>{{ $mLabel($m) }}</option>@endforeach
                </select>
            </div>
            <small class="bh-faint">Pick the same month twice for a single month.</small>

            <label class="form-label small fw-semibold bh-muted mt-3">Project Manager</label>
            <select name="project_manager_id" class="form-select">
                <option value="">All PMs</option>
                @foreach($accountDepartmentEmployees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
            </select>

            <h6 class="fw-bold border-top pt-3 mt-4" style="border-color: var(--bh-border) !important">Include data</h6>
            @foreach(['cycles' => ['Cycles', 'Growth / Visibility / Engagement / Health scores per cycle'], 'performances' => ['Reels / Video', 'Post-level Views H+7 performance records'],
                'content_insights' => ['Content Insights', 'Viewers & Interactions by content type'], 'employees' => ['Employees', 'PM / Conceptor / Editor roster']] as $v => [$l, $h])
                <label class="rp-source">
                    <input type="checkbox" class="form-check-input mt-1" name="sources[]" value="{{ $v }}" checked>
                    <span><span class="d-block small fw-semibold">{{ $l }}</span><span class="d-block small bh-muted">{{ $h }}</span></span>
                </label>
            @endforeach

            <button type="submit" class="btn btn-primary w-100 mt-2" data-rp-generate @disabled($months->isEmpty())><i class="bi bi-magic"></i> Generate Prompt</button>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="bh-card h-100">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <h6 class="fw-bold mb-0">Generated prompt</h6>
                <div class="d-flex gap-2 d-none" data-rp-actions>
                    <button type="button" class="btn btn-sm btn-light-secondary" data-rp-copy><i class="bi bi-clipboard"></i> <span>Copy</span></button>
                    <button type="button" class="btn btn-sm btn-light-secondary" data-rp-download><i class="bi bi-download"></i> Download .txt</button>
                </div>
            </div>
            <div class="alert alert-danger py-2 mt-3 d-none" data-rp-error></div>
            <textarea class="form-control rp-output mt-3 d-none" readonly data-rp-output></textarea>
            <div class="text-center py-5 border rounded-3 mt-3" style="border-style: dashed !important; border-color: var(--bh-border-strong) !important" data-rp-empty>
                <i class="bi bi-file-earmark-text fs-1 bh-faint"></i>
                <p class="fw-semibold mt-2 mb-1">Nothing generated yet</p>
                <small class="bh-faint">Pick a scope and data sources, then generate.</small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('bh-scripts')
<script>
(function () {
    const form = document.getElementById('rp-form');
    const out = document.querySelector('[data-rp-output]'), err = document.querySelector('[data-rp-error]');
    const btn = document.querySelector('[data-rp-generate]');
    const monthName = (ym) => { const [y, m] = ym.split('-'); return new Date(+y, +m - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }); };
    let period = '';

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const sources = [...form.querySelectorAll('[name="sources[]"]:checked')].map((c) => c.value);
        err.classList.add('d-none');
        if (!sources.length) { err.textContent = 'Pick at least one data source.'; err.classList.remove('d-none'); return; }
        let from = form.month_from.value, to = form.month_to.value || from;
        if (to < from) [from, to] = [to, from];
        period = from === to ? monthName(from) : `${monthName(from)} – ${monthName(to)}`;
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating…';
        try {
            const data = await BH.request(@json(route('brand-health.report-prompt.generate')), { method: 'POST', body: { month_from: from, month_to: to, project_manager_id: form.project_manager_id.value || null, sources } });
            out.value = data.prompt;
            out.classList.remove('d-none');
            document.querySelector('[data-rp-empty]').classList.add('d-none');
            document.querySelector('[data-rp-actions]').classList.remove('d-none');
        } catch (ex) {
            err.textContent = ex.message || 'Failed to generate prompt.'; err.classList.remove('d-none');
        } finally {
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-magic"></i> Generate Prompt';
        }
    });

    document.querySelector('[data-rp-copy]').addEventListener('click', async (e) => {
        const label = e.currentTarget.querySelector('span');
        try { await navigator.clipboard.writeText(out.value); label.textContent = 'Copied!'; window.showToast?.('Prompt copied to clipboard.'); setTimeout(() => (label.textContent = 'Copy'), 2000); }
        catch { out.select(); window.showToast?.('Failed to copy — select and copy manually.', 'danger'); }
    });
    document.querySelector('[data-rp-download]').addEventListener('click', () => {
        const slug = period.replace(/\s+/g, '-').replace(/[^\w-]/g, '').toLowerCase();
        const url = URL.createObjectURL(new Blob([out.value], { type: 'text/plain;charset=utf-8' }));
        const a = document.createElement('a'); a.href = url; a.download = `report-prompt-${slug || 'export'}.txt`; a.click();
        URL.revokeObjectURL(url);
    });
})();
</script>
@endsection
