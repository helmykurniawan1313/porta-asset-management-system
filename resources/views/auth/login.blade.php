<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Porta IT</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
        
        <div class="bg-[#1C2434] p-8 text-center border-b-4 border-[#3C50E0]">
            <div class="flex items-center justify-center gap-3 mb-2">
                <div class="w-10 h-10 bg-[#3C50E0] rounded-lg flex items-center justify-center text-white font-bold text-2xl shadow-sm">P</div>
                <span class="text-white text-3xl font-extrabold tracking-tight uppercase">Porta <span class="text-[#3C50E0]">IT</span></span>
            </div>
            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-2">Asset & System Management</p>
        </div>

        <div class="p-8">
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-md border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="block text-[11px] font-bold uppercase text-gray-500 tracking-wider mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                        </div>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" 
                            class="block w-full pl-10 bg-gray-50 border border-gray-200 text-[#1C2434] text-sm rounded-md focus:ring-1 focus:ring-[#3C50E0] focus:border-[#3C50E0] p-2.5 transition shadow-sm">
                    </div>
                    @error('email')
                        <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-[11px] font-bold uppercase text-gray-500 tracking-wider">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-[10px] font-bold text-[#3C50E0] hover:text-[#2e3ea8] hover:underline transition">Forgot Password?</a>
                        @endif
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2-2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="block w-full pl-10 bg-gray-50 border border-gray-200 text-[#1C2434] text-sm rounded-md focus:ring-1 focus:ring-[#3C50E0] focus:border-[#3C50E0] p-2.5 transition shadow-sm">
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 text-[#3C50E0] bg-gray-100 border-gray-300 rounded focus:ring-[#3C50E0] focus:ring-2">
                    <label for="remember_me" class="ml-2 text-xs font-bold text-gray-500">Keep me logged in</label>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center items-center gap-2 bg-[#3C50E0] hover:bg-[#2e3ea8] text-white font-bold text-sm uppercase tracking-widest rounded-md py-3 transition shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#3C50E0]">
                        Secure Sign In
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
                
            </form>
        </div>
        
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Authorized Personnel Only</p>
        </div>
    </div>

</body>
</html>