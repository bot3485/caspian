<x-app-layout>
    <div class="h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans" 
         x-data="window.chatApp()" 
         @click.once="$store.sounds.unlock()"
         @open-chat.window="openMessenger($event.detail.id, $event.detail.name)">
        
        <!-- ДЕКОРАТИВНЫЕ ФОНОВЫЕ СВЕЧЕНИЯ -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <!-- ОСНОВНОЙ КОНТЕЙНЕР ВИДЕОЧАТА -->
        <div class="relative h-full flex flex-col lg:flex-row p-4 gap-4" x-data="window.videoChatComponent({{ auth()->id() }})">
            
            <!-- ЛЕВАЯ ЧАСТЬ: ЗОНА ВИДЕО -->
            <div class="flex-1 relative flex flex-col gap-4">
                
                <!-- ГЛАВНОЕ ОКНО (СОБЕСЕДНИК) -->
                <div class="flex-1 bg-[#080808] rounded-[3rem] border border-white/5 relative overflow-hidden shadow-[0_0_50px_rgba(0,0,0,0.5)] group">
                    <video x-ref="remoteVideo" autoplay playsinline class="w-full h-full object-cover transition-all duration-1000" 
                           :class="isBlurred ? 'blur-[100px] scale-110 opacity-40' : 'opacity-100'"
                           :class="!isInCall && 'opacity-0'"></video>
                    
                    <!-- Оверлей проблем с сетью (Reconnect) -->
                    <div x-show="isReconnecting" x-transition x-cloak 
                         class="absolute inset-0 z-40 bg-black/60 backdrop-blur-md flex flex-col items-center justify-center">
                        <div class="w-16 h-16 border-4 border-amber-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                        <p class="text-amber-500 font-black uppercase text-[10px] tracking-[0.3em]">Восстановление связи...</p>
                        <p class="text-white/40 text-[9px] mt-2 font-bold uppercase">Пытаемся оживить поток</p>
                    </div>

                    <!-- Overlay: Состояния (Поиск / Ожидание) -->
                    <div x-show="!isInCall" class="absolute inset-0 flex flex-col items-center justify-center bg-[#080808]">
                        <template x-if="state === 'searching'">
                            <div class="flex flex-col items-center">
                                <div class="relative flex items-center justify-center">
                                    <div class="w-32 h-32 border-2 border-indigo-500/10 rounded-full"></div>
                                    <div class="absolute inset-0 w-32 h-32 border-t-2 border-indigo-500 rounded-full animate-spin"></div>
                                    <div class="absolute inset-6 bg-indigo-500/20 rounded-full animate-pulse"></div>
                                    <div class="absolute text-2xl animate-bounce">📡</div>
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

                    <!-- Overlay: Кнопка Размытия -->
                    <div x-show="isInCall && isBlurred" class="absolute inset-0 z-30 flex items-center justify-center bg-black/40 backdrop-blur-md">
                        <button @click="isBlurred = false" class="bg-white text-black px-12 py-5 rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:scale-105 transition-all shadow-2xl">Открыть камеру</button>
                    </div>

                    <!-- Индикатор статуса -->
                    <div class="absolute top-8 left-8 z-20">
                        <div class="bg-black/60 backdrop-blur-2xl px-5 py-2.5 rounded-2xl border border-white/10 flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full" :class="isInCall ? 'bg-green-500 animate-pulse shadow-[0_0_10px_#22c55e]' : 'bg-gray-600'"></div>
                            <span class="text-[10px] font-black uppercase tracking-[0.15em] text-white/90" x-html="statusHtml">Готов</span>
                        </div>
                    </div>
                </div>

                <!-- ВАШЕ ОКНО (PIP) -->
                <div class="md:absolute md:bottom-10 md:left-10 w-full md:w-80 aspect-video md:aspect-square bg-black rounded-[2.5rem] overflow-hidden shadow-2xl border-2 border-white/5 z-40 group">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500">
                        <div class="absolute bottom-6 left-6 text-[10px] font-black uppercase tracking-widest text-white/80">Вы</div>
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ ПАНЕЛЬ: МЕССЕНДЖЕР -->
            <div class="w-full lg:w-[400px] flex flex-col gap-4 z-10">
                <div class="flex-1 bg-white/[0.02] border border-white/5 backdrop-blur-3xl rounded-[3rem] flex flex-col overflow-hidden shadow-2xl">
                    <div class="p-8 border-b border-white/5 flex justify-between items-center bg-white/[0.01]">
                        <div>
                            <h3 class="text-[11px] font-black text-white uppercase tracking-[0.2em]">Messenger</h3>
                            <div class="flex items-center gap-1.5 mt-1">
                                <div class="w-1 h-1 bg-green-500 rounded-full"></div>
                                <span class="text-[9px] font-bold text-gray-500 uppercase">E2E Protected</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-8 space-y-6 scrollbar-hide" x-ref="rouletteChat">
                        <template x-for="msg in rouletteMessages">
                            <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col gap-2">
                                <div :class="msg.isMe ? 'bg-indigo-600 rounded-3xl rounded-tr-none' : 'bg-white/5 rounded-3xl rounded-tl-none border border-white/5'" 
                                     class="p-5 text-xs font-medium max-w-[90%] break-words leading-relaxed" x-text="msg.text"></div>
                            </div>
                        </template>
                        <div x-show="isPartnerTyping" class="flex gap-1.5 p-3 bg-white/5 w-fit rounded-2xl animate-pulse">
                            <div class="w-1 h-1 bg-indigo-400 rounded-full animate-bounce"></div>
                            <div class="w-1 h-1 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                        </div>
                    </div>

                    <div class="p-6 border-t border-white/5">
                        <div class="flex gap-3 bg-black/40 p-3 rounded-[1.8rem] border border-white/10 focus-within:border-indigo-500/50 transition-all">
                            <input type="text" x-model="rouletteInput" @input="sendTypingSignal()" @keyup.enter="sendRouletteMsg()" 
                                   placeholder="Текст..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 px-4">
                            <button @click="sendRouletteMsg()" class="bg-white text-black p-4 rounded-2xl hover:bg-indigo-500 hover:text-white transition-all shadow-xl">➔</button>
                        </div>
                    </div>
                </div>

                <div class="h-56 bg-white/[0.02] border border-white/5 rounded-[3rem] p-8" x-data="window.contactsListComponent()">
                     <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-6">Друзья онлайн</h3>
                     <div class="flex-1 overflow-y-auto space-y-3 scrollbar-hide">
                         <template x-for="c in contacts.filter(u => $store.online.has(u.id))" :key="c.id">
                             <div class="flex items-center justify-between group">
                                 <div class="flex items-center gap-4">
                                     <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-xs font-black" x-text="c.name[0]"></div>
                                     <span class="text-xs font-black text-white/80" x-text="c.name"></span>
                                 </div>
                                 <button @click="callPartner(c.id, c.name)" class="opacity-0 group-hover:opacity-100 transition-all text-green-500">📞</button>
                             </div>
                         </template>
                     </div>
                </div>
            </div>

            <!-- ЦЕНТРАЛЬНАЯ ПАНЕЛЬ УПРАВЛЕНИЯ -->
            <div class="absolute bottom-12 left-1/2 -translate-x-1/2 z-50 flex items-center gap-4 px-8 py-5 bg-[#121212]/80 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] shadow-2xl">
                <div class="flex items-center gap-3 border-r border-white/10 pr-6">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white shadow-lg shadow-red-600/20'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all">
                        <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                    </button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white shadow-lg shadow-red-600/20'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all">
                        <span x-text="camEnabled ? '📷' : '🚫'"></span>
                    </button>
                    <button @click="showSettings = !showSettings" class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center text-xl hover:bg-white/10 transition-all">⚙️</button>
                </div>

                <div class="px-2">
                    <template x-if="state === 'idle'">
                        <button @click="startSearch()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-12 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-indigo-500/30 transition-all active:scale-95">Начать поиск</button>
                    </template>
                    <template x-if="state === 'searching'">
                        <button @click="stopSearch()" class="bg-white/5 hover:bg-white/10 text-white px-12 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] border border-white/10 transition-all">Отмена</button>
                    </template>
                    <template x-if="state === 'connected'">
                        <div class="flex items-center gap-3">
                            <button @click="addPartnerToContacts()" :disabled="isPartnerFriend" :class="isPartnerFriend ? 'bg-green-500/20 text-green-500 border-green-500/20' : 'bg-white/5 text-gray-400 border-white/10'" class="w-14 h-14 rounded-2xl border transition-all flex items-center justify-center text-lg">
                                <span x-text="isPartnerFriend ? '✅' : '⭐️'"></span>
                            </button>
                            <button @click="reportPartner()" class="w-14 h-14 bg-red-600/10 border border-red-600/20 text-red-500 rounded-2xl flex items-center justify-center text-lg hover:bg-red-600 hover:text-white transition-all shadow-inner">🚩</button>
                            <button @click="startSearch()" class="bg-white text-black px-12 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] shadow-2xl transition-all active:scale-95">Следующий ➔</button>
                            <button @click="stopSearch()" class="w-14 h-14 bg-red-600/10 border border-red-600/20 text-red-500 rounded-2xl flex items-center justify-center text-lg hover:bg-red-600 hover:text-white transition-all">❌</button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ОКНО НАСТРОЕК -->
            <div x-show="showSettings" x-transition x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-md" @click.self="showSettings = false">
                <div class="bg-[#0a0a0a] border border-white/10 p-12 rounded-[3.5rem] w-[450px] shadow-2xl">
                    <h4 class="text-2xl font-black mb-10 tracking-tighter text-center">Устройства</h4>
                    <div class="space-y-8">
                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-3 ml-1">Камера</label>
                            <select x-model="selectedVideoId" @change="changeDevice()" class="w-full bg-white/5 border-white/10 rounded-2xl py-5 px-6 text-sm font-bold text-white appearance-none">
                                <template x-for="d in devices.video"><option :value="d.deviceId" x-text="d.label"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-3 ml-1">Микрофон</label>
                            <select x-model="selectedAudioId" @change="changeDevice()" class="w-full bg-white/5 border-white/10 rounded-2xl py-5 px-6 text-sm font-bold text-white appearance-none">
                                <template x-for="d in devices.audio"><option :value="d.deviceId" x-text="d.label"></option></template>
                            </select>
                        </div>
                    </div>
                    <button @click="showSettings = false" class="w-full mt-12 bg-white text-black py-5 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">Готово</button>
                </div>
            </div>
        </div>

        <!-- МОДАЛКА ВХОДЯЩЕГО ЗВОНКА -->
        <div x-show="incomingCall.show" x-transition x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl">
            <div class="bg-[#111] border border-white/10 w-full max-w-sm rounded-[3rem] p-10 text-center shadow-2xl">
                <div class="w-24 h-24 bg-indigo-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-8 animate-bounce">📞</div>
                <h3 class="text-2xl font-black text-white mb-2" x-text="incomingCall.callerName"></h3>
                <p class="text-gray-500 text-[10px] font-black uppercase tracking-[0.3em] mb-10">Входящий видеовызов...</p>
                <div class="flex gap-4">
                    <button @click="acceptCall()" class="flex-1 bg-green-600 hover:bg-green-500 text-white py-5 rounded-2xl font-black text-xs transition-all active:scale-95 shadow-xl shadow-green-500/20 uppercase tracking-widest">Принять</button>
                    <button @click="declineCall()" class="flex-1 bg-white/5 hover:bg-white/10 text-white py-5 rounded-2xl font-black text-xs border border-white/10 transition-all uppercase tracking-widest">Отказ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')), iceCandidatePoolSize: 10 };

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
                    [this.message, this.call].forEach(a => { 
                        a.muted = true; a.play().then(() => { a.pause(); a.muted = false; a.currentTime = 0; }).catch(()=>{}); 
                    }); 
                    this.isUnlocked = true; 
                },
                playMsg() { this.message.currentTime = 0; this.message.play().catch(()=>{}); },
                playCall() { this.call.loop = true; this.call.play().catch(()=>{}); },
                stopCall() { this.call.pause(); this.call.currentTime = 0; }
            });
        });

        window.chatApp = function() {
            return { 
                incomingCall: { show: false, callerId: null, callerName: '' },
                init() { 
                    window.Echo.join('online-status')
                        .here(u => Alpine.store('online').set(u))
                        .joining(u => Alpine.store('online').add(u.id))
                        .leaving(u => Alpine.store('online').remove(u.id)); 

                    window.Echo.private(`user.${ {{ auth()->id() }} }`)
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
                    const cname = this.incomingCall.callerName;
                    this.incomingCall.show = false;
                    Alpine.store('sounds').stopCall();
                    window.dispatchEvent(new CustomEvent('initiate-direct-call', { detail: { id: cid, name: cname } }));
                    window.axios.post('/chat/signal', { partnerId: cid, data: { type: 'peer-ready', from: {{ auth()->id() }} } });
                },
                declineCall() {
                    Alpine.store('sounds').stopCall();
                    window.axios.post('/chat/signal', { partnerId: this.incomingCall.callerId, data: { type: 'hang-up', from: {{ auth()->id() }} } });
                    this.incomingCall.show = false;
                }
            }
        };

        window.videoChatComponent = function(myId) {
            return {
                state: 'idle', statusHtml: 'Готов', isInCall: false, partnerId: null, isPartnerFriend: false, isDirectCall: false,
                pc: null, dc: null, localStream: null, iceQueue: [], 
                micEnabled: true, camEnabled: true, selectedVideoId: localStorage.getItem('selectedVideoId') || '', selectedAudioId: localStorage.getItem('selectedAudioId') || '', 
                devices: { video: [], audio: [] }, showSettings: false,
                isBlurred: false, rouletteMessages: [], rouletteInput: '', isPartnerTyping: false, typingTimeout: null, lastTypingSent: 0,
                
                // Stability fixes
                connectionTimeout: null,
                isReconnecting: false,

                async init() {
                    await this.initMedia(); 
                    await this.updateDevicesList();

                    window.addEventListener('beforeunload', () => {
                        if (this.partnerId) this.signal({ type: 'hang-up', reason: 'closed_tab' });
                    });

                    window.Echo.private(`user.${myId}`)
                        .listen('.MatchFoundEvent', (e) => this.onMatchFound(e))
                        .listen('.WebRTCSignalEvent', (e) => this.onSignal(e));
                },

                monitorConnection() {
                    if (!this.pc) return;
                    this.pc.oniceconnectionstatechange = () => {
                        const s = this.pc.iceConnectionState;
                        if (s === 'disconnected') {
                            this.isReconnecting = true;
                            this.statusHtml = '<span class="text-amber-500">Связь потеряна...</span>';
                            this.connectionTimeout = setTimeout(() => {
                                if (this.pc && this.pc.iceConnectionState === 'disconnected') this.resetConnection();
                            }, 8000);
                        }
                        if (s === 'connected' || s === 'completed') {
                            this.isReconnecting = false;
                            this.statusHtml = 'Live';
                            if (this.connectionTimeout) clearTimeout(this.connectionTimeout);
                        }
                        if (s === 'failed') this.resetConnection();
                    };
                },

                async onMatchFound(e) { 
                    if (this.state !== 'searching') return;
                    this.partnerId = Number(e.partnerId); this.isPartnerFriend = !!e.isFriend;
                    this.state = 'connected'; this.isBlurred = true; this.iceQueue = [];
                    this.statusHtml = 'Партнер найден';
                    this.initPC();
                    if (myId > this.partnerId) setTimeout(() => this.sendOffer(), 800);
                },

                async onSignal(e) {
                    const msg = e.data;
                    if (msg.type !== 'incoming-direct-call' && Number(msg.from) !== this.partnerId) return;

                    try {
                        if (msg.type === 'peer-skipped') {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Собеседник переключился на следующего', type: 'info' } }));
                            this.resetConnection();
                            setTimeout(() => this.startSearch(), 1000);
                            return;
                        }
                        if (msg.type === 'peer-ready') { this.statusHtml = 'Соединение...'; this.sendOffer(); }
                        if (msg.type === 'webrtc-offer') { 
                            this.initPC(); 
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'offer', sdp: this.fixSdp(msg.sdpString)})); 
                            const a = await this.pc.createAnswer(); 
                            await this.pc.setLocalDescription(a); 
                            this.signal({ type: 'webrtc-answer', sdpString: a.sdp }); 
                            this.processIceQueue(); 
                        }
                        if (msg.type === 'webrtc-answer') { 
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'answer', sdp: this.fixSdp(msg.sdpString)})); 
                            this.processIceQueue(); 
                        }
                        if (msg.type === 'ice-candidate') {
                            const cand = new RTCIceCandidate(msg.candidate);
                            if (this.pc && this.pc.remoteDescription) await this.pc.addIceCandidate(cand).catch(()=>{});
                            else this.iceQueue.push(cand);
                        }
                        if (msg.type === 'roulette-text-msg') { this.rouletteMessages.push({ isMe: false, text: msg.text }); this.isPartnerTyping = false; Alpine.store('sounds').playMsg(); this.scrollChat(); }
                        if (msg.type === 'hang-up') this.resetConnection();
                    } catch(err) { console.error(err); }
                },

                processIceQueue() {
                    if (!this.pc) return;
                    while (this.iceQueue.length > 0) this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
                },

                initPC() {
                    if (this.pc) return;
                    this.pc = new RTCPeerConnection(window.rtcConfig);
                    this.monitorConnection();
                    this.pc.ontrack = (event) => {
                        if (this.$refs.remoteVideo) {
                            this.$refs.remoteVideo.srcObject = event.streams[0];
                            this.isInCall = true; 
                            this.statusHtml = 'Live';
                        }
                    };
                    if (this.localStream) this.localStream.getTracks().forEach(track => this.pc.addTrack(track, this.localStream));
                    this.pc.onicecandidate = (e) => { if (e.candidate && this.partnerId) this.signal({ type: 'ice-candidate', candidate: e.candidate }); };
                    if (myId > this.partnerId || this.isDirectCall) this.setupDataChannel(this.pc.createDataChannel("roulette-chat")); 
                    this.pc.ondatachannel = (e) => this.setupDataChannel(e.channel);
                },

                async sendOffer() { 
                    if(!this.pc) this.initPC(); 
                    const o = await this.pc.createOffer(); 
                    await this.pc.setLocalDescription(o); 
                    this.signal({ type: 'webrtc-offer', sdpString: o.sdp }); 
                },

                setupDataChannel(ch) { 
                    this.dc = ch; 
                    this.dc.onmessage = (e) => { 
                        const d = JSON.parse(e.data); 
                        if (d.type === 'text') { this.rouletteMessages.push({ isMe: false, text: d.text }); this.isPartnerTyping = false; Alpine.store('sounds').playMsg(); this.scrollChat(); } 
                        if (d.type === 'typing') this.showPartnerTyping();
                    };
                },

                sendRouletteMsg() { 
                    if (!this.rouletteInput || !this.partnerId) return; 
                    if (this.dc && this.dc.readyState === 'open') this.dc.send(JSON.stringify({ type: 'text', text: this.rouletteInput }));
                    else this.signal({ type: 'roulette-text-msg', text: this.rouletteInput });
                    this.rouletteMessages.push({ isMe: true, text: this.rouletteInput }); this.rouletteInput = ''; this.scrollChat(); 
                },

                sendTypingSignal() {
                    if (!this.partnerId) return;
                    if (Date.now() - this.lastTypingSent > 2000) {
                        this.lastTypingSent = Date.now();
                        if (this.dc && this.dc.readyState === 'open') this.dc.send(JSON.stringify({ type: 'typing' }));
                        else window.axios.post('/chat/message/typing', { receiver_id: this.partnerId });
                    }
                },

                showPartnerTyping() { this.isPartnerTyping = true; if (this.typingTimeout) clearTimeout(this.typingTimeout); this.scrollChat(); this.typingTimeout = setTimeout(() => { this.isPartnerTyping = false; }, 3000); },
                scrollChat() { this.$nextTick(() => { if(this.$refs.rouletteChat) this.$refs.rouletteChat.scrollTop = 9999; }); },
                signal(d) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: d }).catch(()=>{}); },
                
                resetConnection() { 
                    if (this.connectionTimeout) clearTimeout(this.connectionTimeout);
                    this.isReconnecting = false;
                    this.partnerId = null; this.isPartnerTyping = false; this.isDirectCall = false; this.iceQueue = [];
                    if (this.pc) { this.pc.close(); this.pc = null; }
                    this.dc = null; this.isInCall = false; this.state = 'idle'; this.statusHtml = 'Готов'; this.rouletteMessages = []; 
                    if(this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
                },

                async startSearch() { 
                    if (this.partnerId && this.state === 'connected') this.signal({ type: 'peer-skipped' });
                    this.resetConnection(); 
                    this.state = 'searching'; 
                    this.statusHtml = 'ПОИСК...'; 
                    await window.axios.post('/chat/search'); 
                },

                stopSearch() { 
                    if(this.partnerId) this.signal({ type: 'hang-up' }); 
                    window.axios.post('/chat/leave'); 
                    this.resetConnection(); 
                },
                
                async initMedia() {
                    try {
                        if(this.localStream) this.localStream.getTracks().forEach(t => t.stop());
                        this.localStream = await navigator.mediaDevices.getUserMedia({ 
                            video: this.selectedVideoId ? { deviceId: { exact: this.selectedVideoId } } : { width: 1280, height: 720 }, 
                            audio: this.selectedAudioId ? { deviceId: { exact: this.selectedAudioId } } : true 
                        }); 
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch (e) { this.statusHtml = 'ОШИБКА КАМЕРЫ'; }
                },

                toggleMic() { this.micEnabled = !this.micEnabled; if (this.localStream) this.localStream.getAudioTracks().forEach(t => t.enabled = this.micEnabled); },
                toggleCam() { this.camEnabled = !this.camEnabled; if (this.localStream) this.localStream.getVideoTracks().forEach(t => t.enabled = this.camEnabled); },
                fixSdp(sd) { return sd ? sd.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ""; },
                async updateDevicesList() { const d = await navigator.mediaDevices.enumerateDevices(); this.devices.video = d.filter(x => x.kind === 'videoinput'); this.devices.audio = d.filter(x => x.kind === 'audioinput'); },
                async changeDevice() { localStorage.setItem('selectedVideoId', this.selectedVideoId); localStorage.setItem('selectedAudioId', this.selectedAudioId); await this.initMedia(); if(this.pc) this.resetConnection(); },
                
                async addPartnerToContacts() { 
                    if (!this.partnerId || this.isPartnerFriend) return; 
                    const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); 
                    if (res.data.action === 'added') { 
                        this.isPartnerFriend = true; 
                        window.dispatchEvent(new CustomEvent('contacts-updated')); 
                        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Собеседник добавлен в контакты', type: 'success' } }));
                    }
                },

                async reportPartner() {
                    if (!this.partnerId) return;
                    const res = await window.axios.post('/report', { reported_id: this.partnerId, reason: 'Inappropriate content' });
                    if (res.data.status === 'success') { 
                        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Жалоба отправлена. Ищем другого...', type: 'success' } }));
                        this.startSearch(); 
                    }
                }
            }
        };

        window.contactsListComponent = function() {
            return { 
                contacts: [], init() { this.load(); window.addEventListener('contacts-updated', () => this.load()); }, 
                async load() { const res = await window.axios.get('/chat/contacts'); this.contacts = res.data.contacts; }, 
                callPartner(id, name) { 
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: `Вызываю ${name}...`, type: 'info' } }));
                    window.dispatchEvent(new CustomEvent('initiate-direct-call', { detail: { id: id, name: name } }));
                    window.axios.post('/chat/contact/call', { contactId: id });
                } 
            }
        };
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        video { background: #000; transition: filter 0.5s ease; }
        body { background-color: #050505 !important; }
    </style>
</x-app-layout>