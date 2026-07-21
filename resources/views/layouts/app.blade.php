<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Настройка для iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Caspian">

    <!-- Иконка для iPhone (Apple Touch Icon) -->
    <!-- Используем твой существующий roulette.jpg, iOS сама его подхватит -->
    <link rel="apple-touch-icon" href="{{ asset('roulette.jpg') }}">

    <!-- Подключение манифеста -->
    <link rel="manifest" href="{{ asset('manifest.json') }}" crossorigin="use-credentials">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Caspian — Intelligence Ecosystem</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="{{ asset('roulette.jpg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        :root { --app-height: 100svh; }
        body { background: #020202; color: #fff; overflow-x: hidden; }
        .caspian-glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 10px; }
    </style>
</head>
<body class="antialiased"
      x-data="window.caspianApp({{ auth()->id() }}, {{ json_encode(auth()->user()->interests ?? []) }}, @js(config('webrtc.ice_servers')))"
      x-init="init()"
      @visibilitychange.window="handleVisibilityChange()"
      @focus.window="handleVisibilityChange()">
      
    <!-- SMART TOAST NOTIFICATIONS (ANTI-SPAM) -->
    <div x-data="{ 
            toasts: [], 
            addToast(msg) {
                if (this.toasts.some(t => t.msg === msg)) return;
                
                const id = Date.now();
                this.toasts.push({id, msg});
                setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 3500);
            },
            async changeLanguage(lang) {
                try {
                    await window.axios.post('/profile', { 
                        _method: 'PATCH',
                        locale: lang 
                    });
                    window.location.reload(); 
                } catch (e) {
                    this.addToast('Language change failed');
                }
            }
        }"
         @toast.window="addToast($event.detail.msg)" 
         class="fixed top-24 left-1/2 -translate-x-1/2 z-[1000] w-full max-w-xs space-y-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-[-20px] scale-90"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90"
                 class="pointer-events-auto bg-brand-indigo/90 backdrop-blur-2xl border border-white/20 px-6 py-3 rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.5)] text-center">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-white" x-text="toast.msg"></span>
            </div>
        </template>
    </div>

    <!-- INCOMING CALL (English Only) -->
    <div x-show="incomingCall" class="fixed top-8 left-1/2 -translate-x-1/2 z-[600] w-full max-w-sm px-4" x-cloak x-transition>
        <div class="caspian-glass p-4 rounded-[2.5rem] shadow-2xl flex items-center justify-between border-brand-indigo/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-brand-indigo rounded-2xl flex items-center justify-center animate-pulse font-black shadow-lg" x-text="incomingCall?.fromName ? incomingCall.fromName[0] : '?'"></div>
                <div>
                    <p class="text-[8px] font-black text-brand-indigo uppercase tracking-[0.3em]">{{ __('app.Incoming_Session') }}</p>
                    <p class="text-sm font-black uppercase italic" x-text="incomingCall?.fromName"></p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="rejectCall()" class="w-12 h-12 bg-white/5 hover:bg-red-600 rounded-full flex items-center justify-center transition-all">✕</button>
                <button @click="acceptCall()" class="w-12 h-12 bg-brand-indigo hover:scale-110 rounded-full flex items-center justify-center shadow-indigo-500/50 shadow-lg transition-all">📞</button>
            </div>
        </div>
    </div>

    <div class="flex flex-col min-h-screen relative">
        @include('layouts.navigation')
        <main class="flex-1 relative">{{ $slot }}</main>

        <!-- Sidebar Messenger -->
        <div x-show="globalSidebarOpen" @click.outside="globalSidebarOpen = false"
             class="fixed right-0 top-0 bottom-0 z-[450] w-full md:w-[420px] bg-[#050505] border-l border-white/[0.05] shadow-2xl flex flex-col"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-end="translate-x-full" x-cloak>
            <div class="p-8 border-b border-white/5 flex justify-between items-center bg-[#080808]">
                <h2 class="text-[10px] font-black uppercase tracking-[0.5em] text-gray-500">{{ __('app.Personal_Messenger') }}</h2>
                <button @click="globalSidebarOpen = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/5 transition-colors">✕</button>
            </div>
            @include('partials.messenger-content')
        </div>
    </div>

<script>
window.caspianApp = function(myId, myInterests, iceServers) {
    return {
        // --- UI & TABS ---
        globalSidebarOpen: false, tab: 'chat', controlsOpen: false, state: 'idle', callContext: null,
        incomingCall: null, ringtone: new Audio('/sounds/call.mp3'), msgSound: new Audio('/sounds/message.mp3'),
        audioUnlocked: false,
        layoutFocus: 'split', // может быть 'split', 'remote', 'local'
        actionsOpen: false,
        isPartnerProfileOpen: false,
        uiShowPartnerCard: false,
        showLevelUp: false,
        currentLevel: {{ auth()->user()->level }},
        totalXp: {{ auth()->user()->xp }},
        hasNewNotification: false,
        callDuration: 0,
        callTimer: null,
        timerExpanded: false, // по умолчанию свернут
        // --- PARTNER DATA ---
        partnerId: null, partnerData: null, isFriend: false, partnerState: 'active',
        isPartnerTyping: false, typingPartnerName: '', ping: 0, 

        vibrate(ms = 10) {
            if ('vibrate' in navigator) navigator.vibrate(ms);
        },

        // --- LISTS ---
        friendsList: [], historyList: [], blockedList: [],
        activeFriend: null, friendMessages: [], friendChatInput: '',

        // --- WEBRTC & FILTERS ---
        pc: null, localStream: null, micEnabled: true, camEnabled: true, 
        isRemoteBlurred: false, showSelfVideo: true,
        beautyFilter: false, cinemaFilter: false,
        partnerFilters: { beauty: false, cinema: false },
        ignoreOffer: false,

        // --- CHAT ---
        messages: [], chatInput: '', 
        deviceModalOpen: false,
        videoDevices: [],
        audioDevices: [],
        selectedVideoId: '',
        selectedAudioId: '',
        showInterestMatch: false,
        commonInterests: [],
        blitzSound: new Audio('/sounds/glitch.wav'),
        isRinging: false,
        isBlitzActive: false,
        blitzCooldown: 0,
        blitzTimer: null,

        // --- INTERNAL LOGIC ---
        isProcessingSignal: false, makingOffer: false, processedEvents: new Set(), iceQueue: [],
        rtcConfig: { iceServers: iceServers, bundlePolicy: "balanced", iceCandidatePoolSize: 10 },

        filterModalOpen: false,
        targetGender: '{{ Auth::user()->target_gender }}',
        targetAgeMin: {{ Auth::user()->target_age_min }},
        targetAgeMax: {{ Auth::user()->target_age_max }},

       // --- 2. ЛОГИКА ТАРГЕТИНГА (Перенесено из чата) ---
        targetCountry: '{{ Auth::user()->target_country }}',
        targetGender: '{{ Auth::user()->target_gender ?: 'all' }}',
        targetAgeMin: {{ Auth::user()->target_age_min ?: 18 }},
        targetAgeMax: {{ Auth::user()->target_age_max ?: 99 }},
        showIcebreakerOverlay: false,
        icebreakerQuestion: '',
        icebreakerTimer: null,
        icebreakerCooldown: 0,
        iceTimer: null,     


        countryNames: {
            'global': '🌍 {{__('app.Global_Match')}}',
            'az': '🇦🇿 Azerbaijan', 'ge': '🇬🇪 Georgia', 'am': '🇦🇲 Armenia',
            'ru': '🇷🇺 Russia', 'kz': '🇰🇿 Kazakhstan', 'uz': '🇺🇿 Uzbekistan',
            'ua': '🇺🇦 Ukraine', 'tr': '🇹🇷 Turkey', 'de': '🇩🇪 Germany',
            'es': '🇪🇸 Spain', 'pl': '🇵🇱 Poland', 'us': '🇺🇸 USA',
            'ca': '🇨🇦 Canada', 'fr': '🇫🇷 France', 'it': '🇮🇹 Italy', 'gb': '🇬🇧 UK'
        },


        async updateTargetCountry(country) {
            this.targetCountry = country;
            try {
                await window.axios.post('/profile', { _method: 'PATCH', target_country: country });
                window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Target_Country_Updated') }}' } }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Update_Failed') }}' } }));
            }
        },
async applyFilters() {
    try {
        await window.axios.post('/profile', { 
            _method: 'PATCH',
            target_gender: this.targetGender,
            target_age_min: this.targetAgeMin,
            target_age_max: this.targetAgeMax
        });
        this.filterModalOpen = false;
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Target_Gender_Updated') }} 🎯' } }));
        
        // Если мы уже в поиске, перезапускаем его с новыми фильтрами
        if(this.state === 'searching') this.startSearch(); 
    } catch (e) {
        console.error("Filter Save Error:", e);
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Save_Failed') }}' } }));
    }
},

async removeContact(contactId) {
    if (!confirm('{{ __('app.Remove_Friend_Sure') }}')) return;
    
    try {
        await window.axios.post('/chat/contact/remove', { contactId });
        
        // Обновляем список друзей локально
        this.loadFriends();
        
        // Если был открыт чат с этим человеком, закрываем его
        if (this.activeFriend && Number(this.activeFriend.id) === Number(contactId)) {
            this.activeFriend = null;
        }
        
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __("app.Contact_Unlinked") }} ✕' } }));
    } catch (e) {
        console.error("Error removing contact:", e);
    }
},


startCallTimer() {
    this.stopCallTimer();
    this.callDuration = 0;
    this.callTimer = setInterval(() => {
        if (this.state === 'connected') {
            this.callDuration++;
        } else {
            this.stopCallTimer();
        }
    }, 1000);
},

stopCallTimer() {
    if (this.callTimer) {
        clearInterval(this.callTimer);
        this.callTimer = null;
    }
    this.callDuration = 0;
},

formatCallTime() {
    const mins = Math.floor(this.callDuration / 60);
    const secs = this.callDuration % 60;
    return (mins < 10 ? '0' + mins : mins) + ":" + (secs < 10 ? '0' + secs : secs);
},

async sendIcebreaker() {
    // Если время еще не вышло — ничего не делаем
    if (this.state !== 'connected' || this.icebreakerCooldown > 0) return;

    try {
        const res = await window.axios.get('/icebreaker/random');
        const index = res.data.index;
        this.signal({ type: 'icebreaker', index: index });
        await this.displayIcebreaker(index);

        // Устанавливаем Кулдаун на 60 секунд
        this.icebreakerCooldown = 60;
        this.iceTimer = setInterval(() => {
            this.icebreakerCooldown--;
            if (this.icebreakerCooldown <= 0) clearInterval(this.iceTimer);
        }, 1000);
    } catch (e) {
        console.error("Icebreaker failed", e);
    }
},
async displayIcebreaker(index) {
    try {
        const res = await window.axios.get(`/icebreaker/content/${index}`);
        
        // Очищаем старый таймер, если он был
        if (this.icebreakerTimer) clearTimeout(this.icebreakerTimer);
        
        // Устанавливаем текст и показываем окно
        this.icebreakerQuestion = res.data.question;
        this.showIcebreakerOverlay = true;

        // Скрываем через 12 секунд
        this.icebreakerTimer = setTimeout(() => {
            this.showIcebreakerOverlay = false;
        }, 12000); 
    } catch (e) {
        console.error("Icebreaker content fetch failed", e);
    }
},


triggerBlitz() {
    // Если мы не в чате, эффект уже занят или идет откат — выходим
    if (this.state !== 'connected' || this.isBlitzActive || this.blitzCooldown > 0) return;

    // 1. МГНОВЕННЫЙ ЗАПУСК ТАЙМЕРА (60 секунд)
    this.blitzCooldown = 60;
    
    // Очищаем старый интервал если он вдруг был
    if (this.blitzTimer) clearInterval(this.blitzTimer);
    
    // Запускаем счетчик
    this.blitzTimer = setInterval(() => {
        this.blitzCooldown--;
        if (this.blitzCooldown <= 0) {
            clearInterval(this.blitzTimer);
            this.blitzTimer = null;
        }
    }, 1000);

    // 2. ОТПРАВКА СИГНАЛА
    this.signal({ type: 'blitz' });

    // 3. ЗАПУСК ВИЗУАЛА У СЕБЯ
    this.startBlitzEffect();
},
    
    startBlitzEffect() {
    this.isBlitzActive = true;
    this.vibrate([100, 50, 100, 50, 200]); // Ритмичная вибрация

    // Звук (обязательно добавь в папку public/sounds агрессивный звук помех)
    if (this.blitzSound) {
        this.blitzSound.volume = 1.0;
        this.blitzSound.play();
    }

    // Тряска всего экрана (body)
    document.body.style.transition = 'none';
    
    let hellInterval = setInterval(() => {
        if (!this.isBlitzActive) {
            clearInterval(hellInterval);
            document.body.style.transform = '';
            document.body.style.filter = '';
            return;
        }

        // Рандомные вспышки на весь экран
        const bgColor = Math.random() > 0.8 ? '#4a0000' : '#020202';
        document.body.style.backgroundColor = bgColor;
        
        // Хаотичный сдвиг всего интерфейса
        const x = (Math.random() - 0.5) * 20;
        const y = (Math.random() - 0.5) * 20;
        document.body.style.transform = `translate(${x}px, ${y}px)`;
        
        // Инверсия всего сайта на доли секунды
        if (Math.random() > 0.95) {
            document.body.style.filter = 'invert(1) contrast(2)';
        } else {
            document.body.style.filter = '';
        }
    }, 50);

    // Авто-выключение через 6.66 секунды (для стиля)
    setTimeout(() => {
        this.isBlitzActive = false;
        document.body.style.backgroundColor = '#020202';
        document.body.style.transition = 'all 1s ease';
    }, 6666);
},


async rebootMobileCamera() {
    if (!this.localStream || !this.pc || this.state !== 'connected') return;
    
    console.log("[Android Fix] Аппаратное пробуждение камеры...");
    
    try {
        // 1. Получаем старый видеотрек
        const oldTrack = this.localStream.getVideoTracks()[0];
        const deviceId = oldTrack ? oldTrack.getSettings().deviceId : null;

        // 2. Запрашиваем НОВЫЙ поток от той же камеры
        // Важно: запрашиваем только video, чтобы не прерывать звук (audio: false)
        const freshStream = await navigator.mediaDevices.getUserMedia({
            video: { 
                deviceId: deviceId ? { exact: deviceId } : undefined,
                width: { ideal: 640 }, 
                height: { ideal: 480 } 
            },
            audio: false 
        });

        const newTrack = freshStream.getVideoTracks()[0];

        // 3. Заменяем трек в PeerConnection через replaceTrack
        // Это "бесшовный" метод WebRTC — картинка у собеседника просто "оживет"
        const sender = this.pc.getSenders().find(s => s.track && s.track.kind === 'video');
        if (sender) {
            await sender.replaceTrack(newTrack);
            console.log("[WebRTC] Трек успешно заменен в канале");
        }

        // 4. Обновляем локальный поток, чтобы и мы видели себя
        oldTrack.stop(); // Обязательно останавливаем старый зависший трек
        this.localStream.removeTrack(oldTrack);
        this.localStream.addTrack(newTrack);
        
        if (this.$refs.localVideo) {
            this.$refs.localVideo.srcObject = this.localStream;
        }

        // 5. Просим удаленную сторону прислать ключевой кадр (на случай если их декодер затупил)
        this.signal({ type: 'request-keyframe' });

    } catch (e) {
        console.error("[Android Fix] Ошибка перезапуска камеры:", e);
    }
},

async openDeviceSettings() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    this.videoDevices = devices.filter(d => d.kind === 'videoinput');
    this.audioDevices = devices.filter(d => d.kind === 'audioinput');

    // Обязательно синхронизируем выбранные значения с текущим потоком
    if (this.localStream) {
        this.selectedVideoId = this.localStream.getVideoTracks()[0].getSettings().deviceId;
        this.selectedAudioId = this.localStream.getAudioTracks()[0].getSettings().deviceId;
    }
    
    this.deviceModalOpen = true;
},

async changeVideoDevice() {
    try {
        // 1. Останавливаем старые треки
        if (this.localStream) {
            this.localStream.getTracks().forEach(t => t.stop());
        }

        const constraints = {
            video: { deviceId: { ideal: this.selectedVideoId }, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: { deviceId: { ideal: this.selectedAudioId } }
        };
        const newStream = await navigator.mediaDevices.getUserMedia(constraints);

        // 2. Останавливаем только ТЕКУЩИЕ треки
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
        }

        // 3. Обновляем локальную ссылку и видео-тег
        this.localStream = newStream;
        if (this.$refs.localVideo) {
            this.$refs.localVideo.srcObject = newStream;
            await this.$refs.localVideo.play();
        }

        // 4. ГЛАВНОЕ: Заменяем треки в активном PeerConnection без разрыва связи
        if (this.pc) {
            const videoTrack = newStream.getVideoTracks()[0];
            const audioTrack = newStream.getAudioTracks()[0];
            
            const videoSender = this.pc.getSenders().find(s => s.track?.kind === 'video');
            const audioSender = this.pc.getSenders().find(s => s.track?.kind === 'audio');

            if (videoSender) await videoSender.replaceTrack(videoTrack);
            if (audioSender) await audioSender.replaceTrack(audioTrack);
        }

        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: '{{ __('app.Hardware_Synced') }}'}}));
        this.deviceModalOpen = false;
    } catch (e) {
        console.error(e);
        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: '{{ __('app.Device_Error_Access_Denied') }}'}}));
    }
},

refreshVideoTags() {
    const localEl = document.getElementById('localVideo');
    if (localEl && this.localStream && localEl.srcObject !== this.localStream) {
        localEl.srcObject = this.localStream;
    }
},

async changeAudioDevice() {
    // Аналогичная логика для аудио (просто перезапрашиваем поток)
    this.changeVideoDevice(); 
},

    async init() {
            const self = this; 

            this.$watch('globalSidebarOpen', value => {
                if (value) {
                        this.hasNewNotification = false;
                    }
                });
            const onceUnlock = () => {
                self.unlockAudio();
                window.removeEventListener('touchstart', onceUnlock);
                window.removeEventListener('mousedown', onceUnlock);
            };
            window.addEventListener('touchstart', onceUnlock, { passive: true });
            window.addEventListener('mousedown', onceUnlock, { passive: true });
            
            this.ringtone.load();
            this.msgSound.load();
            this.blitzSound.load();

            window.Echo.private(`user.${myId}`)
                .listen('.MatchFoundEvent', (e) => {
                    if (self.callContext === 'personal') return;
                    self.vibrate(20); // Вибрация при нахождении пары
                    self.handleMatch(e);
                })
                .listen('.XpGainedEvent', (e) => {
                    if (e.currentLevel > this.currentLevel) {
                        this.currentLevel = e.currentLevel;
                        this.showLevelUp = true;
                        this.vibrate(50);
                        setTimeout(() => { this.showLevelUp = false; }, 5000);
                    }
                    this.totalXp = e.totalXp;
                })
                .listen('.WebRTCSignalEvent', (e) => self.handleSignal(e))
                .listen('.MessageSentEvent', (e) => self.handleIncomingMsg(e))
                .listen('.UserTypingEvent', (e) => self.handleTyping(e));

                if (window.location.pathname === '/chat') {
                    this.$nextTick(async () => {
                        await self.initMedia();
                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('accept_call')) self.setupPersonalCall(parseInt(urlParams.get('accept_call')), true);
                        else if (urlParams.has('call_to')) self.setupPersonalCall(parseInt(urlParams.get('call_to')), false);
                        setInterval(() => { this.refreshVideoTags(); }, 2000);
                    });
                }
                this.loadFriends(); this.loadHistory(); this.loadBlocked();
                this.startStats();
                this.startHeartbeat();
        },

formatDateTime(msg) {
            if (!msg) return '';
            
            const dateSource = msg.created_at || msg.timestamp;
            if (!dateSource) return '';

            const msgDate = new Date(dateSource);
            const today = new Date();
            const yesterday = new Date();
            yesterday.setDate(today.getDate() - 1);

            // Форматируем время: HH:MM
            const timeStr = msgDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            // 1. Проверяем, сегодня ли отправлено сообщение
            if (msgDate.toDateString() === today.toDateString()) {
                return timeStr;
            }

            // 2. Проверяем, вчера ли
            if (msgDate.toDateString() === yesterday.toDateString()) {
                return `Вчера, ${timeStr}`;
            }

            // 3. Проверяем, прошлый ли это год
            if (msgDate.getFullYear() !== today.getFullYear()) {
                // Выведет локализованную дату с годом: "14 июля 2025 г., 10:59" (или без "г." в зависимости от настроек)
                const dateWithYearStr = msgDate.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
                return `${dateWithYearStr}, ${timeStr}`;
            }

            // 4. Если текущий год, но старше двух дней: "14 июля, 10:59"
            const dateStr = msgDate.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
            return `${dateStr}, ${timeStr}`;
        },
        // --- MEDIA CONTROL ---

async initMedia() {
    if (this.localStream) {
        if (this.$refs.localVideo) this.$refs.localVideo.srcObject = this.localStream;
        return;
    }
    try {
        this.localStream = await navigator.mediaDevices.getUserMedia({ 
            video: { width: { ideal: 640 }, height: { ideal: 480 }, frameRate: { max: 30 } }, 
            audio: true 
        });
        if (this.$refs.localVideo) {
            this.$refs.localVideo.srcObject = this.localStream;
            // ФИКС ДЛЯ МОБИЛОК: Принудительный старт
            this.$refs.localVideo.play().catch(e => console.log("Auto-play blocked", e));
        }
    } catch (e) { 
        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: '{{ __('app.Camera_Permission_Denied') }}'}})); 
    }
    if (this.$refs.localVideo) {
        this.$refs.localVideo.srcObject = this.localStream;
        
        // Специфичный фикс для iPhone: принудительный вызов play()
        // Safari блокирует видео, если не было взаимодействия, 
        // поэтому мы вызываем это внутри асинхронного потока
        try {
            await this.$refs.localVideo.play();
        } catch (e) {
            console.log("iOS Autoplay prevented, waiting for user click");
        }
    }
},

        toggleFocus(target) {
            if (this.state !== 'connected') return; // не меняем в режиме поиска
            this.layoutFocus = (this.layoutFocus === target) ? 'split' : target;
        },
        toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
        toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
        toggleBeauty() {
            this.beautyFilter = !this.beautyFilter;
            this.syncFilters();
            window.dispatchEvent(new CustomEvent('toast', {detail: {msg: this.beautyFilter ? '{{ __('app.Contrast_Filter_On') }}' : '{{ __('app.Contrast_Filter_Off') }}'}}));
        },

        toggleCinema() {
            this.cinemaFilter = !this.cinemaFilter;
            this.syncFilters();
            window.dispatchEvent(new CustomEvent('toast', {detail: {msg: this.cinemaFilter ? '{{ __('app.Monochrome_Filter_On') }}' : '{{ __('app.Monochrome_Filter_Off') }}'}}));
        },

        syncFilters() {
            // Отправляем текущее состояние своих фильтров партнеру
            this.signal({ 
                type: 'filter-sync', 
                filters: { beauty: this.beautyFilter, cinema: this.cinemaFilter } 
            });
        },
        
        getFilterClass(target) {
            const f = (target === 'local') ? { b: this.beautyFilter, c: this.cinemaFilter } : { b: this.partnerFilters.beauty, c: this.partnerFilters.cinema };
            if (f.b && f.c) return 'filter-both';
            if (f.b) return 'filter-beauty';
            if (f.c) return 'filter-cinema';
            return '';
        },

        // --- WebRTC HANDLERS ---

 initPC() {
    this.startVideoWatchdog();
    if (this.pc) this.pc.close();
    const self = this;
    this.iceQueue = [];
    this.pc = new RTCPeerConnection(this.rtcConfig);

    // 1. Обработка необходимости пересогласования
    this.pc.onnegotiationneeded = async () => {
        try {
            console.log("[WebRTC] Negotiation needed...");
            await self.sendOffer();
        } catch (err) {
            console.error("[WebRTC] Negotiation Error:", err);
        }
    };

    // 2. Отправка ICE-кандидатов партнеру
    this.pc.onicecandidate = (e) => { 
        if (e.candidate) self.signal({ type: 'ice', candidate: e.candidate }); 
    };

    // 3. Мониторинг общего состояния соединения
    this.pc.onconnectionstatechange = () => {
        console.log("[WebRTC] Connection State:", this.pc.connectionState);
        
        if (this.pc.connectionState === 'connected') {
            console.log("[WebRTC] Fully Connected. Checking track health...");
            this.startVideoWatchdog();
            // Если через 2 секунды после коннекта видео всё еще черное (readyState < 2)
            setTimeout(() => {
                const remoteVid = document.getElementById('remoteVideo') || this.$refs.remoteVideo;
                if (remoteVid && remoteVid.readyState < 2) {
                    console.warn("[WebRTC] Video stuck at black screen. Nudging with ICE Restart...");
                    // Инициируем рестарт только если мы "вежливая" сторона, чтобы не было коллизий
                    if (self.isPolite()) {
                        self.sendOffer({ iceRestart: true });
                    }
                }
            }, 2000);
        }
        if (this.pc.connectionState === 'failed' || this.pc.connectionState === 'disconnected') {
            // Если всё совсем упало — выключаем его
            if (this.watchdogTimer) clearInterval(this.watchdogTimer);
        }
    };

    // 4. Обработка входящих медиа-треков
this.pc.ontrack = (e) => {
    const remoteStream = e.streams[0];
    const videoEl = document.getElementById('remoteVideo');
    
    if (videoEl && remoteStream) {
        if (videoEl.srcObject !== remoteStream) {
            videoEl.srcObject = remoteStream;
        }

        setTimeout(() => {
            const playPromise = videoEl.play();
            if (playPromise !== undefined) {
                playPromise.catch(err => {
                    // ИГНОРИРУЕМ AbortError (прервано переключением)
                    if (err.name === 'AbortError') return; 
                    
                    // Если это другая ошибка (например, блокировка автоплея iOS)
                    videoEl.muted = true;
                    videoEl.play().then(() => {
                        setTimeout(() => { videoEl.muted = false; }, 100);
                    }).catch(e => {});
                });
            }
        }, 150);
    }
};

    // 5. КРИТИЧЕСКИЙ БЛОК: Мониторинг сетевых путей (ICE)
this.pc.oniceconnectionstatechange = () => {
        const iceState = self.pc.iceConnectionState;
        console.log("[WebRTC] ICE State Changed:", iceState);

        if (['disconnected', 'failed'].includes(iceState)) {
            this.partnerState = 'problem';
            this.wasDisconnected = true; // Флаг реального обрыва
            console.log("[WebRTC] Network path lost. Starting recovery timer...");
            
            setTimeout(() => {
                const currentState = self.pc.iceConnectionState;
                if (['disconnected', 'failed'].includes(currentState)) {
                    // УБРАНА ПРОВЕРКА isPolite! Теперь спасают связь оба.
                    console.log("[WebRTC] Proactive ICE Restart initiated after dropout.");
                    self.sendOffer({ iceRestart: true });
                }
            }, 3000);
        } 
        else if (['connected', 'completed'].includes(iceState)) {
            this.partnerState = 'active';

            this.$nextTick(() => {
                const remoteVid = self.$refs.remoteVideo || document.getElementById('remoteVideo');
                if (remoteVid) {
                    // ХАК ПЕРЕЗАПУСКА ВИДЕО: Срабатывает ТОЛЬКО если до этого был обрыв
                    if (this.wasDisconnected && remoteVid.srcObject) {
                        const stream = remoteVid.srcObject;
                        remoteVid.srcObject = null;
                        remoteVid.srcObject = stream;
                        this.wasDisconnected = false;
                    }

                    remoteVid.play().catch(err => {
                        remoteVid.muted = true; 
                        remoteVid.play();
                    });
                }
            });
        }
    };

    // 6. Добавление локальных треков в соединение
    if (this.localStream) {
        this.localStream.getTracks().forEach(t => {
            this.pc.addTrack(t, this.localStream);
        });
    }
},

        isPolite() {
                if (!this.partnerId) return true; // По умолчанию вежливы, если партнер не определен
                return Number(myId) < Number(this.partnerId);
        },

async handleSignal(e) {
    const m = e.data;
    const self = this;
    
    // Блокировка сигналов рулетки, если мы в групповой комнате
    if (window.location.pathname.includes('/rooms/') && 
        ['offer', 'answer', 'ice'].includes(m.type)) {
        return; 
    }

    // 1. СИСТЕМНЫЕ СИГНАЛЫ (Высокий приоритет)
    if (m.type === 'you-are-blocked') { 
        this.stopCall(false); 
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Blacklisted') }}' } }));
        setTimeout(() => window.location.href = '/dashboard', 3000);
        return; 
    }
    
    // Входящий вызов
    if (m.type === 'incoming-call') { 
        this.incomingCall = m; 
        this.isRinging = true;
        this.playSound(this.ringtone); // Используем наш фикс для звука
        return; 
    }

    // Если звонок сброшен или принят - выключаем рингтон
    if (['hang-up', 'peer-disconnected', 'call-accepted'].includes(m.type)) {
        this.stopRingtone();
        if (m.type !== 'call-accepted') this.reset();
    }
    
    // 2. ЗАЩИТА: Проверка наличия PeerConnection ТОЛЬКО для строгих медиа-событий
    const strictMediaSignals = ['offer', 'answer', 'ice', 'request-keyframe', 'blitz', 'filter-sync'];
    if (strictMediaSignals.includes(m.type)) {
        if (!this.pc && m.type !== 'call-accepted') {
            console.warn("[WebRTC] Signal received but PeerConnection is null. Ignoring:", m.type);
            return; 
        }
    }
    
    // 3. ЛОГИКА ВОССТАНОВЛЕНИЯ (Status Sync)
    if (m.type === 'status-sync') {
        this.partnerState = m.state;
        if (m.state === 'active') {
            console.log("[WebRTC] Partner is back. Checking stream health...");
            // Тот самый таймер для проверки "черного экрана"
            setTimeout(() => {
                const videoEl = document.getElementById('remoteVideo');
                if (videoEl && videoEl.readyState < 2 && this.state === 'connected') {
                    console.warn("[WebRTC] Stream is still frozen. Initiating ICE Restart...");
                    this.sendOffer({ iceRestart: true });
                }
            }, 2500);
        }
        return;
    }

    // Запрос ключевого кадра (важно для iOS после возврата из фона)
    if (m.type === 'request-keyframe') {
        console.log("[WebRTC] Partner requested keyframe.");
        if (this.pc) {
            this.pc.getSenders().forEach(sender => {
                if (sender.track && sender.track.kind === 'video') {
                    sender.track.enabled = false;
                    sender.track.enabled = true;
                }
            });
        }
        return;
    }

    // 4. ИНТЕРАКТИВ И СОЦИАЛКА
    if (m.type === 'filter-sync') { this.partnerFilters = m.filters; return; }
    if (['peer-disconnected', 'hang-up', 'peer-skipped'].includes(m.type)) { this.stopCall(false); return; }
    
if (m.type === 'call-accepted') { 
    console.log("[WebRTC] Partner is ready. Initiating handshake...");
    this.state = 'connected'; 
    this.startCallTimer();
    if (!this.pc) this.initPC(); 
    
    // Принудительно загоняем треки перед созданием оффера
    if (this.localStream) {
        const senders = this.pc.getSenders();
        this.localStream.getTracks().forEach(track => {
            if (!senders.some(s => s.track && s.track.kind === track.kind)) {
                this.pc.addTrack(track, this.localStream);
            }
        });
    }

    if (Number(myId) < Number(this.partnerId)) {
        console.log("[WebRTC] I am the leader, sending offer...");
        setTimeout(() => { this.sendOffer(); }, 500); 
    }
    return; 
}
    
    if (m.type === 'icebreaker') {
        this.displayIcebreaker(m.index);
        return;
    }

    if (m.type === 'blitz') {
        this.startBlitzEffect(); // Запускаем эффект, так как его инициировал партнер
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '⚡️ {{ __('app.System_Overload') }}' } }));
        return;
    }

    if (m.type === 'roulette-chat') {
        // Воспроизводим звук входящего сообщения
        if (this.audioUnlocked) this.msgSound.play().catch(()=>{});
        
        // Добавляем текст в интерфейс рулетки
        this.messages.push({
            isMe: false, 
            text: m.text, 
            timestamp: Date.now()
        }); 
        this.scrollChat(); 
        return;
    }

    // 5. ОСНОВНОЙ СИГНАЛИНГ WebRTC (Perfect Negotiation)
    if (this.isProcessingSignal) return;
    
    try {
        if (m.type === 'offer') {
            this.isProcessingSignal = true;
            
            const offerCollision = (this.makingOffer || this.pc.signalingState !== 'stable');
            this.ignoreOffer = !this.isPolite() && offerCollision;
            
            if (this.ignoreOffer) {
                console.warn("[WebRTC] Collision: Ignoring offer");
                this.isProcessingSignal = false;
                return;
            }

            if (m.iceRestart) {
                console.log("[WebRTC] Partner initiated ICE Restart. Clearing old candidates...");
                this.iceQueue = [];
            }

            if (offerCollision) {
                console.log("[WebRTC] Collision: Polite peer rolling back local offer.");
                await Promise.all([
                    this.pc.setLocalDescription({ type: "rollback" }),
                    this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }))
                ]);
            } else {
                await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }));
            }
            
            const answer = await this.pc.createAnswer();
            await this.pc.setLocalDescription(answer);
            this.signal({ type: 'answer', sdp: this.pc.localDescription.sdp }); 
            while(this.iceQueue.length) {
                await this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
            }
            this.isProcessingSignal = false;

        } else if (m.type === 'answer') {
            this.isProcessingSignal = true;
            if (this.pc.signalingState === 'have-local-offer') {
                await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(m.sdp) }));
                while(this.iceQueue.length) {
                    await this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
                }
            }
            this.isProcessingSignal = false;

        } else if (m.type === 'ice') {
            const candidate = new RTCIceCandidate(m.candidate);
            if (!this.pc || !this.pc.remoteDescription || !this.pc.remoteDescription.type) {
                this.iceQueue.push(candidate);
            } else {
                await this.pc.addIceCandidate(candidate).catch(e => {
                    if (!this.ignoreOffer) console.warn("[WebRTC] ICE Error", e);
                });
            }
        }
    } catch (err) {
        console.error("[WebRTC] Signal Handling Critical Error:", err);
        this.isProcessingSignal = false;
    }

},

        // --- CALLS LOGIC ---

async handleMatch(e) {
            if (this.callContext === 'personal') return;
            this.isPartnerProfileOpen = false;
            this.uiShowPartnerCard = false;
            // В Laravel Echo объект события e обычно содержит public свойства класса MatchFoundEvent.
            // Если событие пришло как { partnerData: {...} }, берем его, если нет - берем e напрямую.
            const partner = e.partnerData || e || {};
            const newPartnerId = partner.id ? Number(partner.id) : null;
            
            if (!newPartnerId) {
                console.error("[Caspian] Match received but partner ID is missing!", e);
                return;
            }

            // Анти-дублирование
            if (this.partnerId === newPartnerId && this.state === 'connected') {
                return;
            }

            this.reset();
            this.partnerId = newPartnerId;
            this.callContext = 'roulette';
            
            // Наполняем объект данными
            this.partnerData = {
                id: this.partnerId,
                name: partner.name || 'Anonymous',
                gender: partner.gender, // Сохраняем пол
                age: partner.age,       // Сохраняем возраст
                level: partner.level || 1,
                badge: partner.badge,
                rank_name: partner.rank_name || 'Regular',
                karma: partner.karma || 0,
                country_code: partner.country_code || 'us',
                country_flag: partner.country_flag || 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg',
                interests: partner.interests || [],
                // Забираем общие интересы, если бэкенд их прислал
                common_interests: partner.common_interests || [] ,
                karma: partner.karma || 0,
                blocked_count: partner.blocked_count || 0,
                ban_count: partner.ban_count || 0,
                vpn: partner.vpn,
            };
            
            this.isFriend = this.friendsList.some(f => Number(f.id) === this.partnerId);

            // ЛОГИКА ОТОБРАЖЕНИЯ ИНТЕРЕСОВ
            // 1. Приоритет данным от бэкенда (common_interests)
            // 2. Если бэкенд пуст, считаем сами на фронте
            let common = this.partnerData.common_interests;
            
            if (!common || common.length === 0) {
                const myInts = (this.myInterests || []).map(i => String(i).toLowerCase());
                const pInts = (this.partnerData.interests || []).map(i => String(i).toLowerCase());
                common = pInts.filter(i => myInts.includes(i));
            }

            this.commonInterests = common;

            if (this.commonInterests.length > 0) {
                this.showInterestMatch = true;
                setTimeout(() => { this.showInterestMatch = false; }, 6000);
            }

            // Статус и инициализация
            this.state = 'connected';
            this.tab = 'chat';
            
            this.initPC();

            if (myId < this.partnerId) {
                setTimeout(() => this.sendOffer(), 1200);
            }

            this.startCallTimer();
        },
// Находим функцию setupPersonalCall и заменяем её на улучшенную версию
async setupPersonalCall(id, isAccepted) {
    this.callContext = 'personal';
    this.partnerId = id;
    this.state = 'connecting';
    this.micEnabled = true; // Принудительно включаем звук
    this.camEnabled = true; // Принудительно включаем видео

    window.history.replaceState({}, '', '/chat');

    try {
        // 1. КРИТИЧЕСКИЙ ФИКС: Создаем PeerConnection ДО включения камеры.
        // Если придет offer или ice, пока мы ждем разрешения на камеру, они не потеряются!
        if (!this.pc) this.initPC();

        // 2. Включаем камеру (ждет получения потока)
        await this.initMedia();

        // 3. Безопасно добавляем треки в уже существующий PC
        if (this.pc && this.localStream) {
            const senders = this.pc.getSenders();
            this.localStream.getTracks().forEach(track => {
                if (!senders.some(s => s.track && s.track.kind === track.kind)) {
                    this.pc.addTrack(track, this.localStream);
                }
            });
        }

        // 4. Инфо о партнере
        const res = await window.axios.get(`/chat/user-info/${id}`);
        this.partnerData = res.data;

        if (isAccepted) {
            // МЫ ПРИНИМАЕМ ЗВОНОК
            this.state = 'connected';
            setTimeout(() => {
                this.signal({ type: 'call-accepted' });
            }, 500);
        } else {
            // МЫ ЗВОНИМ
            const r = await window.axios.post('/chat/contact/call', { contactId: id });

            if (r.data.status === 'busy') {
                this.stopCall(false);
                window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.User_Is_Busy') }}' } }));
                return;
            }

            window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Calling') }}...' } }));
        }
    } catch (e) {
        console.error("Call Setup Error:", e);
        this.stopCall(false);
    }
},

        unblock(blockedId) {
            window.axios.post('/chat/unblock', { blockedId: blockedId })
                .then(() => {
                    window.dispatchEvent(new CustomEvent('toast', {detail: {msg: '{{ __('app.Interlocutor_Unblocked') }}'}}));
                    this.loadBlocked(); // Обновить список ЧС
                    this.loadFriends(); // <--- ЭТОТ ВЫЗОВ ВЕРНЕТ ДРУГА В ИНТЕРФЕЙС
                    this.loadHistory(); // <--- ЭТОТ ВЫЗОВ ВЕРНЕТ В ИСТОРИЮ
                });
        },

        stopRingtone() {
            this.isRinging = false;
            this.ringtone.pause();
            this.ringtone.currentTime = 0;
        },

// 1. ПРАВИЛЬНАЯ БЕСШУМНАЯ РАЗБЛОКИРОВКА
unlockAudio() {
    if (this.audioUnlocked) return;
    
    // Сразу ставим флаг, чтобы больше не пытаться
    this.audioUnlocked = true;

    try {
        // Разблокируем системный контекст через "тишину"
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (AudioContext) {
            const ctx = new AudioContext();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            gain.gain.value = 0; 
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start(0);
            oscillator.stop(0.1);
            if (ctx.state === 'suspended') ctx.resume();
        }

        // КРИТИЧЕСКИЙ МОМЕНТ: "Прогреваем" файлы ТИХО
        // Мы запускаем их с громкостью 0 и сразу стопаем. 
        // Это дает iOS понять, что эти файлы "легитимны" для будущего автоплея.
        [this.ringtone, this.msgSound, this.blitzSound].forEach(audio => {
            audio.muted = true; // Мьютим перед "прогревом"
            audio.play().then(() => {
                audio.pause();
                audio.muted = false; // Возвращаем звук
                audio.currentTime = 0;
            }).catch(() => {});
        });

        console.log("Caspian Audio: Stealth Unlock Done 🔓");
    } catch (e) {
        console.error("Audio unlock error", e);
    }
},

// 2. СТРОГИЙ КОНТРОЛЬ ПРОИГРЫВАНИЯ
playSound(audioElement) {
    if (!this.audioUnlocked || !audioElement) return;

    // Сбрасываем и играем
    audioElement.pause();
    audioElement.currentTime = 0;
    
    // На iOS лучше играть после микро-паузы, чтобы не было конфликта с кликом
    setTimeout(() => {
        const playPromise = audioElement.play();
        if (playPromise !== undefined) {
            playPromise.catch(err => {
                if (err.name !== 'AbortError') console.warn("Playback prevented", err);
            });
        }
    }, 20);
},

// 3. ФУНКЦИЯ ОСТАНОВКИ (обязательно вызывай её при сбросе/приеме звонка)
stopRingtone() {
    this.isRinging = false;
    this.ringtone.pause();
    this.ringtone.currentTime = 0;
},
        acceptCall() {
            this.stopRingtone(); // Выключаем звук перед переходом
            const fromId = this.incomingCall.fromId;
            window.location.href = `/chat?accept_call=${fromId}`;
        },

        rejectCall() {
            this.stopRingtone(); // Выключаем звук
            if (this.incomingCall) this.signalTo(this.incomingCall.fromId, { type: 'hang-up' });
            this.incomingCall = null;
        },

        stopCall(notify = true) {
            if (notify && this.partnerId) this.signal({ type: 'hang-up' });
            this.stopRingtone();
            this.reset();
            window.axios.post('/chat/leave');
            window.dispatchEvent(new CustomEvent('toast', {detail: {msg: '{{ __('app.Call_Ended') }}'}}));
        },

reset() {
    this.ringtone.pause();
    this.stopCallTimer();
    this.isPartnerProfileOpen = false;
    this.uiShowPartnerCard = false;
    if (this.pc) { this.pc.close(); this.pc = null; }
    if (this.$refs.remoteVideo) {
        this.$refs.remoteVideo.pause();
        this.$refs.remoteVideo.srcObject = null;
    }
    this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.callContext = null;
    this.partnerFilters = { beauty: false, cinema: false }; this.messages = [];
    
    if (this.watchdogTimer) {
        clearInterval(this.watchdogTimer);
        this.watchdogTimer = null;
    }

    // Правильная очистка таймеров
    if (this.iceTimer) { clearInterval(this.iceTimer); this.iceTimer = null; }
    if (this.blitzTimer) { clearInterval(this.blitzTimer); this.blitzTimer = null; }
    this.icebreakerCooldown = 0;
    this.blitzCooldown = 0;
},

        // --- MESSENGER & DATA ---

async toggleContact() {
    if (!this.partnerId) return;
    try {
        const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId });
        
        if (res.data.action === 'requested') {
            window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Request_Encryted_And_Send') }} 📡' } }));
            this.isFriend = false; 
            // Это сообщение создало в базе SYSTEM_FRIEND_REQUEST, 
            // которое сразу отобразится в чате
        } else if (res.data.action === 'exists') {
             window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Protocol_Already_Active') }}' } }));
        }
    } catch (e) {
        console.error(e);
    }
},

async handleFriendRequest(senderId, action) {
    try {
        if (action === 'accept') {
            await window.axios.post('/chat/contact/accept', { senderId });
            window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Identyty_Verified') }} ✓' } }));
        } else {
            await window.axios.post('/chat/contact/decline', { senderId });
            window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Request_Terminated') }}' } }));
        }
        
        // Моментально обновляем данные
        this.loadFriends();
        this.loadHistory();
        
        // Перезагружаем историю сообщений, чтобы системное сообщение сменилось на результат
        if (this.activeFriend && Number(this.activeFriend.id) === Number(senderId)) {
            this.openFriendChat(senderId);
        }
    } catch (e) {
        console.error("Friend request error:", e);
    }
},

    handleIncomingMsg(e) {
        const m = e.messageData;
        
        // 1. Защита от дублей
        if (this.processedEvents.has('msg_' + m.id)) return;
        
        // Если мессенджер закрыт в момент прихода чего-либо — зажигаем иконку
        if (!this.globalSidebarOpen) {
            this.hasNewNotification = true;
            this.vibrate(30); // Короткая вибрация для привлечения внимания
        }
        this.processedEvents.add('msg_' + m.id);
        
        // 2. Системные события дружбы (Handshake)
        if (m.message === 'SYSTEM_FRIEND_REQUEST' || m.message === 'SYSTEM_FRIEND_ACCEPTED') {
            this.loadFriends();
            this.loadHistory();
            return;
        }

        // 3. Воспроизводим звук сообщения
        if (this.audioUnlocked) this.msgSound.play().catch(()=>{});

        // 4. Определение отправителя (с фолбэком)
        const senderIdNum = Number(m.sender_id || (this.activeFriend ? this.activeFriend.id : 0));
        if (!senderIdNum) return;

        // 5. CASE 1: Если чат с этим человеком открыт прямо сейчас
        if (this.activeFriend && Number(this.activeFriend.id) === senderIdNum) { 
            this.friendMessages.push(m); 
            this.scrollFriendChat(); 
            return;
        }

        // 6. CASE 2: Логика фоновых уведомлений (Чат закрыт)
        
        // Сначала ищем в списке друзей
        const friend = this.friendsList.find(f => Number(f.id) === senderIdNum);

            if (friend) {
                // 1. Если это друг (или уже есть в списке контактов) — обновляем индикацию в "Контактах"
                friend.has_new_message = true; 
                friend.unread_count = (friend.unread_count || 0) + 1;
                
                // Перемещаем контакт на самый верх списка друзей
                this.friendsList = [
                    friend,
                    ...this.friendsList.filter(f => Number(f.id) !== senderIdNum)
                ];
            } else {
                // 2. Если отправителя нет в "Контактах" — это новый протокол (незнакомец)
                // Просто перегружаем списки: человек появится в "Контактах" (с ярлыком Pending/New)
                this.loadFriends();
                this.loadHistory();

                // Показываем глобальное уведомление, так как в Контактах он может быть внизу
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { msg: `Incoming Secure Connection Request` } 
                }));
            }
        },

        handleTyping(e) {
            if (e.senderId === this.partnerId || (this.activeFriend && e.senderId === this.activeFriend.id)) {
                this.isPartnerTyping = true;
                this.typingPartnerName = (this.partnerId === e.senderId) ? (this.partnerData?.name || 'Partner') : this.activeFriend.name;
                
                // Сбрасываем старый таймер, если он был
                if (this.typingTimer) clearTimeout(this.typingTimer);
                
                // Прячем уведомление через 3 секунды тишины
                this.typingTimer = setTimeout(() => { 
                    this.isPartnerTyping = false; 
                }, 3000);
            }
        },

        async sendOffer(options = {}) { 
            if (!this.pc) return;
            
            const isRestart = options.iceRestart === true;

            if (this.makingOffer || (this.pc.signalingState !== 'stable' && !isRestart)) {
                return;
            }

            try {
                this.makingOffer = true;
                const offer = await this.pc.createOffer(options);
                
                if (this.pc.signalingState !== 'stable' && !isRestart) {
                    return;
                }
                
                await this.pc.setLocalDescription(offer);
                
                // Ждем успешной отправки по сети
                await this.signal({ 
                    type: 'offer', 
                    sdp: this.pc.localDescription.sdp,
                    iceRestart: isRestart 
                });
                
            } catch (err) {
                console.error("[WebRTC] Offer Network Error:", err);
                // Если сигнал не ушел (нет интернета), откатываем состояние, чтобы не зависнуть
                if (this.pc && this.pc.signalingState !== 'stable') {
                    await this.pc.setLocalDescription({ type: "rollback" }).catch(()=>{});
                }
                // Если это попытка спасти связь, пробуем еще раз через 3 сек
                if (isRestart) {
                    setTimeout(() => this.sendOffer({ iceRestart: true }), 3000);
                }
            } finally {
                this.makingOffer = false;
            }
        },
        
        reportPartner() {
            if (!this.partnerId || !confirm('{{ __("app.Report_Desc") }}')) return;

            const video = document.getElementById('remoteVideo');
            let screenshot = null;

            try {
                // Создаем невидимый холст для захвата кадра
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Сжимаем в JPEG для экономии трафика
                screenshot = canvas.toDataURL('image/jpeg', 0.7);
            } catch (e) {
                console.error("Failed to capture evidence", e);
            }

            window.axios.post('/report', { 
                reported_id: this.partnerId, 
                reason: 'general',
                image: screenshot // Отправляем "фото прегрешения"
            }).then(() => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Repoert_Transmitted_To_Shield') }} 🛡️' } }));
                this.startSearch();
            });
        },


        signal(data) { 
            if (this.partnerId) {
                return window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } }); 
            }
            return Promise.resolve();
        },
        signalTo(toId, data) { 
            return window.axios.post('/chat/signal', { partnerId: toId, data: { ...data, from: myId } }); 
        },
        normalizeSdp(sdp) { return typeof sdp === 'string' ? sdp.trim().split('\n').map(l => l.trim()).join('\r\n') + '\r\n' : sdp; },
        startHeartbeat() { setInterval(() => window.axios.post('/ping'), 15000); },
        startStats() { setInterval(async () => { if (this.pc?.iceConnectionState === 'connected') { const s = await this.pc.getStats(); s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); },
        handleVisibilityChange() {
            if (!this.partnerId) return;

            if (document.visibilityState === 'visible') {
                console.log("[App] Вернулись во вкладку. Восстановление...");
                
                // Оповещаем партнера, что мы снова тут
                this.signal({ type: 'status-sync', state: 'active' });

                // Если это мобилка (Android/iOS) — принудительно оживляем камеру
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                if (isMobile) {
                    // Даем браузеру 300мс "проснуться", прежде чем дергать камеру
                    setTimeout(() => this.rebootMobileCamera(), 300);
                }

                // Если ICE соединение упало — чиним его
                if (this.pc && (this.pc.iceConnectionState === 'disconnected' || this.pc.iceConnectionState === 'failed')) {
                    console.warn("[Watchdog] Соединение потеряно в фоне. Рестарт сети...");
                    this.sendOffer({ iceRestart: true });
                }

                // На всякий случай запускаем видео теги (Safari часто ставит их на паузу)
                const remoteVid = document.getElementById('remoteVideo');
                if (remoteVid) remoteVid.play().catch(()=>{});
                
            } else {
                // Ушли в фон
                this.signal({ type: 'status-sync', state: 'away' });
            }
        },
        startVideoWatchdog() {
            // Если таймер уже запущен — сначала очищаем его (защита от дублей)
            if (this.watchdogTimer) clearInterval(this.watchdogTimer);

            console.log("[Watchdog] Guard activated.");

            this.watchdogTimer = setInterval(() => {
                const video = document.getElementById('remoteVideo');
                
                // ПРОВЕРКА: Если мы в статусе "подключены", но видео-поток "замер" (readyState < 2)
                // 0 = HAVE_NOTHING, 1 = HAVE_METADATA. Если данные не идут — readyState будет 0 или 1.
                if (this.state === 'connected' && video && video.readyState < 2) {
                    console.warn("[Watchdog] Stream frozen or black screen detected. Auto-repairing...");
                    
                    // Инициируем "Вежливый перезапуск" соединения (ICE Restart)
                    this.sendOffer({ iceRestart: true });
                }
            }, 5000); // Проверка каждые 5 секунд
        },

        async startSearch() { 
            this.vibrate(15); // Тактильный отклик при клике
            this.reset(); 
            this.isPartnerProfileOpen = false;  
            this.state = 'searching'; 
            this.callContext = 'roulette'; 
            await window.axios.post('/chat/search'); 
        },
        loadFriends() {
            window.axios.get('/chat/contacts').then(r => {
                this.friendsList = r.data.contacts.sort((a, b) => {
                    // Сначала сортируем по статусу (online выше offline)
                    if (a.is_online !== b.is_online) {
                        return b.is_online ? 1 : -1;
                    }
                    // Внутри каждой группы (online/offline) сохраняем порядок от сервера
                    return 0;
                });
            });
        },
        loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
        loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
        scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
        scrollFriendChat() { this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); },
        
        // Проверка: есть ли вообще что-то непрочитанное
        hasUnread() {
            return this.hasUnreadFriends() || this.hasUnreadHistory();
        },

        // Проверка: есть ли сообщения от друзей
        hasUnreadFriends() {
            return this.friendsList.some(f => f.unread_count > 0);
        },

        // Проверка: есть ли сообщения из истории (Logs)
        hasUnreadHistory() {
            return this.historyList.some(h => h.unread_count > 0);
        },
        
        openFriendChat(friendId) {
            if (!friendId) {
                console.error("Caspian DEBUG: Ошибка - передан пустой ID!");
                return;
            }

            const idNum = Number(friendId);

            // 1. Поиск контакта (сначала в друзьях, потом в истории)
            let target = this.friendsList.find(x => Number(x.id) === idNum);
            let isFriend = !!target;

            if (!target) {
                target = this.historyList.find(x => Number(x.id) === idNum);
            }

            // 2. Установка активного собеседника
            // Берем все данные из найденного объекта (включая статус accepted/pending)
            // Если вообще ничего не нашли — создаем объект "Незнакомец"
            this.activeFriend = target 
                ? { ...target } 
                : { id: idNum, name: 'User #' + idNum, status: 'none', is_online: false };

            // 3. Контекстное переключение вкладки (чтобы подсветить активный раздел)
            this.tab = isFriend ? 'friends' : 'history';

            // 4. Сброс индикации непрочитанных (локально)
            if (target) {
                target.unread_count = 0;
                if (isFriend) target.has_new_message = false;
            }

            // 5. Загрузка истории сообщений
            this.friendMessages = []; 
            window.axios.get(`/chat/history/${idNum}`)
                .then(res => { 
                    this.friendMessages = Array.isArray(res.data.messages) ? res.data.messages : []; 
                    // Прокрутка вниз после рендера сообщений
                    this.scrollFriendChat(); 
                })
                .catch(err => {
                    console.error("Caspian DEBUG: Axios Error:", err);
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Failed_To_Load_History') }}' } }));
                }); 
        },

        async clearMessages(contactId) {
            if (!confirm('Clear all messages in this chat?')) return;
            try {
                await window.axios.post('/chat/clear-messages', { contactId });
                this.friendMessages = []; // Моментально очищаем экран
                window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.History_Terminated') }} 🗑️' } }));
            } catch (e) { console.error(e); }
        },

        t(key) {
            const trans = {
                'male': '{{ __("app.Male") }}',
                'female': '{{ __("app.Female") }}',
                'all': '{{ __("app.Global_Match") }}'
            };
            return trans[key] || key;
        },
        async sendMsg() { 
            if (!this.chatInput.trim() || !this.partnerId) return; 
            const t = this.chatInput; 
            this.chatInput = ''; 
            
            // 1. Отображаем сообщение у себя
            this.messages.push({isMe: true, text: t, timestamp: Date.now()}); 
            this.scrollChat(); 
            
            // 2. Отправляем через WebRTC-сигналинг (БЕЗ сохранения в базу данных!)
            this.signal({ type: 'roulette-chat', text: t }).catch(err => {
                console.error("Failed to send roulette message:", err);
            });
        },
        async sendFriendMsg() { 
            if (!this.friendChatInput.trim() || !this.activeFriend) return; 
            const t = this.friendChatInput; 
            this.friendChatInput = ''; 
            
            // Отправляем на бэкенд
            const res = await window.axios.post('/chat/message/send', { 
                receiver_id: this.activeFriend.id, 
                message: t 
            }); 
            
            // Пушим ответ сервера (в котором ГАРАНТИРОВАННО есть id из базы данных!)
            this.friendMessages.push(res.data.message); 
            this.scrollFriendChat(); 
        },
        sendTypingSignal() { const rid = this.activeFriend ? this.activeFriend.id : this.partnerId; if (rid) window.axios.post('/chat/message/typing', { receiver_id: rid }); },
        callFriend(f) { if (!f.is_online) return; window.location.href = '/chat?call_to=' + f.id; }
    
    }
};
</script>
<div x-show="showLevelUp" x-transition.opacity.scale.origin.center class="fixed inset-0 z-[3000] flex items-center justify-center pointer-events-none" x-cloak>
    <div class="relative bg-[#0a0a0a]/90 backdrop-blur-3xl border-2 border-brand-indigo p-10 rounded-[3rem] shadow-[0_0_100px_rgba(99,102,241,0.5)] text-center">
        <div class="text-6xl mb-4">🏆</div>
        <h2 class="text-4xl font-black uppercase italic tracking-tighter text-white">{{ __('app.Level_UP') }}!</h2>
        <div class="mt-2 flex items-center justify-center gap-3">
            <span class="text-2xl font-black text-brand-indigo" x-text="'LVL ' + currentLevel"></span>
        </div>
    </div>
</div>
</body>
</html>
<style>