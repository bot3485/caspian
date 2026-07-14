<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>CASPIAN — Universe of Connections</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-hidden bg-[#020202] text-white select-none">
    <div class="fixed top-6 right-6 z-[9999]">
    <x-language-switcher />
</div>
    <!-- Ethereal Background Glow Engine -->
    <div class="fixed inset-0 z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[60%] h-[60%] bg-indigo-600/5 blur-[160px] rounded-full animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-[50%] h-[50%] bg-purple-600/5 blur-[160px] rounded-full animate-pulse" style="animation-delay: 3s"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col justify-between">
        <!-- NAVIGATION -->
        <nav class="flex justify-between items-center px-6 md:px-12 h-24 max-w-7xl w-full mx-auto">
            <div class="flex items-center gap-3">
                <img src="{{ asset('roulette.jpg') }}" class="w-9 h-9 rounded-xl border border-white/10 shadow-lg shadow-indigo-500/10" alt="C">
                <span class="text-lg font-black tracking-tighter uppercase italic">Caspian <span class="text-indigo-500">3.1</span></span>
            </div>
        </nav>

        <!-- MAIN HERO CONTEXT -->
        <main class="flex-1 flex flex-col items-center justify-center px-6 text-center max-w-4xl mx-auto">
            <div class="inline-flex px-4 py-1.5 rounded-full border border-indigo-500/15 bg-indigo-500/5 text-[8px] font-black uppercase tracking-[0.3em] text-indigo-400 mb-8 animate-pulse">
                {{ __('messages.welcome_future') }}
            </div>
            
            <h1 class="text-[clamp(2.5rem,12vw,8rem)] font-black leading-[0.85] tracking-tighter uppercase italic mb-8 bg-gradient-to-b from-white via-white to-white/30 bg-clip-text text-transparent">
                {{ __('messages.visual') }}<br><span class="text-indigo-500">{{ __('messages.freedom') }}</span>
            </h1>
            
            <p class="max-w-md text-gray-500 font-bold text-xs sm:text-sm uppercase tracking-wider leading-relaxed mb-12">
                {{ __('messages.next_gen') }} <br>
                {{ __('messages.p2p') }}
            </p>

            <!-- ACTION DECK -->
            <div class="w-full max-w-md bg-[#050505]/40 backdrop-blur-xl border border-white/[0.04] p-3 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                @guest
                    <div class="flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('register') }}" class="flex-1 bg-brand-indigo hover:scale-[1.02] active:scale-95 text-white py-4 px-6 rounded-2xl font-black text-[9px] uppercase tracking-[0.25em] text-center transition-all duration-300 shadow-xl shadow-brand-indigo/15 border border-white/15">
                            {{ __('messages.create_account') }}
                        </a>
                        <a href="{{ route('login') }}" class="flex-1 bg-white/[0.02] border border-white/[0.06] hover:bg-white/10 text-gray-300 hover:text-white py-4 px-6 rounded-2xl font-black text-[9px] uppercase tracking-[0.25em] text-center transition-all duration-300">
                            {{ __('messages.already_registered') }}
                        </a>
                    </div>
                @else
                    <!-- Для авторизованных пользователей ведем на Dashboard -->
                    <a href="{{ route('dashboard') }}" class="block w-full bg-brand-indigo hover:scale-[1.01] active:scale-95 text-white py-4.5 px-8 rounded-2xl font-black text-[9px] uppercase tracking-[0.25em] text-center transition-all duration-300 border border-white/10 shadow-xl">
                        {{ __('messages.open_dashboard') }} ➔
                    </a>
                @endguest
            </div>
        </main>

        <!-- FOOTER -->
        <footer class="h-24 flex items-center justify-center opacity-30">
            <p class="text-[8px] font-black uppercase tracking-[0.6em] text-gray-600">Caspian Ecosystem © 2026</p>
        </footer>
    </div>
</body>
</html>