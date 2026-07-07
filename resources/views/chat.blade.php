<x-app-layout>
    <div class="h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans" 
         x-data="window.chatApp({{ auth()->id() }})" 
         @click.once="$store.sounds.unlock()">
        
        <!-- ДЕКОРАТИВНЫЕ СВЕЧЕНИЯ -->
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
                    
                    <!-- Оверлей восстановления связи (если инет пропал) -->
                    <div x-show="isReconnecting" x-transition x-cloak 
                         class="absolute inset-0 z-50 bg-black/60 backdrop-blur-md flex flex-col items-center justify-center">
                        <div class="w-16 h-16 border-4 border-amber-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                        <p class="text-amber-500 font-black uppercase text-[10px] tracking-[0.3em]">Восстановление связи...</p>
                        <p class="text-white/40 text-[9px] mt-2 font-bold uppercase">Ждем ответа от партнера</p>
                    </div>

                    <!-- Состояния Поиска -->
                    <div x-show="!isInCall" class="absolute inset-0 flex flex-col items-center justify-center bg-[#080808]">
                        <template x-if="state === 'searching'">
                            <div class="flex flex-col items-center">
                                <div class="relative flex items-center justify-center">
                                    <div class="w-32 h-32 border-2 border-indigo-500/10 rounded-full animate-ping"></div>
                                    <div class="absolute inset-0 w-32 h-32 border-t-2 border-indigo-500 rounded-full animate-spin"></div>
                                    <span class="text-3xl animate-bounce">📡</span>
                                </div>
                                <h3 class="mt-10 text-indigo-400 font-black uppercase text-[11px] tracking-[0.5em] animate-pulse">Ищем интересного собеседника</h3>
                            </div>
                        </template>
                        <template x-if="state === 'idle'">
                            <div class="text-center">
                                <div class="w-24 h-24 bg-white/5 rounded-full flex items-center justify-center text-4xl mb-6 mx-auto border border-white/5 shadow-inner">👋</div>
                                <span class="text-gray-500 font-black uppercase text-[10px] tracking-[0.4em]">Готов к новым знакомствам</span>
                            </div>
                        </template>
                    </div>

                    <!-- Кнопка размытия -->
                    <div x-show="isInCall && isBlurred" class="absolute inset-0 z-30 flex items-center justify-center bg-black/40 backdrop-blur-md">
                        <button @click="isBlurred = false" class="bg-white text-black px-12 py-5 rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:scale-105 transition-all shadow-2xl">Открыть камеру</button>
                    </div>

                    <!-- Индикатор Live -->
                    <div class="absolute top-8 left-8 z-20">
                        <div class="bg-black/60 backdrop-blur-2xl px-5 py-2.5 rounded-2xl border border-white/10 flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full" :class="isInCall ? 'bg-green-500 animate-pulse shadow-[0_0_10px_#22c55e]' : 'bg-gray-600'"></div>
                            <span class="text-[10px] font-black uppercase tracking-[0.15em] text-white/90" x-html="statusHtml"></span>
                        </div>
                    </div>
                </div>

                <!-- PIP (Вы) -->
                <div class="md:absolute md:bottom-10 md:left-10 w-full md:w-80 aspect-video md:aspect-square bg-black rounded-[2.5rem] overflow-hidden shadow-2xl border-2 border-white/5 z-40 group">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity">
                         <span class="text-[10px] font-black uppercase tracking-widest">Ваша камера</span>
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ ПАНЕЛЬ -->
            <div class="w-full lg:w-[400px] flex flex-col gap-4 z-10">
                <div class="flex-1 bg-white/[0.02] border border-white/5 backdrop-blur-3xl rounded-[3rem] flex flex-col overflow-hidden shadow-2xl">
                    <div class="p-8 border-b border-white/5 flex justify-between items-center bg-white/[0.01]">
                        <h3 class="text-[11px] font-black text-white uppercase tracking-[0.2em]">Messenger</h3>
                        <div class="flex gap-1.5" x-show="isPartnerTyping">
                            <div class="w-1 h-1 bg-indigo-500 rounded-full animate-bounce"></div>
                            <div class="w-1 h-1 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-8 space-y-6 scrollbar-hide" x-ref="rouletteChat">
                        <template x-for="msg in rouletteMessages">
                            <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col gap-2">
                                <div :class="msg.isMe ? 'bg-indigo-600 rounded-3xl rounded-tr-none' : 'bg-white/5 rounded-3xl rounded-tl-none border border-white/5'" 
                                     class="p-5 text-xs font-medium max-w-[90%] break-words leading-relaxed" x-text="msg.text"></div>
                            </div>
                        </template>
                    </div>

                    <div class="p-6 border-t border-white/5">
                        <div class="flex gap-3 bg-black/40 p-3 rounded-[1.8rem] border border-white/10 focus-within:border-indigo-500/50 transition-all">
                            <input type="text" x-model="rouletteInput" @input="sendTypingSignal()" @keyup.enter="sendRouletteMsg()" 
                                   placeholder="Текст..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 px-4">
                            <button @click="sendRouletteMsg()" class="bg-white text-black p-4 rounded-2xl hover:bg-indigo-500 hover:text-white transition-all shadow-xl">➔</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- УПРАВЛЕНИЕ -->
            <div class="absolute bottom-12 left-1/2 -translate-x-1/2 z-50 flex items-center gap-4 px-8 py-5 bg-[#121212]/80 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] shadow-2xl">
                <div class="flex items-center gap-3 border-r border-white/10 pr-6">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all">
                        <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                    </button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all">
                        <span x-text="camEnabled ? '📷' : '🚫'"></span>
                    </button>
                </div>

                <div class="px-2">
                    <template x-if="state === 'idle'">
                        <button @click="startSearch()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-12 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-indigo-500/30 transition-all">Начать поиск</button>
                    </template>
                    <template x-if="state === 'searching'">
                        <button @click="stopSearch()" class="bg-white/5 hover:bg-white/10 text-white px-12 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] border border-white/10 transition-all">Отмена</button>
                    </template>
                    <template x-if="state === 'connected'">
                        <div class="flex items-center gap-3">
                            <button @click="reportPartner()" class="w-14 h-14 bg-red-600/10 border border-red-600/20 text-red-500 rounded-2xl flex items-center justify-center text-lg hover:bg-red-600 hover:text-white transition-all shadow-inner">🚩</button>
                            <button @click="addPartnerToContacts()" :disabled="isPartnerFriend" :class="isPartnerFriend ? 'bg-green-500/20 text-green-500' : 'bg-white/5 text-gray-400'" class="w-14 h-14 rounded-2xl border border-white/10 transition-all flex items-center justify-center text-lg">
                                <span x-text="isPartnerFriend ? '✅' : '⭐️'"></span>
                            </button>
                            <button @click="startSearch()" class="bg-white text-black px-12 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] shadow-2xl active:scale-95 transition-all">Следующий ➔</button>
                            <button @click="stopSearch()" class="w-14 h-14 bg-red-600/10 border border-red-600/20 text-red-500 rounded-2xl flex items-center justify-center text-lg hover:bg-red-600 hover:text-white transition-all">❌</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('sounds', {
                message: new Audio('/sounds/message.mp3'), 
                isUnlocked: false,
                unlock() { this.isUnlocked = true; },
                playMsg() { if(this.isUnlocked) { this.message.currentTime = 0; this.message.play().catch(()=>{}); } }
            });
        });

        window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')), iceCandidatePoolSize: 10 };

        window.chatApp = function(myId) {
            return { 
                init() { 
                    window.Echo.join('online-status')
                        .here(u => {})
                        .joining(u => {})
                        .leaving(u => {}); 

                    window.Echo.private(`user.${myId}`)
                        .listen('.XpGainedEvent', (e) => {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { msg: `+${e.xpGained} XP за общение!`, type: 'success' } }));
                        });
                }
            }
        };

        window.videoChatComponent = function(myId) {
            return {
                state: 'idle', statusHtml: 'Готов', isInCall: false, partnerId: null, isPartnerFriend: false,
                pc: null, dc: null, localStream: null, iceQueue: [], 
                micEnabled: true, camEnabled: true, isBlurred: false,
                rouletteMessages: [], rouletteInput: '', isPartnerTyping: false, typingTimeout: null,
                isReconnecting: false, lastTypingSent: 0,

                async init() {
                    await this.initMedia(); 
                    window.Echo.private(`user.${myId}`)
                        .listen('.MatchFoundEvent', (e) => this.onMatchFound(e))
                        .listen('.WebRTCSignalEvent', (e) => this.onSignal(e));
                },

                async onMatchFound(e) { 
                    if (this.state !== 'searching') return;
                    this.partnerId = Number(e.partnerId); this.isPartnerFriend = !!e.isFriend;
                    this.state = 'connected'; this.isBlurred = true; this.statusHtml = 'Live';
                    this.initPC();
                    if (myId > this.partnerId) setTimeout(() => this.sendOffer(), 1000);
                },

                async onSignal(e) {
                    const msg = e.data;
                    if (Number(msg.from) !== this.partnerId) return;

                    if (msg.type === 'peer-skipped' || msg.type === 'peer-disconnected' || msg.type === 'hang-up') {
                        this.resetConnection();
                        if (msg.type === 'peer-skipped') setTimeout(() => this.startSearch(), 500);
                        return;
                    }

                    if (msg.type === 'typing') this.showTyping();

                    try {
                        if (msg.type === 'webrtc-offer') { 
                            this.initPC(); 
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'offer', sdp: this.sanitizeSdp(msg.sdp.sdp)})); 
                            const a = await this.pc.createAnswer(); 
                            await this.pc.setLocalDescription(a); 
                            this.signal({ type: 'webrtc-answer', sdp: a }); 
                            this.drainIce();
                        }
                        if (msg.type === 'webrtc-answer') { 
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'answer', sdp: this.sanitizeSdp(msg.sdp.sdp)})); 
                            this.drainIce();
                        }
                        if (msg.type === 'ice-candidate') {
                            const cand = new RTCIceCandidate(msg.candidate);
                            if (this.pc && this.pc.remoteDescription) await this.pc.addIceCandidate(cand).catch(()=>{});
                            else this.iceQueue.push(cand);
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
                    
                    // Мониторинг сети
                    this.pc.oniceconnectionstatechange = () => {
                        this.isReconnecting = (this.pc.iceConnectionState === 'disconnected');
                        if (this.pc.iceConnectionState === 'failed') this.resetConnection();
                    };

                    this.pc.ontrack = (event) => { 
                        if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = event.streams[0]; 
                        this.isInCall = true; 
                    };

                    if (this.localStream) this.localStream.getTracks().forEach(track => this.pc.addTrack(track, this.localStream));
                    this.pc.onicecandidate = (e) => { if (e.candidate) this.signal({ type: 'ice-candidate', candidate: e.candidate }); };
                    
                    this.dc = this.pc.createDataChannel("chat");
                    this.setupDC(this.dc);
                    this.pc.ondatachannel = (e) => this.setupDC(e.channel);
                },

                setupDC(channel) {
                    channel.onmessage = (e) => {
                        const d = JSON.parse(e.data);
                        if (d.type === 'text') { this.rouletteMessages.push({ isMe: false, text: d.text }); this.scrollChat(); Alpine.store('sounds').playMsg(); }
                        if (d.type === 'typing') this.showTyping();
                    };
                },

                async sendOffer() { 
                    this.initPC(); 
                    const o = await this.pc.createOffer(); 
                    await this.pc.setLocalDescription(o); 
                    this.signal({ type: 'webrtc-offer', sdp: o }); 
                },

                drainIce() { while(this.iceQueue.length > 0) this.pc.addIceCandidate(this.iceQueue.shift()).catch(()=>{}); },

                sendRouletteMsg() { 
                    if (!this.rouletteInput) return; 
                    const txt = this.rouletteInput;
                    if (this.dc && this.dc.readyState === 'open') this.dc.send(JSON.stringify({ type: 'text', text: txt }));
                    else this.signal({ type: 'roulette-text-msg', text: txt });
                    this.rouletteMessages.push({ isMe: true, text: txt }); this.rouletteInput = ''; this.scrollChat(); 
                },

                sendTypingSignal() {
                    if (Date.now() - this.lastTypingSent < 2000) return;
                    this.lastTypingSent = Date.now();
                    if (this.dc && this.dc.readyState === 'open') this.dc.send(JSON.stringify({ type: 'typing' }));
                    else this.signal({ type: 'typing' });
                },

                showTyping() {
                    this.isPartnerTyping = true;
                    if (this.typingTimeout) clearTimeout(this.typingTimeout);
                    this.typingTimeout = setTimeout(() => this.isPartnerTyping = false, 3000);
                },

                scrollChat() { this.$nextTick(() => { if(this.$refs.rouletteChat) this.$refs.rouletteChat.scrollTop = 9999; }); },
                signal(d) { if(this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: d }).catch(()=>{}); },
                
                resetConnection() { 
                    if (this.pc) { this.pc.close(); this.pc = null; }
                    if (this.$refs.remoteVideo) { this.$refs.remoteVideo.srcObject = null; this.$refs.remoteVideo.load(); }
                    this.partnerId = null; this.isInCall = false; this.state = 'idle'; this.statusHtml = 'Готов'; this.rouletteMessages = []; this.isBlurred = false; this.isReconnecting = false;
                },

                async startSearch() { 
                    if (this.partnerId) this.signal({ type: 'peer-skipped' });
                    this.resetConnection(); this.state = 'searching'; this.statusHtml = 'Поиск...';
                    await window.axios.post('/chat/search'); 
                },

                stopSearch() { if(this.partnerId) this.signal({ type: 'hang-up' }); this.resetConnection(); window.axios.post('/chat/leave'); },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch(e) { window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Доступ к камере запрещен', type: 'error' } })); }
                },

                toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
                toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
                sanitizeSdp(sdp) { return sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n'; },
                
                async reportPartner() {
                    if (!confirm('Пожаловаться на пользователя?')) return;
                    await window.axios.post('/report', { reported_id: this.partnerId, reason: 'abuse' });
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Жалоба отправлена', type: 'error' } }));
                    this.startSearch();
                },

                async addPartnerToContacts() { 
                    await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); 
                    this.isPartnerFriend = true; 
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Добавлен в контакты!', type: 'success' } }));
                }
            }
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        video { background: #000; transition: filter 0.6s ease; }
    </style>
</x-app-layout>