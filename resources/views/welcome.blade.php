<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caspian — Экосистема общения</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-white antialiased selection:bg-indigo-500 selection:text-white">
    <div class="relative min-h-screen flex flex-col justify-center items-center overflow-hidden">
        <!-- Декоративные градиенты на фоне -->
        <div class="absolute top-0 -left-4 w-72 h-72 bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-purple-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-blue-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

        <div class="relative z-10 text-center px-4">
            <div class="flex justify-center mb-8">
                <x-application-logo class="w-20 h-20 fill-current text-indigo-500" />
            </div>
            
            <h1 class="text-6xl md:text-8xl font-black tracking-tighter mb-4 bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-500">
                CASPIAN
            </h1>
            <p class="text-lg md:text-xl text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                Интеллектуальная платформа для видео-встреч, случайных знакомств и приватного общения. 
                <span class="text-indigo-400 font-bold">Будущее связи уже здесь.</span>
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-white text-black rounded-2xl font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                        ПЕРЕЙТИ В ПАНЕЛЬ УПРАВЛЕНИЯ
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-8 py-4 bg-indigo-600 text-white rounded-2xl font-black hover:bg-indigo-700 transition-all transform hover:scale-105 active:scale-95 shadow-lg shadow-indigo-500/20">
                        ВОЙТИ В СИСТЕМУ
                    </a>
                    <a href="{{ route('register') }}" class="px-8 py-4 bg-gray-800 text-white border border-gray-700 rounded-2xl font-black hover:bg-gray-700 transition-all transform hover:scale-105 active:scale-95">
                        РЕГИСТРАЦИЯ
                    </a>
                @endauth
            </div>

            <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8 text-left max-w-5xl mx-auto">
                <div class="p-6 bg-white/5 backdrop-blur-lg rounded-3xl border border-white/10">
                    <div class="text-indigo-400 text-2xl mb-3">🎲</div>
                    <h3 class="font-bold text-lg mb-1">Рулетка</h3>
                    <p class="text-sm text-gray-500">Видео-чат один на один с алгоритмом умного подбора партнеров.</p>
                </div>
                <div class="p-6 bg-white/5 backdrop-blur-lg rounded-3xl border border-white/10">
                    <div class="text-indigo-400 text-2xl mb-3">👥</div>
                    <h3 class="font-bold text-lg mb-1">Конференции</h3>
                    <p class="text-sm text-gray-500">Групповые комнаты до 6 человек с защитой паролем и P2P связью.</p>
                </div>
                <div class="p-6 bg-white/5 backdrop-blur-lg rounded-3xl border border-white/10">
                    <div class="text-indigo-400 text-2xl mb-3">💬</div>
                    <h3 class="font-bold text-lg mb-1">Мессенджер</h3>
                    <p class="text-sm text-gray-500">Постоянные чаты с друзьями и отслеживание статуса «В сети».</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>