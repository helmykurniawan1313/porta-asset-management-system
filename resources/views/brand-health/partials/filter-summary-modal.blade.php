{{--
    "Summarize with AI" for the currently filtered set — Blade port of FilterSummaryModal.vue.
    @include('brand-health.partials.filter-summary-modal', ['endpoint' => route(...), 'countKey' => 'cycle_count', 'countNoun' => 'cycle'])
    Open from JS: BH.openFilterSummary(filterQueryObject, 'human readable filters', onGenerated(data))
--}}
<div class="modal fade" id="filter-summary-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Summarize with AI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small rounded-3 px-3 py-2 mb-3" style="background: var(--bh-raised); color: var(--bh-ink-muted)">Filters: <span data-fs-label></span></p>
                <label class="form-label small fw-semibold bh-muted">Provider</label>
                <select class="form-select mb-3" data-fs-provider data-no-search>
                    <option value="groq">Groq</option>
                    <option value="gemini">Gemini</option>
                </select>
                <label class="form-label small fw-semibold bh-muted">Custom instructions (optional)</label>
                <textarea class="form-control mb-3" rows="2" maxlength="500" data-fs-prompt placeholder="e.g. focus on engagement trend, keep it short"></textarea>
                <button type="button" class="btn btn-primary w-100" data-fs-run>Summarize</button>
                <p class="small mt-2 mb-0 d-none" style="color: var(--status-parah-ink)" data-fs-error></p>
                <div class="rounded-3 border p-3 mt-3 d-none" data-fs-result>
                    <p class="mb-2" style="white-space: pre-line" data-fs-text></p>
                    <small class="bh-faint" data-fs-meta></small>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const endpoint = @json($endpoint);
    const countKey = @json($countKey ?? 'cycle_count');
    const countNoun = @json($countNoun ?? 'cycle');
    const el = document.getElementById('filter-summary-modal');
    const $ = (sel) => el.querySelector(sel);
    let query = {}, onGenerated = null;

    BH.openFilterSummary = function (filterQuery, label, callback) {
        query = filterQuery; onGenerated = callback;
        $('[data-fs-label]').textContent = label;
        $('[data-fs-provider]').value = @json(config('services.brand_health.ai_summary.provider', 'groq'));
        $('[data-fs-error]').classList.add('d-none');
        bootstrap.Modal.getOrCreateInstance(el).show();
    };

    $('[data-fs-run]').addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true; btn.textContent = 'Summarizing…';
        $('[data-fs-error]').classList.add('d-none');
        try {
            const data = await BH.request(endpoint, { method: 'POST', body: { ...query, prompt: $('[data-fs-prompt]').value || undefined, provider: $('[data-fs-provider]').value } });
            $('[data-fs-text]').textContent = data.summary;
            const n = data[countKey];
            $('[data-fs-meta]').textContent = `${n} ${countNoun}${n === 1 ? '' : 's'} · generated ${new Date(data.generated_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })}`;
            $('[data-fs-result]').classList.remove('d-none');
            btn.textContent = 'Regenerate';
            if (onGenerated) onGenerated(data);
        } catch (e) {
            $('[data-fs-error]').textContent = e.message;
            $('[data-fs-error]').classList.remove('d-none');
            btn.textContent = 'Summarize';
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
