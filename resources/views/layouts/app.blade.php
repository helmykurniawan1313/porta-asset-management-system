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
        .choices__inner {
            min-height: calc(1.5em + .75rem + 2px); padding: .3rem .75rem; font-size: 1rem;
            background: var(--bs-body-bg, #fff); border: 1px solid #dce7f1; border-radius: .375rem;
        }
        .choices.is-focused .choices__inner, .choices.is-open .choices__inner { border-color: #435ebe; box-shadow: 0 0 0 .2rem rgba(67, 94, 190, .15); }
        .choices.is-open .choices__inner { border-radius: .375rem .375rem 0 0; }
        .choices__list--single { padding: .1rem 1rem .1rem 0; }
        .choices[data-type*=select-one]::after { border-color: #6c757d transparent transparent; right: 14px; }
        .choices__list--dropdown, .choices__list[aria-expanded] { z-index: 1060; border-color: #dce7f1; border-radius: 0 0 .375rem .375rem; box-shadow: 0 12px 24px rgba(16, 24, 40, .12); }
        .choices__list--dropdown .choices__item, .choices__list[aria-expanded] .choices__item { font-size: .95rem; padding: .55rem .9rem; }
        .choices__list--dropdown .choices__item--selectable.is-highlighted { background: rgba(67, 94, 190, .1); }
        .choices__list--dropdown .choices__list { max-height: 280px; }
        .choices[data-type*=select-one] .choices__input { border-bottom-color: #dce7f1; padding: .55rem .9rem; font-size: .95rem; }
        .choices__heading { font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #8a94a6; padding: .6rem .9rem .3rem; border-bottom: 0; }
        .choices__list--multiple .choices__item { background: #435ebe; border-color: #435ebe; border-radius: 999px; font-weight: 600; }
        .choices__placeholder { opacity: .6; }
        .choices.is-small .choices__inner { min-height: calc(1.5em + .5rem + 2px); padding: .15rem .5rem; font-size: .875rem; }
        [data-bs-theme="dark"] .choices__inner,
        [data-bs-theme="dark"] .choices__list--dropdown,
        [data-bs-theme="dark"] .choices__list[aria-expanded] { background: #1e1e2d; border-color: rgba(255, 255, 255, .12); color: #e5e7eb; }
        [data-bs-theme="dark"] .choices__input { background: transparent !important; color: #e5e7eb; border-bottom-color: rgba(255, 255, 255, .12) !important; }
        [data-bs-theme="dark"] .choices__list--dropdown .choices__item--selectable.is-highlighted { background: rgba(129, 140, 248, .2); }
        [data-bs-theme="dark"] .choices__list--multiple .choices__item { background: #6366f1; border-color: #6366f1; }
    </style>

    @yield('styles')
</head>
<body>
    <script src="{{ asset('assets/static/js/initTheme.js') }}"></script>

    <div id="app">
        @include('layouts.partials.sidebar')

        <div id="main" class='layout-navbar navbar-fixed'>
            @include('layouts.partials.header')

            <div id="main-content">
                
                <div class="page-heading mb-4">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-12 col-md-6 order-md-1 order-last">
                                <h3 class="fw-bold mb-0">@yield('header_title') {{ $header ?? '' }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
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
        .hr-modal {
            border-radius: 1rem;
            background-color: var(--porta-surface);
            color: var(--porta-text);
        }
        .hr-modal .modal-header {
            padding: 1.5rem 1.5rem 0;
        }
        .hr-modal .modal-footer {
            border-top: 1px solid var(--porta-border);
            background-color: var(--porta-surface-muted);
        }
    </style>

    @yield('modals')

    <div class="modal fade" id="app-confirm-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
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