<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChatRoulette</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#050505] text-white font-sans antialiased" 
      x-data="globalCallHandler()" 
      x-init="initGlobal()"
      @click="unlockAudio()" 
      @touchstart.once="unlockAudio()">

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
        callTimestamp: 0,
        ringtone: new Audio('/sounds/call.mp3'),
        msgSound: new Audio('/sounds/message.mp3'),
        audioUnlocked: false,
        soundEnabled: true, // По умолчанию включено
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream,

        initGlobal() {
            this.ringtone.loop = true;
            
            // Если устройство — iPhone, подготавливаемся к возможным проблемам
            if (this.isIOS) {
                console.log("iOS detected: applying advanced audio constraints");
            }

            window.addEventListener('play-msg-sound', () => this.playMsgSound());

            @auth
            window.Echo.private('user.{{ auth()->id() }}')
                .listen('.WebRTCSignalEvent', (e) => {
                    const data = e.data;
                    
                    // Обработка входящего звонка
                    if (data.type === 'incoming-call') {
                        this.incomingCall = data;
                        this.callTimestamp = Date.now();
                        this.playRingtone();
                    }

                    // Остановка при сбросе
                    if (['hang-up', 'peer-disconnected', 'peer-skipped'].includes(data.type)) {
                        if (this.incomingCall && Number(data.from) === Number(this.incomingCall.fromId)) {
                            this.stopRingtone();
                            this.incomingCall = null;
                        }
                    }
                });
            @endauth
        },

        async unlockAudio() {
            if (this.audioUnlocked) return;

            // 1. Пытаемся "прокачать" аудио коротким воспроизведением
            const sounds = [this.ringtone, this.msgSound];
            for (let sound of sounds) {
                try {
                    sound.muted = true;
                    await sound.play();
                    sound.pause();
                    sound.currentTime = 0;
                    sound.muted = false;
                } catch (e) {
                    console.warn("Audio fail on this device:", e);
                    // Если даже при клике браузер ругается, отключаем звуки совсем
                    this.soundEnabled = false;
                }
            }

            this.audioUnlocked = true;
            console.log("Audio system unlocked");

            // ИСПРАВЛЕНИЕ: Проверяем, не "протух" ли звонок (если пришел более 15 сек назад)
            // Это уберет срабатывание мелодии при случайном нажатии на кнопки
            if (this.incomingCall && (Date.now() - this.callTimestamp < 15000)) {
                this.playRingtone();
            } else {
                this.stopRingtone();
                this.incomingCall = null;
            }
        },

        playRingtone() {
            if (!this.soundEnabled) return;

            // На iOS Chrome/Safari звонок не заиграет, пока нет клика.
            // Но мы вызываем его здесь, чтобы он заиграл СРАЗУ, если аудио уже разблокировано.
            this.ringtone.play().catch(e => {
                // Если ошибка - значит аудио еще заблокировано, это нормально для первого раза
                console.log("Waiting for user interaction to play ringtone...");
            });
        },

        playMsgSound() {
            if (!this.soundEnabled || !this.audioUnlocked) return;

            // Сбрасываем и играем
            this.msgSound.currentTime = 0;
            this.msgSound.play().catch(e => {
                console.warn("Message sound blocked by system");
            });
        },

        stopRingtone() {
            this.ringtone.pause();
            this.ringtone.currentTime = 0;
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
        }
    }
}
    </script>
</body>
</html>