<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Porta IT')</title>
    
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app-dark.css') }}">
    
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

            <footer>
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

    @yield('modals')

    <script src="{{ asset('assets/static/js/components/dark.js') }}"></script>
    <script src="{{ asset('assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/compiled/js/app.js') }}"></script>
    
    @yield('scripts')
</body>
</html>