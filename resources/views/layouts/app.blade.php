<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Caspian — Next Gen Video</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
        :root { --app-height: 100vh; }
        html, body { height: var(--app-height); background: #050505; overflow-x: hidden; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 10px; }
    </style>

<script>
// 1. Система тостов (уведомлений)
window.toastSystem = function() {
    return {
        toasts: [],
        add(data) {
            const id = Date.now();
            this.toasts.push({ id, msg: data.msg, type: data.type || 'info', show: true });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
        }
    }
};

// 2. Глобальный обработчик звонков
window.globalCallHandler = function() {
    return {
        incomingCall: null, ringtone: new Audio('/sounds/call.mp3'), audioUnlocked: false,
        initGlobal() {
            this.ringtone.loop = true;
            @auth
            window.Echo.private('user.{{ auth()->id() }}').listen('.WebRTCSignalEvent', (e) => {
                if (e.data.type === 'incoming-call') { 
                    this.incomingCall = e.data; 
                    if (this.audioUnlocked) this.ringtone.play().catch(() => {});
                    setTimeout(() => { if(this.incomingCall) this.rejectCall(); }, 30000);
                }
                if (['hang-up', 'peer-disconnected'].includes(e.data.type)) { 
                    this.ringtone.pause(); this.incomingCall = null; 
                }
            });
            @endauth
        },
        unlockAudio() {
            if (this.audioUnlocked) return;
            this.ringtone.muted = true;
            this.ringtone.play().then(() => { this.ringtone.pause(); this.ringtone.muted = false; }).catch(()=>{});
            this.audioUnlocked = true;
        },
        acceptCall() { this.ringtone.pause(); window.location.href = '/chat?accept_call=' + this.incomingCall.fromId; },
        rejectCall() {
            if(this.incomingCall) window.axios.post('/chat/signal', { partnerId: this.incomingCall.fromId, data: { type: 'hang-up', from: {{ auth()->id() }} } });
            this.ringtone.pause(); this.incomingCall = null;
        }
    }
};

// 3. ОСНОВНОЙ ДВИЖОК ВИДЕОЧАТА
window.videoChatApp = function(myId, myInterests, iceServers) {
    return {
        // Состояние
        tab: 'chat', controlsOpen: true, state: 'idle', partnerId: null, partnerData: null,
        isFriend: false, partnerState: 'active', isCallingFriend: false, activeFriend: null,
        friendMessages: [], friendChatInput: '', pc: null, localStream: null,
        friendsList: [], historyList: [], blockedList: [],
        micEnabled: true, camEnabled: true, isRemoteBlurred: false, showSelfVideo: true,
        messages: [], chatInput: '', ping: 0, beautyFilter: localStorage.getItem('beauty_filter') === 'true',
        showDeviceModal: false, devices: [], selectedCam: '', selectedMic: '',
        lastTypingSent: 0, signalQueue: [], iceQueue: [], isProcessingSignal: false,
        offlineTimer: null, remoteStreamSet: false, isHandlingMatch: false,
        
        rtcConfig: { iceServers: iceServers, bundlePolicy: "balanced", iceCandidatePoolSize: 10 },

        // Инициализация
        async init() {
            window.Echo.private(`user.${myId}`)
                .listen('.MatchFoundEvent', (e) => {
                    if (this.isHandlingMatch || (this.partnerId === Number(e.partnerData.id) && this.state === 'connected')) return;
                    this.handleMatch(e);
                })
                .listen('.WebRTCSignalEvent', (e) => this.handleSignal(e))
                .listen('.MessageSentEvent', (e) => this.handleIncomingMsg(e))
                .listen('.UserTypingEvent', (e) => {
                    if (e.senderId === this.partnerId || (this.activeFriend && e.senderId === this.activeFriend.id)) {
                        let name = (this.partnerId === e.senderId) ? (this.partnerData?.name || 'Собеседник') : this.activeFriend.name;
                        window.dispatchEvent(new CustomEvent('typing-start', { detail: { name: name } }));
                        clearTimeout(this.typingTimeout);
                        this.typingTimeout = setTimeout(() => { window.dispatchEvent(new CustomEvent('typing-end')); }, 3000);
                    }
                });

            if (window.location.pathname === '/chat') {
                await this.initMedia();
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('accept_call')) {
                    const fId = parseInt(urlParams.get('accept_call'));
                    this.partnerId = fId; this.state = 'connected';
                    window.axios.get(`/chat/user-info/${fId}`).then(res => { this.partnerData = res.data; this.openFriendChat(res.data); });
                    setTimeout(() => { this.signal({ type: 'call-accepted' }); }, 1000);
                }
            }
            this.loadFriends(); this.loadHistory(); this.loadBlocked();
            this.startStats();
        },

        // Работа с медиа
        async initMedia() {
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 }, audio: true });
                if (this.$refs.localVideo) this.$refs.localVideo.srcObject = this.localStream;
            } catch(e) { window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Ошибка камеры', type:'error'}})); }
        },

        // WebRTC Основное
initPC() {
            if (this.pc) return;
            
            this.remoteStreamSet = false;
            // Добавляем конфигурацию с вашим TURN
            this.pc = new RTCPeerConnection(this.rtcConfig);
            
            this.pc.onicecandidate = (e) => { 
                if (e.candidate) {
                    // console.log("New ICE Candidate");
                    this.signal({ type: 'ice', candidate: e.candidate }); 
                }
            };

            this.pc.ontrack = (e) => { 
                const videoEl = this.$refs.remoteVideo;
                if (videoEl) {
                    console.log("Remote stream found, linking...");
                    videoEl.srcObject = e.streams[0];
                    
                    // Самый надежный способ автоплея
                    videoEl.load(); 
                    videoEl.muted = true;
                    
                    let playPromise = videoEl.play();
                    if (playPromise !== undefined) {
                        playPromise.then(() => {
                            console.log("Video playing...");
                            this.remoteStreamSet = true;
                            setTimeout(() => { videoEl.muted = false; }, 500);
                        }).catch(err => {
                            console.log("Play failed, waiting for user click");
                        });
                    }
                }
            };

            this.pc.oniceconnectionstatechange = () => {
                if (!this.pc) return;
                const s = this.pc.iceConnectionState;
                console.log("ICE Connection State:", s);
                
                if (s === 'failed' || s === 'disconnected') {
                    // Если упало - пробуем ICE Restart
                    this.pc.createOffer({ iceRestart: true }).then(o => {
                        this.pc.setLocalDescription(o);
                        this.signal({ type: 'offer', sdp: o.sdp });
                    });
                }
                
                this.handlePartnerState(s === 'connected' || s === 'completed' ? 'active' : 'offline');
            };

            // Добавляем камеру ДО отправки оффера
            if (this.localStream) {
                this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
            }
        },

        async handleSignal(e) {
            this.signalQueue.push(e);
            if (this.isProcessingSignal) return;
            this.isProcessingSignal = true;
            while (this.signalQueue.length > 0) {
                const event = this.signalQueue.shift();
                const msg = event.data;
                if (['peer-disconnected', 'peer-skipped', 'hang-up'].includes(msg.type)) { this.reset(); if(msg.type === 'peer-skipped') this.startSearch(); continue; }
                if (msg.type === 'user-state-changed') { this.partnerState = msg.state; continue; }
                if (msg.type === 'call-accepted') { this.state = 'connected'; this.initPC(); await this.sendOffer(); continue; }
                if (!this.pc && ['offer', 'ice'].includes(msg.type)) this.initPC();
                if (!this.pc) continue;
                try {
                    const cleanSdp = msg.sdp ? this.normalizeSdp(msg.sdp) : null;
                    if (msg.type === 'offer') {
                        if (this.pc.signalingState !== "stable") await this.pc.setLocalDescription({type: "rollback"});
                        await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: cleanSdp }));
                        const answer = await this.pc.createAnswer();
                        await this.pc.setLocalDescription(answer);
                        this.signal({ type: 'answer', sdp: answer.sdp });
                    } else if (msg.type === 'answer') {
                        if (this.pc.signalingState === "have-local-offer") await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: cleanSdp }));
                    } else if (msg.type === 'ice' && msg.candidate) {
                        if (this.pc.remoteDescription) await this.pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(()=>{});
                        else this.iceQueue.push(msg.candidate);
                    }
                } catch(err) {}
            }
            this.isProcessingSignal = false;
        },

        async sendOffer() {
            if(!this.pc || this.pc.signalingState !== "stable") return;
            try {
                const offer = await this.pc.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true });
                await this.pc.setLocalDescription(offer);
                this.signal({ type: 'offer', sdp: offer.sdp });
            } catch(e) { console.error("Offer error"); }
        },

async handleMatch(e) {
            if (this.isHandlingMatch) return;
            this.isHandlingMatch = true;
            
            console.log("Match Found! Connecting via our TURN...");
            this.reset();
            
            this.partnerId = Number(e.partnerData.id);
            this.partnerData = e.partnerData;
            this.state = 'connected';

            // Инициализация WebRTC у обоих участников
            this.initPC();

            // Тот, у кого ID меньше — Инициатор (Offer)
            if (myId < this.partnerId) {
                // 500мс достаточно, так как маршрутизация теперь симметричная и быстрая
                setTimeout(async () => {
                    if (this.pc && this.pc.signalingState === "stable") {
                        await this.sendOffer();
                    }
                    this.isHandlingMatch = false;
                }, 500); 
            } else {
                setTimeout(() => { this.isHandlingMatch = false; }, 500);
            }
        },

        // Хелперы и API
        signal(data) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } }).catch(err => { if (err.response?.status === 403) this.reset(); }); },
        reset() {
            if (this.pc) { this.pc.close(); this.pc = null; }
            this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.messages = []; this.iceQueue = []; this.signalQueue = [];
            this.remoteStreamSet = false; this.isHandlingMatch = false;
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
            clearTimeout(this.offlineTimer);
        },
        handlePartnerState(s) { this.partnerState = s; if (s === 'offline') { clearTimeout(this.offlineTimer); this.offlineTimer = setTimeout(() => { if (this.partnerState === 'offline' && this.state === 'connected') this.startSearch(); }, 15000); } else { clearTimeout(this.offlineTimer); } },
        normalizeSdp(sdp) { if (!sdp) return ""; return sdp.trim().split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n'; },
        
        // Поиск
        async startSearch() { if (this.partnerId) this.signal({ type: 'peer-skipped' }); this.reset(); this.state = 'searching'; await window.axios.post('/chat/search'); },
        stopSearch() { if (this.partnerId) this.signal({ type: 'hang-up' }); this.reset(); window.axios.post('/chat/leave'); },
        
        // Мессенджер
        async openFriendChat(f) { this.tab = 'friends'; this.activeFriend = f; const res = await window.axios.get(`/chat/history/${f.id}`); this.friendMessages = res.data.messages; this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); },
        async sendMsg() { if (!this.chatInput.trim() || !this.partnerId) return; const t = this.chatInput; this.chatInput = ''; this.messages.push({isMe: true, text: t, timestamp: Date.now()}); window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t }); this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
        async sendFriendMsg() { if (!this.friendChatInput.trim() || !this.activeFriend) return; const t = this.friendChatInput; this.friendChatInput = ''; const res = await window.axios.post('/chat/message/send', { receiver_id: this.activeFriend.id, message: t }); this.friendMessages.push(res.data.message); this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); },
        handleIncomingMsg(e) { const m = e.messageData; if (this.state === 'connected' && m.sender_id === this.partnerId) { this.messages.push({isMe: false, text: m.message, timestamp: Date.now()}); this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); } if (this.activeFriend && m.sender_id === this.activeFriend.id) { this.friendMessages.push(m); this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); } new Audio('/sounds/message.mp3').play().catch(()=>{}); },
        sendTypingSignal() { const rid = this.activeFriend ? this.activeFriend.id : this.partnerId; if (rid && Date.now() - this.lastTypingSent > 2000) { this.lastTypingSent = Date.now(); window.axios.post('/chat/message/typing', { receiver_id: rid }); } },
        
        // Интерфейс
        toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
        toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
        toggleBeauty() { this.beautyFilter = !this.beautyFilter; localStorage.setItem('beauty_filter', this.beautyFilter); },
        getDevices() { navigator.mediaDevices.enumerateDevices().then(d => { this.devices = d.filter(x => x.kind.includes('input')); this.showDeviceModal = true; }); },
        async switchDevice(kind, id) { const c = kind === 'video' ? { video: { deviceId: { exact: id } } } : { audio: { deviceId: { exact: id } } }; try { const s = await navigator.mediaDevices.getUserMedia(c); if (this.pc) { const snd = this.pc.getSenders().find(x => x.track && x.track.kind === s.getTracks()[0].kind); if (snd) snd.replaceTrack(s.getTracks()[0]); } if (kind === 'video') { this.selectedCam = id; this.$refs.localVideo.srcObject = s; } } catch(e) {} },
        async callFriend(f) { const res = await window.axios.post('/chat/contact/call', { contactId: f.id }); if (res.data.status === 'busy') { window.dispatchEvent(new CustomEvent('toast', {detail: {msg: res.data.message, type:'error'}})); return; } this.reset(); this.partnerId = f.id; this.state = 'searching'; this.isCallingFriend = true; this.openFriendChat(f); window.dispatchEvent(new CustomEvent('close-messenger')); },
        async toggleContact() { const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); this.isFriend = res.data.isFriend; this.loadFriends(); },
        async reportPartner() { if(confirm('Заблокировать?')) { await window.axios.post('/report', {reported_id: this.partnerId, reason: 'abuse'}); this.startSearch(); } },
        loadFriends() { window.axios.get('/chat/contacts').then(r => this.friendsList = r.data.contacts); },
        loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
        loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
        startStats() { setInterval(async () => { if (this.pc?.iceConnectionState === 'connected') { const s = await this.pc.getStats(); s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); }
    }
};
</script>
</head>
<body class="font-sans antialiased text-white" 
      x-data="{ 
        ...window.globalCallHandler(), 
        mobileMenuOpen: false, 
        globalSidebarOpen: false,
        isPartnerTyping: false,
        typingPartnerName: ''
      }" 
      x-init="initGlobal()"
      @click="unlockAudio()"
      @typing-start.window="isPartnerTyping = true; typingPartnerName = $event.detail.name"
      @typing-end.window="isPartnerTyping = false"
      @close-messenger.window="globalSidebarOpen = false">

    <!-- МОДАЛКА ЗВОНКА -->
    <div x-show="incomingCall" class="fixed top-10 left-1/2 -translate-x-1/2 z-[600] w-full max-w-sm px-4" x-cloak x-transition>
        <div class="bg-[#121212]/90 backdrop-blur-3xl border border-indigo-500/30 p-5 rounded-[2.5rem] shadow-2xl flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center animate-pulse font-black shadow-lg" x-text="incomingCall?.fromName ? incomingCall.fromName[0] : '?'"></div>
                <div>
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Входящий вызов</p>
                    <p class="text-sm font-black uppercase italic" x-text="incomingCall?.fromName"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="rejectCall()" class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center text-xl shadow-lg shadow-red-600/20">✕</button>
                <button @click="acceptCall()" class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-xl shadow-lg shadow-green-500/20">📞</button>
            </div>
        </div>
    </div>

    <!-- ТОСТЫ -->
    <div x-data="window.toastSystem()" @toast.window="add($event.detail)" class="fixed top-6 left-1/2 -translate-x-1/2 z-[1000] w-full max-w-xs space-y-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show" x-transition class="pointer-events-auto bg-indigo-600/90 backdrop-blur-xl border border-indigo-400/50 px-6 py-3 rounded-2xl shadow-2xl text-center">
                <span class="text-[10px] font-black uppercase tracking-widest" x-text="toast.msg"></span>
            </div>
        </template>
    </div>

    <div class="flex flex-col min-h-screen relative">
        @include('layouts.navigation')
        <main class="flex-1 flex flex-col">{{ $slot }}</main>

        <!-- ГЛОБАЛЬНЫЙ МЕССЕНДЖЕР -->
        <div x-show="globalSidebarOpen" 
             @click.outside="globalSidebarOpen = false"
             class="fixed right-0 top-0 bottom-0 z-[450] w-full md:w-[400px] bg-[#080808] border-l border-white/5 shadow-2xl flex flex-col"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             x-cloak
             x-data="window.videoChatApp({{ auth()->id() }}, {{ json_encode(auth()->user()->interests ?? []) }}, @js(config('webrtc.ice_servers')))">
            
            <div class="p-6 border-b border-white/5 bg-[#0a0a0a] flex justify-between items-center shrink-0">
                <h2 class="text-xs font-black uppercase tracking-[0.3em] italic">Messenger</h2>
                <button @click="$dispatch('close-messenger')" class="text-gray-500 hover:text-white transition-colors">✕</button>
            </div>
            @include('partials.messenger-content')
        </div>
    </div>
</body>
</html>