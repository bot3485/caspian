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
        html, body { min-height: var(--app-height); background: #050505; margin: 0; padding: 0; overflow-x: hidden; -webkit-overflow-scrolling: touch; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 10px; }
    </style>
</head>
<body class="font-sans antialiased text-white" 
      x-data="window.caspianApp({{ auth()->id() }}, {{ json_encode(auth()->user()->interests ?? []) }}, @js(config('webrtc.ice_servers')))"
      x-init="init()"
      @click="unlockAudio()"
      @visibilitychange.window="handleVisibilityChange()">

    <!-- CALL MODAL -->
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

    <!-- NOTIFICATIONS -->
    <div x-data="{ toasts: [] }" @toast.window="const id = Date.now(); toasts.push({id, msg: $event.detail.msg}); setTimeout(() => toasts = toasts.filter(t => t.id !== id), 3000)" class="fixed top-20 left-1/2 -translate-x-1/2 z-[1000] w-full max-w-xs space-y-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="pointer-events-auto bg-indigo-600/90 backdrop-blur-xl border border-indigo-400/50 px-6 py-2 rounded-xl shadow-2xl text-center">
                <span class="text-[9px] font-black uppercase tracking-widest" x-text="toast.msg"></span>
            </div>
        </template>
    </div>

    <div class="flex flex-col min-h-screen relative">
        @include('layouts.navigation')
        <main class="flex-1 relative">{{ $slot }}</main>

        <div x-show="globalSidebarOpen" @click.outside="globalSidebarOpen = false"
             class="fixed right-0 top-0 bottom-0 z-[450] w-full md:w-[400px] bg-[#080808] border-l border-white/5 shadow-2xl flex flex-col"
             x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" x-cloak>
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
        globalSidebarOpen: false, tab: 'chat', controlsOpen: true, state: 'idle', callContext: null,
        partnerId: null, partnerData: null, isFriend: false, partnerState: 'active',
        incomingCall: null, ringtone: new Audio('/sounds/call.mp3'), msgSound: new Audio('/sounds/message.mp3'),
        activeFriend: null, friendMessages: [], friendChatInput: '', friendsList: [], historyList: [], blockedList: [],
        isPartnerTyping: false, typingPartnerName: '',
        pc: null, localStream: null, micEnabled: true, camEnabled: true, isRemoteBlurred: false, 
        showSelfVideo: true, messages: [], chatInput: '', ping: 0,
        beautyFilter: localStorage.getItem('beauty_filter') === 'true',
        isProcessingSignal: false, makingOffer: false, audioUnlocked: false,
        processedEvents: new Set(),
        iceQueue: [], // Очередь кандидатов до установки RemoteDescription
        
        rtcConfig: { 
            iceServers: iceServers, 
            bundlePolicy: "balanced", 
            iceCandidatePoolSize: 10 
        },

        async init() {
            const self = this;
            this.ringtone.loop = true;

            window.Echo.private(`user.${myId}`)
                .listen('.MatchFoundEvent', (e) => self.handleMatch(e))
                .listen('.WebRTCSignalEvent', (e) => self.handleSignal(e))
                .listen('.MessageSentEvent', (e) => self.handleIncomingMsg(e))
                .listen('.UserTypingEvent', (e) => self.handleTyping(e));

            if (window.location.pathname === '/chat') {
                await this.initMedia();
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('accept_call')) this.setupPersonalCall(parseInt(urlParams.get('accept_call')), true);
                if (urlParams.has('call_to')) this.setupPersonalCall(parseInt(urlParams.get('call_to')), false);
            }
            this.loadFriends(); this.loadHistory(); this.loadBlocked();
            this.startStats();
        },

        async initMedia() {
            if (this.localStream) return;
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: { ideal: 1280 }, height: { ideal: 720 } }, 
                    audio: true 
                });
                if (this.$refs.localVideo) this.$refs.localVideo.srcObject = this.localStream;
            } catch(e) {
                console.error("Camera access denied:", e);
                window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Camera access denied'}}));
            }
        },

        async handleMatch(e) {
            if (this.callContext === 'personal') return;
            this.reset();
            this.partnerId = Number(e.partnerData.id); 
            this.partnerData = e.partnerData; 
            this.isFriend = !!e.isFriend; 
            this.state = 'connected';
            this.callContext = 'roulette';
            this.startHeartbeat(); 
            this.initPC();
            if (myId < this.partnerId) { 
                setTimeout(() => { this.sendOffer(); }, 1000); 
            }
        },

        initPC() {
            if (this.pc) {
                this.pc.close();
                this.pc = null;
            }
            const self = this;
            this.iceQueue = [];
            this.pc = new RTCPeerConnection(this.rtcConfig);
            
            this.pc.onicecandidate = (e) => { 
                if (e.candidate) self.signal({ type: 'ice', candidate: e.candidate }); 
            };
            
            this.pc.ontrack = (e) => { 
                if (self.$refs.remoteVideo) self.$refs.remoteVideo.srcObject = e.streams[0]; 
            };

            this.pc.oniceconnectionstatechange = () => {
                console.log("ICE State:", this.pc.iceConnectionState);
                if (['disconnected', 'failed'].includes(self.pc?.iceConnectionState)) { self.partnerState = 'problem'; }
            };

            if (this.localStream) {
                this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
            }
        },

async handleSignal(e) {
            const m = e.data;
            if (m.type === 'incoming-call') { this.incomingCall = m; if(this.audioUnlocked) this.ringtone.play().catch(()=>{}); return; }
            if (m.type === 'status-sync') { this.partnerState = m.state; return; }
            if (['peer-disconnected', 'hang-up', 'peer-skipped'].includes(m.type)) { this.stopCall(false); return; }
            if (m.type === 'call-accepted') { this.state = 'connected'; this.initPC(); setTimeout(() => this.sendOffer(), 1000); return; }

            // Защита от одновременной обработки нескольких сигналов
            if (this.isProcessingSignal) return;
            this.isProcessingSignal = true;

            try {
                if (m.type === 'offer') {
                    // Если мы получили оффер, а соединение уже стабильно или есть другой оффер — пересоздаем PC
                    if (!this.pc || this.pc.signalingState !== 'stable') this.initPC();
                    
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }));
                    const answer = await this.pc.createAnswer();
                    await this.pc.setLocalDescription(answer);
                    this.signal({ type: 'answer', sdp: this.pc.localDescription.sdp });
                    
                    while(this.iceQueue.length) {
                        await this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
                    }
                } 
                else if (m.type === 'answer') {
                    // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Принимаем Answer только если мы в состоянии 'have-local-offer'
                    if (this.pc && this.pc.signalingState === 'have-local-offer') {
                        await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(m.sdp) }));
                    } else {
                        console.warn("Ignored answer signal: signalingState is not 'have-local-offer' (current: " + (this.pc ? this.pc.signalingState : 'null') + ")");
                    }
                } 
                else if (m.type === 'ice') {
                    const candidate = new RTCIceCandidate(m.candidate);
                    if (this.pc && this.pc.remoteDescription && this.pc.remoteDescription.type) {
                        await this.pc.addIceCandidate(candidate).catch(e => {});
                    } else {
                        this.iceQueue.push(candidate);
                    }
                }
            } catch (e) {
                console.error("WebRTC Signaling Error:", e);
                // Если произошла критическая ошибка состояния — пробуем мягкий перезапуск
                if (e.name === 'InvalidStateError' && m.type === 'offer') {
                    this.initPC();
                }
            } finally {
                this.isProcessingSignal = false;
            }
        },

        reset() {
            this.stopHeartbeat(); 
            this.ringtone.pause(); 
            this.incomingCall = null;
            if (this.pc) {
                this.pc.onicecandidate = null;
                this.pc.ontrack = null;
                this.pc.oniceconnectionstatechange = null;
                this.pc.close();
                this.pc = null;
            }
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
            this.partnerId = null; 
            this.partnerData = null; 
            this.state = 'idle'; 
            this.partnerState = 'active';
            this.messages = [];
            this.iceQueue = [];
        },

        stopCall(notify = true) {
            if (notify && this.partnerId) this.signal({ type: 'hang-up' });
            this.reset();
            window.axios.post('/chat/leave');
            window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Call Ended'}}));
        },

        // --- ОСТАЛЬНЫЕ МЕТОДЫ БЕЗ ИЗМЕНЕНИЙ ---
        async sendOffer() { 
            if (!this.pc || this.makingOffer || this.pc.signalingState !== 'stable') return;
            this.makingOffer = true; 
            try { 
                const offer = await this.pc.createOffer(); 
                await this.pc.setLocalDescription(offer); 
                this.signal({ type: 'offer', sdp: this.pc.localDescription.sdp }); 
            } catch(e){ console.error(e); } finally { this.makingOffer = false; } 
        },
        signal(data) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } }).catch(()=>{}); },
        normalizeSdp(sdp) { return typeof sdp === 'string' ? sdp.trim().split('\n').map(l => l.trim()).join('\r\n') + '\r\n' : sdp; },
        unlockAudio() { if (this.audioUnlocked) return; this.ringtone.muted = true; this.ringtone.play().then(() => { this.ringtone.pause(); this.ringtone.muted = false; }).catch(()=>{}); this.audioUnlocked = true; },
        startHeartbeat() { this.heartbeatTimer = setInterval(() => window.axios.post('/ping'), 15000); },
        stopHeartbeat() { clearInterval(this.heartbeatTimer); },
        startStats() { setInterval(async () => { if (this.pc?.iceConnectionState === 'connected') { const s = await this.pc.getStats(); s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); },
        handleVisibilityChange() { if (this.partnerId) this.signal({ type: 'status-sync', state: document.visibilityState === 'hidden' ? 'away' : 'active' }); },
        async startSearch() { this.reset(); this.state = 'searching'; this.callContext = 'roulette'; this.startHeartbeat(); await window.axios.post('/chat/search'); },
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
                clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => { this.isPartnerTyping = false; }, 3000);
            }
        },
        scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
        scrollFriendChat() { this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); },
        loadFriends() { window.axios.get('/chat/contacts').then(r => this.friendsList = r.data.contacts); },
        loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
        loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
        openFriendChat(f) { this.tab = 'friends'; this.activeFriend = f; window.axios.get(`/chat/history/${f.id}`).then(res => { this.friendMessages = res.data.messages; this.scrollFriendChat(); }); },
        async sendMsg() { if (!this.chatInput.trim() || !this.partnerId) return; const t = this.chatInput; this.chatInput = ''; this.messages.push({isMe: true, text: t, timestamp: Date.now()}); window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t }); this.scrollChat(); },
        async sendFriendMsg() { if (!this.friendChatInput.trim() || !this.activeFriend) return; const t = this.friendChatInput; this.friendChatInput = ''; const res = await window.axios.post('/chat/message/send', { receiver_id: this.activeFriend.id, message: t }); this.friendMessages.push(res.data.message); this.scrollFriendChat(); },
        sendTypingSignal() { const rid = this.activeFriend ? this.activeFriend.id : this.partnerId; if (rid) window.axios.post('/chat/message/typing', { receiver_id: rid }); },
        callFriend(f) { if (!f.is_online) return; window.location.href = '/chat?call_to=' + f.id; },
        toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
        toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
        toggleBeauty() { this.beautyFilter = !this.beautyFilter; localStorage.setItem('beauty_filter', this.beautyFilter); },
        reportPartner() { if (!this.partnerId || !confirm('Report and block?')) return; window.axios.post('/report', { reported_id: this.partnerId, reason: 'general' }).then(() => { this.startSearch(); }); },
        toggleContact() { window.axios.post('/chat/contact/add', { contactId: this.partnerId }).then(r => { this.isFriend = r.data.isFriend; this.loadFriends(); }); },
        unblock(id) { window.axios.post('/chat/unblock', { blockedId: id }).then(() => { this.loadBlocked(); this.loadHistory(); }); },
        signalTo(toId, data) { if (toId) window.axios.post('/chat/signal', { partnerId: toId, data: { ...data, from: myId } }).catch(()=>{}); }
    }
};
</script>
</body>
</html>