<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#050505">

    <title>Caspian — Next Gen Video</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
        :root { --app-height: 100vh; }
        html, body { 
            height: var(--app-height);
            /*overflow: hidden; /* Запрещаем скролл всему телу, скроллить будем внутри компонентов */
            background: #050505;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 10px; }
        
        /* Анимация появления Bottom Sheet */
        .bottom-sheet-enter { transform: translateY(100%); }
        .bottom-sheet-enter-active { transform: translateY(0); transition: transform 0.4s cubic-bezier(0.23, 1, 0.32, 1); }
    </style>
    <script>
        const appHeight = () => document.documentElement.style.setProperty('--app-height', `${window.innerHeight}px`);
        window.addEventListener('resize', appHeight);
        appHeight();
    </script>
</head>
<body class="font-sans antialiased text-white" 
      x-data="{ ...globalCallHandler(), mobileMenuOpen: false }" 
      x-init="initGlobal()">

    <!-- ГЛОБАЛЬНАЯ СИСТЕМА УВЕДОМЛЕНИЙ -->
    <div x-data="toastSystem()" @toast.window="add($event.detail)" class="fixed top-6 left-1/2 -translate-x-1/2 z-[1000] w-full max-w-xs space-y-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="pointer-events-auto backdrop-blur-xl border px-6 py-3 rounded-2xl shadow-2xl flex items-center justify-center text-center"
                 :class="toast.type === 'error' ? 'bg-red-600/90 border-red-400/50' : 'bg-indigo-600/90 border-indigo-400/50'">
                <span class="text-[10px] font-black uppercase tracking-widest" x-text="toast.msg"></span>
            </div>
        </template>
    </div>

    <div class="flex flex-col min-h-screen relative">
        @include('layouts.navigation')

        <main class="flex-1 flex flex-col">{{ $slot }}</main>

               <!-- MOBILE NAV -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-[400] bg-black/80 backdrop-blur-3xl border-t border-white/5 pb-safe">
            <div class="flex justify-around items-center h-16">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('dashboard') ? 'text-indigo-400' : 'text-gray-500' }}">
                    <span class="text-xl">🏠</span>
                    <span class="text-[7px] font-black uppercase tracking-widest">Home</span>
                </a>
                <a href="{{ route('chat') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('chat') ? 'text-indigo-400' : 'text-gray-500' }}">
                    <span class="text-xl">🎲</span>
                    <span class="text-[7px] font-black uppercase tracking-widest">Roulette</span>
                </a>
                <a href="{{ route('rooms.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('rooms.*') ? 'text-indigo-400' : 'text-gray-500' }}">
                    <span class="text-xl">👥</span>
                    <span class="text-[7px] font-black uppercase tracking-widest">Spaces</span>
                </a>
                <!-- Кнопка Профиль теперь открывает меню -->
                <button @click="mobileMenuOpen = true" class="flex flex-col items-center gap-1 text-gray-500">
                    <div class="w-6 h-6 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-[10px] font-black text-white">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <span class="text-[7px] font-black uppercase tracking-widest">Account</span>
                </button>
            </div>
        </nav>

        <!-- MOBILE ACCOUNT MENU (Bottom Sheet) -->
        <div x-show="mobileMenuOpen" class="fixed inset-0 z-[500] bg-black/60 backdrop-blur-sm" @click="mobileMenuOpen = false" x-transition.opacity></div>
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="fixed inset-x-0 bottom-0 z-[510] bg-[#0a0a0a] border-t border-white/10 rounded-t-[2.5rem] p-8 pb-12">
            
            <div class="w-12 h-1 bg-white/10 rounded-full mx-auto mb-8"></div>
            
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center text-2xl font-black">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div>
                    <h3 class="text-xl font-black uppercase">{{ Auth::user()->name }}</h3>
                    <p class="text-indigo-400 text-xs font-bold uppercase tracking-widest">{{ Auth::user()->rank_name }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <a href="{{ route('profile.edit') }}" class="w-full bg-white/5 py-4 rounded-2xl text-center font-black text-xs uppercase tracking-widest">
                    ⚙️ Настройки профиля
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full bg-red-600/10 text-red-500 py-4 rounded-2xl font-black text-xs uppercase tracking-widest border border-red-500/20">
                        🚪 Выйти из аккаунта
                    </button>
                </form>
                <button @click="mobileMenuOpen = false" class="w-full py-4 text-gray-500 font-black text-[10px] uppercase tracking-[0.3em]">
                    Закрыть
                </button>
            </div>
        </div>
    </div>
</body>

    <script>
        function toastSystem() {
            return {
                toasts: [],
                add(data) {
                    const id = Date.now();
                    this.toasts.push({ id, msg: data.msg, type: data.type || 'info', show: true });
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
                }
            }
        }
        function globalCallHandler() {
            return {
                incomingCall: null, callTimestamp: 0,
                ringtone: new Audio('/sounds/call.mp3'), msgSound: new Audio('/sounds/message.mp3'),
                audioUnlocked: false, soundEnabled: true,
                initGlobal() {
                    this.ringtone.loop = true;
                    @auth
                    window.Echo.private('user.{{ auth()->id() }}').listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.type === 'incoming-call') { this.incomingCall = e.data; this.callTimestamp = Date.now(); this.playRingtone(); }
                        if (['hang-up', 'peer-disconnected'].includes(e.data.type)) { this.stopRingtone(); this.incomingCall = null; }
                    });
                    @endauth
                },
                async unlockAudio() {
                    if (this.audioUnlocked) return;
                    const sounds = [this.ringtone, this.msgSound];
                    for (let s of sounds) { try { s.muted = true; await s.play(); s.pause(); s.currentTime = 0; s.muted = false; } catch (e) { this.soundEnabled = false; } }
                    this.audioUnlocked = true;
                    if (this.incomingCall && (Date.now() - this.callTimestamp < 15000)) this.playRingtone();
                },
                playRingtone() { if (this.soundEnabled) this.ringtone.play().catch(() => {}); },
                stopRingtone() { this.ringtone.pause(); this.ringtone.currentTime = 0; },
                acceptCall() { this.stopRingtone(); window.location.href = '/chat?accept_call=' + this.incomingCall.fromId; },
                rejectCall() {
                    window.axios.post('/chat/signal', { partnerId: this.incomingCall.fromId, data: { type: 'hang-up', from: {{ auth()->id() }} } });
                    this.stopRingtone(); this.incomingCall = null;
                }
            }
        }
    </script>
</body>
</html>