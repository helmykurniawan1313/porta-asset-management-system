<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Porta IT')</title>
    
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app-dark.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/porta-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/extensions/choices.js/public/assets/styles/choices.min.css') }}">
    <style>
        /* Searchable selects (Choices.js) — match Bootstrap form controls, light + dark. */
        .choices { margin-bottom: 0; }
        /* --bs-body-bg is the tinted page background in this theme, so the control uses white explicitly. */
        .choices__inner {
            display: flex; align-items: center; min-height: 38px; padding: .25rem 2.25rem .25rem .75rem !important; font-size: 1rem;
            background: #fff; border: 1px solid #dce7f1; border-radius: .25rem;
        }
        .choices.is-focused .choices__inner, .choices.is-open .choices__inner { border-color: var(--porta-accent); box-shadow: 0 0 0 .2rem var(--porta-accent-soft); }
        .choices.is-open .choices__inner { border-radius: .25rem .25rem 0 0; }
        .choices__list--single { padding: 0; }
        /* Same chevron as .form-select instead of Choices' triangle. */
        .choices[data-type*=select-one]::after, .choices[data-type*=select-one].is-open::after {
            border: 0; width: 16px; height: 12px; margin-top: -6px; right: 12px;
            background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") no-repeat center / 16px 12px;
        }
        [data-bs-theme="dark"] .choices[data-type*=select-one]::after { filter: invert(1); }
        .choices__list--dropdown, .choices__list[aria-expanded] { z-index: 1060; border-color: #dce7f1; border-radius: 0 0 .25rem .25rem; box-shadow: 0 12px 24px rgba(16, 24, 40, .12); }
        .choices__list--dropdown .choices__item, .choices__list[aria-expanded] .choices__item { font-size: .95rem; padding: .55rem .9rem; }
        /* A compact control (e.g. "All PMs") still gets a list wide enough for full names, one per line;
           Choices' default word-break: break-all split names mid-word. */
        .choices[data-type*=select-one]:not(.is-small) { min-width: 11rem; }
        .choices__list--dropdown, .choices__list[aria-expanded] { min-width: 100%; width: max-content; max-width: min(22rem, 90vw); }
        .choices__list--dropdown .choices__item, .choices__list[aria-expanded] .choices__item {
            word-break: normal; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .choices[data-type*=select-one] .choices__input { min-width: 0; width: 100%; }
        .choices__list--dropdown .choices__item--selectable.is-highlighted { background: var(--porta-accent-soft); }
        .choices__list--dropdown .choices__list { max-height: 280px; }
        .choices[data-type*=select-one] .choices__input { border-bottom-color: #dce7f1; padding: .55rem .9rem; font-size: .95rem; }
        .choices__heading { font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #8a94a6; padding: .6rem .9rem .3rem; border-bottom: 0; }
        .choices__list--multiple .choices__item { background: var(--porta-accent); border-color: var(--porta-accent); border-radius: 999px; font-weight: 600; }
        .choices__placeholder { opacity: .6; }
        .choices.is-small .choices__inner { min-height: 31px; padding: .15rem 2rem .15rem .5rem !important; font-size: .875rem; }
        [data-bs-theme="dark"] .choices__inner,
        [data-bs-theme="dark"] .choices__list--dropdown,
        [data-bs-theme="dark"] .choices__list[aria-expanded] { background: #1e1e2d; border-color: rgba(255, 255, 255, .12); color: #e5e7eb; }
        [data-bs-theme="dark"] .choices__input { background: transparent !important; color: #e5e7eb; border-bottom-color: rgba(255, 255, 255, .12) !important; }
        [data-bs-theme="dark"] .choices__list--dropdown .choices__item--selectable.is-highlighted { background: rgba(129, 140, 248, .2); }
        [data-bs-theme="dark"] .choices__list--multiple .choices__item { background: #6366f1; border-color: #6366f1; }
    </style>

    <script>
        // One wording for every DataTables table (pages pass this as their "language").
        window.PORTA_DT_LANGUAGE = {
            search: '', searchPlaceholder: 'Search…', lengthMenu: '_MENU_ per page',
            info: 'Showing _START_–_END_ of _TOTAL_', infoEmpty: 'No records', infoFiltered: '(filtered from _MAX_)',
            zeroRecords: 'No matching records', emptyTable: 'Nothing here yet',
            paginate: { previous: '‹', next: '›' },
        };
    </script>
    @yield('styles')
</head>
<body>
    <script src="{{ asset('assets/static/js/initTheme.js') }}"></script>

    <div id="app">
        @include('layouts.partials.sidebar')

        <div id="main" class='layout-navbar navbar-fixed'>
            @include('layouts.partials.header')

            <div id="main-content">
                
                {{-- Pages with their own heading (e.g. Brand Health) leave header_title unset. --}}
                @if(View::hasSection('header_title') || isset($header))
                <div class="page-heading mb-4">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-12 col-md-6 order-md-1 order-last">
                                <h3 class="fw-bold mb-0">@yield('header_title') {{ $header ?? '' }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <section class="section">
                    @yield('content')
                    {{ $slot ?? '' }}
                </section>
                
            </div>

            <footer class="app-footer-fixed">
                <div class="footer clearfix mb-0 text-muted px-4">
                    <div class="float-start">
                        <p>2026 &copy; Porta IT</p>
                    </div>
                    <div class="float-end">
                        <p>Crafted with <span class="text-danger"><i class="bi bi-heart-fill"></i></span> by Helmy Kurniawan</p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <style>
        #main {
            display: flex !important;
            flex-direction: column;
            min-height: 100vh;
        }
        #main-content {
            flex: 1 0 auto;
        }
        .app-footer-fixed {
            flex-shrink: 0;
            padding: 0.5rem 0;
        }
        /* Modal styling is global (porta-theme.css). */
    </style>

    @yield('modals')

    <div class="modal fade" id="app-confirm-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                        <span id="app-confirm-title">Are you sure?</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted mb-0" id="app-confirm-message">This action cannot be undone.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold" id="app-confirm-submit">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;" id="app-toast-container"></div>

    <script src="{{ asset('assets/static/js/components/dark.js') }}"></script>
    <script src="{{ asset('assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/compiled/js/app.js') }}"></script>

    <script>
        window.showToast = function (message, type) {
            type = type || 'success';
            var container = document.getElementById('app-toast-container');
            if (!container) return;

            var icons = { success: 'bi-check-circle', danger: 'bi-exclamation-triangle', info: 'bi-info-circle' };
            var colors = { success: 'text-success', danger: 'text-danger', info: 'text-info' };

            var toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center border-0 shadow-sm mb-2';
            toastEl.setAttribute('role', 'alert');
            toastEl.innerHTML =
                '<div class="d-flex">' +
                    '<div class="toast-body d-flex align-items-center gap-2">' +
                        '<i class="bi ' + (icons[type] || icons.success) + ' ' + (colors[type] || colors.success) + '"></i>' +
                        '<span></span>' +
                    '</div>' +
                    '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                '</div>';
            toastEl.querySelector('.toast-body span').textContent = message;

            container.appendChild(toastEl);

            var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
            toast.show();
        };

        document.addEventListener('DOMContentLoaded', function () {
            @if(session('status'))
                window.showToast(@json(str_contains(session('status'), ' ') ? session('status') : 'Saved successfully.'), 'success');
            @endif
            @if(session('error'))
                window.showToast(@json(session('error')), 'danger');
            @endif
        });
    </script>

    <script>
        (function () {
            var pendingForm = null;
            var modalEl = document.getElementById('app-confirm-modal');
            var titleEl = document.getElementById('app-confirm-title');
            var messageEl = document.getElementById('app-confirm-message');
            var submitBtn = document.getElementById('app-confirm-submit');

            window.confirmDelete = function (form, message, title) {
                pendingForm = form;
                titleEl.textContent = title || 'Are you sure?';
                messageEl.textContent = message || 'This action cannot be undone.';
                new bootstrap.Modal(modalEl).show();
                return false;
            };

            submitBtn.addEventListener('click', function () {
                if (pendingForm) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                    pendingForm.submit();
                    pendingForm = null;
                }
            });
        })();
    </script>

    <script src="{{ asset('assets/extensions/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script>
        /**
         * Every <select> with more than 8 options becomes searchable, app-wide.
         * Opt out with data-no-search. The original <select> stays in the form and
         * still fires "change", so existing form posts and listeners keep working.
         */
        (function () {
            var MIN_OPTIONS = 8;

            function enhance(root, skipModals) {
                if (typeof Choices === 'undefined') return;
                (root || document).querySelectorAll('select').forEach(function (sel) {
                    if (sel.dataset.choicesReady || sel.hasAttribute('data-no-search')) return;
                    // Pages can render dozens of edit modals; build those only when the modal opens.
                    if (skipModals && sel.closest('.modal')) return;
                    if (sel.closest('.dataTables_length, .dataTables_wrapper .dataTables_filter')) return;
                    var count = sel.querySelectorAll('option').length;
                    if (count <= MIN_OPTIONS) return;

                    sel.dataset.choicesReady = '1';
                    var placeholder = sel.dataset.placeholder
                        || (sel.querySelector('option[value=""]') || {}).textContent
                        || 'Select…';
                    var choices = new Choices(sel, {
                        searchEnabled: true,
                        searchPlaceholderValue: 'Type to search…',
                        searchResultLimit: 50,
                        searchFields: ['label'],
                        fuseOptions: { threshold: 0.3, ignoreLocation: true },
                        shouldSort: false,
                        itemSelectText: '',
                        removeItemButton: sel.multiple,
                        placeholder: true,
                        placeholderValue: sel.multiple ? placeholder : null,
                        noResultsText: 'No match',
                        allowHTML: false,
                    });
                    if (sel.classList.contains('form-select-sm')) {
                        choices.containerOuter.element.classList.add('is-small');
                    }
                    // Lets page scripts change the value later: sel._choices.setChoiceByValue('5').
                    sel._choices = choices;
                });
            }

            document.addEventListener('DOMContentLoaded', function () { enhance(document, true); });
            // Content shown later (modals, AJAX-loaded sections) gets enhanced when it appears.
            document.addEventListener('show.bs.modal', function (e) { enhance(e.target); });
            window.enhanceSelects = enhance;
        })();
    </script>

    @yield('scripts')
</body>
</html>