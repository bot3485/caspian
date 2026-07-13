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
                // Игнорируем, если такое сообщение уже висит на экране (Anti-spam)
                if (this.toasts.some(t => t.msg === msg)) return;
                
                const id = Date.now();
                this.toasts.push({id, msg});
                setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 3500);
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
                <h2 class="text-[10px] font-black uppercase tracking-[0.5em] text-gray-500">Communications</h2>
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

        // 2. СБРОС ТЕГА (Важно для мобилок: полностью очищаем старый поток из плеера)
        const localEl = document.getElementById('localVideo');
        if (localEl) localEl.srcObject = null;

        // 3. Запрашиваем новый поток
        // Используем только deviceId без exact, чтобы браузер мог адаптироваться
        const constraints = {
            video: { 
                deviceId: { ideal: this.selectedVideoId },
                width: { ideal: 1280 }, // Задние камеры любят более высокое разрешение
                height: { ideal: 720 }
            },
            audio: { 
                deviceId: { ideal: this.selectedAudioId } 
            }
        };

        const newStream = await navigator.mediaDevices.getUserMedia(constraints);
        this.localStream = newStream;

        // 4. Привязываем новый поток
        if (localEl) {
            localEl.srcObject = this.localStream;
            // Принудительный запуск через 100мс (дает мобильному браузеру "продышаться")
            setTimeout(() => {
                localEl.play().catch(e => console.warn("Mobile autoplay failed, click needed"));
            }, 100);
        }

        // 5. Заменяем трек у партнера, если мы в звонке
        if (this.pc) {
            const videoTrack = this.localStream.getVideoTracks()[0];
            const videoSender = this.pc.getSenders().find(s => s.track?.kind === 'video');
            if (videoSender) await videoSender.replaceTrack(videoTrack);
            
            const audioTrack = this.localStream.getAudioTracks()[0];
            const audioSender = this.pc.getSenders().find(s => s.track?.kind === 'audio');
            if (audioSender) await audioSender.replaceTrack(audioTrack);
        }

        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Hardware Switched'}}));
    } catch (e) {
        console.error("Camera switch failed:", e);
        // Если задняя камера не завелась, пробуем вернуться хоть к какой-то
        this.localStream = null;
        await this.initMedia();
    }
    
    this.deviceModalOpen = false;
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
            window.dispatchEvent(new CustomEvent('toast', {detail: {msg: this.beautyFilter ? 'Beauty Filter On' : 'Beauty Filter Off'}}));
        },

        toggleCinema() {
            this.cinemaFilter = !this.cinemaFilter;
            this.syncFilters();
            window.dispatchEvent(new CustomEvent('toast', {detail: {msg: this.cinemaFilter ? 'Cinema Mode On' : 'Cinema Mode Off'}}));
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
            this.pc.ontrack = (e) => { if (self.$refs.remoteVideo) self.$refs.remoteVideo.srcObject = e.streams[0]; };
            
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
                    detail: { msg: 'YOU HAVE BEEN BLACKLISTED BY THIS USER' }
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
    this.reset();
    this.partnerId = Number(e.partnerData?.id);
    this.partnerData = e.partnerData || {};
    
    // Расчет совпадений
    const pInterests = this.partnerData.interests || [];
    this.commonInterests = pInterests.filter(i => this.myInterests.includes(i));
    if (this.commonInterests.length > 0) {
        this.showInterestMatch = true;
        setTimeout(() => { this.showInterestMatch = false; }, 6000);
    }

    this.state = 'connected';
    this.initPC();
    if (myId < this.partnerId) setTimeout(() => this.sendOffer(), 1200);
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
                    window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Protocol Restored'}}));
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
            const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId });
            this.isFriend = res.data.isFriend;
            this.loadFriends();
        },

        handleIncomingMsg(e) {
            const m = e.messageData;
            if (this.processedEvents.has('msg_' + m.id)) return;
            this.processedEvents.add('msg_' + m.id);
            if (this.audioUnlocked) this.msgSound.play().catch(()=>{});
            if (this.state === 'connected' && m.sender_id === this.partnerId) { this.messages.push({isMe: false, text: m.message, timestamp: Date.now()}); this.scrollChat(); }
            if (this.activeFriend && m.sender_id === this.activeFriend.id) { this.friendMessages.push(m); this.scrollFriendChat(); }
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
            if (!this.pc || this.makingOffer || this.pc.signalingState !== 'stable') return;
            this.makingOffer = true; 
            try { 
                const offer = await this.pc.createOffer(options); 
                await this.pc.setLocalDescription(offer); 
                this.signal({ type: 'offer', sdp: this.pc.localDescription.sdp }); 
            } finally { this.makingOffer = false; } 
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

            // Определяем, скрыт ли пользователь реально
            // hidden — вкладка неактивна, !hasFocus() — окно свернуто или перекрыто
            const isAway = document.hidden || document.visibilityState === 'hidden' || !document.hasFocus();
            const newState = isAway ? 'away' : 'active';

            // Отправляем сигнал только если состояние реально изменилось, чтобы не спамить
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
        openFriendChat(f) { this.tab = 'friends'; this.activeFriend = f; window.axios.get(`/chat/history/${f.id}`).then(res => { this.friendMessages = res.data.messages; this.scrollFriendChat(); }); },
        async sendMsg() { if (!this.chatInput.trim() || !this.partnerId) return; const t = this.chatInput; this.chatInput = ''; this.messages.push({isMe: true, text: t, timestamp: Date.now()}); window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t }); this.scrollChat(); },
        async sendFriendMsg() { if (!this.friendChatInput.trim() || !this.activeFriend) return; const t = this.friendChatInput; this.friendChatInput = ''; const res = await window.axios.post('/chat/message/send', { receiver_id: this.activeFriend.id, message: t }); this.friendMessages.push(res.data.message); this.scrollFriendChat(); },
        sendTypingSignal() { const rid = this.activeFriend ? this.activeFriend.id : this.partnerId; if (rid) window.axios.post('/chat/message/typing', { receiver_id: rid }); },
        callFriend(f) { if (!f.is_online) return; window.location.href = '/chat?call_to=' + f.id; }
    }
};
</script>
</body>
</html>