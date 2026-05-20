<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Porta IT | Asset Management System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-pattern {
            background-color: #F8FAFC;
            background-image: radial-gradient(#3C50E0 0.5px, transparent 0.5px);
            background-size: 24px 24px;
            background-opacity: 0.1;
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex flex-col">

    <nav class="bg-white border-b border-gray-100 py-4 px-6 lg:px-12 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-[#3C50E0] rounded-lg flex items-center justify-center text-white font-bold text-xl">P</div>
            <span class="text-[#1C2434] text-xl font-bold tracking-tight uppercase">Porta <span class="text-[#3C50E0]">IT</span></span>
        </div>

        <div class="flex items-center gap-4">
            @if (Route::has('login'))
                @auth
                    <a href="{{ route('inventory.index', 'Dashboard') }}" class="text-sm font-bold text-[#3C50E0] border border-[#3C50E0] px-5 py-2 rounded-md hover:bg-[#3C50E0] hover:text-white transition uppercase tracking-widest">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-bold text-gray-500 hover:text-[#3C50E0] transition uppercase tracking-widest">Sign In</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-sm font-bold bg-[#3C50E0] text-white px-5 py-2 rounded-md hover:bg-[#2e3ea8] transition shadow-md uppercase tracking-widest">Register</a>
                    @endif
                @endauth
            @endif
        </div>
    </nav>

    <main class="flex-1 flex items-center">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-20 grid lg:grid-cols-2 gap-12 items-center">
            
            <div class="space-y-8">
                <div>
                    <span class="inline-block bg-blue-50 text-[#3C50E0] text-[10px] font-extrabold uppercase tracking-[0.2em] px-3 py-1 rounded-full mb-4 border border-blue-100">Enterprise Edition v3.0</span>
                    <h1 class="text-5xl lg:text-6xl font-extrabold text-[#1C2434] leading-tight">
                        Internal Asset <br>
                        <span class="text-[#3C50E0]">Management.</span>
                    </h1>
                    <p class="text-gray-500 text-lg mt-6 max-w-lg leading-relaxed">
                        The central nervous system for your IT operations. Track hardware lifecycle, manage software subscriptions, and monitor employee assignments in one professional portal.
                    </p>
                </div>

                <div class="flex flex-wrap gap-4">
                    @auth
                        <a href="{{ route('inventory.index', 'Dashboard') }}" class="bg-[#1C2434] text-white px-8 py-4 rounded-lg font-bold text-sm uppercase tracking-widest hover:bg-[#2d3a4d] transition shadow-xl flex items-center gap-3">
                            Enter Management Console
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="bg-[#3C50E0] text-white px-8 py-4 rounded-lg font-bold text-sm uppercase tracking-widest hover:bg-[#2e3ea8] transition shadow-xl">
                            Secure Login
                        </a>
                        <a href="#features" class="bg-white border border-gray-200 text-gray-600 px-8 py-4 rounded-lg font-bold text-sm uppercase tracking-widest hover:bg-gray-50 transition shadow-sm">
                            Learn More
                        </a>
                    @endauth
                </div>
            </div>

            <div class="hidden lg:block relative">
                <div class="bg-white p-8 rounded-2xl shadow-2xl border border-gray-100 rotate-1 relative z-10">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                    </div>
                    <div class="space-y-4">
                        <div class="h-4 bg-gray-100 rounded w-3/4"></div>
                        <div class="h-32 bg-gray-50 rounded-lg flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="h-4 bg-gray-100 rounded"></div>
                            <div class="h-4 bg-gray-100 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
                <div class="absolute -top-10 -right-10 w-64 h-64 bg-blue-100 rounded-full blur-3xl opacity-50"></div>
                <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-gray-200 rounded-full blur-2xl opacity-50"></div>
            </div>
        </div>
    </main>

    <footer class="py-10 border-t border-gray-100 text-center">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em]">
            &copy; {{ date('Y') }} Porta IT Management System | Strictly Internal
        </p>
    </footer>

</body>
</html>