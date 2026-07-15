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

        // --- INTERNAL LOGIC ---
        isProcessingSignal: false, makingOffer: false, processedEvents: new Set(), iceQueue: [],
        rtcConfig: { iceServers: iceServers, bundlePolicy: "balanced", iceCandidatePoolSize: 10 },


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
            
            this.pc.onicecandidate = (e) => { if (e.candidate) self.signal({ type: 'ice', candidate: e.candidate }); };
            this.pc.ontrack = (e) => { 
                const remoteStream = e.streams[0];
                
                let videoEl = self.$refs.remoteVideo;
                if (!videoEl) {
                    videoEl = document.getElementById('remoteVideo');
                }

                if (videoEl) {
                    // Анти-прерывание: Если этот поток уже привязан к видеотегу, не перезаписываем его!
                    if (videoEl.srcObject === remoteStream) {
                        return;
                    }

                    videoEl.srcObject = remoteStream;
                    
                    // Безопасный запуск воспроизведения через промис
                    const playPromise = videoEl.play();
                    if (playPromise !== undefined) {
                        playPromise.then(() => {
                        }).catch(err => {
                            if (err.name === 'AbortError') {
                                console.warn("[Caspian WebRTC] Play was aborted by browser loading cycle, retrying in 100ms...");
                                setTimeout(() => videoEl.play().catch(() => {}), 100);
                            } else {
                                console.warn("[Caspian WebRTC] Play blocked, trying muted...", err);
                                videoEl.muted = true;
                                videoEl.play().catch(e => console.error("[Caspian WebRTC] Double block play failed:", e));
                            }
                        });
                    }
                } else {
                    console.error("[Caspian WebRTC] CRITICAL: Video element (#remoteVideo / x-ref) not found in DOM!");
                }
            };
            
            this.pc.oniceconnectionstatechange = () => {
                if (self.pc.iceConnectionState === 'failed') self.sendOffer({ iceRestart: true });
                if (['disconnected', 'failed'].includes(self.pc.iceConnectionState)) self.partnerState = 'problem';
                else if (self.pc.iceConnectionState === 'connected') self.partnerState = 'active';
            };

            if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
        },

        async handleSignal(e) {
            const m = e.data;
            const self = this;
            if (m.type === 'you-are-blocked') {
                this.stopCall(false); // Обрываем звонок
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { msg: '{{ __('app.Blacklisted') }}' }
                }));
                // Можно даже сделать редирект, чтобы он не видел пустой экран
                setTimeout(() => window.location.href = '/dashboard', 3000);
                return;
            }
            if (m.type === 'incoming-call') { this.incomingCall = m; if(this.audioUnlocked) this.ringtone.play().catch(()=>{}); return; }
            if (m.type === 'status-sync') { this.partnerState = m.state; return; }
            if (m.type === 'filter-sync') { this.partnerFilters = m.filters; return; }
            if (['peer-disconnected', 'hang-up', 'peer-skipped'].includes(m.type)) { this.stopCall(false); return; }
            if (m.type === 'call-accepted') { this.state = 'connected'; this.initPC(); setTimeout(() => self.sendOffer(), 1000); return; }

            if (this.isProcessingSignal) return;
            this.isProcessingSignal = true;

            try {
                if (m.type === 'offer') {
                    if (!this.pc || this.pc.signalingState !== 'stable') this.initPC();
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }));
                    const answer = await this.pc.createAnswer();
                    await this.pc.setLocalDescription(answer);
                    this.signal({ type: 'answer', sdp: this.pc.localDescription.sdp });
                    this.syncFilters();
                    while(this.iceQueue.length) await this.pc.addIceCandidate(this.iceQueue.shift()).catch(()=>{});
                } else if (m.type === 'answer' && this.pc && this.pc.signalingState === 'have-local-offer') {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(m.sdp) }));
                    this.syncFilters();
                } else if (m.type === 'ice') {
                    const cand = new RTCIceCandidate(m.candidate);
                    if (this.pc && this.pc.remoteDescription) await this.pc.addIceCandidate(cand).catch(()=>{});
                    else this.iceQueue.push(cand);
                }
            } finally { this.isProcessingSignal = false; }
        },

        // --- CALLS LOGIC ---

async handleMatch(e) {
            if (this.callContext === 'personal') return;
            
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
            if (!this.pc) {
                console.warn("[Caspian WebRTC] sendOffer aborted: RTCPeerConnection (this.pc) is null.");
                return;
            }
            if (this.makingOffer) {
                console.warn("[Caspian WebRTC] sendOffer aborted: already making offer.");
                return;
            }
            if (this.pc.signalingState !== 'stable') {
                console.warn(`[Caspian WebRTC] sendOffer aborted: signalingState is not stable (current: ${this.pc.signalingState})`);
                return;
            }

            // Проверяем, есть ли вообще треки в PeerConnection
            const senders = this.pc.getSenders();
            if (senders.length === 0 && this.localStream) {
                this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
            }

            this.makingOffer = true; 
            
            try { 
                const offer = await this.pc.createOffer(options); 
                await this.pc.setLocalDescription(offer); 
                this.signal({ type: 'offer', sdp: this.pc.localDescription.sdp }); 
            } catch (error) {
                console.error("[Caspian WebRTC] CRITICAL ERROR inside sendOffer:", error);
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

            // Считаем пользователя away, ТОЛЬКО если вкладка реально свернута или скрыта.
            // Игнорируем focus/blur (hasFocus), чтобы не ломать тесты в соседних окнах и мобилках.
            const isAway = document.hidden || document.visibilityState === 'hidden';
            const newState = isAway ? 'away' : 'active';

            if (this.myLastStatus !== newState) {
                this.myLastStatus = newState;
                this.signal({ 
                    type: 'status-sync', 
                    state: newState 
                });
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