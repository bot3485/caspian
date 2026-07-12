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
        :root { --app-height: 100svh; }
        html, body { 
            height: var(--app-height); 
            background: #050505; 
            overflow: hidden; 
            position: fixed; 
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 10px; }
    </style>
</head>
<body class="font-sans antialiased text-white" 
      x-data="window.caspianApp({{ auth()->id() }}, {{ json_encode(auth()->user()->interests ?? []) }}, @js(config('webrtc.ice_servers')))"
      x-init="init()"
      @click="unlockAudio()"
      @close-messenger.window="globalSidebarOpen = false">

    <!-- ВХОДЯЩИЙ ЗВОНОК -->
    <div x-show="incomingCall" class="fixed top-6 left-1/2 -translate-x-1/2 z-[600] w-full max-w-sm px-4" x-cloak x-transition>
        <div class="bg-[#121212]/95 backdrop-blur-3xl border border-indigo-500/30 p-4 rounded-[2rem] shadow-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center animate-pulse font-black" x-text="incomingCall?.fromName ? incomingCall.fromName[0] : '?'"></div>
                <div>
                    <p class="text-[8px] font-black text-indigo-400 uppercase tracking-widest">Calling...</p>
                    <p class="text-sm font-black uppercase" x-text="incomingCall?.fromName"></p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="rejectCall()" class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">✕</button>
                <button @click="acceptCall()" class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">📞</button>
            </div>
        </div>
    </div>

    <!-- ТОСТЫ -->
    <div x-data="{ toasts: [] }" @toast.window="const id = Date.now(); toasts.push({id, msg: $event.detail.msg}); setTimeout(() => toasts = toasts.filter(t => t.id !== id), 3000)" class="fixed top-20 left-1/2 -translate-x-1/2 z-[1000] w-full max-w-xs space-y-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="pointer-events-auto bg-indigo-600/90 backdrop-blur-xl border border-indigo-400/50 px-6 py-2 rounded-xl shadow-2xl text-center">
                <span class="text-[9px] font-black uppercase tracking-widest" x-text="toast.msg"></span>
            </div>
        </template>
    </div>

    <div class="flex flex-col h-full relative">
        @include('layouts.navigation')

        <main class="flex-1 relative overflow-hidden">
            {{ $slot }}
        </main>

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
             x-cloak>
            
            <div class="p-6 border-b border-white/5 bg-[#0a0a0a] flex justify-between items-center shrink-0">
                <h2 class="text-xs font-black uppercase tracking-[0.3em] italic">Messenger</h2>
                <button @click="globalSidebarOpen = false" class="text-gray-500 hover:text-white">✕</button>
            </div>
            @include('partials.messenger-content')
        </div>
    </div>

<script>
window.caspianApp = function(myId, myInterests, iceServers) {
    return {
        globalSidebarOpen: false, tab: 'chat', controlsOpen: true, state: 'idle',
        partnerId: null, partnerData: null, isFriend: false, partnerState: 'active',
        incomingCall: null, ringtone: new Audio('/sounds/call.mp3'),
        isCallingFriend: false, activeFriend: null, friendMessages: [], friendChatInput: '',
        pc: null, localStream: null, friendsList: [], historyList: [], blockedList: [],
        micEnabled: true, camEnabled: true, isRemoteBlurred: false, showSelfVideo: true,
        messages: [], chatInput: '', ping: 0, beautyFilter: localStorage.getItem('beauty_filter') === 'true',
        showDeviceModal: false, devices: [], selectedCam: '', selectedMic: '',
        lastTypingSent: 0, signalQueue: [], iceQueue: [], isProcessingSignal: false,
        audioUnlocked: false, remoteStreamSet: false, isPartnerTyping: false, typingPartnerName: '',
        isHandlingMatch: false, offlineTimer: null, heartbeatTimer: null,
        rtcConfig: { iceServers: iceServers, bundlePolicy: "balanced", iceCandidatePoolSize: 10 },

        async init() {
            const self = this;
            this.ringtone.loop = true;
            window.Echo.private(`user.${myId}`)
                .listen('.MatchFoundEvent', (e) => {
                    if (self.isHandlingMatch || (self.partnerId === Number(e.partnerData.id) && self.state === 'connected')) return;
                    self.handleMatch(e);
                })
                .listen('.WebRTCSignalEvent', (e) => self.handleSignal(e))
                .listen('.MessageSentEvent', (e) => self.handleIncomingMsg(e))
                .listen('.UserTypingEvent', (e) => {
                    if (e.senderId === self.partnerId || (self.activeFriend && e.senderId === self.activeFriend.id)) {
                        self.isPartnerTyping = true;
                        self.typingPartnerName = (self.partnerId === e.senderId) ? (self.partnerData?.name || 'Partner') : self.activeFriend.name;
                        clearTimeout(self.typingTimeout);
                        self.typingTimeout = setTimeout(() => { self.isPartnerTyping = false; }, 3000);
                    }
                });

            if (window.location.pathname === '/chat') {
                await this.initMedia();
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('accept_call')) {
                    const fId = parseInt(urlParams.get('accept_call'));
                    this.partnerId = fId; this.state = 'connected';
                    window.axios.get(`/chat/user-info/${fId}`).then(res => { 
                        self.partnerData = res.data; 
                        self.openFriendChat(res.data);
                        setTimeout(() => { self.signal({ type: 'call-accepted' }); }, 1000);
                    });
                }
            }
            this.loadFriends(); this.loadHistory(); this.loadBlocked();
            this.startStats();
        },

        // --- HEARTBEAT: Чтобы Cron не удалял нас из базы ---
        startHeartbeat() {
            this.stopHeartbeat();
            this.heartbeatTimer = setInterval(() => {
                window.axios.post('/ping').catch(e => {
                    if (e.response?.status === 401) window.location.reload();
                });
            }, 15000);
        },
        stopHeartbeat() {
            if (this.heartbeatTimer) { clearInterval(this.heartbeatTimer); this.heartbeatTimer = null; }
        },

        async initMedia() {
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: { ideal: 640 }, height: { ideal: 480 } }, 
                    audio: true 
                });
                if (this.$refs.localVideo) this.$refs.localVideo.srcObject = this.localStream;
            } catch(e) { window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Camera Error'}})); }
        },

        unlockAudio() {
            if (this.audioUnlocked) return;
            this.ringtone.muted = true;
            this.ringtone.play().then(() => { this.ringtone.pause(); this.ringtone.muted = false; }).catch(()=>{});
            this.audioUnlocked = true;
        },

        acceptCall() { this.ringtone.pause(); window.location.href = '/chat?accept_call=' + this.incomingCall.fromId; },
        rejectCall() {
            if(this.incomingCall) this.signalTo(this.incomingCall.fromId, { type: 'hang-up' });
            this.ringtone.pause(); this.incomingCall = null;
        },

        initPC() {
            if (this.pc) return;
            const self = this;
            this.remoteStreamSet = false;
            this.pc = new RTCPeerConnection(this.rtcConfig);
            this.pc.onicecandidate = (e) => { if (e.candidate) self.signal({ type: 'ice', candidate: e.candidate }); };
            this.pc.ontrack = (e) => { 
                const videoEl = self.$refs.remoteVideo;
                if (videoEl && videoEl.srcObject !== e.streams[0]) {
                    videoEl.srcObject = e.streams[0];
                    videoEl.muted = true;
                    videoEl.play().then(() => {
                        // Трюк для звука: un-mute после начала видео
                        setTimeout(() => { if(videoEl) videoEl.muted = false; }, 1000);
                    }).catch(() => {});
                }
            };
            this.pc.oniceconnectionstatechange = () => {
                if (!this.pc) return;
                const s = this.pc.iceConnectionState;
                if (s === 'connected' || s === 'completed') self.handlePartnerState('active');
                else if (s === 'disconnected' || s === 'failed') {
                    // ТЕРПЕЛИВЫЙ ДИСКОННЕКТ: ждем 10 сек перед тем как менять статус на offline
                    setTimeout(() => {
                        if (self.pc && (self.pc.iceConnectionState === 'disconnected' || self.pc.iceConnectionState === 'failed')) {
                            self.handlePartnerState('offline');
                        }
                    }, 10000);
                }
            };
            if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
        },

        async handleSignal(e) {
            const self = this;
            const msg = e.data;
            if (msg.type === 'incoming-call') { this.incomingCall = msg; if(this.audioUnlocked) this.ringtone.play().catch(()=>{}); return; }
            if (['peer-disconnected', 'peer-skipped', 'hang-up'].includes(msg.type)) { this.reset(); this.incomingCall = null; this.ringtone.pause(); if(msg.type === 'peer-skipped') this.startSearch(); return; }
            if (msg.type === 'user-state-changed') { this.partnerState = msg.state; return; }
            if (msg.type === 'call-accepted') { this.state = 'connected'; this.initPC(); this.sendOffer(); return; }
            if (!this.pc && ['offer', 'ice'].includes(msg.type)) this.initPC();
            if (!this.pc) return;

            this.signalQueue.push(e);
            if (this.isProcessingSignal) return;
            this.isProcessingSignal = true;

            while (this.signalQueue.length > 0) {
                const event = this.signalQueue.shift();
                const m = event.data;
                try {
                    const cleanSdp = m.sdp ? this.normalizeSdp(m.sdp) : null;
                    if (m.type === 'offer') {
                        if (this.pc.signalingState !== "stable") await this.pc.setLocalDescription({type: "rollback"});
                        await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: cleanSdp }));
                        const answer = await this.pc.createAnswer();
                        await this.pc.setLocalDescription(answer);
                        this.signal({ type: 'answer', sdp: answer.sdp });
                    } else if (m.type === 'answer') {
                        if (this.pc.signalingState === "have-local-offer") await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: cleanSdp }));
                    } else if (m.type === 'ice' && m.candidate) {
                        if (this.pc.remoteDescription) await this.pc.addIceCandidate(new RTCIceCandidate(m.candidate)).catch(()=>{});
                        else this.iceQueue.push(m.candidate);
                    }
                } catch(err) { console.warn("RTC Signal Error"); }
            }
            this.isProcessingSignal = false;
        },

        async sendOffer() {
            if(!this.pc || this.pc.signalingState !== "stable") return;
            try {
                const offer = await this.pc.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true });
                await this.pc.setLocalDescription(offer);
                this.signal({ type: 'offer', sdp: offer.sdp });
            } catch(e) {}
        },

        async handleMatch(e) {
            const self = this;
            this.isHandlingMatch = true;
            this.reset();
            this.partnerId = Number(e.partnerData.id); this.partnerData = e.partnerData; this.isFriend = !!e.isFriend; this.state = 'connected';
            
            this.startHeartbeat(); // Включаем пинг при матче

            if (this.partnerData.interests && Array.isArray(this.partnerData.interests)) {
                const common = myInterests.filter(v => self.partnerData.interests.includes(v));
                if (common.length > 0) window.dispatchEvent(new CustomEvent('toast', {detail: {msg: `Match Interest: ${common[0]}!`}}));
            }

            this.initPC();
            if (myId < this.partnerId) {
                setTimeout(async () => {
                    await self.sendOffer();
                    self.isHandlingMatch = false;
                }, 1200);
            } else {
                setTimeout(() => { self.isHandlingMatch = false; }, 1200);
            }
        },

        signal(data) { this.signalTo(this.partnerId, data); },
        signalTo(toId, data) { if (toId) window.axios.post('/chat/signal', { partnerId: toId, data: { ...data, from: myId } }).catch(e => { if(e.response?.status === 403) this.reset(); }); },
        
        reset() {
            this.stopHeartbeat();
            if (this.pc) { 
                this.pc.onicecandidate = null; this.pc.ontrack = null; 
                this.pc.oniceconnectionstatechange = null; this.pc.close(); 
                this.pc = null; 
            }
            this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.messages = []; this.iceQueue = []; this.signalQueue = [];
            this.remoteStreamSet = false; this.isHandlingMatch = false;
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
            clearTimeout(this.offlineTimer);
        },

        handlePartnerState(s) { 
            this.partnerState = s; 
            if (s === 'offline') {
                clearTimeout(this.offlineTimer);
                this.offlineTimer = setTimeout(() => { 
                    if (this.partnerState === 'offline' && this.state === 'connected') this.startSearch(); 
                }, 15000);
            } else { clearTimeout(this.offlineTimer); }
        },
        normalizeSdp(sdp) { if (!sdp) return ""; return sdp.trim().split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n'; },
        async startSearch() { if (this.partnerId) this.signal({ type: 'peer-skipped' }); this.reset(); this.state = 'searching'; this.startHeartbeat(); await window.axios.post('/chat/search'); },
        stopSearch() { if (this.partnerId) this.signal({ type: 'hang-up' }); this.reset(); window.axios.post('/chat/leave'); },
        async openFriendChat(f) { this.tab = 'friends'; this.activeFriend = f; const res = await window.axios.get(`/chat/history/${f.id}`); this.friendMessages = res.data.messages; this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); },
        async sendMsg() { if (!this.chatInput.trim() || !this.partnerId) return; const t = this.chatInput; this.chatInput = ''; this.messages.push({isMe: true, text: t, timestamp: Date.now()}); window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t }); this.scrollChat(); },
        async sendFriendMsg() { if (!this.friendChatInput.trim() || !this.activeFriend) return; const t = this.friendChatInput; this.friendChatInput = ''; const res = await window.axios.post('/chat/message/send', { receiver_id: this.activeFriend.id, message: t }); this.friendMessages.push(res.data.message); this.scrollFriendChat(); },
        
        handleIncomingMsg(e) { 
            const m = e.messageData; 
            if (this.state === 'connected' && m.sender_id === this.partnerId) { 
                this.messages.push({isMe: false, text: m.message, timestamp: Date.now()}); 
                this.scrollChat(); 
            } 
            if (this.activeFriend && m.sender_id === this.activeFriend.id) { 
                this.friendMessages.push(m); 
                this.scrollFriendChat(); 
            } 
            new Audio('/sounds/message.mp3').play().catch(()=>{}); 
        },

        // --- НОВЫЕ МЕТОДЫ (ИСПРАВЛЕНИЕ ОШИБОК) ---
        sendTypingSignal() {
            const now = Date.now();
            if (now - this.lastTypingSent < 2000) return; // Throttling 2 сек
            const rid = this.activeFriend ? this.activeFriend.id : this.partnerId;
            if (rid) {
                this.lastTypingSent = now;
                window.axios.post('/chat/message/typing', { receiver_id: rid }).catch(() => {});
            }
        },

        toggleContact() {
            if (!this.partnerId) return;
            window.axios.post('/chat/contact/add', { contactId: this.partnerId }).then(r => {
                this.isFriend = r.data.isFriend;
                this.loadFriends();
                window.dispatchEvent(new CustomEvent('toast', {detail: {msg: r.data.action === 'added' ? 'Added to Friends' : 'Removed'}}));
            });
        },

        reportPartner() {
            if (!this.partnerId || !confirm('Report this user?')) return;
            window.axios.post('/report', { reported_id: this.partnerId, reason: 'general' }).then(() => {
                window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Report Sent'}}));
                this.startSearch();
            });
        },

        callFriend(f) {
            window.axios.post('/chat/contact/call', { contactId: f.id }).then(r => {
                if (r.data.status === 'busy') {
                    window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'User Busy'}}));
                } else {
                    window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Calling...'}}));
                }
            });
        },

        unblock(id) {
            window.axios.post('/chat/unblock', { blockedId: id }).then(() => {
                this.loadBlocked();
                this.loadHistory();
                window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Unblocked'}}));
            });
        },

        // --- ЗАГРУЗКА ДАННЫХ ---
        loadFriends() { window.axios.get('/chat/contacts').then(r => this.friendsList = r.data.contacts); },
        loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
        loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
        
        scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
        scrollFriendChat() { this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); },
        
        toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
        toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
        toggleBeauty() { this.beautyFilter = !this.beautyFilter; localStorage.setItem('beauty_filter', this.beautyFilter); },
        
        getDevices() { navigator.mediaDevices.enumerateDevices().then(d => { this.devices = d.filter(x => x.kind.includes('input')); this.showDeviceModal = true; }); },
        
        async switchDevice(kind, id) { 
            const c = kind === 'video' ? { video: { deviceId: { exact: id } } } : { audio: { deviceId: { exact: id } } }; 
            try { 
                const s = await navigator.mediaDevices.getUserMedia(c); 
                if (this.pc) { 
                    const snd = this.pc.getSenders().find(x => x.track && x.track.kind === s.getTracks()[0].kind); 
                    if (snd) snd.replaceTrack(s.getTracks()[0]); 
                } 
                if (kind === 'video') { 
                    this.selectedCam = id; 
                    if(this.$refs.localVideo) this.$refs.localVideo.srcObject = s; 
                } 
            } catch(e) {} 
        },
        
        startStats() { 
            setInterval(async () => { 
                if (this.pc?.iceConnectionState === 'connected') { 
                    const s = await this.pc.getStats(); 
                    s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); 
                } 
            }, 3000); 
        }
    }
};
</script>
</body>
</html>