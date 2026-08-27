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

    @yield('scripts')
</body>
</html>