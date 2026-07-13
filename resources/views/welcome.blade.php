<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>CASPIAN — Universe of Connections</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-hidden">
    <!-- Глобальный фон с живыми градиентами -->
    <div class="fixed inset-0 z-0">
        <div class="absolute top-[-20%] left-[-10%] w-[70%] h-[70%] bg-indigo-600/10 blur-[150px] rounded-full animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-[60%] h-[60%] bg-purple-600/10 blur-[150px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col">
        <!-- Навигация -->
        <nav class="flex justify-between items-center px-8 h-24">
            <div class="flex items-center gap-3">
                <img src="{{ asset('roulette.jpg') }}" class="w-10 h-10 rounded-xl neon-glow" alt="C">
                <span class="text-xl font-black tracking-tighter uppercase italic">Caspian <span class="text-indigo-500">3.0</span></span>
            </div>
            <div class="flex gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 hover:text-white transition-colors">Login</a>
                @endauth
            </div>
        </nav>

        <!-- Контент -->
        <main class="flex-1 flex flex-col items-center justify-center px-4 text-center">
            <div class="inline-block px-4 py-1 rounded-full border border-indigo-500/20 bg-indigo-500/5 text-[9px] font-black uppercase tracking-[0.3em] text-indigo-400 mb-8">
                Welcome to the Future of Communication
            </div>
            
            <h1 class="text-[clamp(3rem,15vw,10rem)] font-black leading-[0.8] tracking-tighter uppercase italic mb-10">
                Visual<br><span class="text-indigo-600">Freedom.</span>
            </h1>
            
            <p class="max-w-xl text-gray-500 font-medium text-lg leading-relaxed mb-12">
                Интеллектуальная экосистема для видеосвязи нового поколения. <br class="hidden md:block">
                Безопасность, скорость и абсолютное качество.
            </p>

            <div class="flex flex-col sm:flex-row gap-6">
                @guest
                    <a href="{{ route('register') }}" class="btn-primary !px-12 !py-5 !text-xs">Start Journey</a>
                @else
                    <a href="{{ route('chat') }}" class="btn-primary !px-12 !py-5 !text-xs">Open Roulette</a>
                @endguest
            </div>
        </main>

        <!-- Футер -->
        <footer class="h-24 flex items-center justify-center opacity-20">
            <p class="text-[10px] font-bold uppercase tracking-[0.5em]">Caspian Ecosystem © 2025</p>
        </footer>
    </div>
</body>
</html>