<x-app-layout>
    <div class="h-[calc(100svh-0px)] md:h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans selection:bg-indigo-500/30 flex" 
         x-data="window.videoChatApp({{ auth()->id() }}, {{ json_encode(auth()->user()->interests ?? []) }})"
         @touchstart="touchStart = $event.touches[0].clientY"
         @touchend="handleSwipe($event)"
         @click="unlockAudio()">
        
        <!-- ДЕКОРАТИВНЫЕ СВЕЧЕНИЯ -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <!-- ОСНОВНАЯ ЗОНА (ВИДЕО) -->
        <div class="flex-1 relative bg-black overflow-hidden flex items-center justify-center transition-all duration-500">
            
            <!-- ВЕРХНЯЯ ПАНЕЛЬ ПАРТНЕРА -->
            <div x-show="state === 'connected' && partnerData" class="absolute top-6 left-6 z-[90]" x-transition>
                <div class="bg-black/40 backdrop-blur-2xl p-2 pr-6 rounded-2xl border border-white/10 flex items-center gap-3 shadow-2xl">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center font-black text-lg shadow-lg" x-text="partnerData?.name?.[0]"></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-tighter" x-text="partnerData?.name"></span>
                            <span class="bg-indigo-600 text-[7px] font-black px-1.5 py-0.5 rounded-md" x-text="'LVL ' + (partnerData?.level || 1)"></span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5 opacity-80">
                            <div class="w-1.5 h-1.5 rounded-full" :class="partnerState === 'active' ? 'bg-green-500 shadow-[0_0_8px_#22c55e]' : (partnerState === 'away' ? 'bg-amber-500' : 'bg-red-500')"></div>
                            <span class="text-[8px] font-black uppercase tracking-widest">
                                <span x-show="partnerState === 'active'" x-text="partnerData?.rank_name"></span>
                                <span x-show="partnerState === 'away'" class="text-amber-500 italic font-bold">Вне вкладки</span>
                                <span x-show="partnerState === 'offline'" class="text-red-500 italic font-bold">Связь прервана</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ВИДЕО СОБЕСЕДНИКА -->
            <video x-ref="remoteVideo" autoplay playsinline 
                   class="w-full h-full object-cover transition-all duration-700" 
                   :class="isRemoteBlurred ? 'blur-[100px] scale-110 opacity-50' : 'opacity-100'"></video>
            
            <!-- ИНДИКАТОР ЗАБЛЮРЕННОСТИ -->
            <div x-show="isRemoteBlurred" class="absolute inset-0 flex flex-col items-center justify-center bg-indigo-950/20 backdrop-blur-3xl z-[85] pointer-events-none">
                <span class="text-6xl mb-4">🙈</span>
                <p class="text-[10px] font-black uppercase tracking-[0.5em]">Собеседник скрыт</p>
            </div>

            <!-- PIP СВОЁ ВИДЕО -->
            <div x-show="showSelfVideo" class="absolute bottom-40 md:bottom-28 md:left-8 right-6 w-28 md:w-56 aspect-[3/4] md:aspect-video bg-[#111] rounded-3xl overflow-hidden shadow-2xl border border-white/10 z-[80] transition-all">
                <video x-ref="localVideo" autoplay muted playsinline 
                       class="w-full h-full object-cover scale-x-[-1] transition-all" 
                       :class="!camEnabled && 'opacity-0'"
                       :style="beautyFilter ? 'filter: saturate(1.3) brightness(1.05) contrast(0.95) blur(0.4px);' : ''"></video>
                <div x-show="!camEnabled" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 text-xl">🚫</div>
            </div>

            <!-- ЭКРАН ПОИСКА -->
            <div x-show="state === 'searching'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-[110]">
                <div class="relative w-20 h-20 mb-6">
                    <div class="absolute inset-0 border-2 border-indigo-500/30 rounded-full animate-ping"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-3xl">📡</div>
                </div>
                <h3 class="text-white font-black uppercase text-[9px] tracking-[0.4em] animate-pulse" x-text="isCallingFriend ? 'Вызов друга...' : 'Поиск собеседника...'"></h3>
            </div>

            <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
            <div class="absolute bottom-24 md:bottom-10 left-0 right-0 px-4 z-[130] flex flex-col items-center gap-3 pointer-events-none">
                <div class="pointer-events-auto flex flex-col items-center gap-3 w-full max-w-lg">
                    
                    <!-- ДОП КНОПКИ -->
                    <div x-show="controlsOpen" x-transition class="flex items-center gap-1.5 p-1.5 bg-black/60 backdrop-blur-3xl border border-white/10 rounded-2xl shadow-2xl">
                        <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">🎤</button>
                        <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">📷</button>
                        <button @click="isRemoteBlurred = !isRemoteBlurred" :class="isRemoteBlurred ? 'bg-indigo-600 shadow-lg' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">🙈</button>
                        <button @click="toggleBeauty()" :class="beautyFilter ? 'bg-pink-600 shadow-lg' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">✨</button>
                        <button @click="getDevices()" class="w-10 h-10 md:w-12 md:h-12 bg-white/5 rounded-xl flex items-center justify-center">⚙️</button>
                        
                        <template x-if="state === 'connected'">
                            <div class="flex items-center gap-1.5">
                                <div class="w-px h-6 bg-white/10 mx-1"></div>
                                <button @click="toggleContact()" :class="isFriend ? 'bg-green-600/20 text-green-400' : 'bg-white/5 text-white'" class="h-10 md:h-12 px-4 rounded-xl border border-white/5 font-black text-[9px] uppercase tracking-widest">
                                    <span x-text="isFriend ? 'Друг ✓' : '+ Друг'"></span>
                                </button>
                                <button @click="report(partnerId)" class="w-10 h-10 md:w-12 md:h-12 bg-red-600/10 text-red-500 rounded-xl flex items-center justify-center">🚩</button>
                            </div>
                        </template>
                    </div>

                    <!-- ГЛАВНЫЙ ОСТРОВОК -->
                    <div class="w-full bg-[#121212]/90 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] p-2 flex items-center justify-between shadow-2xl">
                        <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center transition-transform" :class="controlsOpen && 'rotate-180'">
                            <span class="text-[8px]" x-text="controlsOpen ? '▼' : '⚡'"></span>
                        </button>
                        <div class="flex-1 flex justify-center px-4">
                            <template x-if="state === 'idle'"><button @click="startSearch()" class="bg-indigo-600 w-full h-12 rounded-full font-black text-[10px] uppercase shadow-lg active:scale-95 transition-all">Начать поиск</button></template>
                            <template x-if="state === 'searching'"><button @click="stopSearch()" class="bg-red-600 w-full h-12 rounded-full font-black text-[10px] uppercase animate-pulse">Остановить</button></template>
                            <template x-if="state === 'connected'">
                                <div class="flex items-center gap-2 w-full">
                                    <button @click="stopSearch()" class="bg-red-600/20 text-red-500 px-6 h-12 rounded-full font-black text-[10px] uppercase active:scale-95 transition-all">Стоп</button>
                                    <button @click="startSearch()" class="bg-white text-black flex-1 h-12 rounded-full font-black text-[10px] uppercase shadow-xl active:scale-95 transition-all">Далее ➔</button>
                                </div>
                            </template>
                        </div>
                        <button @click="sidebarOpen = !sidebarOpen; mobileSidebarOpen = !mobileSidebarOpen" class="w-12 h-12 rounded-full bg-indigo-600/10 text-indigo-400 flex items-center justify-center text-lg border border-indigo-500/20 relative">
                            💬
                            <div x-show="isPartnerTyping" class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-500 rounded-full animate-ping"></div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- МЕССЕНДЖЕР -->
        <div x-show="sidebarOpen" x-transition class="hidden lg:flex flex-col bg-[#080808] border-l border-white/5 w-[400px] shrink-0 relative overflow-hidden">
            <div class="p-6 border-b border-white/5 bg-[#0a0a0a] flex justify-between items-center">
                <h2 class="text-xs font-black uppercase tracking-[0.3em] italic">Messenger</h2>
                <button @click="sidebarOpen = false" class="text-gray-500 hover:text-white transition-colors">✕</button>
            </div>
            @include('partials.messenger-content')
        </div>

        <div x-show="mobileSidebarOpen" class="fixed inset-0 z-[240] bg-black/60 backdrop-blur-sm lg:hidden" @click="mobileSidebarOpen = false" x-cloak x-transition.opacity></div>
        <div :class="mobileSidebarOpen ? 'translate-y-0' : 'translate-y-full'" class="fixed inset-x-0 bottom-0 z-[250] h-[85vh] lg:hidden flex flex-col bg-[#080808] border-t border-white/5 rounded-t-[3rem] transition-transform duration-500 overflow-hidden">
            <div class="p-4 flex justify-center border-b border-white/5 shrink-0" @click="mobileSidebarOpen = false"><div class="w-12 h-1 bg-white/10 rounded-full"></div></div>
            @include('partials.messenger-content')
        </div>

        <!-- МОДАЛКА НАСТРОЕК -->
        <div x-show="showDeviceModal" x-cloak class="fixed inset-0 z-[400] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl" x-transition>
            <div class="bg-[#0f0f0f] border border-white/10 w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl">
                <h3 class="text-lg font-black uppercase tracking-tighter mb-8 text-center italic text-white">Настройки</h3>
                <div class="space-y-6">
                    <template x-for="kind in ['video', 'audio']">
                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 mb-2 block ml-1" x-text="kind === 'video' ? 'Камера' : 'Микрофон'"></label>
                            <div class="max-h-40 overflow-y-auto space-y-2 custom-scrollbar">
                                <template x-for="d in devices.filter(x => x.kind === (kind + 'input'))">
                                    <button @click="switchDevice(kind, d.deviceId)" :class="(kind === 'video' ? selectedCam : selectedMic) === d.deviceId ? 'border-indigo-500 bg-indigo-500/10 text-white' : 'border-white/5 bg-white/5 text-gray-400'" class="w-full text-left px-4 py-4 rounded-2xl border text-[11px] font-bold truncate transition-all" x-text="d.label || (kind + ' ' + d.deviceId.slice(0,5))"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <button @click="showDeviceModal = false" class="w-full mt-8 bg-white text-black py-5 rounded-3xl font-black text-[10px] uppercase shadow-xl active:scale-95 transition-all">Готово</button>
            </div>
        </div>
    </div>

    <script>
    window.rtcConfig = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }, { urls: 'stun:stun1.l.google.com:19302' }], bundlePolicy: "balanced" };

    window.videoChatApp = function(myId, myInterests) {
        return {
            sidebarOpen: false, mobileSidebarOpen: false, tab: 'chat', controlsOpen: true, touchStart: 0,
            state: 'idle', partnerId: null, partnerData: null, isFriend: false,
            partnerState: 'active', offlineTimer: null, isCallingFriend: false,
            pc: null, localStream: null, onlineList: [], friendsList: [], historyList: [], blockedList: [],
            micEnabled: true, camEnabled: true, isRemoteBlurred: false,
            showSelfVideo: true, messages: [], chatInput: '', ping: 0, beautyFilter: localStorage.getItem('beauty_filter') === 'true',
            showDeviceModal: false, devices: [], selectedCam: '', selectedMic: '',
            xpPopups: [], matchInterests: [], showIceBreaker: false, myInterests: myInterests,
            isPartnerTyping: false, msgSound: new Audio('/sounds/message.mp3'),
            audioUnlocked: false, iceQueue: [], currentLevel: {{ auth()->user()->level }}, lastTypingSent: 0,

            async init() {
                window.Echo.join('online-status').here(u => this.onlineList = u).joining(u => this.onlineList.push(u)).leaving(u => this.onlineList = this.onlineList.filter(x => x.id !== u.id));
                window.Echo.private(`user.${myId}`)
                    .listen('.MatchFoundEvent', (e) => this.handleMatch(e))
                    .listen('.WebRTCSignalEvent', (e) => this.handleSignal(e))
                    .listen('.MessageSentEvent', (e) => this.handleIncomingMsg(e))
                    .listen('.UserTypingEvent', (e) => {
                        if (e.senderId === this.partnerId) {
                            this.isPartnerTyping = true;
                            clearTimeout(this.typingTimeout);
                            this.typingTimeout = setTimeout(() => this.isPartnerTyping = false, 3000);
                        }
                    })
                    .listen('.XpGainedEvent', (e) => this.showXpPopup(e.xpGained, e.totalXp, e.currentLevel));
                
                document.addEventListener('visibilitychange', () => { 
                    const s = document.hidden ? 'away' : 'active';
                    if (this.partnerId) this.signal({ type: 'user-state-changed', state: s }); 
                });
                
                window.addEventListener('online', () => { if (this.pc) this.pc.restartIce(); });
                await this.initMedia();
                this.loadFriends(); this.loadHistory(); this.loadBlocked();
                setInterval(() => { if (this.state !== 'idle') window.axios.post('/ping').catch(()=>{}); }, 15000);
            },

            unlockAudio() { if (!this.audioUnlocked) { this.msgSound.play().then(() => { this.msgSound.pause(); this.audioUnlocked = true; }).catch(() => {}); } },
            normalizeSdp(sdp) { if (typeof sdp !== 'string') sdp = sdp.sdp || ""; return sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n'; },

            scrollChat() { 
                this.$nextTick(() => { 
                    const box = this.$refs.chatBox;
                    if(box) box.scrollTop = box.scrollHeight; 
                }); 
            },

            initPC() {
                if (this.pc) return;
                this.pc = new RTCPeerConnection(window.rtcConfig);
                this.pc.onicecandidate = (e) => e.candidate && this.signal({type:'ice', candidate: e.candidate});
                this.pc.ontrack = (e) => { if (this.$refs.remoteVideo) { this.$refs.remoteVideo.srcObject = e.streams[0]; this.$refs.remoteVideo.play().catch(()=>{}); } };
                this.pc.oniceconnectionstatechange = () => {
                    if (this.pc.iceConnectionState === 'failed') this.pc.restartIce();
                    if (['disconnected', 'closed'].includes(this.pc.iceConnectionState)) this.handlePartnerState('offline');
                    else if (this.pc.iceConnectionState === 'connected') this.handlePartnerState('active');
                };
                if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
                this.startStats();
            },

            async handleSignal(e) {
                const msg = e.data; const senderId = Number(msg.from);
                if (msg.type === 'peer-disconnected') { this.reset(); if (this.state === 'searching') return; return; }
                if (msg.type === 'user-state-changed') { this.handlePartnerState(msg.state); return; }
                if (msg.type === 'privacy-toggled') { this.isBlurredByPartner = msg.enabled; return; }
                if (msg.type === 'receiver-ready') { this.initPC(); this.sendOffer(); return; }
                if (!this.pc && ['offer', 'ice'].includes(msg.type)) this.initPC();

                try {
                    if (msg.type === 'offer') {
                        if (this.pc.signalingState !== "stable") return;
                        await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'offer', sdp: this.normalizeSdp(msg.sdp)}));
                        const answer = await this.pc.createAnswer();
                        await this.pc.setLocalDescription(answer);
                        this.signal({ type: 'answer', sdp: answer.sdp });
                        this.processIceQueue();
                    } else if (msg.type === 'answer') {
                        if (this.pc.signalingState !== "have-local-offer") return;
                        await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'answer', sdp: this.normalizeSdp(msg.sdp)}));
                        this.processIceQueue();
                    } else if (msg.type === 'ice' && msg.candidate) {
                        if (this.pc?.remoteDescription) await this.pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(()=>{});
                        else this.iceQueue.push(msg.candidate);
                    }
                } catch(err) { console.warn('Signaling Error:', err); }
            },

            async sendOffer() { if(!this.pc) return; const offer = await this.pc.createOffer(); await this.pc.setLocalDescription(offer); this.signal({ type: 'offer', sdp: offer.sdp }); },
            processIceQueue() { while(this.iceQueue.length > 0) { this.pc.addIceCandidate(new RTCIceCandidate(this.iceQueue.shift())).catch(()=>{}); } },
            handleIncomingMsg(e) { if (e.messageData.sender_id === this.partnerId) { this.messages.push({isMe: false, text: e.messageData.message, timestamp: Date.now()}); this.scrollChat(); if(this.audioUnlocked) this.msgSound.play().catch(()=>{}); } },

            async sendMsg() {
                if (!this.chatInput.trim() || !this.partnerId) return;
                const t = this.chatInput; this.chatInput = '';
                this.messages.push({isMe: true, text: t, timestamp: Date.now()});
                this.scrollChat();
                window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t }).catch(()=>{});
            },

            sendTypingSignal() { if (this.partnerId && Date.now() - this.lastTypingSent > 2000) { this.lastTypingSent = Date.now(); window.axios.post('/chat/message/typing', { receiver_id: this.partnerId }); } },
            async startSearch() { if(this.partnerId) this.signal({type:'peer-skipped'}); this.reset(); this.state = 'searching'; await window.axios.post('/chat/search'); },
            async handleMatch(e) { this.reset(); this.partnerId = Number(e.partnerData.id); this.partnerData = e.partnerData; this.isFriend = !!e.isFriend; this.state = 'connected'; if (e.partnerData.common_interests?.length) { this.matchInterests = e.partnerData.common_interests; this.showIceBreaker = true; setTimeout(() => this.showIceBreaker = false, 4000); } this.initPC(); if (myId > this.partnerId) setTimeout(()=>this.sendOffer(), 1500); },
            stopSearch() { this.reset(); window.axios.post('/chat/leave'); },
            reset() { 
                clearInterval(this.statsInterval); 
                if (this.pc) { this.pc.onicecandidate = null; this.pc.ontrack = null; this.pc.close(); this.pc = null; } 
                this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.messages = []; this.iceQueue = []; 
                this.isRemoteBlurred = false; this.partnerState = 'active'; this.isPartnerTyping = false;
                if (this.$refs.remoteVideo) { this.$refs.remoteVideo.srcObject = null; this.$refs.remoteVideo.load(); } 
            },
            signal(data) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } }).catch(()=>{}); },
            async initMedia() { 
                try { 
                    this.localStream = await navigator.mediaDevices.getUserMedia({ video: { width: 1280, height: 720 }, audio: true }); 
                    this.$refs.localVideo.srcObject = this.localStream; 
                } catch(e){ console.error("Camera error"); } 
            },
            toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
            toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
            toggleBeauty() { this.beautyFilter = !this.beautyFilter; localStorage.setItem('beauty_filter', this.beautyFilter); },
            getDevices() { navigator.mediaDevices.enumerateDevices().then(d => { this.devices = d.filter(x => x.kind.includes('input')); this.showDeviceModal = true; }); },
            async switchDevice(kind, id) {
                const c = kind === 'video' ? { video: { deviceId: id } } : { audio: { deviceId: id } };
                const s = await navigator.mediaDevices.getUserMedia(c);
                const t = s.getTracks()[0];
                if (this.pc) { const send = this.pc.getSenders().find(x => x.track.kind === t.kind); if (send) send.replaceTrack(t); }
                const old = this.localStream.getTracks().find(x => x.kind === t.kind);
                this.localStream.removeTrack(old); this.localStream.addTrack(t);
                if (kind === 'video') { this.selectedCam = id; this.$refs.localVideo.srcObject = this.localStream; } else this.selectedMic = id;
            },
            async toggleContact() { const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); this.isFriend = res.data.isFriend; this.loadFriends(); },
            async report(id) { if(confirm('Заблокировать?')) { await window.axios.post('/report', {reported_id:id, reason:'abuse'}); this.startSearch(); } },
            loadFriends() { window.axios.get('/chat/contacts').then(r => this.friendsList = r.data.contacts); },
            loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
            loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
            async unblock(id) { await window.axios.post('/chat/unblock', { blockedId: id }); this.loadBlocked(); },
            async callFriend(f) { this.partnerId = f.id; this.state = 'searching'; this.isCallingFriend = true; await window.axios.post('/chat/contact/call', { contactId: f.id }); },
            handlePartnerState(s) { this.partnerState = s; if (s === 'offline') this.offlineTimer = setTimeout(() => this.stopSearch(), 30000); else clearTimeout(this.offlineTimer); },
            showXpPopup(a, tx, cl) { const id = Date.now(); this.xpPopups.push({ id, amount: a }); if(cl > this.currentLevel) { window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Уровень: '+cl, type:'success'}})); this.currentLevel = cl; } setTimeout(() => this.xpPopups = this.xpPopups.filter(p => p.id !== id), 4000); },
            startStats() { this.statsInterval = setInterval(async () => { if (this.pc?.iceConnectionState === 'connected') { const s = await this.pc.getStats(); s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); },
            handleSwipe(e) { if (!this.mobileSidebarOpen && (this.touchStart - e.changedTouches[0].clientY > 100)) this.startSearch(); },
        }
    };
    </script>
</x-app-layout>