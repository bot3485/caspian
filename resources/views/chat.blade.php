<x-app-layout>
    <div class="py-6 bg-gray-950 min-h-screen text-gray-200" 
         x-data="chatApp()" 
         @click.once="$store.sounds.unlock()"
         @close-messenger.window="messengerOpen = false"
         @open-chat.window="openMessenger($event.detail.id, $event.detail.name)">
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- ЛЕВАЯ КОЛОНКА (ВИДЕО) -->
                <div class="lg:col-span-3 space-y-4" x-data="videoChatComponent({{ auth()->id() }})">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- ВЫ -->
                        <div class="bg-black rounded-[2.5rem] overflow-hidden shadow-2xl h-[480px] flex items-center justify-center relative border border-white/5">
                            <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                            
                            <div class="absolute bottom-6 right-6 flex gap-2 z-10">
                                <button @click="showSettings = !showSettings" class="bg-white/10 hover:bg-white/20 backdrop-blur-xl p-3.5 rounded-2xl border border-white/10 text-white transition-all">⚙️</button>
                                <button @click="toggleMic()" :class="micEnabled ? 'bg-white/10' : 'bg-red-500/80'" class="backdrop-blur-xl p-3.5 rounded-2xl border border-white/10 text-white">
                                    <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                                </button>
                                <button @click="toggleCam()" :class="camEnabled ? 'bg-white/10' : 'bg-red-500/80'" class="backdrop-blur-xl p-3.5 rounded-2xl border border-white/10 text-white">
                                    <span x-text="camEnabled ? '📷' : '🚫'"></span>
                                </button>
                            </div>
                            <div class="absolute bottom-6 left-6 bg-black/40 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/10 text-[10px] font-black uppercase text-white/70">ВЫ (LIVE)</div>

                            <!-- Настройки устройств -->
                            <div x-show="showSettings" x-transition x-cloak @click.away="showSettings = false"
                                 class="absolute top-6 right-6 w-64 bg-gray-900/95 backdrop-blur-2xl rounded-3xl shadow-2xl p-5 z-30 border border-white/10 text-white">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-400 block mb-1 uppercase">Камера</label>
                                        <select x-model="selectedVideoId" @change="changeDevice()" class="w-full bg-black/50 border-white/10 rounded-xl text-[11px] text-white">
                                            <template x-for="d in devices.video"><option :value="d.deviceId" x-text="d.label"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-400 block mb-1 uppercase">Микрофон</label>
                                        <select x-model="selectedAudioId" @change="changeDevice()" class="w-full bg-black/50 border-white/10 rounded-xl text-[11px] text-white">
                                            <template x-for="d in devices.audio"><option :value="d.deviceId" x-text="d.label"></option></template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- СОБЕСЕДНИК -->
                        <div class="bg-black rounded-[2.5rem] overflow-hidden shadow-2xl h-[480px] flex items-center justify-center relative border border-white/5">
                            <video x-ref="remoteVideo" autoplay playsinline 
                                   class="w-full h-full object-cover transition-all duration-700" 
                                   :class="isBlurred ? 'blur-[80px] grayscale brightness-50' : ''"
                                   :class="!isInCall && 'opacity-0'"></video>
                            
                            <!-- Overlay при блюре -->
                            <div x-show="isInCall && isBlurred" class="absolute inset-0 z-30 flex items-center justify-center">
                                <button @click="isBlurred = false; if(autoBlurTimer) clearTimeout(autoBlurTimer)" 
                                        class="bg-indigo-600/80 hover:bg-indigo-600 backdrop-blur-md border border-white/20 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase">
                                    Открыть видео
                                </button>
                            </div>

                            <div x-show="isInCall" class="absolute top-6 left-6 flex gap-2 z-20">
                                <button @click="isBlurred = !isBlurred" :class="isBlurred ? 'bg-amber-500' : 'bg-white/10'" class="p-3.5 rounded-2xl backdrop-blur-xl border border-white/10 text-white">🛡️</button>
                                <button @click="showReportModal = true" class="bg-red-600/20 hover:bg-red-600 p-3.5 rounded-2xl backdrop-blur-xl border border-white/10 text-white">🚩</button>
                            </div>

                            <div x-show="!isInCall" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-950 transition-all">
                                <template x-if="state === 'searching'"><div class="flex flex-col items-center"><div class="w-16 h-16 border-4 border-t-indigo-500 border-white/5 rounded-full animate-spin mb-6"></div><span class="text-indigo-400 font-black uppercase text-[10px] tracking-[0.3em] animate-pulse">Поиск...</span></div></template>
                                <template x-if="state === 'idle'"><div class="text-center"><div class="text-5xl mb-4 opacity-20">👋</div><span class="text-gray-600 font-black uppercase text-[10px] tracking-[0.2em]">Готов</span></div></template>
                            </div>
                        </div>
                    </div>

                    <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-900/50 backdrop-blur-xl p-6 rounded-[2.5rem] border border-white/5 flex flex-col justify-between min-h-[250px]">
                            <div>
                                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Статус</h3>
                                <div class="p-4 bg-black/40 rounded-2xl border border-white/5 mb-4">
                                    <div class="text-white font-black text-xs uppercase" x-html="statusHtml"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <button x-show="state === 'idle'" @click="startSearch()" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-5 rounded-2xl font-black text-xs transition-all">НАЧАТЬ ПОИСК</button>
                                <div x-show="state === 'connected' && !isDirectCall" class="flex gap-2">
                                    <button @click="addPartnerToContacts()" class="flex-1 bg-white/5 hover:bg-white/10 text-white py-5 rounded-2xl font-black text-[10px] uppercase border border-white/10">⭐️ В друзья</button>
                                    <button @click="startSearch()" class="flex-[2] bg-indigo-600 hover:bg-indigo-500 text-white py-5 rounded-2xl font-black text-xs transition-all">СЛЕДУЮЩИЙ ➔</button>
                                </div>
                                <button x-show="isInCall" @click="hangUp()" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-500 py-5 rounded-2xl font-black text-xs border border-red-500/20">ЗАВЕРШИТЬ 📞</button>
                                <button x-show="state === 'searching'" @click="stopSearch()" class="w-full bg-white/5 text-gray-400 py-5 rounded-2xl font-black text-xs">ОТМЕНА</button>
                            </div>
                        </div>

                        <!-- ЧАТ РУЛЕТКИ (P2P) -->
                        <div x-show="state === 'connected' && !isDirectCall" class="bg-gray-900/50 backdrop-blur-xl rounded-[2.5rem] border border-white/5 md:col-span-2 flex flex-col h-[280px] overflow-hidden">
                            <div class="p-4 border-b border-white/5 bg-black/20 flex justify-between items-center text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                <span>Быстрый чат</span>
                                <span x-show="dc && dc.readyState === 'open'" class="text-green-500 flex items-center gap-1.5"><span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-ping"></span> Прямой канал</span>
                            </div>
                            <div class="flex-1 overflow-y-auto p-5 space-y-3 scrollbar-hide" x-ref="rouletteChat">
                                <template x-for="msg in rouletteMessages">
                                    <div :class="msg.isMe ? 'bg-indigo-600 text-white ml-auto rounded-l-2xl rounded-tr-2xl' : 'bg-white/10 text-gray-200 mr-auto rounded-r-2xl rounded-tl-2xl'" class="p-4 text-xs font-bold max-w-[80%] shadow-sm" x-text="msg.text"></div>
                                </template>
                            </div>
                            <div class="p-3 bg-black/40 flex gap-2">
                                <input type="text" x-model="rouletteInput" @keyup.enter="sendRouletteMsg()" placeholder="Сообщение..." class="flex-1 bg-white/5 border-none rounded-xl px-5 text-xs text-white">
                                <button @click="sendRouletteMsg()" class="bg-indigo-600 text-white p-3.5 rounded-xl hover:bg-indigo-500">➔</button>
                            </div>
                        </div>

                        <!-- МЕССЕНДЖЕР (для друзей) -->
                        <div x-show="messengerOpen" x-data="messengerComponent({{ auth()->id() }})" class="bg-gray-900 rounded-[2.5rem] border border-white/10 md:col-span-2 flex flex-col h-[350px] overflow-hidden">
                             <div class="p-5 border-b border-white/5 bg-black/20 flex items-center justify-between">
                                <span class="text-xs font-black text-white uppercase tracking-widest" x-text="chatPartnerName"></span>
                                <button @click="$dispatch('close-messenger')" class="text-gray-500 hover:text-white">✕</button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-5 space-y-4 scrollbar-hide bg-black/20" x-ref="msgContainer" @scroll="handleScroll">
                                <template x-for="m in messages" :key="m.id">
                                    <div :class="Number(m.sender_id) === {{ auth()->id() }} ? 'bg-indigo-600 text-white ml-auto rounded-l-2xl rounded-tr-2xl' : 'bg-gray-800 text-gray-200 rounded-r-2xl rounded-tl-2xl'" class="p-4 text-xs font-bold max-w-[85%]" x-text="m.message"></div>
                                </template>
                            </div>
                            <div class="p-4 bg-gray-900 border-t border-white/5 flex gap-2">
                                <input type="text" x-model="newMessage" @input="sendTyping()" @keyup.enter="send()" placeholder="Ваше сообщение..." class="flex-1 bg-black/40 border-none rounded-2xl px-5 text-xs text-white">
                                <button @click="send()" class="bg-indigo-600 text-white px-8 rounded-2xl text-[10px] font-black uppercase">Отправить</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- КОНТАКТЫ -->
                <div class="bg-gray-900/50 backdrop-blur-xl p-6 rounded-[2.5rem] border border-white/5 flex flex-col h-[750px]" x-data="contactsListComponent()">
                    <h2 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.3em] mb-8 ml-2">Друзья в сети</h2>
                    <div class="flex-1 overflow-y-auto space-y-3 scrollbar-hide">
                        <template x-for="c in contacts" :key="c.id">
                            <div @click="$dispatch('open-chat', {id: c.id, name: c.name})" class="p-4 bg-white/5 rounded-[2rem] border border-transparent flex items-center justify-between hover:bg-white/10 transition-all cursor-pointer group">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-indigo-600/20 rounded-2xl flex items-center justify-center text-sm font-black text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white" x-text="c.name[0]"></div>
                                    <div>
                                        <div class="text-xs font-black text-white" x-text="c.name"></div>
                                        <div class="text-[9px] font-bold uppercase tracking-tighter mt-0.5">
                                            <template x-if="$store.online.has(c.id)"><span class="text-green-500">● В сети</span></template>
                                            <template x-if="!$store.online.has(c.id)"><span class="text-gray-400">Offline</span></template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    [this.message, this.call].forEach(a => { a.muted = true; a.play().then(() => { a.pause(); a.muted = false; a.currentTime = 0; }).catch(()=>{}); });
                    this.isUnlocked = true;
                },
                playMsg() { this.message.currentTime = 0; this.message.play().catch(()=>{}); },
                playCall() { this.call.loop = true; this.call.play().catch(()=>{}); },
                stopCall() { this.call.pause(); this.call.currentTime = 0; }
            });
        });

        function chatApp() {
            return {
                messengerOpen: false,
                init() {
                    window.Echo.join('online-status').here(u => Alpine.store('online').set(u)).joining(u => Alpine.store('online').add(u.id)).leaving(u => Alpine.store('online').remove(u.id));
                },
                openMessenger(id, name) { this.messengerOpen = true; this.$dispatch('load-chat-history', {id, name}); }
            }
        }

        function videoChatComponent(myId) {
            return {
                state: 'idle', statusHtml: 'Система готова', isInCall: false, isDirectCall: false,
                partnerId: null, pc: null, dc: null, localStream: null, micEnabled: true, camEnabled: true,
                rouletteMessages: [], rouletteInput: [],
                isBlurred: false, autoBlurTimer: null, showReportModal: false,
                devices: { video: [], audio: [] }, selectedVideoId: localStorage.getItem('selectedVideoId') || '',
                selectedAudioId: localStorage.getItem('selectedAudioId') || '', showSettings: false,

                async init() {
                    await this.initMedia(); await this.updateDevicesList();
                    window.Echo.private(`user.${myId}`)
                        .listen('.MatchFoundEvent', (e) => this.onMatchFound(e))
                        .listen('.WebRTCSignalEvent', (e) => this.onSignal(e));
                    
                    window.addEventListener('beforeunload', () => { if(this.partnerId) this.stopSearch(); });
                },

                fixSdp(sd) { return sd ? sd.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ""; },
                async updateDevicesList() { const d = await navigator.mediaDevices.enumerateDevices(); this.devices.video = d.filter(x => x.kind === 'videoinput'); this.devices.audio = d.filter(x => x.kind === 'audioinput'); },

                async initMedia() {
                    try {
                        if(this.localStream) this.localStream.getTracks().forEach(t => t.stop());
                        this.localStream = await navigator.mediaDevices.getUserMedia({ 
                            video: this.selectedVideoId ? { deviceId: { exact: this.selectedVideoId } } : true, 
                            audio: this.selectedAudioId ? { deviceId: { exact: this.selectedAudioId } } : true 
                        }); 
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch (e) { if(this.selectedVideoId) { this.selectedVideoId = ''; this.initMedia(); } }
                },

                async changeDevice() { localStorage.setItem('selectedVideoId', this.selectedVideoId); localStorage.setItem('selectedAudioId', this.selectedAudioId); await this.initMedia(); if(this.pc) this.resetConnection(); },

                async startSearch() { this.resetConnection(); this.state = 'searching'; this.statusHtml = 'ИЩЕМ ПАРУ...'; await window.axios.post('/chat/search'); },
                
                onMatchFound(e) { 
                    this.partnerId = Number(e.partnerId); this.state = 'connected'; this.isBlurred = true; 
                    if(this.autoBlurTimer) clearTimeout(this.autoBlurTimer);
                    this.autoBlurTimer = setTimeout(() => { this.isBlurred = false; }, 3000); 
                    setTimeout(() => { if (myId > this.partnerId) this.sendOffer(); }, 500);
                },

                async sendOffer() { this.initPC(); const o = await this.pc.createOffer(); await this.pc.setLocalDescription(o); this.signal({ type: 'webrtc-offer', sdpString: o.sdp }); },
                
                initPC() {
                    if (this.pc) return;
                    this.pc = new RTCPeerConnection(window.rtcConfig);
                    if (myId > this.partnerId) this.setupDataChannel(this.pc.createDataChannel("chat"));
                    else this.pc.ondatachannel = (e) => this.setupDataChannel(e.channel);

                    // ПОРЯДОК: Аудио, потом Видео
                    const aT = this.localStream.getAudioTracks()[0];
                    const vT = this.localStream.getVideoTracks()[0];
                    if (aT) this.pc.addTrack(aT, this.localStream);
                    if (vT) this.pc.addTrack(vT, this.localStream);

                    this.pc.ontrack = (e) => { this.$refs.remoteVideo.srcObject = e.streams[0]; this.isInCall = true; this.statusHtml = '<span class="text-green-500">● В ЭФИРЕ</span>'; };
                    this.pc.onicecandidate = (e) => { if (e.candidate) this.signal({ type: 'ice-candidate', candidate: e.candidate }); };
                },

                setupDataChannel(ch) { 
                    this.dc = ch; 
                    this.dc.onmessage = (e) => { 
                        const d = JSON.parse(e.data); 
                        if (d.type === 'text') { this.rouletteMessages.push({ isMe: false, text: d.text }); Alpine.store('sounds').playMsg(); this.$nextTick(() => this.$refs.rouletteChat.scrollTop = 9999); } 
                    }; 
                },

                async onSignal(e) {
                    const msg = e.data;
                    if (msg.type === 'peer-disconnected' || msg.type === 'hang-up') {
                        console.log("Partner left"); this.resetConnection();
                        if (!this.isDirectCall) { this.statusHtml = '<span class="text-amber-500">ПАРТНЕР УШЕЛ</span>'; setTimeout(() => this.startSearch(), 1500); }
                        return;
                    }
                    if (!this.partnerId) return;
                    try {
                        if (msg.type === 'webrtc-offer') { this.initPC(); await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'offer', sdp: this.fixSdp(msg.sdpString)})); const a = await this.pc.createAnswer(); await this.pc.setLocalDescription(a); this.signal({ type: 'webrtc-answer', sdpString: a.sdp }); }
                        if (msg.type === 'webrtc-answer') await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'answer', sdp: this.fixSdp(msg.sdpString)}));
                        if (msg.type === 'ice-candidate') this.pc?.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(()=>{});
                        if (msg.type === 'roulette-text-msg') { this.rouletteMessages.push({ isMe: false, text: msg.text }); Alpine.store('sounds').playMsg(); this.$nextTick(() => this.$refs.rouletteChat.scrollTop = 9999); }
                    } catch(err) { console.error(err); }
                },

                sendRouletteMsg() { 
                    if (!this.rouletteInput || !this.partnerId) return; 
                    if (this.dc?.readyState === 'open') this.dc.send(JSON.stringify({ type: 'text', text: this.rouletteInput }));
                    else this.signal({ type: 'roulette-text-msg', text: this.rouletteInput });
                    this.rouletteMessages.push({ isMe: true, text: this.rouletteInput }); this.rouletteInput = ''; this.$nextTick(() => this.$refs.rouletteChat.scrollTop = 9999); 
                },

                signal(d) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: Number(this.partnerId), data: d }).catch(e => { if(e.response?.status === 403) console.log("Signal forbidden"); }); },

                resetConnection() { 
                    this.partnerId = null;
                    if (this.pc) { this.pc.onicecandidate = null; this.pc.ontrack = null; this.pc.close(); this.pc = null; }
                    this.dc = null; this.isInCall = false; this.state = 'idle'; this.statusHtml = 'Готов'; this.rouletteMessages = []; 
                    if(this.$refs.remoteVideo) { this.$refs.remoteVideo.pause(); this.$refs.remoteVideo.srcObject = null; this.$refs.remoteVideo.load(); }
                    Alpine.store('sounds').stopCall(); 
                },

                stopSearch() { window.axios.post('/chat/leave'); this.resetConnection(); },
                hangUp() { this.signal({ type: 'hang-up' }); this.resetConnection(); },
                async addPartnerToContacts() { if (!this.partnerId) return; await window.axios.post('/chat/contact/toggle', { contactId: this.partnerId }); alert("Готово!"); window.dispatchEvent(new CustomEvent('contacts-updated')); },
                async reportPartner(r) { await window.axios.post('/report', { reported_id: this.partnerId, reason: r }); alert("Жалоба принята!"); this.showReportModal = false; this.startSearch(); }
            }
        }

        // Вспомогательные функции мессенджера и контактов остаются почти как были
        function messengerComponent(myId) {
            return {
                messages: [], chatPartnerId: null, chatPartnerName: '', newMessage: '', isPartnerTyping: false,
                init() {
                    window.addEventListener('load-chat-history', async (e) => { this.chatPartnerId = e.detail.id; this.chatPartnerName = e.detail.name; this.messages = []; const res = await window.axios.get(`/chat/history/${this.chatPartnerId}`); this.messages = res.data.messages; this.$nextTick(() => this.$refs.msgContainer.scrollTop = 9999); });
                    window.Echo.private(`user.${myId}`).listen('.MessageSentEvent', (e) => { Alpine.store('sounds').playMsg(); if (Number(this.chatPartnerId) === Number(e.messageData.sender_id)) { this.messages.push(e.messageData); this.$nextTick(() => this.$refs.msgContainer.scrollTop = 9999); } });
                },
                async send() { if (!this.newMessage.trim()) return; const res = await window.axios.post('/chat/message/send', { receiver_id: this.chatPartnerId, message: this.newMessage }); this.messages.push(res.data.message); this.newMessage = ''; this.$nextTick(() => this.$refs.msgContainer.scrollTop = 9999); },
                sendTyping() {} 
            }
        }

        function contactsListComponent() {
            return {
                contacts: [], initialLoaded: false,
                init() { this.load(); window.addEventListener('contacts-updated', () => this.load()); },
                async load() { const res = await window.axios.get('/chat/contacts'); this.contacts = res.data.contacts; this.initialLoaded = true; }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        video { background: #000; image-rendering: -webkit-optimize-contrast; }
    </style>
</x-app-layout>