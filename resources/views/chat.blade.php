<x-app-layout>
    <div class="h-[calc(100svh-0px)] md:h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans selection:bg-indigo-500/30 flex" 
         x-data="window.videoChatApp({{ auth()->id() }}, {{ json_encode(auth()->user()->interests ?? []) }}, @js(config('webrtc.ice_servers')))"
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
                                <span x-show="partnerState === 'active'" x-text="partnerData?.rank_name || 'Собеседник'"></span>
                                <span x-show="partnerState === 'away'" class="text-amber-500 italic font-bold">Вне вкладки</span>
                                <span x-show="partnerState === 'offline'" class="text-red-500 italic font-bold">Потеря связи...</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ИНДИКАТОР ПИНГА -->
            <div x-show="state === 'connected' && ping > 0" class="absolute top-6 right-6 z-[90] bg-black/20 px-3 py-1.5 rounded-full border border-white/5">
                <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Ping: <span :class="ping < 100 ? 'text-green-500' : 'text-amber-500'" x-text="ping + 'ms'"></span></span>
            </div>

            <!-- ВИДЕО СОБЕСЕДНИКА -->
            <video x-ref="remoteVideo" autoplay playsinline 
                   class="w-full h-full object-cover transition-all duration-700" 
                   :class="isRemoteBlurred ? 'blur-[100px] scale-110 opacity-50' : 'opacity-100'"></video>
            
            <!-- СОСТОЯНИЕ: ОЖИДАНИЕ/ПОИСК -->
            <div x-show="state === 'searching' || state === 'idle'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-[110]">
                <div class="relative w-20 h-20 mb-6">
                    <div class="absolute inset-0 border-2 border-indigo-500/30 rounded-full" :class="state === 'searching' && 'animate-ping'"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-3xl" x-text="state === 'searching' ? '📡' : '👋'"></div>
                </div>
                <h3 class="text-white font-black uppercase text-[9px] tracking-[0.4em]" 
                    :class="state === 'searching' && 'animate-pulse'"
                    x-text="state === 'searching' ? (isCallingFriend ? 'Вызов друга...' : 'Поиск собеседника...') : 'Нажми старт для общения'"></h3>
            </div>

            <!-- PIP СВОЁ ВИДЕО -->
            <div x-show="showSelfVideo" class="absolute bottom-40 md:bottom-28 md:left-8 right-6 w-28 md:w-56 aspect-[3/4] md:aspect-video bg-[#111] rounded-3xl overflow-hidden shadow-2xl border border-white/10 z-[80] transition-all">
                <video x-ref="localVideo" autoplay muted playsinline 
                       class="w-full h-full object-cover scale-x-[-1] transition-all" 
                       :class="!camEnabled && 'opacity-0'"
                       :style="beautyFilter ? 'filter: saturate(1.3) brightness(1.05) contrast(0.95) blur(0.4px);' : ''"></video>
                <div x-show="!camEnabled" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 text-xl">🚫</div>
            </div>

            <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
            <div class="absolute bottom-24 md:bottom-10 left-0 right-0 px-4 z-[130] flex flex-col items-center gap-3 pointer-events-none">
                <div class="pointer-events-auto flex flex-col items-center gap-3 w-full max-w-lg">
                    
                    <!-- ДОП КНОПКИ -->
                    <div x-show="controlsOpen" x-transition class="flex items-center gap-1.5 p-1.5 bg-black/60 backdrop-blur-3xl border border-white/10 rounded-2xl shadow-2xl">
                        <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600 shadow-[0_0_15px_rgba(220,38,38,0.4)]'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">
                            <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                        </button>
                        <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600 shadow-[0_0_15px_rgba(220,38,38,0.4)]'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">
                            <span x-text="camEnabled ? '📷' : '🚫'"></span>
                        </button>
                        <button @click="isRemoteBlurred = !isRemoteBlurred" :class="isRemoteBlurred ? 'bg-indigo-600 shadow-lg' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">🙈</button>
                        <button @click="toggleBeauty()" :class="beautyFilter ? 'bg-pink-600 shadow-lg' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">✨</button>
                        <button @click="getDevices()" class="w-10 h-10 md:w-12 md:h-12 bg-white/5 rounded-xl flex items-center justify-center">⚙️</button>
                        
                        <template x-if="state === 'connected'">
                            <div class="flex items-center gap-1.5">
                                <div class="w-px h-6 bg-white/10 mx-1"></div>
                                <button @click="toggleContact()" :class="isFriend ? 'bg-green-600/20 text-green-400' : 'bg-white/5 text-white'" class="h-10 md:h-12 px-4 rounded-xl border border-white/5 font-black text-[9px] uppercase tracking-widest">
                                    <span x-text="isFriend ? 'Друг ✓' : '+ Друг'"></span>
                                </button>
                                <button @click="reportPartner()" class="w-10 h-10 md:w-12 md:h-12 bg-red-600/10 text-red-500 rounded-xl flex items-center justify-center">🚩</button>
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

        <!-- МЕССЕНДЖЕР (Desktop) -->
        <div x-show="sidebarOpen" x-transition class="hidden lg:flex flex-col bg-[#080808] border-l border-white/5 w-[400px] shrink-0 relative overflow-hidden">
            <div class="p-6 border-b border-white/5 bg-[#0a0a0a] flex justify-between items-center">
                <h2 class="text-xs font-black uppercase tracking-[0.3em] italic">Messenger</h2>
                <button @click="sidebarOpen = false" class="text-gray-500 hover:text-white transition-colors">✕</button>
            </div>
            @include('partials.messenger-content')
        </div>

        <!-- МЕССЕНДЖЕР (Mobile Bottom Sheet) -->
        <div x-show="mobileSidebarOpen" class="fixed inset-0 z-[240] bg-black/60 backdrop-blur-sm lg:hidden" @click="mobileSidebarOpen = false" x-cloak x-transition.opacity></div>
        <div :class="mobileSidebarOpen ? 'translate-y-0' : 'translate-y-full'" class="fixed inset-x-0 bottom-0 z-[250] h-[85vh] lg:hidden flex flex-col bg-[#080808] border-t border-white/5 rounded-t-[3rem] transition-transform duration-500 overflow-hidden">
            <div class="p-4 flex justify-center border-b border-white/5 shrink-0" @click="mobileSidebarOpen = false"><div class="w-12 h-1 bg-white/10 rounded-full"></div></div>
            @include('partials.messenger-content')
        </div>

        <!-- МОДАЛКА НАСТРОЕК УСТРОЙСТВ -->
        <div x-show="showDeviceModal" x-cloak class="fixed inset-0 z-[400] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl" x-transition>
            <div class="bg-[#0f0f0f] border border-white/10 w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl">
                <h3 class="text-lg font-black uppercase tracking-tighter mb-8 text-center italic text-white">Устройства</h3>
                <div class="space-y-6">
                    <template x-for="kind in ['video', 'audio']">
                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 mb-2 block ml-1" x-text="kind === 'video' ? 'Камера' : 'Микрофон'"></label>
                            <div class="max-h-40 overflow-y-auto space-y-2 custom-scrollbar">
                                <template x-for="d in devices.filter(x => x.kind === (kind + 'input'))">
                                    <button @click="switchDevice(kind, d.deviceId)" 
                                            :class="(kind === 'video' ? selectedCam : selectedMic) === d.deviceId ? 'border-indigo-500 bg-indigo-500/10 text-white' : 'border-white/5 bg-white/5 text-gray-400'" 
                                            class="w-full text-left px-4 py-4 rounded-2xl border text-[11px] font-bold truncate transition-all" 
                                            x-text="d.label || (kind + ' ' + d.deviceId.slice(0,5))"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <button @click="showDeviceModal = false" class="w-full mt-8 bg-white text-black py-5 rounded-3xl font-black text-[10px] uppercase shadow-xl active:scale-95 transition-all">Закрыть</button>
            </div>
        </div>
    </div>

<script>
window.videoChatApp = function(myId, myInterests, iceServers) {
    return {
        sidebarOpen: false, mobileSidebarOpen: false, tab: 'chat', controlsOpen: true,
        state: 'idle', partnerId: null, partnerData: null, isFriend: false,
        partnerState: 'active', offlineTimer: null, isCallingFriend: false,
        activeFriend: null, friendMessages: [], friendChatInput: '',
        pc: null, localStream: null, onlineList: [], friendsList: [], historyList: [], blockedList: [],
        micEnabled: true, camEnabled: true, isRemoteBlurred: false,
        showSelfVideo: true, messages: [], chatInput: '', ping: 0, beautyFilter: localStorage.getItem('beauty_filter') === 'true',
        showDeviceModal: false, devices: [], selectedCam: '', selectedMic: '',
        isPartnerTyping: false, msgSound: new Audio('/sounds/message.mp3'),
        audioUnlocked: false, iceQueue: [], lastTypingSent: 0,
        rtcConfig: { 
            iceServers: iceServers, 
            bundlePolicy: "balanced",
            iceCandidatePoolSize: 10
        },

        async init() {
            window.Echo.join('online-status')
                .here(u => this.onlineList = u)
                .joining(u => this.onlineList.push(u))
                .leaving(u => this.onlineList = this.onlineList.filter(x => x.id !== u.id));

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
                });

            document.addEventListener('visibilitychange', () => { 
                if (this.partnerId) this.signal({ type: 'user-state-changed', state: document.hidden ? 'away' : 'active' }); 
            });

            await this.initMedia();
            this.loadFriends(); this.loadHistory(); this.loadBlocked();

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('accept_call')) {
                const friendId = parseInt(urlParams.get('accept_call'));
                window.history.replaceState({}, document.title, window.location.pathname);
                this.partnerId = friendId;
                this.state = 'connected'; 
                this.isCallingFriend = true;
                window.axios.get(`/chat/user-info/${friendId}`).then(res => {
                    this.partnerData = res.data;
                    this.openFriendChat(res.data);
                });
                setTimeout(() => { this.signal({ type: 'call-accepted' }); }, 1000);
            }
        },

        normalizeSdp(sdp) {
            if (!sdp) return "";
            return sdp.trim().split('\n').map(line => line.trim()).join('\r\n') + '\r\n';
        },

        unlockAudio() { 
            if (!this.audioUnlocked) { 
                this.msgSound.play().then(() => { this.msgSound.pause(); this.audioUnlocked = true; }).catch(() => {}); 
            } 
        },

        initPC() {
            if (this.pc) return;
            this.pc = new RTCPeerConnection(this.rtcConfig);
            this.pc.onicecandidate = (e) => { if (e.candidate) this.signal({ type: 'ice', candidate: e.candidate }); };
            this.pc.ontrack = (e) => { if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = e.streams[0]; };
            this.pc.oniceconnectionstatechange = () => {
                if (['failed', 'disconnected', 'closed'].includes(this.pc.iceConnectionState)) this.handlePartnerState('offline');
                else if (this.pc.iceConnectionState === 'connected' || this.pc.iceConnectionState === 'completed') this.handlePartnerState('active');
            };
            if (this.localStream) { this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream)); }
        },

        async handleSignal(e) {
            const msg = e.data;
            if (msg.type === 'peer-disconnected' || msg.type === 'peer-skipped' || msg.type === 'hang-up') { 
                this.reset(); 
                if(msg.type === 'peer-skipped') this.startSearch(); 
                return; 
            }
            if (msg.type === 'user-state-changed') { this.handlePartnerState(msg.state); return; }
            if (msg.type === 'call-accepted') {
                this.state = 'connected';
                window.axios.get(`/chat/user-info/${msg.from}`).then(res => { this.partnerData = res.data; });
                this.initPC();
                this.sendOffer();
                return;
            }
            if (!this.pc && ['offer', 'ice'].includes(msg.type)) this.initPC();
            try {
                if (msg.type === 'offer') {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(msg.sdp) }));
                    const answer = await this.pc.createAnswer();
                    await this.pc.setLocalDescription(answer);
                    this.signal({ type: 'answer', sdp: answer.sdp });
                    this.processIceQueue();
                } 
                else if (msg.type === 'answer') {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(msg.sdp) }));
                    this.processIceQueue();
                } 
                else if (msg.type === 'ice' && msg.candidate) {
                    if (this.pc.remoteDescription) await this.pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(e => {});
                    else this.iceQueue.push(msg.candidate);
                }
            } catch(err) { console.error('Signal Error:', err); }
        },

        processIceQueue() { while(this.iceQueue.length > 0) this.pc.addIceCandidate(new RTCIceCandidate(this.iceQueue.shift())).catch(e => {}); },

        async sendOffer() {
            if(!this.pc) return;
            const offer = await this.pc.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true });
            await this.pc.setLocalDescription(offer);
            this.signal({ type: 'offer', sdp: offer.sdp });
        },

        async handleMatch(e) {
            this.reset();
            this.partnerId = Number(e.partnerData.id);
            this.partnerData = e.partnerData;
            this.isFriend = !!e.isFriend;
            this.state = 'connected';
            this.isCallingFriend = false;
            this.tab = 'chat';
            this.activeFriend = null;
            if (myId > this.partnerId) {
                setTimeout(() => { this.initPC(); this.sendOffer(); }, 1500);
            }
        },

        async openFriendChat(friend) {
            this.tab = 'friends';
            this.activeFriend = friend;
            const res = await window.axios.get(`/chat/history/${friend.id}`);
            this.friendMessages = res.data.messages;
            this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; });
        },

        async sendFriendMsg() {
            if (!this.friendChatInput.trim() || !this.activeFriend) return;
            const text = this.friendChatInput; this.friendChatInput = '';
            const res = await window.axios.post('/chat/message/send', { receiver_id: this.activeFriend.id, message: text });
            this.friendMessages.push(res.data.message);
            this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; });
        },

        handleIncomingMsg(e) {
            const msg = e.messageData;
            if (this.state === 'connected' && !this.isCallingFriend && msg.sender_id === this.partnerId) {
                this.messages.push({isMe: false, text: msg.message, timestamp: Date.now()});
                this.scrollChat();
            }
            if (this.activeFriend && msg.sender_id === this.activeFriend.id) {
                this.friendMessages.push(msg);
                this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; });
            }
            if(this.audioUnlocked) this.msgSound.play().catch(()=>{});
            if (!this.activeFriend || this.activeFriend.id !== msg.sender_id) {
                window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Новое сообщение', type:'info'}}));
            }
        },

        async callFriend(f) {
            const res = await window.axios.post('/chat/contact/call', { contactId: f.id });
            if (res.data.status === 'busy') {
                window.dispatchEvent(new CustomEvent('toast', {detail: {msg: res.data.message, type:'error'}}));
                return;
            }
            this.reset();
            this.partnerId = f.id;
            this.state = 'searching';
            this.isCallingFriend = true;
            this.openFriendChat(f);
            this.mobileSidebarOpen = false;
        },

        signal(data) {
            if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } }).catch(() => {});
        },

        async initMedia() {
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 }, audio: true });
                this.$refs.localVideo.srcObject = this.localStream;
            } catch(e) { window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Ошибка камеры', type:'error'}})); }
        },

        reset() {
            if (this.pc) { this.pc.onicecandidate = null; this.pc.ontrack = null; this.pc.close(); this.pc = null; }
            this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.messages = []; this.iceQueue = []; this.isCallingFriend = false;
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
        },

        async startSearch() {
            if (this.partnerId) this.signal({ type: 'peer-skipped' });
            this.reset(); this.state = 'searching'; this.isCallingFriend = false;
            await window.axios.post('/chat/search');
        },

        stopSearch() { 
            if (this.partnerId) this.signal({ type: 'hang-up' });
            this.reset(); 
            window.axios.post('/chat/leave'); 
        },

        async sendMsg() {
            if (!this.chatInput.trim() || !this.partnerId) return;
            if (this.isCallingFriend) {
                window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Используйте чат с другом', type:'info'}}));
                return;
            }
            const t = this.chatInput; this.chatInput = '';
            this.messages.push({isMe: true, text: t, timestamp: Date.now()});
            this.scrollChat();
            window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t });
        },
        sendTypingSignal() { if (this.partnerId && Date.now() - this.lastTypingSent > 2000) { this.lastTypingSent = Date.now(); window.axios.post('/chat/message/typing', { receiver_id: this.partnerId }); } },
        scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
        toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
        toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
        toggleBeauty() { this.beautyFilter = !this.beautyFilter; localStorage.setItem('beauty_filter', this.beautyFilter); },
        getDevices() { navigator.mediaDevices.enumerateDevices().then(d => { this.devices = d.filter(x => x.kind.includes('input')); this.showDeviceModal = true; }); },
        
        async switchDevice(kind, id) {
            const constraints = kind === 'video' ? { video: { deviceId: { exact: id } } } : { audio: { deviceId: { exact: id } } };
            try {
                const newStream = await navigator.mediaDevices.getUserMedia(constraints);
                const newTrack = newStream.getTracks()[0];
                if (this.pc) { const sender = this.pc.getSenders().find(s => s.track && s.track.kind === newTrack.kind); if (sender) sender.replaceTrack(newTrack); }
                const oldTrack = this.localStream.getTracks().find(t => t.kind === newTrack.kind);
                if(oldTrack) oldTrack.stop();
                this.localStream.removeTrack(oldTrack); this.localStream.addTrack(newTrack);
                if (kind === 'video') { this.selectedCam = id; this.$refs.localVideo.srcObject = this.localStream; }
            } catch(e) { console.error(e); }
        },
        async toggleContact() { const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); this.isFriend = res.data.isFriend; this.loadFriends(); },
        async reportPartner() { if(confirm('Заблокировать?')) { await window.axios.post('/report', {reported_id: this.partnerId, reason: 'abuse'}); this.startSearch(); } },
        loadFriends() { window.axios.get('/chat/contacts').then(r => this.friendsList = r.data.contacts); },
        loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
        loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
        async unblock(id) { await window.axios.post('/chat/unblock', { blockedId: id }); this.loadBlocked(); },
        handlePartnerState(s) { this.partnerState = s; if (s === 'offline') this.offlineTimer = setTimeout(() => { if (this.partnerState === 'offline') this.startSearch(); }, 15000); else clearTimeout(this.offlineTimer); },
        startStats() { 
            this.statsInterval = setInterval(async () => { 
                if (this.pc?.iceConnectionState === 'connected') { 
                    const stats = await this.pc.getStats(); 
                    stats.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); 
                } 
            }, 3000); 
        }
    }
};
</script>
</x-app-layout>