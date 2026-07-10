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
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        
        :root { --app-height: 100vh; }
        
        html {
            background: #050505;
            height: -webkit-fill-available;
        }

        body { 
            min-height: 100vh;
            min-height: -webkit-fill-available;
            /* Разрешаем скролл по умолчанию */
            overflow-y: auto; 
            overflow-x: hidden;
            background: #050505;
            color: white;
            -webkit-font-smoothing: antialiased;
        }

        /* Убираем полосу прокрутки, но оставляем функционал */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <script>
        const appHeight = () => document.documentElement.style.setProperty('--app-height', `${window.innerHeight}px`);
        window.addEventListener('resize', appHeight);
        appHeight();
    </script>
</head>
<body class="font-sans antialiased selection:bg-indigo-500/30" 
      x-data="globalCallHandler()" 
      x-init="initGlobal()"
      @click="unlockAudio()" 
      @touchstart.once="unlockAudio()">

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

    <div class="flex flex-col relative min-h-screen">
        
        <!-- DESKTOP NAV -->
        <div class="hidden lg:block shrink-0">
            @include('layouts.navigation')
        </div>

        <!-- MAIN -->
        <main class="flex-1 relative">
            {{ $slot }}
        </main>

        <!-- MOBILE NAV -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-[200] bg-black/60 backdrop-blur-3xl border-t border-white/5 pb-safe">
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
                <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('profile.edit') ? 'text-indigo-400' : 'text-gray-500' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[7px] font-black uppercase tracking-widest">Profile</span>
                </a>
            </div>
        </nav>

        <!-- CALL MODAL (Incoming) -->
        <div x-show="incomingCall" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center bg-black/90 backdrop-blur-2xl p-6">
            <div class="bg-[#0a0a0a] border border-white/10 p-8 rounded-[3rem] text-center max-w-sm w-full">
                <div class="w-20 h-20 bg-indigo-600 rounded-[2rem] flex items-center justify-center text-4xl mx-auto mb-6 animate-pulse shadow-2xl shadow-indigo-500/20">📞</div>
                <h2 class="text-2xl font-black mb-2 uppercase" x-text="incomingCall?.fromName"></h2>
                <p class="text-indigo-400 text-[10px] font-black uppercase tracking-widest mb-10">Входящий вызов...</p>
                <div class="flex flex-col gap-3">
                    <button @click="acceptCall()" class="w-full bg-white text-black py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-500 hover:text-white transition-all">Принять</button>
                    <button @click="rejectCall()" class="w-full bg-white/5 text-gray-500 py-4 rounded-2xl font-black text-xs uppercase tracking-widest">Отклонить</button>
                </div>
            </div>
        </div>
    </div>

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