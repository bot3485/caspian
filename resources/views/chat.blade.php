<x-app-layout>
    <div class="h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans" 
         x-data="window.chatApp({{ auth()->id() }})" 
         @click.once="$store.sounds.unlock()"
         @open-chat.window="openMessenger($event.detail.id, $event.detail.name)">
        
        <!-- ФОНОВЫЕ СВЕЧЕНИЯ -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <div class="relative h-full flex flex-col lg:flex-row p-4 gap-4" x-data="window.videoChatComponent({{ auth()->id() }})">
            
            <!-- ЗОНА ВИДЕО -->
            <div class="flex-1 relative flex flex-col gap-4">
                
                <div class="flex-1 bg-[#080808] rounded-[3rem] border border-white/5 relative overflow-hidden shadow-2xl group">
                    <video x-ref="remoteVideo" autoplay playsinline class="w-full h-full object-cover transition-all duration-1000" 
                           :class="isBlurred ? 'blur-[100px] scale-110 opacity-40' : 'opacity-100'"
                           :class="!isInCall && 'opacity-0'"></video>
                    
                    <!-- Reconnect Overlay -->
                    <div x-show="isReconnecting" x-transition x-cloak 
                         class="absolute inset-0 z-40 bg-black/60 backdrop-blur-md flex flex-col items-center justify-center">
                        <div class="w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                        <p class="text-amber-500 font-black uppercase text-[10px] tracking-widest">Восстановление связи...</p>
                    </div>

                    <!-- States Overlay -->
                    <div x-show="!isInCall" class="absolute inset-0 flex flex-col items-center justify-center bg-[#080808]">
                        <template x-if="state === 'searching'">
                            <div class="flex flex-col items-center">
                                <div class="relative flex items-center justify-center">
                                    <div class="w-24 h-24 border-2 border-indigo-500/10 rounded-full animate-ping"></div>
                                    <div class="absolute inset-0 w-24 h-24 border-t-2 border-indigo-500 rounded-full animate-spin"></div>
                                    <div class="absolute text-xl">📡</div>
                                </div>
                                <h3 class="mt-8 text-indigo-400 font-black uppercase text-[10px] tracking-[0.4em] animate-pulse">Поиск собеседника</h3>
                            </div>
                        </template>
                        <template x-if="state === 'idle'">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center text-3xl mb-4 mx-auto border border-white/5">👋</div>
                                <span class="text-gray-500 font-black uppercase text-[9px] tracking-widest">Готовы к общению?</span>
                            </div>
                        </template>
                    </div>

                    <!-- Blur Toggle -->
                    <div x-show="isInCall && isBlurred" class="absolute inset-0 z-30 flex items-center justify-center bg-black/40 backdrop-blur-xl">
                        <button @click="isBlurred = false" class="bg-white text-black px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-105 transition-all">Открыть камеру</button>
                    </div>

                    <!-- Status Indicator -->
                    <div class="absolute top-8 left-8 z-20">
                        <div class="bg-black/60 backdrop-blur-2xl px-4 py-2 rounded-xl border border-white/10 flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full" :class="isInCall ? 'bg-green-500 animate-pulse' : 'bg-gray-600'"></div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-white/90" x-html="statusHtml">Готов</span>
                        </div>
                    </div>
                </div>

                <!-- PIP (Local) -->
                <div class="md:absolute md:bottom-10 md:left-10 w-full md:w-72 aspect-video md:aspect-square bg-black rounded-[2rem] overflow-hidden shadow-2xl border-2 border-white/5 z-40 group">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                </div>
            </div>

            <!-- MESSENGER -->
            <div class="w-full lg:w-[380px] flex flex-col gap-4 z-10">
                <div class="flex-1 bg-white/[0.02] border border-white/5 backdrop-blur-3xl rounded-[2.5rem] flex flex-col overflow-hidden shadow-2xl">
                    <div class="p-6 border-b border-white/5">
                        <h3 class="text-[10px] font-black text-white uppercase tracking-widest">Messenger</h3>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6 space-y-4 scrollbar-hide" x-ref="rouletteChat">
                        <template x-for="msg in rouletteMessages">
                            <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col gap-1">
                                <div :class="msg.isMe ? 'bg-indigo-600 rounded-2xl rounded-tr-none' : 'bg-white/10 rounded-2xl rounded-tl-none'" 
                                     class="p-4 text-xs font-medium max-w-[85%] break-words" x-text="msg.text"></div>
                            </div>
                        </template>
                    </div>

                    <div class="p-4 border-t border-white/5">
                        <div class="flex gap-2 bg-black/40 p-2 rounded-2xl border border-white/10">
                            <input type="text" x-model="rouletteInput" @keyup.enter="sendRouletteMsg()" 
                                   placeholder="Текст..." class="flex-1 bg-transparent border-none text-xs focus:ring-0 px-3">
                            <button @click="sendRouletteMsg()" class="bg-white text-black w-10 h-10 rounded-xl hover:bg-indigo-500 hover:text-white transition-all">➔</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTROLS -->
            <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-50 flex items-center gap-4 px-8 py-4 bg-[#121212]/90 backdrop-blur-3xl border border-white/10 rounded-[2rem] shadow-2xl">
                <div class="flex items-center gap-3 border-r border-white/10 pr-6">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-xl flex items-center justify-center transition-all">
                        <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                    </button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-xl flex items-center justify-center transition-all">
                        <span x-text="camEnabled ? '📷' : '🚫'"></span>
                    </button>
                </div>

                <div class="px-2">
                    <template x-if="state === 'idle'">
                        <button @click="startSearch()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">Начать поиск</button>
                    </template>
                    <template x-if="state === 'searching'">
                        <button @click="stopSearch()" class="bg-white/5 text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest border border-white/10 transition-all">Отмена</button>
                    </template>
                    <template x-if="state === 'connected'">
                        <div class="flex items-center gap-3">
                            <button @click="addPartnerToContacts()" :disabled="isPartnerFriend" :class="isPartnerFriend ? 'text-green-500 bg-green-500/10' : 'bg-white/5'" class="w-12 h-12 rounded-xl border border-white/10 flex items-center justify-center text-lg">
                                <span x-text="isPartnerFriend ? '✅' : '⭐️'"></span>
                            </button>
                            <button @click="startSearch()" class="bg-white text-black px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl active:scale-95 transition-all">Следующий ➔</button>
                            <button @click="stopSearch()" class="w-12 h-12 bg-red-600/10 text-red-500 border border-red-600/20 rounded-xl flex items-center justify-center text-lg">❌</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- INCOMING CALL MODAL -->
        <div x-show="incomingCall.show" x-transition x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-xl">
            <div class="bg-[#111] border border-white/10 w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl">
                <div class="w-20 h-20 bg-indigo-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-6 animate-bounce">📞</div>
                <h3 class="text-xl font-black text-white" x-text="incomingCall.callerName"></h3>
                <p class="text-gray-500 text-[9px] font-black uppercase tracking-widest mb-8 mt-2">Входящий видеовызов</p>
                <div class="flex gap-4">
                    <button @click="acceptCall()" class="flex-1 bg-green-600 text-white py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-green-500/20">Принять</button>
                    <button @click="declineCall()" class="flex-1 bg-white/5 text-white py-4 rounded-xl font-black text-[10px] uppercase tracking-widest border border-white/10">Отказ</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('online', {
                users: new Set(),
                set(arr) { this.users = new Set(arr.map(u => Number(u.id))); },
                add(id) { this.users.add(Number(id)); },
                remove(id) { this.users.delete(Number(id)); },
                has(id) { return this.users.has(Number(id)); }
            });
            Alpine.store('sounds', {
                message: new Audio('/sounds/message.mp3'), 
                call: new Audio('/sounds/call.mp3'), 
                isUnlocked: false,
                unlock() { 
                    if (this.isUnlocked) return; 
                    [this.message, this.call].forEach(a => { a.muted = true; a.play().then(() => { a.pause(); a.muted = false; a.currentTime = 0; }).catch(()=>{}); }); 
                    this.isUnlocked = true; 
                },
                playMsg() { if(this.isUnlocked) { this.message.currentTime = 0; this.message.play().catch(()=>{}); } },
                playCall() { if(this.isUnlocked) { this.call.loop = true; this.call.play().catch(()=>{}); } },
                stopCall() { this.call.pause(); this.call.currentTime = 0; }
            });
        });

        window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')), iceCandidatePoolSize: 10 };

        window.chatApp = function(myId) {
            return { 
                incomingCall: { show: false, callerId: null, callerName: '' },
                init() { 
                    window.Echo.join('online-status')
                        .here(u => Alpine.store('online').set(u))
                        .joining(u => Alpine.store('online').add(u.id))
                        .leaving(u => Alpine.store('online').remove(u.id)); 

                    window.Echo.private(`user.${myId}`)
                        .listen('.XpGainedEvent', (e) => {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { msg: `+${e.xpGained} XP за общение!`, type: 'success' } }));
                        })
                        .listen('.WebRTCSignalEvent', (e) => {
                            if (e.data.type === 'incoming-direct-call') {
                                this.incomingCall = { show: true, callerId: e.data.callerId, callerName: e.data.callerName };
                                Alpine.store('sounds').playCall();
                            }
                            if (e.data.type === 'hang-up' && this.incomingCall.show) {
                                this.incomingCall.show = false;
                                Alpine.store('sounds').stopCall();
                            }
                        });
                }, 
                acceptCall() {
                    const cid = this.incomingCall.callerId;
                    this.incomingCall.show = false;
                    Alpine.store('sounds').stopCall();
                    window.axios.post('/chat/signal', { partnerId: cid, data: { type: 'peer-ready', from: myId } });
                },
                declineCall() {
                    Alpine.store('sounds').stopCall();
                    window.axios.post('/chat/signal', { partnerId: this.incomingCall.callerId, data: { type: 'hang-up', from: myId } });
                    this.incomingCall.show = false;
                }
            }
        };

        window.videoChatComponent = function(myId) {
            return {
                state: 'idle', statusHtml: 'Готов', isInCall: false, partnerId: null, isPartnerFriend: false,
                pc: null, localStream: null, iceQueue: [], 
                micEnabled: true, camEnabled: true, isBlurred: false,
                rouletteMessages: [], rouletteInput: '', isReconnecting: false,

                async init() {
                    await this.initMedia(); 
                    window.Echo.private(`user.${myId}`)
                        .listen('.MatchFoundEvent', (e) => this.onMatchFound(e))
                        .listen('.WebRTCSignalEvent', (e) => this.onSignal(e));
                },

                async onMatchFound(e) { 
                    if (this.state !== 'searching') return;
                    this.partnerId = Number(e.partnerId); 
                    this.isPartnerFriend = !!e.isFriend;
                    this.state = 'connected'; 
                    this.isBlurred = true;
                    this.statusHtml = 'Live';
                    this.initPC();
                    if (myId > this.partnerId) setTimeout(() => this.sendOffer(), 800);
                },

                async onSignal(e) {
                    const msg = e.data;
                    if (msg.type !== 'incoming-direct-call' && Number(msg.from) !== this.partnerId) return;

                    if (msg.type === 'peer-skipped') {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Собеседник переключился', type: 'info' } }));
                        this.resetConnection();
                        setTimeout(() => this.startSearch(), 500); 
                        return;
                    }
                    if (msg.type === 'hang-up' || msg.type === 'peer-disconnected') {
                        this.resetConnection();
                        return;
                    }

                    try {
                        if (msg.type === 'webrtc-offer') { 
                            this.initPC(); 
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'offer', sdp: this.fixSdp(msg.sdpString)})); 
                            const a = await this.pc.createAnswer(); 
                            await this.pc.setLocalDescription(a); 
                            this.signal({ type: 'webrtc-answer', sdpString: a.sdp }); 
                        }
                        if (msg.type === 'webrtc-answer') { 
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'answer', sdp: this.fixSdp(msg.sdpString)})); 
                        }
                        if (msg.type === 'ice-candidate') {
                            if (this.pc && this.pc.remoteDescription) await this.pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(()=>{});
                        }
                        if (msg.type === 'roulette-text-msg') { 
                            this.rouletteMessages.push({ isMe: false, text: msg.text }); 
                            this.scrollChat(); 
                            Alpine.store('sounds').playMsg();
                        }
                    } catch(err) { console.error(err); }
                },

                initPC() {
                    if (this.pc) return;
                    this.pc = new RTCPeerConnection(window.rtcConfig);
                    this.pc.ontrack = (event) => { 
                        if (this.$refs.remoteVideo) { 
                            this.$refs.remoteVideo.srcObject = event.streams[0]; 
                            this.isInCall = true; 
                        } 
                    };
                    if (this.localStream) this.localStream.getTracks().forEach(track => this.pc.addTrack(track, this.localStream));
                    this.pc.onicecandidate = (e) => { if (e.candidate && this.partnerId) this.signal({ type: 'ice-candidate', candidate: e.candidate }); };
                },

                async sendOffer() { this.initPC(); const o = await this.pc.createOffer(); await this.pc.setLocalDescription(o); this.signal({ type: 'webrtc-offer', sdpString: o.sdp }); },
                sendRouletteMsg() { if (!this.rouletteInput) return; this.signal({ type: 'roulette-text-msg', text: this.rouletteInput }); this.rouletteMessages.push({ isMe: true, text: this.rouletteInput }); this.rouletteInput = ''; this.scrollChat(); },
                scrollChat() { this.$nextTick(() => { if (this.$refs.rouletteChat) this.$refs.rouletteChat.scrollTop = 9999; }); },
                signal(d) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: d }); },
                
                resetConnection() { 
                    if (this.pc) { this.pc.close(); this.pc = null; }
                    if (this.$refs.remoteVideo) { this.$refs.remoteVideo.srcObject = null; this.$refs.remoteVideo.load(); }
                    this.partnerId = null; this.isInCall = false; this.state = 'idle'; this.statusHtml = 'Готов'; this.rouletteMessages = []; this.isBlurred = false;
                },

                async startSearch() { 
                    if (this.partnerId) {
                        try { await window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { type: 'peer-skipped', from: myId } }); } catch(e){}
                    }
                    this.resetConnection(); 
                    this.state = 'searching'; 
                    this.statusHtml = 'ПОИСК...'; 
                    await window.axios.post('/chat/search'); 
                },

                stopSearch() { if(this.partnerId) this.signal({ type: 'hang-up' }); window.axios.post('/chat/leave'); this.resetConnection(); },
                async initMedia() { try { this.localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true }); this.$refs.localVideo.srcObject = this.localStream; } catch(e) { console.error("Media error", e); } },
                toggleMic() { this.micEnabled = !this.micEnabled; if (this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
                toggleCam() { this.camEnabled = !this.camEnabled; if (this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
                fixSdp(sd) { return sd ? sd.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ""; },
                async addPartnerToContacts() { if (this.partnerId) { await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); this.isPartnerFriend = true; window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Добавлено в контакты!', type: 'success' } })); } }
            }
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        video { background: #000; transition: filter 0.5s ease; }
    </style>
</x-app-layout>