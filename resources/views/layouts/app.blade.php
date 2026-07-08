<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChatRoulette</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#050505] text-white font-sans antialiased selection:bg-indigo-500/30" 
      x-data="globalCallHandler()" 
      x-init="initGlobal()"
      @click="unlockAudio()" 
      @mousemove.once="unlockAudio()" 
      @keydown.once="unlockAudio()">

    <div class="min-h-screen flex flex-col relative">
        @include('layouts.navigation')

        <main class="flex-1">
            {{ $slot }}
        </main>

        <!-- ГЛОБАЛЬНОЕ ОКНО ВХОДЯЩЕГО ЗВОНКА -->
        <div x-show="incomingCall" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/90 backdrop-blur-2xl" x-cloak x-transition>
            <div class="bg-[#0a0a0a] border border-white/10 p-12 rounded-[3.5rem] text-center max-w-sm w-full shadow-2xl shadow-indigo-500/10">
                <div class="relative w-24 h-24 mx-auto mb-8">
                    <div class="absolute inset-0 bg-indigo-600 rounded-[2rem] animate-ping opacity-20"></div>
                    <div class="relative bg-indigo-600 rounded-[2rem] w-full h-full flex items-center justify-center text-4xl shadow-xl">📞</div>
                </div>
                
                <h2 class="text-3xl font-black mb-2 uppercase tracking-tighter" x-text="incomingCall?.fromName"></h2>
                <p class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] mb-12 italic animate-pulse">Входящий вызов...</p>
                
                <div class="flex flex-col gap-3">
                    <button @click="acceptCall()" class="w-full bg-white text-black py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-500 hover:text-white transition-all shadow-xl">Принять вызов</button>
                    <button @click="rejectCall()" class="w-full bg-white/5 text-gray-500 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all">Отклонить</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function globalCallHandler() {
            return {
                incomingCall: null,
                ringtone: new Audio('/sounds/call.mp3'),
                audioUnlocked: false,

                initGlobal() {
                    this.ringtone.loop = true;
                    this.ringtone.preload = 'auto'; // Предзагрузка файла
                    
                    @auth
                    window.Echo.private('user.{{ auth()->id() }}')
                        .listen('.WebRTCSignalEvent', (e) => {
                            const data = e.data;
                            
                            if (data.type === 'incoming-call') {
                                console.log("Incoming call from:", data.fromName);
                                this.incomingCall = data;
                                // Пытаемся запустить звук
                                this.playRingtone();
                            }

                            if (data.type === 'hang-up' || data.type === 'peer-disconnected') {
                                if (this.incomingCall && Number(data.from) === Number(this.incomingCall.fromId)) {
                                    this.stopRingtone();
                                    this.incomingCall = null;
                                }
                            }
                        });
                    @endauth
                },

                // Метод для воспроизведения
                playRingtone() {
                    if (this.incomingCall) {
                        this.ringtone.play().catch(err => {
                            console.warn("Звук заблокирован браузером. Ожидание клика...");
                        });
                    }
                },

                // Метод разблокировки звука
                unlockAudio() {
                    if (this.audioUnlocked) {
                        // Если уже разблокировано, но есть входящий звонок и тишина - включаем
                        if (this.incomingCall && this.ringtone.paused) this.playRingtone();
                        return;
                    }
                    
                    // Хак для пробития защиты браузера
                    this.ringtone.play().then(() => {
                        this.audioUnlocked = true;
                        console.log("Audio System Activated");
                        // Если пока звонка нет - сразу стопаем "тестовый" запуск
                        if (!this.incomingCall) {
                            this.ringtone.pause();
                            this.ringtone.currentTime = 0;
                        }
                    }).catch(() => {
                        // Всё еще заблокировано
                    });
                },

                acceptCall() {
                    const fromId = this.incomingCall.fromId;
                    this.stopRingtone();
                    this.incomingCall = null;
                    window.location.href = '/chat?accept_call=' + fromId;
                },

                rejectCall() {
                    if (this.incomingCall) {
                        window.axios.post('/chat/signal', { 
                            partnerId: this.incomingCall.fromId, 
                            data: { type: 'hang-up', from: {{ auth()->id() }} } 
                        }).catch(() => {});
                    }
                    this.stopRingtone();
                    this.incomingCall = null;
                },

                stopRingtone() {
                    if (this.ringtone) {
                        this.ringtone.pause();
                        this.ringtone.currentTime = 0;
                        this.ringtone.load(); // Очистка буфера
                    }
                }
            }
        }
    </script>
</body>
</html>