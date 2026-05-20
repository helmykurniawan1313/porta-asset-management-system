<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Porta IT</title>

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
            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-2">Create Admin Account</p>
        </div>

        <div class="p-8">
            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-[11px] font-bold uppercase text-gray-500 tracking-wider mb-1">Full Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" 
                            class="block w-full pl-10 bg-gray-50 border border-gray-200 text-[#1C2434] text-sm rounded-md focus:ring-1 focus:ring-[#3C50E0] focus:border-[#3C50E0] p-2.5 transition shadow-sm">
                    </div>
                    @error('name')
                        <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-[11px] font-bold uppercase text-gray-500 tracking-wider mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                        </div>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" 
                            class="block w-full pl-10 bg-gray-50 border border-gray-200 text-[#1C2434] text-sm rounded-md focus:ring-1 focus:ring-[#3C50E0] focus:border-[#3C50E0] p-2.5 transition shadow-sm">
                    </div>
                    @error('email')
                        <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-[11px] font-bold uppercase text-gray-500 tracking-wider mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2-2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                            class="block w-full pl-10 bg-gray-50 border border-gray-200 text-[#1C2434] text-sm rounded-md focus:ring-1 focus:ring-[#3C50E0] focus:border-[#3C50E0] p-2.5 transition shadow-sm">
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-[11px] font-bold uppercase text-gray-500 tracking-wider mb-1">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                            class="block w-full pl-10 bg-gray-50 border border-gray-200 text-[#1C2434] text-sm rounded-md focus:ring-1 focus:ring-[#3C50E0] focus:border-[#3C50E0] p-2.5 transition shadow-sm">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center items-center gap-2 bg-[#3C50E0] hover:bg-[#2e3ea8] text-white font-bold text-sm uppercase tracking-widest rounded-md py-3 transition shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#3C50E0]">
                        Register Account
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </button>
                </div>
                
                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-[10px] font-bold text-gray-400 hover:text-[#3C50E0] uppercase tracking-widest transition">Already registered? Sign in instead</a>
                </div>
            </form>
        </div>
        
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">System Registration Portal</p>
        </div>
    </div>

</body>
</html>