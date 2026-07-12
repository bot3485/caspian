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
            min-height: var(--app-height); 
            background: #050505; 
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
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
                    <p class="text-[8px] font-black text-indigo-400 uppercase tracking-widest">Incoming Call</p>
                    <p class="text-sm font-black uppercase" x-text="incomingCall?.fromName"></p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="rejectCall()" class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center shadow-lg">✕</button>
                <button @click="acceptCall()" class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center shadow-lg">📞</button>
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

    <div class="flex flex-col min-h-screen relative">
        @include('layouts.navigation')

        <main class="flex-1 relative">
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
        audioUnlocked: false, isPartnerTyping: false, typingPartnerName: '',
        isHandlingMatch: false, offlineTimer: null, heartbeatTimer: null,
        makingOffer: false, isCalling: false, isPersonalCall: false,
        rtcConfig: { iceServers: iceServers, bundlePolicy: "balanced", iceCandidatePoolSize: 10 },

        async init() {
            const self = this;
            this.ringtone.loop = true;

            // Отслеживание активности вкладки (Away status)
            document.addEventListener('visibilitychange', () => {
                const status = document.hidden ? 'away' : 'active';
                if (this.partnerId) this.signal({ type: 'status-update', status: status });
            });
            
            window.Echo.private(`user.${myId}`)
                .listen('.MatchFoundEvent', (e) => {
                    if (self.isPersonalCall || self.isHandlingMatch) return;
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
                    this.isPersonalCall = true;
                    this.partnerId = parseInt(urlParams.get('accept_call'));
                    this.state = 'connected';
                    window.axios.get(`/chat/user-info/${this.partnerId}`).then(res => { 
                        self.partnerData = res.data;
                        self.isFriend = true;
                        self.openFriendChat(res.data);
                        setTimeout(() => { self.signal({ type: 'call-accepted' }); }, 1500);
                    });
                }
                if (urlParams.has('call_to')) {
                    this.isPersonalCall = true;
                    this.partnerId = parseInt(urlParams.get('call_to'));
                    window.axios.get(`/chat/user-info/${this.partnerId}`).then(res => {
                        self.partnerData = res.data;
                        window.axios.post('/chat/contact/call', { contactId: self.partnerId }).then(r => {
                            if (r.data.status === 'busy') {
                                window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'User Busy'}}));
                                self.reset();
                            } else { self.state = 'connected'; }
                        });
                    });
                }
            }
            this.loadFriends(); this.loadHistory(); this.loadBlocked();
            this.startStats();
        },

        startHeartbeat() {
            this.stopHeartbeat();
            this.heartbeatTimer = setInterval(() => {
                window.axios.post('/ping').catch(e => { if (e.response?.status === 401) window.location.reload(); });
            }, 15000);
        },
        stopHeartbeat() { if (this.heartbeatTimer) { clearInterval(this.heartbeatTimer); this.heartbeatTimer = null; } },

        async initMedia() {
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: 640, height: 480, frameRate: 24 }, 
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
        rejectCall() { if(this.incomingCall) this.signalTo(this.incomingCall.fromId, { type: 'hang-up' }); this.ringtone.pause(); this.incomingCall = null; },

        initPC() {
            if (this.pc) return;
            const self = this;
            this.pc = new RTCPeerConnection(this.rtcConfig);
            this.pc.onicecandidate = (e) => { if (e.candidate) self.signal({ type: 'ice', candidate: e.candidate }); };
            this.pc.ontrack = (e) => { 
                const videoEl = self.$refs.remoteVideo;
                if (videoEl && e.streams[0]) {
                    videoEl.srcObject = e.streams[0];
                    videoEl.play().catch(err => console.warn(err));
                }
            };
            this.pc.oniceconnectionstatechange = () => {
                const s = this.pc?.iceConnectionState;
                if (['connected', 'completed'].includes(s)) self.handlePartnerState('active');
                if (['disconnected', 'failed'].includes(s)) self.handlePartnerState('offline');
            };
            if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
        },

        async handleSignal(e) {
            const self = this;
            const m = e.data;
            if (m.type === 'incoming-call') { this.incomingCall = m; if(this.audioUnlocked) this.ringtone.play().catch(()=>{}); return; }
            if (['peer-disconnected', 'peer-skipped', 'hang-up'].includes(m.type)) { 
                this.reset(); this.incomingCall = null; this.ringtone.pause(); 
                if(m.type === 'peer-skipped' && !this.isPersonalCall) this.startSearch(); 
                return; 
            }
            if (m.type === 'status-update') { this.handlePartnerState(m.status); return; }
            if (m.type === 'call-accepted') { this.state = 'connected'; this.initPC(); setTimeout(() => { self.sendOffer(); }, 1000); return; }
            
            if (!this.pc && ['offer', 'ice'].includes(m.type)) this.initPC();
            if (!this.pc) return;

            try {
                if (m.type === 'offer') {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }));
                    const answer = await this.pc.createAnswer();
                    await this.pc.setLocalDescription(answer);
                    this.signal({ type: 'answer', sdp: this.pc.localDescription.sdp });
                } else if (m.type === 'answer') {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(m.sdp) }));
                } else if (m.type === 'ice' && m.candidate) {
                    await this.pc.addIceCandidate(new RTCIceCandidate(m.candidate)).catch(()=>{});
                }
            } catch(err) { console.error("Signal Error", err); }
        },

        async sendOffer() {
            if (!this.pc || this.makingOffer) return;
            try {
                this.makingOffer = true;
                const offer = await this.pc.createOffer();
                await this.pc.setLocalDescription(offer);
                this.signal({ type: 'offer', sdp: this.pc.localDescription.sdp });
            } catch (e) { console.warn(e); } finally { this.makingOffer = false; }
        },

        async handleMatch(e) {
            this.isHandlingMatch = true; this.reset();
            this.partnerId = Number(e.partnerData.id); this.partnerData = e.partnerData; this.isFriend = !!e.isFriend; this.state = 'connected';
            this.startHeartbeat(); this.initPC();
            if (myId < this.partnerId) { setTimeout(() => { this.sendOffer(); this.isHandlingMatch = false; }, 1000); } 
            else { this.isHandlingMatch = false; }
        },

        signal(data) { this.signalTo(this.partnerId, data); },
        signalTo(toId, data) { if (toId) window.axios.post('/chat/signal', { partnerId: toId, data: { ...data, from: myId } }).catch(()=>{}); },
        
        reset() {
            this.stopHeartbeat();
            if (this.pc) { this.pc.close(); this.pc = null; }
            this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.messages = [];
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
            clearTimeout(this.offlineTimer);
        },

        handlePartnerState(s) { 
            this.partnerState = s; 
            if (s === 'offline' && this.state === 'connected' && !this.isPersonalCall) {
                clearTimeout(this.offlineTimer);
                this.offlineTimer = setTimeout(() => { if (this.partnerState === 'offline') this.startSearch(); }, 10000);
            }
        },

        normalizeSdp(sdp) { if (!sdp) return ""; return sdp.trim().split('\n').map(l => l.trim()).join('\r\n') + '\r\n'; },
        async startSearch() { this.reset(); this.state = 'searching'; this.startHeartbeat(); await window.axios.post('/chat/search'); },
        stopSearch() { if (this.partnerId) this.signal({ type: 'hang-up' }); this.reset(); window.axios.post('/chat/leave'); },
        async openFriendChat(f) { this.tab = 'friends'; this.activeFriend = f; const res = await window.axios.get(`/chat/history/${f.id}`); this.friendMessages = res.data.messages; this.scrollFriendChat(); },
        async sendMsg() { if (!this.chatInput.trim() || !this.partnerId) return; const t = this.chatInput; this.chatInput = ''; this.messages.push({isMe: true, text: t, timestamp: Date.now()}); window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t }); this.scrollChat(); },
        async sendFriendMsg() { if (!this.friendChatInput.trim() || !this.activeFriend) return; const t = this.friendChatInput; this.friendChatInput = ''; const res = await window.axios.post('/chat/message/send', { receiver_id: this.activeFriend.id, message: t }); this.friendMessages.push(res.data.message); this.scrollFriendChat(); },
        handleIncomingMsg(e) { const m = e.messageData; if (this.state === 'connected' && m.sender_id === this.partnerId) { this.messages.push({isMe: false, text: m.message, timestamp: Date.now()}); this.scrollChat(); } if (this.activeFriend && m.sender_id === this.activeFriend.id) { this.friendMessages.push(m); this.scrollFriendChat(); } },
        sendTypingSignal() { const rid = this.activeFriend ? this.activeFriend.id : this.partnerId; if (rid) window.axios.post('/chat/message/typing', { receiver_id: rid }); },
        toggleContact() { window.axios.post('/chat/contact/add', { contactId: this.partnerId }).then(r => { this.isFriend = r.data.isFriend; this.loadFriends(); }); },
        loadFriends() { window.axios.get('/chat/contacts').then(r => this.friendsList = r.data.contacts); },
        loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
        loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
        // Вызов друга из мессенджера
callFriend(f) { 
    if (!f.is_online) { 
        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'User is Offline'}})); 
        return; 
    } 
    window.location.href = '/chat?call_to=' + f.id; 
},

// Разблокировка пользователя
unblock(id) { 
    window.axios.post('/chat/unblock', { blockedId: id }).then(() => { 
        this.loadBlocked(); 
        this.loadHistory(); 
        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Unblocked'}}));
    }); 
},

// Жалоба на текущего партнера в рулетке
reportPartner() { 
    if (!this.partnerId || !confirm('Report and block this user?')) return; 
    window.axios.post('/report', { reported_id: this.partnerId, reason: 'general' }).then(() => { 
        window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Reported & Blocked'}})); 
        this.startSearch(); // Автоматический переход к следующему после жалобы
    }); 
},
        scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
        scrollFriendChat() { this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); },
        toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
        toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
        toggleBeauty() { this.beautyFilter = !this.beautyFilter; localStorage.setItem('beauty_filter', this.beautyFilter); },
        startStats() { setInterval(async () => { if (this.pc?.iceConnectionState === 'connected') { const s = await this.pc.getStats(); s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); }
    }
};
</script>
</body>
</html>