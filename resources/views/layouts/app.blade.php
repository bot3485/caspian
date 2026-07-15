<!DOCTYPE html>
<html lang="en">
<head>
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
      @click="unlockAudio()"
      @visibilitychange.window="handleVisibilityChange()"
      @pagehide.window="handleVisibilityChange()"
      @blur.window="handleVisibilityChange()"
      @focus.window="handleVisibilityChange()"
      makingOffer: false,
      ignoreOffer: false,
      isSettingRemoteAnswerPending: false,
      >
      
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
                    <p class="text-[8px] font-black text-brand-indigo uppercase tracking-[0.3em]">Incoming Session</p>
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

        // --- PARTNER DATA ---
        partnerId: null, partnerData: null, isFriend: false, partnerState: 'active',
        isPartnerTyping: false, typingPartnerName: '', ping: 0, 

        // --- LISTS ---
        friendsList: [], historyList: [], blockedList: [],
        activeFriend: null, friendMessages: [], friendChatInput: '',

        // --- WEBRTC & FILTERS ---
        pc: null, localStream: null, micEnabled: true, camEnabled: true, 
        isRemoteBlurred: false, showSelfVideo: true,
        beautyFilter: false, cinemaFilter: false,
        partnerFilters: { beauty: false, cinema: false },

        // --- CHAT ---
        messages: [], chatInput: '', 
        deviceModalOpen: false,
        videoDevices: [],
        audioDevices: [],
        selectedVideoId: '',
        selectedAudioId: '',
        showInterestMatch: false,
        commonInterests: [],
        isBlitzActive: false,
        blitzSound: new Audio('/sounds/glitch.wav'),

        // --- INTERNAL LOGIC ---
        isProcessingSignal: false, makingOffer: false, processedEvents: new Set(), iceQueue: [],
        rtcConfig: { iceServers: iceServers, bundlePolicy: "balanced", iceCandidatePoolSize: 10 },

filterModalOpen: false,
targetGender: '{{ Auth::user()->target_gender }}',
targetAgeMin: {{ Auth::user()->target_age_min }},
targetAgeMax: {{ Auth::user()->target_age_max }},

async applyFilters() {
    try {
        await window.axios.post('/profile', { 
            _method: 'PATCH',
            target_gender: this.targetGender,
            target_age_min: this.targetAgeMin,
            target_age_max: this.targetAgeMax
        });
        this.filterModalOpen = false;
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Targeting Updated 🎯' } }));
        
        // Если мы уже в поиске, перезапускаем его с новыми фильтрами
        if(this.state === 'searching') this.startSearch(); 
    } catch (e) {
        console.error("Filter Save Error:", e);
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Save Failed' } }));
    }
},

async sendIcebreaker() {
    if (this.state !== 'connected') return;

    try {
        // 1. Получаем случайный индекс от сервера
        const res = await window.axios.get('/icebreaker/random');
        const index = res.data.index;

        // 2. Отправляем этот индекс партнеру
        this.signal({ type: 'icebreaker', index: index });

        // 3. Показываем вопрос у себя (на своем языке)
        await this.displayIcebreaker(index);
    } catch (e) {
        console.error("Icebreaker failed", e);
    }
},
async displayIcebreaker(index) {
    try {
        const res = await window.axios.get(`/icebreaker/content/${index}`);
        window.dispatchEvent(new CustomEvent('toast', { 
            detail: { msg: '🎲 ' + res.data.question } 
        }));
    } catch (e) {
        console.error("Icebreaker content fetch failed", e);
    }
},

    unlockAudio() {
        if(!this.audioUnlocked) {
            // Проигрываем тишину, чтобы "легализовать" звук в браузере
            this.blitzSound.volume = 0;
            this.blitzSound.play().then(() => {
                this.blitzSound.pause();
                this.blitzSound.volume = 1;
                this.audioUnlocked = true;
                console.log("Audio Context Unlocked 🔊");
            }).catch(e => console.log("Audio still locked"));
        }
    },
        triggerBlitz() {
            if (this.state !== 'connected' || this.isBlitzActive) return;

            this.isBlitzActive = true;
            this.signal({ type: 'blitz' });

            // Безопасный запуск звука
            if (this.blitzSound) {
                this.blitzSound.currentTime = 0;
                this.blitzSound.play().catch(e => console.warn("Audio blocked by browser"));
            }

            setTimeout(() => { this.isBlitzActive = false; }, 6000);
        },


async rebootMobileCamera() {
    if (!this.localStream || !this.pc) return;
    
    console.log("[Android Fix] Инициирована аппаратная перезагрузка камеры...");
    
    try {
        // 1. Получаем ID текущей камеры
        const videoTrack = this.localStream.getVideoTracks()[0];
        const currentDeviceId = videoTrack ? videoTrack.getSettings().deviceId : null;

        // 2. Запрашиваем НОВЫЙ поток от той же камеры
        const freshStream = await navigator.mediaDevices.getUserMedia({
            video: { 
                deviceId: currentDeviceId ? { exact: currentDeviceId } : undefined,
                width: { ideal: 640 }, 
                height: { ideal: 480 } 
            },
            audio: false // Аудио не трогаем, чтобы не было щелчка
        });

        const newTrack = freshStream.getVideoTracks()[0];

        // 3. Заменяем трек в PeerConnection БЕЗ пересогласования (replaceTrack)
        const sender = this.pc.getSenders().find(s => s.track && s.track.kind === 'video');
        if (sender) {
            await sender.replaceTrack(newTrack);
        }

        // 4. Обновляем локальный поток и видео-тег
        this.localStream.removeTrack(videoTrack);
        videoTrack.stop(); // Останавливаем старый "зависший" трек
        this.localStream.addTrack(newTrack);
        
        if (this.$refs.localVideo) {
            this.$refs.localVideo.srcObject = this.localStream;
        }

        console.log("[Android Fix] Камера успешно перезапущена.");
    } catch (e) {
        console.error("[Android Fix] Ошибка при перезагрузке камеры:", e);
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
        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Device Error: Access Denied'}}));
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
            this.ringtone.loop = true;

            window.Echo.private(`user.${myId}`)
                .listen('.MatchFoundEvent', (e) => {
                    if (self.callContext === 'personal') return;
                    self.handleMatch(e);
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
        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Camera Permission Denied'}})); 
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
            if (this.pc) this.pc.close();
            const self = this;
            this.iceQueue = [];
            this.pc = new RTCPeerConnection(this.rtcConfig);
            this.pc.onnegotiationneeded = async () => {
                try {
                        console.log("[WebRTC] Negotiation needed...");
                        await self.sendOffer();
                    } catch (err) {
                        console.error("[WebRTC] Negotiation Error:", err);
                    }
                };
            
            this.pc.onicecandidate = (e) => { if (e.candidate) self.signal({ type: 'ice', candidate: e.candidate }); };
            this.pc.onconnectionstatechange = () => {
                if (this.pc.connectionState === 'connected') {
                    console.log("[WebRTC] Fully Connected. Checking tracks...");
                    // Если через 2 секунды после коннекта видео всё еще черное (readyState 0)
                    // пробуем сделать renegotiation
                    setTimeout(() => {
                        const remoteVid = document.getElementById('remoteVideo') || this.$refs.remoteVideo;
                        if (remoteVid && remoteVid.readyState < 2) {
                            console.warn("[WebRTC] Video stuck at black screen. Nudging...");
                            this.sendOffer({ iceRestart: true });
                        }
                    }, 2000);
                }
            };
this.pc.ontrack = (e) => {
    const remoteStream = e.streams[0];
    const videoEl = document.getElementById('remoteVideo') || this.$refs.remoteVideo;
    
    if (videoEl && remoteStream) {
        if (videoEl.srcObject !== remoteStream) {
            videoEl.srcObject = remoteStream;
        }

        // Системный обработчик: когда приходят первые пакеты после паузы
        e.track.onunmute = () => {
            console.log("[WebRTC] Remote track UNMUTED. Playing...");
            videoEl.play().catch(() => {
                videoEl.muted = true;
                videoEl.play();
            });
        };

        videoEl.play().catch(() => {
            videoEl.muted = true;
            videoEl.play();
        });
    }
};
            
this.pc.oniceconnectionstatechange = () => {
    const iceState = self.pc.iceConnectionState;
    console.log("[WebRTC] ICE State:", iceState);

    if (['disconnected', 'failed', 'closed'].includes(iceState)) {
        this.partnerState = 'problem';
    } 
    else if (['connected', 'completed'].includes(iceState)) {
        this.partnerState = 'active';

        // КРИТИЧЕСКИЙ ФИКС: Принудительно заставляем видео играть при восстановлении
        this.$nextTick(() => {
            const remoteVid = self.$refs.remoteVideo || document.getElementById('remoteVideo');
            if (remoteVid && (remoteVid.paused || remoteVid.readyState < 2)) {
                console.log("[WebRTC] Connection restored. Forcing video playback...");
                remoteVid.play().catch(err => {
                    console.warn("[WebRTC] Play failed, retrying muted...", err);
                    remoteVid.muted = true; // Иногда браузер требует mute для автоплея после дисконнекта
                    remoteVid.play();
                });
            }
        });
    }
};

            if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
        },

        isPolite() {
            return Number(myId) < Number(this.partnerId);
        },

async handleSignal(e) {
    const m = e.data;
    const self = this;
    
    // 1. Обработка системных сигналов (оставляем как есть)
    if (m.type === 'you-are-blocked') { 
        this.stopCall(false); 
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: '{{ __('app.Blacklisted') }}' } }));
        setTimeout(() => window.location.href = '/dashboard', 3000);
        return; 
    }
    if (m.type === 'incoming-call') { this.incomingCall = m; if(this.audioUnlocked) this.ringtone.play().catch(()=>{}); return; }
if (m.type === 'status-sync') {
    this.partnerState = m.state;
    
    if (m.state === 'active') {
        console.log("[WebRTC] Partner is back. Checking stream health...");
        
        // Если через 2.5 секунды видео так и не "отмерзло"
        setTimeout(() => {
            const videoEl = document.getElementById('remoteVideo');
            if (videoEl && videoEl.readyState < 2 && this.state === 'connected') {
                console.warn("[WebRTC] Stream is still frozen. Initiating ICE Restart...");
                // Тот, кто "вежливый", инициирует рестарт
                if (this.isPolite && this.isPolite()) {
                    this.sendOffer({ iceRestart: true });
                }
            }
        }, 2500);
    }
    return;
}
    if (m.type === 'filter-sync') { this.partnerFilters = m.filters; return; }
    if (['peer-disconnected', 'hang-up', 'peer-skipped'].includes(m.type)) { this.stopCall(false); return; }
    if (m.type === 'call-accepted') { this.state = 'connected'; this.initPC(); setTimeout(() => self.sendOffer(), 1000); return; }
    if (m.type === 'icebreaker') {
        // Получаем индекс и отображаем вопрос на своем языке
        this.displayIcebreaker(m.index);
        return;
    }
if (m.type === 'blitz') {
    this.isBlitzActive = true;
    
    // Используем тот же объект blitzSound
    if (this.blitzSound) {
        this.blitzSound.currentTime = 0;
        this.blitzSound.play().catch(e => console.warn("Audio blocked for partner"));
    }

    window.dispatchEvent(new CustomEvent('toast', { 
        detail: { msg: '⚡️ SYSTEM OVERLOAD' } 
    }));

    setTimeout(() => { 
        this.isBlitzActive = false; 
    }, 6000);
    return;
}
    // 2. Блокировка параллельной обработки описаний (Offer/Answer)
    if (this.isProcessingSignal) return;
    
    try {

        if (m.type === 'request-keyframe') {
            console.log("[WebRTC] Partner requested keyframe.");
            if (this.pc) {
                this.pc.getSenders().forEach(sender => {
                    if (sender.track && sender.track.kind === 'video') {
                        // Вместо выключения трека, мы просто "шевелим" его
                        // Этого достаточно, чтобы браузер понял: нужно слать ключевой кадр
                        const track = sender.track;
                        if (track.readyState === 'live') {
                            // Простое передергивание без задержек
                            track.enabled = false;
                            track.enabled = true;
                        }
                    }
                });
            }
            return;
        }

        if (m.type === 'offer') {
            this.isProcessingSignal = true;

            const offerCollision = (this.makingOffer || this.pc.signalingState !== 'stable');
            this.ignoreOffer = !this.isPolite() && offerCollision;
            
            if (this.ignoreOffer) {
                console.warn("[WebRTC] Collision: Ignoring offer");
                this.isProcessingSignal = false;
                return;
            }

            if (offerCollision) {
                console.log("[WebRTC] Collision: Polite peer rolling back local offer.");
                // Rollback возвращает состояние в 'stable', чтобы мы могли принять чужой оффер
                await Promise.all([
                    this.pc.setLocalDescription({ type: "rollback" }),
                    this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }))
                ]);
            } else {
                // Обычная установка без коллизии
                await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }));
            }

            await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }));
            const answer = await this.pc.createAnswer();
            await this.pc.setLocalDescription(answer);
            this.signal({ type: 'answer', sdp: this.pc.localDescription.sdp });
            
            // После установки RemoteDescription — выстреливаем накопленные ICE-кандидаты
            while(this.iceQueue.length) {
                await this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
            }
            this.isProcessingSignal = false;

        } else if (m.type === 'answer') {
            this.isProcessingSignal = true;
            
            // Защита от ошибки "Called in wrong state: stable"
            if (this.pc.signalingState === 'have-local-offer') {
                await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(m.sdp) }));
                
                while(this.iceQueue.length) {
                    await this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
                }
            }
            this.isProcessingSignal = false;

        } else if (m.type === 'ice') {
            const candidate = new RTCIceCandidate(m.candidate);
            
            // Если RemoteDescription еще не установлен — кладем в очередь (Fix ошибки №1)
            if (!this.pc || !this.pc.remoteDescription) {
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
            
            // Наполняем объект данными
            this.partnerData = {
                id: this.partnerId,
                name: partner.name || 'Anonymous Peer',
                level: partner.level || 1,
                badge: partner.badge,
                rank_name: partner.rank_name || 'Regular',
                karma: partner.karma || 0,
                country_code: partner.country_code || 'us',
                country_flag: partner.country_flag || 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg',
                interests: partner.interests || [],
                // Забираем общие интересы, если бэкенд их прислал
                common_interests: partner.common_interests || [] 
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
        },
        async setupPersonalCall(id, isAccepted) {
            this.callContext = 'personal';
            this.partnerId = id;
            this.state = 'connecting';
            window.history.replaceState({}, '', '/chat');
            try {
                const res = await window.axios.get(`/chat/user-info/${id}`);
                this.partnerData = res.data;
                this.isFriend = this.friendsList.some(f => f.id === id);
                if (isAccepted) {
                    this.state = 'connected';
                    this.initPC();
                    setTimeout(() => { this.signal({ type: 'call-accepted' }); }, 1000);
                } else {
                    const r = await window.axios.post('/chat/contact/call', { contactId: id });
                    if (r.data.status === 'busy') { this.stopCall(false); alert('User is busy'); }
                    else { this.state = 'connected'; this.initPC(); }
                }
            } catch (e) { this.stopCall(false); }
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

        acceptCall() {
            const fromId = this.incomingCall.fromId;
            this.incomingCall = null;
            this.ringtone.pause();
            window.location.href = `/chat?accept_call=${fromId}`;
        },

        rejectCall() {
            if (this.incomingCall) this.signalTo(this.incomingCall.fromId, { type: 'hang-up' });
            this.ringtone.pause(); this.incomingCall = null;
        },

        stopCall(notify = true) {
            if (notify && this.partnerId) this.signal({ type: 'hang-up' });
            this.reset();
            window.axios.post('/chat/leave');
            window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Call Ended'}}));
        },

        reset() {
            this.ringtone.pause();
            this.isPartnerProfileOpen = false;
            if (this.pc) { this.pc.close(); this.pc = null; }
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
            this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.callContext = null;
            this.partnerFilters = { beauty: false, cinema: false }; this.messages = [];
        },

        // --- MESSENGER & DATA ---

        async toggleContact() {
            if (!this.partnerId) return;
            try {
                const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId });
                
                // Наш контроллер возвращает flag 'isFriend' при добавлении/удалении
                this.isFriend = res.data.isFriend;
                
                // Моментально обновляем локальный список контактов во вкладке Contacts
                this.loadFriends();
                
                // Выводим красивое уведомление в зависимости от действия
                const toastMsg = this.isFriend ? 'Identity Linked ✓' : 'Identity Unlinked ✕';
                window.dispatchEvent(new CustomEvent('toast', { detail: { msg: toastMsg } }));
            } catch (e) {
                console.error("Ошибка при изменении статуса контакта:", e);
            }
        },

handleIncomingMsg(e) {
            const m = e.messageData;
            if (this.processedEvents.has('msg_' + m.id)) return;
            this.processedEvents.add('msg_' + m.id);
            
            if (this.audioUnlocked) this.msgSound.play().catch(()=>{});
            
            // ФОЛБЭК: Если по какой-то причине sender_id не пришел
            if (!m.sender_id) {
                if (this.partnerId) {
                    m.sender_id = this.partnerId;
                } else if (this.activeFriend) {
                    m.sender_id = this.activeFriend.id;
                }
            }

            const senderIdNum = Number(m.sender_id);
            const partnerIdNum = Number(this.partnerId);

            // 1. Если мы в активной РУЛЕТКЕ и сообщение пришло от партнера по рулетке
            if (this.state === 'connected' && this.callContext === 'roulette' && senderIdNum === partnerIdNum) { 
                this.messages.push({
                    isMe: false, 
                    text: m.message, 
                    timestamp: Date.now()
                }); 
                this.scrollChat(); 
                return; // Важно! Прерываем выполнение, чтобы сообщение не улетело во второй чат
            }
            
            // 2. Если мы НЕ в рулетке (или сообщение от другого человека, не партнера по рулетке)
            if (this.activeFriend && senderIdNum === Number(this.activeFriend.id)) { 
                // Чат с этим другом открыт — пушим в персональный чат в реалтайме
                this.friendMessages.push(m); 
                this.scrollFriendChat(); 
            } else {
                // Если чат с ним закрыт — помечаем плашку непрочитанного сообщения
                const friend = this.friendsList.find(f => Number(f.id) === senderIdNum);
                if (friend) {
                    friend.has_new_message = true; 
                    if (!friend.unread_count) friend.unread_count = 0;
                    friend.unread_count++;
                    
                    this.friendsList = [
                        friend,
                        ...this.friendsList.filter(f => Number(f.id) !== senderIdNum)
                    ];
                }
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
    
    // Если мы уже что-то обрабатываем или состояние не стабильно — выходим сразу
    if (this.makingOffer || this.pc.signalingState !== 'stable') return;

    try {
        this.makingOffer = true;
        const offer = await this.pc.createOffer(options);
        
        // КРИТИЧЕСКАЯ ПРОВЕРКА: Пока мы генерировали Offer (async), 
        // мог прилететь чужой Offer и изменить состояние на 'have-remote-offer'
        if (this.pc.signalingState !== 'stable') {
            console.warn("[WebRTC] Signaling state changed while creating offer. Aborting.");
            return;
        }
        
        await this.pc.setLocalDescription(offer);
        this.signal({ 
            type: 'offer', 
            sdp: this.pc.localDescription.sdp 
        });
    } catch (err) {
        console.error("[WebRTC] Offer Error:", err);
    } finally {
        this.makingOffer = false;
    }
},

        reportPartner() { if (!this.partnerId || !confirm('Report and block?')) return; window.axios.post('/report', { reported_id: this.partnerId, reason: 'general' }).then(() => this.startSearch()); },
        signal(data) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } }); },
        signalTo(toId, data) { window.axios.post('/chat/signal', { partnerId: toId, data: { ...data, from: myId } }); },
        normalizeSdp(sdp) { return typeof sdp === 'string' ? sdp.trim().split('\n').map(l => l.trim()).join('\r\n') + '\r\n' : sdp; },
        unlockAudio() { if(!this.audioUnlocked) { this.ringtone.muted=true; this.ringtone.play().then(()=>{this.ringtone.pause(); this.ringtone.muted=false;}); this.audioUnlocked=true; } },
        startHeartbeat() { setInterval(() => window.axios.post('/ping'), 15000); },
        startStats() { setInterval(async () => { if (this.pc?.iceConnectionState === 'connected') { const s = await this.pc.getStats(); s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); },
handleVisibilityChange() {
    if (!this.partnerId) return;

    if (document.visibilityState === 'visible') {
        console.log("[Caspian] Пользователь вернулся. Пробуждаем железо...");
        
        this.signal({ type: 'status-sync', state: 'active' });
        this.signal({ type: 'request-keyframe' });

        // Если это мобильное устройство (Android/iOS)
        const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        
        if (isMobile) {
            // Даем системе 300мс "проснуться" и вызываем магическую перезагрузку
            setTimeout(() => {
                this.rebootMobileCamera();
            }, 300);
        }

        // Пытаемся запустить удаленное видео (если оно стояло на паузе)
        const remoteVid = document.getElementById('remoteVideo');
        if (remoteVid) remoteVid.play().catch(() => {});
    } else {
        this.signal({ type: 'status-sync', state: 'away' });
    }
},
        async startSearch() { this.reset(); this.state = 'searching'; this.callContext = 'roulette'; await window.axios.post('/chat/search'); },
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
        openFriendChat(friendId) {

            if (!friendId) {
                console.error("Caspian DEBUG: Ошибка - передан пустой ID!");
                return;
            }

            // Находим друга в нашем локальном списке
            const target = this.friendsList.find(x => Number(x.id) === Number(friendId));
            
            // Если не нашли в друзьях (например, кликнули из истории встреч), временно создаем объект-заглушку
            this.activeFriend = target ? target : { id: Number(friendId), name: 'User #' + friendId };
            this.tab = 'friends'; 

            // Сбрасываем счетчики локально
            if (target) {
                target.has_new_message = false;
                target.unread_count = 0;
            }

            this.friendMessages = []; // Сразу чистим экран от старых сообщений

            window.axios.get(`/chat/history/${friendId}`)
                .then(res => { 
                    this.friendMessages = Array.isArray(res.data.messages) ? res.data.messages : []; 
                    this.scrollFriendChat(); 
                })
                .catch(err => {
                    console.error("Caspian DEBUG: Ошибка при выполнении Axios-запроса:", err);
                }); 
        },
        async sendMsg() { 
            if (!this.chatInput.trim() || !this.partnerId) return; 
            const t = this.chatInput; 
            this.chatInput = ''; 
            this.messages.push({isMe: true, text: t, timestamp: Date.now()}); 
            
            // Вот здесь отправка!
            window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t }); 
            this.scrollChat(); 
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
</body>
</html>