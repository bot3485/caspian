<x-app-layout>
    <div class="h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans" 
         x-data="window.videoChatApp({{ auth()->id() }})" 
         @click.once="unlockSounds()" @mousemove.once="unlockSounds()">
        
        <!-- ДЕКОРАТИВНЫЕ СВЕЧЕНИЯ -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <div class="relative h-full flex flex-col lg:flex-row">
            
            <!-- ЗОНА ВИДЕО -->
            <div class="flex-1 relative bg-black overflow-hidden">
                <video x-ref="remoteVideo" autoplay playsinline 
                       class="w-full h-full object-cover transition-all duration-1000" 
                       :class="isBlurred ? 'blur-[80px] scale-110 opacity-30' : 'opacity-100'"></video>
                
                <div x-show="isReconnecting" class="absolute inset-0 z-50 bg-black/60 backdrop-blur-md flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <p class="text-indigo-400 font-black uppercase text-[10px] tracking-[0.3em]">Восстановление связи...</p>
                </div>

                <!-- СОСТОЯНИЯ -->
                <div x-show="!isInCall" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-20">
                    <template x-if="state === 'searching'">
                        <div class="flex flex-col items-center">
                            <div class="w-32 h-32 border-2 border-indigo-500/10 rounded-full animate-ping mb-10 flex items-center justify-center text-4xl">📡</div>
                            <h3 class="text-white font-black uppercase text-[11px] tracking-[0.5em] animate-pulse">Установка соединения...</h3>
                        </div>
                    </template>
                    <template x-if="state === 'idle'">
                        <div class="text-center opacity-40">
                            <span class="text-6xl block mb-6">🌊</span>
                            <span class="font-black uppercase text-xs tracking-[0.3em]">Caspian Roulette</span>
                        </div>
                    </template>
                </div>

                <!-- Кнопка размытия -->
                <div x-show="isInCall && isBlurred" class="absolute inset-0 z-30 flex items-center justify-center bg-black/40 backdrop-blur-md">
                    <button @click="isBlurred = false" class="bg-indigo-600 text-white px-12 py-5 rounded-2xl font-black text-xs uppercase tracking-widest shadow-2xl hover:scale-105 transition-all">Открыть камеру</button>
                </div>

                <!-- PIP (Ваше видео) -->
                <div x-show="showSelfVideo" class="absolute bottom-10 left-10 w-48 md:w-64 aspect-video bg-[#111] rounded-3xl overflow-hidden shadow-2xl border border-white/10 z-40 transition-all">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                </div>
            </div>

            <!-- ПРАВАЯ ПАНЕЛЬ -->
            <div class="w-full lg:w-[400px] flex flex-col bg-[#080808] border-l border-white/5 relative z-10" x-data="{ tab: 'chat' }">
                <div class="flex border-b border-white/5 bg-[#0a0a0a]">
                    <button @click="tab = 'chat'" :class="tab === 'chat' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-6 text-[10px] font-black uppercase tracking-widest transition-all">Чат</button>
                    <button @click="tab = 'friends'" :class="tab === 'friends' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-6 text-[10px] font-black uppercase tracking-widest transition-all">Друзья</button>
                </div>
                
                <div x-show="tab === 'chat'" class="flex-1 flex flex-col overflow-hidden">
                    <!-- Заголовок с индикатором печати -->
                    <div class="px-8 py-4 border-b border-white/5 bg-[#0a0a0a]/50 flex justify-between items-center h-12">
                        <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Сообщения</span>
                        <div x-show="isPartnerTyping" class="flex gap-1 items-center" x-cloak>
                            <span class="text-[9px] font-black text-indigo-500 uppercase tracking-widest mr-1">Печатает</span>
                            <div class="w-1 h-1 bg-indigo-500 rounded-full animate-bounce"></div>
                            <div class="w-1 h-1 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8 space-y-4 scrollbar-hide" x-ref="chatBox">
                        <template x-for="msg in messages">
                            <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col">
                                <div :class="msg.isMe ? 'bg-indigo-600 rounded-2xl rounded-tr-none' : 'bg-white/5 rounded-2xl rounded-tl-none border border-white/5'" 
                                     class="p-4 text-[13px] font-medium max-w-[85%] break-words shadow-lg" x-text="msg.text"></div>
                            </div>
                        </template>
                    </div>

                    <div class="p-6 border-t border-white/5 bg-[#0a0a0a]">
                        <div class="flex gap-3 bg-black/40 p-2.5 rounded-2xl border border-white/10 focus-within:border-indigo-500/50 transition-all">
                            <input type="text" x-model="chatInput" 
                                   @input="sendTyping()" 
                                   @keyup.enter="sendMsg()" 
                                   placeholder="Написать..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 px-4 text-white">
                            <button @click="sendMsg()" class="bg-white text-black w-12 h-12 rounded-xl flex items-center justify-center hover:bg-indigo-500 hover:text-white transition-all shadow-lg">➔</button>
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'friends'" class="flex-1 overflow-y-auto p-6 space-y-3 scrollbar-hide">
                    <template x-for="friend in friendsList" :key="friend.id">
                        <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between group hover:border-indigo-500/30 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center font-black" x-text="friend.name[0]"></div>
                                <span class="text-xs font-bold" x-text="friend.name"></span>
                            </div>
                            <button @click="callFriend(friend.id)" class="w-10 h-10 bg-indigo-500 text-white rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">📞</button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- УПРАВЛЕНИЕ -->
            <div class="absolute bottom-10 left-1/2 -translate-x-1/2 lg:left-[calc(50%-200px)] z-[100] flex items-center gap-3 px-6 py-4 bg-[#121212]/90 backdrop-blur-3xl border border-white/10 rounded-3xl shadow-2xl">
                <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-xl flex items-center justify-center text-lg transition-all active:scale-90">
                    <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                </button>
                <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-xl flex items-center justify-center text-lg transition-all active:scale-90">
                    <span x-text="camEnabled ? '📷' : '🚫'"></span>
                </button>
                <button @click="showSelfVideo = !showSelfVideo" class="w-12 h-12 rounded-xl flex items-center justify-center text-lg transition-all" :class="showSelfVideo ? 'bg-white/5' : 'bg-indigo-600'">
                    <span x-text="showSelfVideo ? '🖼️' : '👤'"></span>
                </button>

                <div class="w-px h-8 bg-white/10 mx-2"></div>

                <template x-if="state === 'idle'">
                    <button @click="startSearch()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl transition-all active:scale-95">Начать поиск</button>
                </template>
                
                <template x-if="state === 'searching'">
                    <button @click="stopSearch()" class="bg-white/10 text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest border border-white/10 transition-all hover:bg-white/20">Отмена</button>
                </template>
                
                <template x-if="state === 'connected'">
                    <div class="flex gap-2">
                        <button @click="report()" class="w-12 h-12 bg-red-600/10 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all">🚩</button>
                        
                        <button @click="addContact()" :class="isFriend ? 'text-green-500 bg-green-500/10 border-green-500/20' : 'text-gray-400 bg-white/5 border-white/10'" class="w-12 h-12 rounded-xl border flex items-center justify-center transition-all">
                            <span x-text="isFriend ? '✅' : '⭐'"></span>
                        </button>

                        <button @click="stopSearch()" class="bg-red-600/20 text-red-500 px-6 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all">Стоп</button>
                        <button @click="startSearch()" class="bg-white text-black px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all active:scale-95">Следующий ➔</button>
                    </div>
                </template>
            </div>

            <!-- ВХОДЯЩИЙ ЗВОНОК -->
            <div x-show="incomingCall" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/90 backdrop-blur-xl" x-cloak x-transition>
                <div class="bg-[#0a0a0a] border border-white/10 p-12 rounded-[3rem] text-center max-w-sm w-full shadow-2xl">
                    <div class="w-24 h-24 bg-indigo-600 rounded-[2rem] flex items-center justify-center text-4xl mx-auto mb-8 animate-bounce">📞</div>
                    <h2 class="text-2xl font-black mb-2 uppercase tracking-tighter" x-text="incomingCall?.fromName"></h2>
                    <p class="text-gray-500 text-[10px] font-bold uppercase tracking-[0.2em] mb-10">Входящий видеозвонок...</p>
                    <div class="flex gap-3">
                        <button @click="acceptCall()" class="flex-1 bg-green-600 text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl">Принять</button>
                        <button @click="rejectCall()" class="flex-1 bg-white/5 text-gray-500 py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest">Отклонить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')), iceCandidatePoolSize: 10 };

        window.videoChatApp = function(myId) {
            return {
                state: 'idle', isInCall: false, partnerId: null, isFriend: false,
                pc: null, dc: null, localStream: null, iceQueue: [],
                micEnabled: true, camEnabled: true, isBlurred: false, isReconnecting: false,
                showSelfVideo: true, incomingCall: null, friendsList: [],
                messages: [], chatInput: '', connectionTimeout: null,
                isPartnerTyping: false, typingTimeout: null, lastTypingSent: 0,
                msgSound: new Audio('/sounds/message.mp3'),
                ringtone: new Audio('/sounds/call.mp3'), 
                soundsUnlocked: false,

                async init() {
                    window.Echo.private(`user.${myId}`)
                        .listen('.MatchFoundEvent', (e) => this.handleMatch(e))
                        .listen('.WebRTCSignalEvent', (e) => this.handleSignal(e));
                    await this.initMedia();
                    this.loadFriends();
                },

                async loadFriends() {
                    try {
                        const res = await window.axios.get('/chat/contacts');
                        this.friendsList = res.data.contacts;
                    } catch(e) {}
                },

                async handleMatch(e) {
                    if (this.state !== 'searching' && !this.incomingCall) return;
                    this.partnerId = Number(e.partnerId);
                    this.isFriend = !!e.isFriend;
                    this.state = 'connected';
                    this.isBlurred = true;
                    this.initPC();
                    if (myId > this.partnerId) setTimeout(() => this.sendOffer(), 1000);

                    if (this.connectionTimeout) clearTimeout(this.connectionTimeout);
                    this.connectionTimeout = setTimeout(() => {
                        if (!this.isInCall && this.state === 'connected') {
                            this.startSearch();
                        }
                    }, 15000);
                },

                async handleSignal(e) {
                    const msg = e.data;
                    if (msg.type === 'incoming-call') { 
                        this.incomingCall = msg; 
                        this.playRingtone();
                        return; 
                    }
                    
                    if ((msg.type === 'offer' || msg.type === 'answer') && this.state === 'searching') {
                        this.partnerId = Number(msg.from);
                        this.state = 'connected';
                        this.isBlurred = true;
                    }

                    if (Number(msg.from) !== this.partnerId) return;

                    if (['peer-skipped', 'peer-disconnected', 'hang-up'].includes(msg.type)) {
                        this.reset();
                        if (msg.type === 'peer-skipped') this.startSearch();
                        return;
                    }

                    // Обработка сигнала "Печатает"
                    if (msg.type === 'typing') this.showTypingIndicator();

                    try {
                        if (msg.type === 'offer') {
                            this.initPC();
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type:'offer', sdp: this.sanitizeSdp(msg.sdp.sdp)}));
                            const ans = await this.pc.createAnswer();
                            await this.pc.setLocalDescription(ans);
                            this.signal({type:'answer', sdp: ans});
                            this.drainIce();
                        } else if (msg.type === 'answer') {
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type:'answer', sdp: this.sanitizeSdp(msg.sdp.sdp)}));
                            this.drainIce();
                        } else if (msg.type === 'ice') {
                            const cand = new RTCIceCandidate(msg.candidate);
                            if (this.pc && this.pc.remoteDescription) await this.pc.addIceCandidate(cand).catch(()=>{});
                            else this.iceQueue.push(cand);
                        } else if (msg.type === 'text') {
                            this.messages.push({isMe:false, text: msg.text});
                            this.playMsgSound();
                            this.scrollChat();
                        }
                    } catch(e) { console.error("WebRTC Error:", e); }
                },

                initPC() {
                    if (this.pc) return;
                    this.pc = new RTCPeerConnection(window.rtcConfig);
                    this.pc.oniceconnectionstatechange = () => {
                        this.isReconnecting = (this.pc.iceConnectionState === 'disconnected');
                        if (this.pc.iceConnectionState === 'failed') this.reset();
                    };
                    this.pc.onicecandidate = (e) => { if(e.candidate) this.signal({type:'ice', candidate: e.candidate}); };
                    this.pc.ontrack = (e) => { 
                        if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = e.streams[0]; 
                        this.isInCall = true; 
                    };
                    if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
                    this.dc = this.pc.createDataChannel("chat");
                    this.setupDC(this.dc);
                    this.pc.ondatachannel = (e) => this.setupDC(e.channel);
                },

                setupDC(channel) {
                    channel.onmessage = (e) => {
                        const d = JSON.parse(e.data);
                        if (d.type === 'text') { 
                            this.messages.push({isMe:false, text: d.text}); 
                            this.scrollChat(); 
                            this.playMsgSound(); 
                        }
                        if (d.type === 'typing') this.showTypingIndicator();
                    };
                },

                sendTyping() {
                    // Троттлинг: отправляем сигнал не чаще чем раз в 2 секунды
                    if (Date.now() - this.lastTypingSent < 2000) return;
                    this.lastTypingSent = Date.now();

                    if (this.dc && this.dc.readyState === 'open') {
                        this.dc.send(JSON.stringify({type:'typing'}));
                    } else {
                        this.signal({type:'typing'});
                    }
                },

                showTypingIndicator() {
                    this.isPartnerTyping = true;
                    if (this.typingTimeout) clearTimeout(this.typingTimeout);
                    this.typingTimeout = setTimeout(() => this.isPartnerTyping = false, 3000);
                },

                async sendOffer() {
                    this.initPC();
                    const offer = await this.pc.createOffer();
                    await this.pc.setLocalDescription(offer);
                    this.signal({type:'offer', sdp: offer});
                },

                sendMsg() {
                    if (!this.chatInput.trim()) return;
                    const txt = this.chatInput;
                    this.messages.push({isMe:true, text: txt});
                    if (this.dc && this.dc.readyState === 'open') this.dc.send(JSON.stringify({type:'text', text: txt}));
                    else this.signal({type:'text', text: txt});
                    this.chatInput = '';
                    this.isPartnerTyping = false; // Скрываем у себя на всякий случай
                    this.scrollChat();
                },

                async startSearch() {
                    if (this.partnerId) this.signal({type:'peer-skipped'});
                    this.reset();
                    this.state = 'searching';
                    await window.axios.post('/chat/search');
                },

                stopSearch() {
                    if (this.partnerId) this.signal({type:'hang-up'});
                    this.reset();
                    window.axios.post('/chat/leave');
                    this.state = 'idle';
                },

                signal(data) {
                    if (!this.partnerId) return;
                    window.axios.post('/chat/signal', { partnerId: this.partnerId, data: data }).catch(()=>{});
                },

                reset() {
                    if (this.pc) { this.pc.close(); this.pc = null; }
                    this.stopRingtone();
                    this.partnerId = null; 
                    this.isInCall = false; 
                    this.state = 'idle';
                    this.messages = []; 
                    this.isBlurred = false; 
                    this.iceQueue = [];
                    this.isPartnerTyping = false;
                    if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
                },

                drainIce() { while(this.iceQueue.length > 0) this.pc.addIceCandidate(new RTCIceCandidate(this.iceQueue.shift())).catch(()=>{}); },
                
                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({video:true, audio:true});
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch(e) {}
                },

                toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
                toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
                sanitizeSdp(s) { return s.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n'; },
                
                scrollChat() { 
                    this.$nextTick(() => { 
                        const box = this.$refs.chatBox;
                        if(box) box.scrollTop = box.scrollHeight;
                    }); 
                },
                
                async callFriend(friendId) {
                    this.stopRingtone();
                    this.partnerId = Number(friendId);
                    this.state = 'searching';
                    await window.axios.post('/chat/contact/call', { contactId: friendId });
                },

                async acceptCall() {
                    this.stopRingtone();
                    this.partnerId = Number(this.incomingCall.fromId);
                    this.incomingCall = null;
                    this.state = 'connected';
                    this.isBlurred = true;
                    this.initPC();
                    this.sendOffer();
                },

                rejectCall() { this.stopRingtone(); this.incomingCall = null; },

                unlockSounds() { 
                    this.soundsUnlocked = true; 
                    this.msgSound.play().then(() => { this.msgSound.pause(); }).catch(() => {});
                    this.ringtone.play().then(() => { this.ringtone.pause(); }).catch(() => {});
                },
                playMsgSound() { if(this.soundsUnlocked) this.msgSound.play().catch(()=>{}); },
                playRingtone() { 
                    if(this.soundsUnlocked) {
                        this.ringtone.loop = true;
                        this.ringtone.play().catch(()=>{}); 
                    }
                },
                stopRingtone() { this.ringtone.pause(); this.ringtone.currentTime = 0; },
                
                async report() { if(confirm('Жалоба?')) { await window.axios.post('/report', {reported_id:this.partnerId, reason:'abuse'}); this.startSearch(); } },
                async addContact() { 
                    const res = await window.axios.post('/chat/contact/add', {contactId:this.partnerId}); 
                    this.isFriend = res.data.isFriend;
                    this.loadFriends();
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