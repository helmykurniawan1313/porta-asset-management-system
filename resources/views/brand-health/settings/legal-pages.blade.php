@extends('brand-health.layout')

@section('title', 'Legal Pages | Brand Health')

@section('bh-styles')
<link rel="stylesheet" href="{{ asset('assets/extensions/quill/quill.snow.css') }}">
<style>
    .bh-tabs { display: flex; gap: .25rem; border-bottom: 1px solid var(--bh-border); margin-bottom: 1.25rem; }
    .bh-tabs button { border: 0; background: none; padding: .6rem .9rem; font-weight: 600; color: var(--bh-ink-muted); border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .bh-tabs button.active { color: var(--bh-accent); border-bottom-color: var(--bh-accent); }
    .ql-toolbar.ql-snow, .ql-container.ql-snow { border-color: var(--bh-border); }
    .ql-container { min-height: 360px; font-size: .95rem; background: var(--bh-surface); }
    [data-bs-theme="dark"] .ql-snow .ql-stroke { stroke: #cbd5e1; }
    [data-bs-theme="dark"] .ql-snow .ql-fill { fill: #cbd5e1; }
    [data-bs-theme="dark"] .ql-snow .ql-picker { color: #cbd5e1; }
</style>
@endsection

@section('bh-content')
@php $canEdit = auth()->user()->can('edit-brand-health'); @endphp
<div class="bh-page-head">
    <div>
        <h3>Legal Pages</h3>
        <p>Edit the public Privacy Policy and Data Deletion Instructions pages.</p>
    </div>
</div>

<div class="bh-tabs">
    @foreach($pages as $page)
        <button type="button" class="{{ $loop->first ? 'active' : '' }}" data-tab="{{ $page->slug }}">{{ $page->title }}</button>
    @endforeach
</div>

@foreach($pages as $page)
    <div data-pane="{{ $page->slug }}" style="max-width: 52rem; {{ $loop->first ? '' : 'display:none' }}">
        <a href="{{ url('brand-health/'.$page->slug) }}" target="_blank" rel="noopener" class="small fw-semibold">View live page →</a>
        @if($canEdit)
            <form method="POST" action="{{ route('brand-health.legal-pages.update', $page) }}" class="mt-3" data-legal-form>
                @csrf @method('PUT')
                <input type="hidden" name="content_html">
                <div class="bh-editor">{!! $page->content_html !!}</div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        @else
            <div class="bh-card mt-3">{!! $page->content_html !!}</div>
        @endif
    </div>
@endforeach
@endsection

@section('bh-scripts')
<script src="{{ asset('assets/extensions/quill/quill.min.js') }}"></script>
<script>
    document.querySelectorAll('[data-tab]').forEach((b) => b.addEventListener('click', () => {
        document.querySelectorAll('[data-tab]').forEach((x) => x.classList.toggle('active', x === b));
        document.querySelectorAll('[data-pane]').forEach((p) => p.style.display = p.dataset.pane === b.dataset.tab ? '' : 'none');
    }));
    // Same toolbar scope as Brand Matrix's editor: headings, emphasis, lists, links. The server sanitises with HTMLPurifier.
    document.querySelectorAll('[data-legal-form]').forEach((form) => {
        const quill = new Quill(form.querySelector('.bh-editor'), {
            theme: 'snow',
            modules: { toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
        });
        form.addEventListener('submit', () => { form.querySelector('[name=content_html]').value = quill.root.innerHTML; });
    });
</script>
@endsection
