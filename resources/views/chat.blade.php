<x-app-layout>
    <!-- Подключаем шрифты через Google для стабильности -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <div class="py-6 bg-gray-950 min-h-screen text-gray-200" 
         x-data="window.chatApp()" 
         @click.once="$store.sounds.unlock()"
         @close-messenger.window="messengerOpen = false"
         @open-chat.window="openMessenger($event.detail.id, $event.detail.name)">
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- ЛЕВАЯ КОЛОНКА (ВИДЕО) -->
                <div class="lg:col-span-3 space-y-4" x-data="window.videoChatComponent({{ auth()->id() }})">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- ВАШЕ ВИДЕО -->
                        <div class="bg-black rounded-[2.5rem] overflow-hidden shadow-2xl h-[480px] flex items-center justify-center relative border border-white/5">
                            <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                            
                            <!-- Контейнер кнопок: подняли z-index до 30 и добавили pointer-events-auto -->
                            <div class="absolute bottom-6 right-6 flex gap-2 z-30 pointer-events-auto">
                                <button type="button" 
                                        @click.stop="showSettings = !showSettings" 
                                        class="bg-white/10 hover:bg-white/20 backdrop-blur-xl p-4 rounded-2xl border border-white/10 text-white transition-all active:scale-90">
                                    ⚙️
                                </button>
                                
                                <button type="button" 
                                        @click.stop="toggleMic()" 
                                        :class="micEnabled ? 'bg-white/10' : 'bg-red-500/80'" 
                                        class="backdrop-blur-xl p-4 rounded-2xl border border-white/10 text-white transition-all active:scale-90">
                                    <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                                </button>
                                
                                <button type="button" 
                                        @click.stop="toggleCam()" 
                                        :class="camEnabled ? 'bg-white/10' : 'bg-red-500/80'" 
                                        class="backdrop-blur-xl p-4 rounded-2xl border border-white/10 text-white transition-all active:scale-90">
                                    <span x-text="camEnabled ? '📷' : '🚫'"></span>
                                </button>
                            </div>

                            <div class="absolute bottom-6 left-6 bg-black/40 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/10 text-[10px] font-black uppercase text-white/70 tracking-widest z-20">ВЫ (LIVE)</div>

                            <!-- Окно настроек: подняли z-index -->
                            <div x-show="showSettings" x-transition x-cloak @click.away="showSettings = false"
                                class="absolute top-6 right-6 w-64 bg-gray-900/95 backdrop-blur-2xl rounded-3xl shadow-2xl p-5 z-40 border border-white/10 text-white">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-400 block mb-1 uppercase">Камера</label>
                                        <select x-model="selectedVideoId" @change="changeDevice()" class="w-full bg-black/50 border-white/10 rounded-xl text-[11px] text-white font-bold">
                                            <template x-for="d in devices.video"><option :value="d.deviceId" x-text="d.label"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-400 block mb-1 uppercase">Микрофон</label>
                                        <select x-model="selectedAudioId" @change="changeDevice()" class="w-full bg-black/50 border-white/10 rounded-xl text-[11px] text-white font-bold">
                                            <template x-for="d in devices.audio"><option :value="d.deviceId" x-text="d.label"></option></template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ВИДЕО СОБЕСЕДНИКА -->
                        <div class="bg-black rounded-[2.5rem] overflow-hidden shadow-2xl h-[480px] flex items-center justify-center relative border border-white/5">
                            <video x-ref="remoteVideo" autoplay playsinline class="w-full h-full object-cover transition-all duration-700" 
                                   :class="isBlurred ? 'blur-[80px] grayscale brightness-50' : ''"
                                   :class="!isInCall && 'opacity-0'"></video>
                            
                            <div x-show="isInCall && isBlurred" class="absolute inset-0 z-30 flex items-center justify-center">
                                <button @click="isBlurred = false" class="bg-indigo-600/80 hover:bg-indigo-600 backdrop-blur-md border border-white/20 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl">Открыть видео</button>
                            </div>

                            <div x-show="!isInCall" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-950 transition-all">
                                <template x-if="state === 'searching'"><div class="flex flex-col items-center"><div class="w-16 h-16 border-4 border-t-indigo-500 border-white/5 rounded-full animate-spin mb-6"></div><span class="text-indigo-400 font-black uppercase text-[10px] tracking-[0.3em] animate-pulse">Поиск собеседника...</span></div></template>
                                <template x-if="state === 'idle'"><div class="text-center"><div class="text-5xl mb-4 opacity-20">👋</div><span class="text-gray-600 font-black uppercase text-[10px] tracking-[0.2em]">Готов к общению</span></div></template>
                            </div>
                        </div>
                    </div>

                    <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-900/50 backdrop-blur-xl p-6 rounded-[2.5rem] border border-white/5 flex flex-col justify-between min-h-[250px]">
                            <div>
                                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Текущий статус</h3>
                                <div class="p-4 bg-black/40 rounded-2xl border border-white/5 mb-4">
                                    <div class="text-white font-black text-xs uppercase flex items-center gap-2" x-html="statusHtml"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <button x-show="state === 'idle'" @click="startSearch()" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-5 rounded-2xl font-black text-xs transition-all active:scale-95 shadow-lg shadow-indigo-500/20">НАЧАТЬ ПОИСК</button>
                                <div x-show="state === 'connected'" class="flex flex-col gap-2">
                                    <div class="flex gap-2">
                                        <!-- КНОПКА ДРУЖБЫ v1.8 -->
                                        <button @click="addPartnerToContacts()" 
                                                :disabled="isPartnerFriend"
                                                :class="isPartnerFriend ? 'bg-green-500/20 text-green-400 border-green-500/30' : 'bg-white/5 text-white border-white/10'"
                                                class="flex-1 hover:bg-white/10 py-5 rounded-2xl font-black text-[10px] uppercase border transition-all disabled:cursor-default">
                                            <span x-text="isPartnerFriend ? '✅ В друзьях' : '⭐️ Добавить'"></span>
                                        </button>
                                        <button @click="startSearch()" class="flex-[2] bg-indigo-600 hover:bg-indigo-500 text-white py-5 rounded-2xl font-black text-xs transition-all shadow-lg shadow-indigo-500/10">СЛЕДУЮЩИЙ ➔</button>
                                    </div>
                                    <button @click="stopSearch()" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-500 py-5 rounded-2xl font-black text-xs border border-red-500/20">ЗАКОНЧИТЬ</button>
                                </div>
                                <button x-show="state === 'searching'" @click="stopSearch()" class="w-full bg-white/5 text-gray-400 py-5 rounded-2xl font-black text-xs hover:bg-white/10">ОТМЕНА</button>
                            </div>
                        </div>

                        <!-- ЧАТ РУЛЕТКИ -->
                        <div x-show="state === 'connected'" class="bg-gray-900/50 backdrop-blur-xl rounded-[2.5rem] border border-white/5 md:col-span-2 flex flex-col h-[280px] overflow-hidden shadow-2xl">
                            <div class="p-4 border-b border-white/5 bg-black/20 flex justify-between items-center text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                <span>Быстрый чат</span>
                                <span x-show="dc && dc.readyState === 'open'" class="text-green-500 flex items-center gap-1.5"><span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-ping"></span> P2P активен</span>
                            </div>
                            <div class="flex-1 overflow-y-auto p-5 space-y-3 scrollbar-hide" x-ref="rouletteChat">
                                <template x-for="msg in rouletteMessages">
                                    <div :class="msg.isMe ? 'bg-indigo-600 text-white ml-auto rounded-l-2xl rounded-tr-2xl' : 'bg-white/10 text-gray-200 mr-auto rounded-r-2xl rounded-tl-2xl'" class="p-4 text-xs font-bold max-w-[80%] shadow-sm" x-text="msg.text"></div>
                                </template>

                                <!-- ИНДИКАТОР ПЕЧАТАЕТ v1.8 -->
                                <div x-show="isPartnerTyping" x-transition class="flex items-center gap-2 p-2">
                                     <div class="flex gap-1">
                                         <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce"></span>
                                         <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                                         <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                                     </div>
                                     <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">Собеседник печатает...</span>
                                </div>
                            </div>
                            <div class="p-3 bg-black/40 flex gap-2">
                                <input type="text" x-model="rouletteInput" @input="sendTypingSignal()" @keyup.enter="sendRouletteMsg()" placeholder="Напишите что-нибудь..." class="flex-1 bg-white/5 border-none rounded-xl px-5 text-xs text-white focus:ring-1 focus:ring-indigo-500">
                                <button @click="sendRouletteMsg()" class="bg-indigo-600 text-white p-3.5 rounded-xl hover:bg-indigo-500 transition-all">➔</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- КОНТАКТЫ -->
                <div class="bg-gray-900/50 backdrop-blur-xl p-6 rounded-[2.5rem] border border-white/5 flex flex-col h-[750px]" x-data="window.contactsListComponent()">
                    <h2 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.3em] mb-8 ml-2">Ваши друзья</h2>
                    <div class="flex-1 overflow-y-auto space-y-3 scrollbar-hide">
                        <template x-for="c in contacts" :key="c.id">
                            <div @click="$dispatch('open-chat', {id: c.id, name: c.name})" class="p-4 bg-white/5 rounded-[2rem] border border-transparent flex items-center justify-between hover:bg-white/10 transition-all cursor-pointer group">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-indigo-600/20 rounded-2xl flex items-center justify-center text-sm font-black text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white transition-all" x-text="c.name[0]"></div>
                                    <div>
                                        <div class="text-xs font-black text-white" x-text="c.name"></div>
                                        <div class="text-[9px] uppercase mt-0.5 font-bold">
                                            <template x-if="$store.online.has(c.id)"><span class="text-green-500">● Online</span></template>
                                            <template x-if="!$store.online.has(c.id)"><span class="text-gray-500">Offline</span></template>
                                        </div>
                                    </div>
                                </div>
                                <button x-show="$store.online.has(c.id)" @click.stop="callPartner(c.id, c.name)" class="p-3 text-green-500 opacity-0 group-hover:opacity-100 transition-all hover:scale-110">📞</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Конфиг WebRTC
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
                        a.muted = true; a.play().then(() => { a.pause(); a.muted = false; a.currentTime = 0; }); 
                    }); 
                    this.isUnlocked = true; 
                    console.log("Audio Unlocked");
                },
                playMsg() { this.message.currentTime = 0; this.message.play().catch(()=>{}); },
                playCall() { this.call.loop = true; this.call.play().catch(()=>{}); },
                stopCall() { this.call.pause(); this.call.currentTime = 0; }
            });
        });

        window.chatApp = function() {
            return { 
                messengerOpen: false, 
                init() { 
                    window.Echo.join('online-status')
                        .here(u => Alpine.store('online').set(u))
                        .joining(u => Alpine.store('online').add(u.id))
                        .leaving(u => Alpine.store('online').remove(u.id)); 
                }, 
                openMessenger(id, name) { this.messengerOpen = true; this.$dispatch('load-chat-history', {id, name}); } 
            }
        };

window.videoChatComponent = function(myId) {
            return {
                // СОСТОЯНИЕ
                state: 'idle', 
                statusHtml: 'Готов',
                isInCall: false,
                partnerId: null,
                isPartnerFriend: false, 
                isDirectCall: false,
                
                // WebRTC
                pc: null, 
                dc: null, 
                localStream: null, 
                iceQueue: [], 
                
                // МЕДИА
                micEnabled: true, 
                camEnabled: true, 
                selectedVideoId: localStorage.getItem('selectedVideoId') || '', 
                selectedAudioId: localStorage.getItem('selectedAudioId') || '', 
                devices: { video: [], audio: [] },
                showSettings: false,
                
                // ИНДИКАТОРЫ И ЧАТ
                isBlurred: false, 
                autoBlurTimer: null,
                rouletteMessages: [], 
                rouletteInput: '',
                isPartnerTyping: false,
                typingTimeout: null,
                lastTypingSent: 0,

                async init() {
                    await this.initMedia(); 
                    await this.updateDevicesList();
                    
                    window.Echo.private(`user.${myId}`)
                        .listen('.MatchFoundEvent', (e) => this.onMatchFound(e))
                        .listen('.WebRTCSignalEvent', (e) => this.onSignal(e))
                        .listen('.UserTypingEvent', (e) => {
                            if (Number(this.partnerId) === Number(e.senderId)) {
                                this.showPartnerTyping();
                            }
                        });
                    
                    window.addEventListener('initiate-direct-call', (e) => {
                        this.resetConnection();
                        this.partnerId = Number(e.detail.id);
                        this.isPartnerFriend = true;
                        this.state = 'connected';
                        this.isDirectCall = true;
                        this.statusHtml = `Звонок: ${e.detail.name}`;
                        this.initPC();
                    });

                    window.addEventListener('beforeunload', () => this.stopSearch());
                },

                // УПРАВЛЕНИЕ КАМЕРОЙ И МИКРОФОНОМ
                toggleMic() {
                    this.micEnabled = !this.micEnabled;
                    if (this.localStream) {
                        this.localStream.getAudioTracks().forEach(track => {
                            track.enabled = this.micEnabled;
                        });
                    }
                },

                toggleCam() {
                    this.camEnabled = !this.camEnabled;
                    if (this.localStream) {
                        this.localStream.getVideoTracks().forEach(track => {
                            track.enabled = this.camEnabled;
                        });
                    }
                },

                // ЛОГИКА ТИПИНГА (ПЕЧАТАЕТ...)
                showPartnerTyping() {
                    this.isPartnerTyping = true;
                    if (this.typingTimeout) clearTimeout(this.typingTimeout);
                    this.$nextTick(() => { if(this.$refs.rouletteChat) this.$refs.rouletteChat.scrollTop = 9999; });
                    this.typingTimeout = setTimeout(() => { this.isPartnerTyping = false; }, 3000);
                },

                sendTypingSignal() {
                    if (!this.partnerId) return;
                    let now = Date.now();
                    if (now - this.lastTypingSent > 2000) {
                        this.lastTypingSent = now;
                        if (this.dc && this.dc.readyState === 'open') {
                            this.dc.send(JSON.stringify({ type: 'typing' }));
                        } else {
                            window.axios.post('/chat/message/typing', { receiver_id: this.partnerId });
                        }
                    }
                },

                // WebRTC ЛОГИКА
                async onMatchFound(e) { 
                    if (this.state !== 'searching') return;
                    this.partnerId = Number(e.partnerId); 
                    this.isPartnerFriend = !!e.isFriend;
                    this.state = 'connected'; 
                    this.isBlurred = true; 
                    this.iceQueue = [];
                    if(this.autoBlurTimer) clearTimeout(this.autoBlurTimer);
                    this.autoBlurTimer = setTimeout(() => { this.isBlurred = false; }, 3000); 
                    this.initPC();
                    if (myId > this.partnerId) {
                        setTimeout(() => this.sendOffer(), 500);
                    }
                },

                async onSignal(e) {
                    const msg = e.data;
                    if (Number(msg.from) !== this.partnerId && msg.type !== 'incoming-direct-call') return;

                    try {
                        if (msg.type === 'peer-ready') this.sendOffer();
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
                            const candidate = new RTCIceCandidate(msg.candidate);
                            if (this.pc && this.pc.remoteDescription) await this.pc.addIceCandidate(candidate).catch(e => {});
                            else this.iceQueue.push(candidate);
                        }
                        if (msg.type === 'roulette-text-msg') { 
                            this.rouletteMessages.push({ isMe: false, text: msg.text }); 
                            this.isPartnerTyping = false;
                            Alpine.store('sounds').playMsg(); 
                            this.$nextTick(() => { if(this.$refs.rouletteChat) this.$refs.rouletteChat.scrollTop = 9999; }); 
                        }
                        if (msg.type === 'typing') this.showPartnerTyping();
                    } catch(err) { console.error(err); }
                },

                processIceQueue() {
                    if (!this.pc) return;
                    while (this.iceQueue.length > 0) {
                        this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
                    }
                },

                async initMedia() {
                    try {
                        if(this.localStream) this.localStream.getTracks().forEach(t => t.stop());
                        this.localStream = await navigator.mediaDevices.getUserMedia({ 
                            video: this.selectedVideoId ? { deviceId: { exact: this.selectedVideoId } } : { width: 640, height: 480 }, 
                            audio: this.selectedAudioId ? { deviceId: { exact: this.selectedAudioId } } : true 
                        }); 
                        this.$refs.localVideo.srcObject = this.localStream;
                        // Применяем текущие настройки к новому потоку
                        this.localStream.getAudioTracks().forEach(t => t.enabled = this.micEnabled);
                        this.localStream.getVideoTracks().forEach(t => t.enabled = this.camEnabled);
                    } catch (e) { 
                        this.statusHtml = '<span class="text-red-500 font-bold">ОШИБКА КАМЕРЫ</span>';
                    }
                },

                initPC() {
                    if (this.pc) return;
                    this.pc = new RTCPeerConnection(window.rtcConfig);
                    this.pc.ontrack = (event) => {
                        if (this.$refs.remoteVideo && this.$refs.remoteVideo.srcObject !== event.streams[0]) {
                            this.$refs.remoteVideo.srcObject = event.streams[0];
                        }
                        this.isInCall = true;
                        this.statusHtml = '<span class="text-green-500 font-bold">● В ЭФИРЕ</span>';
                    };
                    if (this.localStream) {
                        this.localStream.getTracks().forEach(track => this.pc.addTrack(track, this.localStream));
                    }
                    this.pc.onicecandidate = (e) => { 
                        if (e.candidate && this.partnerId) this.signal({ type: 'ice-candidate', candidate: e.candidate }); 
                    };
                    this.pc.oniceconnectionstatechange = () => {
                        if (['failed', 'disconnected'].includes(this.pc.iceConnectionState)) {
                            if (this.partnerId && !this.isDirectCall) { this.resetConnection(); this.startSearch(); }
                        }
                    };
                    if (myId > this.partnerId) {
                        this.setupDataChannel(this.pc.createDataChannel("roulette-chat"));
                    } else {
                        this.pc.ondatachannel = (e) => this.setupDataChannel(e.channel);
                    }
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
                        if (d.type === 'text') { 
                            this.rouletteMessages.push({ isMe: false, text: d.text }); 
                            this.isPartnerTyping = false;
                            Alpine.store('sounds').playMsg(); 
                            this.$nextTick(() => { if(this.$refs.rouletteChat) this.$refs.rouletteChat.scrollTop = 9999; }); 
                        } 
                        if (d.type === 'typing') this.showPartnerTyping();
                    };
                },

                sendRouletteMsg() { 
                    if (!this.rouletteInput || !this.partnerId) return; 
                    if (this.dc && this.dc.readyState === 'open') {
                        this.dc.send(JSON.stringify({ type: 'text', text: this.rouletteInput }));
                    } else {
                        this.signal({ type: 'roulette-text-msg', text: this.rouletteInput });
                    }
                    this.rouletteMessages.push({ isMe: true, text: this.rouletteInput }); 
                    this.rouletteInput = ''; 
                    this.isPartnerTyping = false;
                    this.$nextTick(() => { if(this.$refs.rouletteChat) this.$refs.rouletteChat.scrollTop = 9999; }); 
                },

                signal(d) { 
                    if (!this.partnerId) return;
                    window.axios.post('/chat/signal', { partnerId: this.partnerId, data: d }).catch(e => {}); 
                },

                resetConnection() { 
                    this.partnerId = null; this.iceQueue = []; this.isPartnerFriend = false; this.isPartnerTyping = false;
                    if (this.pc) { this.pc.onicecandidate = null; this.pc.ontrack = null; this.pc.close(); this.pc = null; }
                    this.dc = null; this.isInCall = false; this.state = 'idle'; this.statusHtml = 'Готов'; this.rouletteMessages = []; 
                    if(this.$refs.remoteVideo) { this.$refs.remoteVideo.pause(); this.$refs.remoteVideo.srcObject = null; }
                },

                async startSearch() { 
                    if (this.state === 'searching') return;
                    this.resetConnection(); this.state = 'searching'; 
                    this.statusHtml = '<span class="animate-pulse">ПОИСК...</span>'; 
                    await window.axios.post('/chat/search'); 
                },

                stopSearch() { 
                    if(this.partnerId) this.signal({ type: 'hang-up' }); 
                    window.axios.post('/chat/leave'); this.resetConnection(); 
                },

                fixSdp(sd) { return sd ? sd.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ""; },
                async updateDevicesList() { const d = await navigator.mediaDevices.enumerateDevices(); this.devices.video = d.filter(x => x.kind === 'videoinput'); this.devices.audio = d.filter(x => x.kind === 'audioinput'); },
                async changeDevice() { localStorage.setItem('selectedVideoId', this.selectedVideoId); localStorage.setItem('selectedAudioId', this.selectedAudioId); await this.initMedia(); if(this.pc) this.resetConnection(); },
                async addPartnerToContacts() { 
                    if (!this.partnerId || this.isPartnerFriend) return; 
                    const res = await window.axios.post('/chat/contact/toggle', { contactId: this.partnerId }); 
                    if (res.data.action === 'added') { this.isPartnerFriend = true; window.dispatchEvent(new CustomEvent('contacts-updated')); }
                }
            }
        };

        window.contactsListComponent = function() {
            return { 
                contacts: [], 
                init() { 
                    this.load(); 
                    window.addEventListener('contacts-updated', () => this.load()); 
                }, 
                async load() { 
                    const res = await window.axios.get('/chat/contacts'); 
                    this.contacts = res.data.contacts; 
                }, 
                callPartner(id, name) { 
                    if(confirm(`Позвонить ${name}?`)) {
                        window.dispatchEvent(new CustomEvent('initiate-direct-call', { 
                            detail: { id: id, name: name } 
                        }));
                        window.axios.post('/chat/contact/call', { contactId: id });
                    }
                } 
            }
        };
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        video { background: #000; image-rendering: -webkit-optimize-contrast; object-fit: cover; }
        body { font-family: 'Figtree', sans-serif; }
    </style>
</x-app-layout>