<x-app-layout>
    <!-- Главный контейнер. Клик разблокирует аудио-движок -->
    <div class="py-6 bg-gray-50 min-h-screen" 
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
                        <div class="bg-gray-900 rounded-3xl overflow-hidden shadow-xl h-[450px] flex items-center justify-center relative border border-gray-800">
                            <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                            
                            <div class="absolute top-4 right-4 flex gap-2 z-10">
                                <button @click="showSettings = !showSettings" class="bg-white/10 hover:bg-white/20 backdrop-blur-md p-3 rounded-2xl border border-white/10 text-white transition-all">⚙️</button>
                                <button @click="toggleMic()" :class="micEnabled ? 'bg-gray-900/80' : 'bg-red-600'" class="p-3 rounded-2xl border border-gray-700 text-white transition-colors">
                                    <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                                </button>
                                <button @click="toggleCam()" :class="camEnabled ? 'bg-gray-900/80' : 'bg-red-600'" class="p-3 rounded-2xl border border-gray-700 text-white transition-colors">
                                    <span x-text="camEnabled ? '📷' : '🚫'"></span>
                                </button>
                            </div>

                            <!-- Настройки устройств -->
                            <div x-show="showSettings" x-transition x-cloak @click.away="showSettings = false"
                                 class="absolute top-20 right-4 w-64 bg-white rounded-3xl shadow-2xl p-5 z-30 border border-gray-100 text-gray-800">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Медиа-устройства</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-400 block mb-1 uppercase">Камера</label>
                                        <select x-model="selectedVideoId" @change="changeDevice()" class="w-full bg-gray-50 border-none rounded-xl text-[11px] font-bold">
                                            <template x-for="d in devices.video"><option :value="d.deviceId" x-text="d.label"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-400 block mb-1 uppercase">Микрофон</label>
                                        <select x-model="selectedAudioId" @change="changeDevice()" class="w-full bg-gray-50 border-none rounded-xl text-[11px] font-bold">
                                            <template x-for="d in devices.audio"><option :value="d.deviceId" x-text="d.label"></option></template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- СОБЕСЕДНИК -->
                        <div class="bg-gray-900 rounded-3xl overflow-hidden shadow-xl h-[450px] flex items-center justify-center relative border border-gray-800">
                            <video x-ref="remoteVideo" autoplay playsinline 
                                   class="w-full h-full object-cover transition-all duration-700" 
                                   :class="isBlurred ? 'blur-[60px] grayscale brightness-50' : ''"
                                   :class="!isInCall && 'opacity-0'"></video>
                            
                            <!-- Overlay при блюре -->
                            <div x-show="isInCall && isBlurred" class="absolute inset-0 z-30 flex items-center justify-center">
                                <button @click="isBlurred = false; if(autoBlurTimer) clearTimeout(autoBlurTimer)" 
                                        class="bg-white/20 backdrop-blur-md border border-white/30 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-white/40 transition-all">
                                    Открыть видео
                                </button>
                            </div>

                            <!-- Кнопки Безопасности -->
                            <div x-show="isInCall" class="absolute top-4 left-4 flex gap-2 z-20">
                                <button @click="isBlurred = !isBlurred" :class="isBlurred ? 'bg-amber-500' : 'bg-black/40'" class="p-3 rounded-2xl backdrop-blur-md border border-white/10 text-white transition-all">🛡️</button>
                                <button @click="showReportModal = true" class="bg-red-600/80 hover:bg-red-600 p-3 rounded-2xl backdrop-blur-md border border-white/10 text-white transition-all">🚩</button>
                            </div>

                            <div x-show="showReportModal" x-transition x-cloak class="absolute inset-0 bg-black/90 backdrop-blur-xl z-40 flex items-center justify-center p-8 text-center">
                                <div class="w-full max-w-xs text-white">
                                    <h4 class="font-black text-sm uppercase mb-6 tracking-tighter">На что жалуемся?</h4>
                                    <div class="space-y-3">
                                        <button @click="reportPartner('nudity')" class="w-full bg-white/10 hover:bg-white/20 py-4 rounded-2xl text-xs font-bold transition-all">Непотребство</button>
                                        <button @click="reportPartner('harassment')" class="w-full bg-white/10 hover:bg-white/20 py-4 rounded-2xl text-xs font-bold transition-all">Оскорбления</button>
                                        <button @click="showReportModal = false" class="block w-full mt-4 text-gray-500 text-[10px] font-black uppercase tracking-widest">Отмена</button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="!isInCall" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 transition-all">
                                <div class="w-16 h-16 border-4 border-t-indigo-500 border-gray-800 rounded-full animate-spin mb-4" x-show="state === 'searching'"></div>
                                <span class="text-gray-500 font-black uppercase text-[10px] tracking-[0.2em]" x-text="state === 'searching' ? 'Ищем пару...' : 'Ожидание'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col justify-between min-h-[250px]">
                            <div>
                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Статус</h3>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 mb-4 transition-all">
                                    <div class="text-gray-800 font-black text-xs uppercase" x-html="statusHtml"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <button x-show="state === 'idle'" @click="startSearch()" class="w-full bg-indigo-600 text-white py-5 rounded-2xl font-black shadow-lg text-xs hover:bg-indigo-700 transition-all">НАЧАТЬ ПОИСК</button>
                                
                                <div x-show="state === 'connected' && !isDirectCall" class="flex gap-2">
                                    <button @click="addPartnerToContacts()" class="flex-1 bg-amber-400 text-amber-900 py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-amber-500 transition-all">⭐️ В друзья</button>
                                    <button @click="startSearch()" class="flex-[2] bg-gray-900 text-white py-5 rounded-2xl font-black text-xs hover:bg-black transition-all">СЛЕДУЮЩИЙ ➔</button>
                                </div>

                                <button x-show="isInCall" @click="hangUp()" class="w-full bg-red-100 text-red-600 py-5 rounded-2xl font-black text-xs hover:bg-red-200 transition-all">ЗАВЕРШИТЬ 📞</button>
                                <button x-show="state === 'searching'" @click="stopSearch()" class="w-full bg-gray-100 text-gray-500 py-5 rounded-2xl font-black text-xs transition-all">ОТМЕНА</button>
                            </div>
                        </div>

                        <!-- ЧАТ РУЛЕТКИ (P2P) -->
                        <div x-show="state === 'connected' && !isDirectCall" class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 md:col-span-2 flex flex-col h-[280px] overflow-hidden">
                            <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                <span>Быстрый чат (P2P)</span>
                                <span x-show="dc && dc.readyState === 'open'" class="text-green-500">● Прямой канал</span>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4 space-y-2 scrollbar-hide" x-ref="rouletteChat">
                                <template x-for="msg in rouletteMessages">
                                    <div :class="msg.isMe ? 'bg-indigo-600 text-white ml-auto rounded-l-2xl rounded-tr-2xl' : 'bg-gray-100 text-gray-800 mr-auto rounded-r-2xl rounded-tl-2xl'" 
                                         class="p-3 text-xs font-bold max-w-[80%] shadow-sm" x-text="msg.text"></div>
                                </template>
                            </div>
                            <div class="p-3 bg-gray-50 flex gap-2">
                                <input type="text" x-model="rouletteInput" @keyup.enter="sendRouletteMsg()" placeholder="Текст..." class="flex-1 bg-white border-none rounded-xl px-5 text-xs font-bold focus:ring-2 focus:ring-indigo-500">
                                <button @click="sendRouletteMsg()" class="bg-indigo-600 text-white p-3 rounded-xl hover:bg-indigo-700 transition-all">➔</button>
                            </div>
                        </div>

                        <!-- МЕССЕНДЖЕР -->
                        <div x-show="messengerOpen" x-data="messengerComponent({{ auth()->id() }})" 
                             class="bg-white rounded-[2.5rem] shadow-2xl border border-indigo-100 md:col-span-2 flex flex-col h-[350px] overflow-hidden transition-all">
                            <div class="p-4 border-b border-indigo-50 bg-indigo-50/30 flex items-center justify-between">
                                <span class="text-xs font-black text-gray-800" x-text="chatPartnerName"></span>
                                <button @click="$dispatch('close-messenger')" class="text-gray-400 hover:text-gray-600 font-bold transition-colors">✕</button>
                            </div>
                            
                            <div class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-hide" 
                                 x-ref="msgContainer" 
                                 @scroll="handleScroll">
                                <template x-for="m in messages" :key="m.id">
                                    <div :class="Number(m.sender_id) === {{ auth()->id() }} ? 'bg-indigo-500 text-white ml-auto rounded-l-2xl rounded-tr-2xl' : 'bg-gray-100 text-gray-800 rounded-r-2xl rounded-tl-2xl'" 
                                         class="p-3 text-xs font-bold max-w-[85%] shadow-sm" x-text="m.message"></div>
                                </template>
                            </div>

                            <!-- ИНДИКАТОР ПЕЧАТИ -->
                            <div class="px-4 py-1 bg-white h-6">
                                <div x-show="isPartnerTyping" class="text-[9px] text-indigo-500 font-black uppercase tracking-widest animate-pulse">
                                    ✍️ <span x-text="chatPartnerName"></span> печатает...
                                </div>
                            </div>

                            <div class="p-3 bg-white border-t border-gray-100 flex gap-2">
                                <input type="text" x-model="newMessage" @input="sendTyping()" @keyup.enter="send()" placeholder="Текст..." class="flex-1 bg-gray-50 border-none rounded-xl px-5 text-xs font-bold focus:ring-2 focus:ring-indigo-500">
                                <button @click="send()" class="bg-indigo-600 text-white px-6 rounded-xl text-xs font-black hover:bg-indigo-700 transition-all">SEND</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- КОНТАКТЫ -->
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col h-[750px]" x-data="contactsListComponent()">
                    <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6">Список друзей</h2>
                    <div class="flex-1 overflow-y-auto space-y-3 scrollbar-hide">
                        <!-- SKELETONS -->
                        <template x-if="contacts.length === 0 && !initialLoaded">
                            <div class="space-y-3">
                                <template x-for="i in 5">
                                    <div class="p-4 bg-gray-50 rounded-3xl animate-pulse flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gray-200 rounded-2xl"></div>
                                        <div class="flex-1 space-y-2"><div class="h-2 bg-gray-200 rounded w-2/3"></div><div class="h-2 bg-gray-200 rounded w-1/3"></div></div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-for="c in contacts" :key="c.id">
                            <div @click="$dispatch('open-chat', {id: c.id, name: c.name})" 
                                 class="p-4 bg-white rounded-3xl border border-gray-50 flex items-center justify-between hover:border-indigo-200 hover:shadow-md transition-all cursor-pointer group">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 bg-indigo-50 rounded-2xl flex items-center justify-center text-sm font-black text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white transition-all" x-text="c.name[0]"></div>
                                    <div>
                                        <div class="text-xs font-black text-gray-800" x-text="c.name"></div>
                                        <div class="text-[9px] font-bold uppercase tracking-tighter">
                                            <template x-if="$store.online.has(c.id)"><span class="text-green-500">● Online</span></template>
                                            <template x-if="!$store.online.has(c.id)"><span class="text-gray-400" x-text="'Был: ' + formatLastSeen(c.last_seen)"></span></template>
                                        </div>
                                    </div>
                                </div>
                                <button x-show="$store.online.has(c.id)" @click.stop="callPartner(c.id, c.name)" class="p-3 text-green-600 opacity-0 group-hover:opacity-100 transition-all hover:scale-110">📞</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')) };

        // 1. GLOBAL STORES
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

        function chatApp() {
            return {
                messengerOpen: false,
                init() {
                    window.Echo.join('online-status')
                        .here(u => Alpine.store('online').set(u))
                        .joining(u => { Alpine.store('online').add(u.id); this.$dispatch('contacts-updated'); })
                        .leaving(u => { Alpine.store('online').remove(u.id); this.$dispatch('contacts-updated'); });
                },
                openMessenger(id, name) { this.messengerOpen = true; this.$dispatch('load-chat-history', {id, name}); }
            }
        }

        function videoChatComponent(myId) {
            return {
                state: 'idle', statusHtml: 'Готов', isInCall: false, isDirectCall: false,
                partnerId: null, pc: null, dc: null, localStream: null, micEnabled: true, camEnabled: true,
                rouletteMessages: [], rouletteInput: '', iceQueue: [],
                isBlurred: false, autoBlurTimer: null, showReportModal: false,
                devices: { video: [], audio: [] }, selectedVideoId: localStorage.getItem('selectedVideoId') || '',
                selectedAudioId: localStorage.getItem('selectedAudioId') || '', showSettings: false,

                async init() {
                    await this.initMedia(true); await this.updateDevicesList();
                    window.Echo.private(`user.${myId}`)
                        .listen('.MatchFoundEvent', (e) => this.onMatchFound(e))
                        .listen('.WebRTCSignalEvent', (e) => this.onSignal(e));
                    
                    window.addEventListener('start-direct-call', async (e) => {
                        this.resetConnection(); this.partnerId = Number(e.detail.id); this.isDirectCall = true;
                        this.state = 'connected'; this.statusHtml = `Вызов: ${e.detail.name}`;
                        await window.axios.post('/chat/contact/call', { contactId: this.partnerId });
                    });
                },

                fixSdp(sd) { return sd ? sd.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ""; },
                async updateDevicesList() { const d = await navigator.mediaDevices.enumerateDevices(); this.devices.video = d.filter(x => x.kind === 'videoinput'); this.devices.audio = d.filter(x => x.kind === 'audioinput'); },
                async initMedia(firstTime = false) {
                    try {
                        const c = { video: this.selectedVideoId ? { deviceId: { exact: this.selectedVideoId } } : true, audio: this.selectedAudioId ? { deviceId: { exact: this.selectedAudioId } } : true };
                        this.localStream = await navigator.mediaDevices.getUserMedia(c); this.$refs.localVideo.srcObject = this.localStream;
                    } catch (e) { if(this.selectedVideoId) { this.selectedVideoId = ''; this.initMedia(); } }
                },
                async changeDevice() { localStorage.setItem('selectedVideoId', this.selectedVideoId); localStorage.setItem('selectedAudioId', this.selectedAudioId); await this.initMedia(); },
                async startSearch() { this.resetConnection(); this.state = 'searching'; this.statusHtml = 'Поиск...'; await window.axios.post('/chat/search'); },
                
                onMatchFound(e) { 
                    this.partnerId = Number(e.partnerId); this.state = 'connected'; this.isBlurred = true; 
                    if(this.autoBlurTimer) clearTimeout(this.autoBlurTimer);
                    this.autoBlurTimer = setTimeout(() => { this.isBlurred = false; }, 3000); 
                    if (myId > this.partnerId) setTimeout(() => this.sendOffer(), 1000); 
                },

                async sendOffer() { this.initPC(); const o = await this.pc.createOffer(); await this.pc.setLocalDescription(o); this.signal({ type: 'webrtc-offer', sdpString: o.sdp }); },
                
                initPC() {
                    if (this.pc) return;
                    this.pc = new RTCPeerConnection(window.rtcConfig);
                    if (myId > this.partnerId) this.setupDataChannel(this.pc.createDataChannel("chat"));
                    else this.pc.ondatachannel = (e) => this.setupDataChannel(e.channel);
                    this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
                    this.pc.ontrack = (e) => { this.$refs.remoteVideo.srcObject = e.streams[0]; this.isInCall = true; this.statusHtml = '<span class="text-green-500 font-black">● В ЭФИРЕ</span>'; };
                    this.pc.onicecandidate = (e) => { if (e.candidate) this.signal({ type: 'ice-candidate', candidate: e.candidate }); };
                },

                setupDataChannel(ch) { 
                    this.dc = ch; 
                    this.dc.onmessage = (e) => { 
                        const d = JSON.parse(e.data); 
                        if (d.type === 'text') { 
                            this.rouletteMessages.push({ isMe: false, text: d.text }); 
                            Alpine.store('sounds').playMsg();
                            this.$nextTick(() => this.$refs.rouletteChat.scrollTop = 9999); 
                        } 
                    }; 
                },

                async onSignal(e) {
                    const msg = e.data;
                    if (msg.type === 'typing') return;
                    if (msg.type === 'incoming-direct-call') {
                        Alpine.store('sounds').playCall();
                        if (confirm(`Звонит ${msg.callerName}. Принять?`)) { 
                            Alpine.store('sounds').stopCall();
                            this.partnerId = Number(msg.callerId); this.isDirectCall = true; this.state = 'connected'; this.initPC(); this.signal({type:'peer-ready'}); 
                        } else { Alpine.store('sounds').stopCall(); }
                        return;
                    }
                    if (msg.type === 'peer-ready') return this.sendOffer();
                    try {
                        if (msg.type === 'webrtc-offer') { this.initPC(); await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'offer', sdp: this.fixSdp(msg.sdpString)})); const a = await this.pc.createAnswer(); await this.pc.setLocalDescription(a); this.signal({ type: 'webrtc-answer', sdpString: a.sdp }); }
                        if (msg.type === 'webrtc-answer') await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'answer', sdp: this.fixSdp(msg.sdpString)}));
                        if (msg.type === 'ice-candidate') this.pc?.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(()=>{});
                        if (msg.type === 'roulette-text-msg') { this.rouletteMessages.push({ isMe: false, text: msg.text }); Alpine.store('sounds').playMsg(); this.$nextTick(() => this.$refs.rouletteChat.scrollTop = 9999); }
                        if (msg.type === 'hang-up') this.resetConnection();
                    } catch(err) { console.error(err); }
                },

                sendRouletteMsg() { 
                    if (!this.rouletteInput || !this.partnerId) return; 
                    if (this.dc?.readyState === 'open') this.dc.send(JSON.stringify({ type: 'text', text: this.rouletteInput }));
                    else this.signal({ type: 'roulette-text-msg', text: this.rouletteInput });
                    this.rouletteMessages.push({ isMe: true, text: this.rouletteInput }); 
                    this.rouletteInput = ''; this.$nextTick(() => this.$refs.rouletteChat.scrollTop = 9999); 
                },
                async addPartnerToContacts() { if (!this.partnerId) return; const res = await window.axios.post('/chat/contact/toggle', { contactId: this.partnerId }); alert(res.data.action === 'added' ? "Добавлен!" : "Удален"); window.dispatchEvent(new CustomEvent('contacts-updated')); },
                async reportPartner(r) { await window.axios.post('/report', { reported_id: this.partnerId, reason: r }); alert("Жалоба принята"); this.showReportModal = false; this.startSearch(); },
                toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
                toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
                signal(d) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: d }); },
                resetConnection() { if (this.pc) this.pc.close(); this.pc = null; this.dc = null; this.partnerId = null; this.isInCall = false; this.state = 'idle'; this.statusHtml = 'Готов'; this.rouletteMessages = []; if(this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null; Alpine.store('sounds').stopCall(); },
                stopSearch() { window.axios.post('/chat/leave'); this.resetConnection(); },
                hangUp() { this.signal({ type: 'hang-up' }); this.resetConnection(); }
            }
        }

        function messengerComponent(myId) {
            return {
                messages: [], chatPartnerId: null, chatPartnerName: '', newMessage: '', isPartnerTyping: false, typingTimeout: null, lastTypingSent: 0, isLoading: false, hasMore: true,
                init() {
                    window.addEventListener('load-chat-history', async (e) => { 
                        this.chatPartnerId = e.detail.id; this.chatPartnerName = e.detail.name; this.messages = []; this.hasMore = true; this.isLoading = false;
                        await this.loadHistory(); this.scrollToBottom(); 
                    });
                    window.Echo.private(`user.${myId}`).listen('.MessageSentEvent', (e) => { 
                        // ДОБАВЬ ЭТИ ЛОГИ:
                        console.log("Входящее сообщение поймано сокетом!", e.messageData);
                        console.log("Пытаюсь воспроизвести звук...");

                        Alpine.store('sounds').playMsg(); 

                        if (Number(this.chatPartnerId) === Number(e.messageData.sender_id)) { 
                            this.messages.push(e.messageData); 
                            this.isPartnerTyping = false; 
                            this.scrollToBottom(); 
                        } else {
                            window.dispatchEvent(new CustomEvent('contacts-updated'));
                        }
                    }).listen('.WebRTCSignalEvent', (e) => { 
                        if (e.data.type === 'typing' && Number(e.data.from) === Number(this.chatPartnerId)) { 
                            this.isPartnerTyping = true; 
                            if(this.typingTimeout) clearTimeout(this.typingTimeout); 
                            this.typingTimeout = setTimeout(() => this.isPartnerTyping = false, 3000); 
                        } 
                    });
                },
                async loadHistory() {
                    if (this.isLoading || !this.hasMore) return;
                    this.isLoading = true;
                    const beforeId = this.messages.length > 0 ? this.messages[0].id : null;
                    const oldHeight = this.$refs.msgContainer.scrollHeight;
                    try {
                        const res = await window.axios.get(`/chat/history/${this.chatPartnerId}`, { params: { before_id: beforeId } });
                        this.messages = [...res.data.messages, ...this.messages];
                        this.hasMore = res.data.has_more;
                        if (beforeId) this.$nextTick(() => { this.$refs.msgContainer.scrollTop = this.$refs.msgContainer.scrollHeight - oldHeight; });
                    } finally { this.isLoading = false; }
                },
                handleScroll() { if (this.$refs.msgContainer.scrollTop < 50) this.loadHistory(); },
                sendTyping() { if (Date.now() - this.lastTypingSent > 2000) { this.lastTypingSent = Date.now(); window.axios.post('/chat/message/typing', { receiver_id: this.chatPartnerId }); } },
                async send() {
                    if (!this.newMessage.trim()) return;
                    const res = await window.axios.post('/chat/message/send', { receiver_id: this.chatPartnerId, message: this.newMessage });
                    this.messages.push(res.data.message); this.newMessage = ''; this.scrollToBottom();
                },
                scrollToBottom() { this.$nextTick(() => { if(this.$refs.msgContainer) this.$refs.msgContainer.scrollTop = 99999; }); }
            }
        }

        function contactsListComponent() {
            return {
                contacts: [], initialLoaded: false,
                init() { this.load(); window.addEventListener('contacts-updated', () => this.load()); setInterval(() => this.load(), 60000); },
                async load() { const res = await window.axios.get('/chat/contacts'); this.contacts = res.data.contacts; this.initialLoaded = true; },
                formatLastSeen(d) { 
                    if(!d) return 'давно'; const diff = Math.floor((new Date() - new Date(d)) / 1000); 
                    if(diff < 60) return 'только что'; if(diff < 3600) return Math.floor(diff/60) + 'м назад'; 
                    if(diff < 86400) return Math.floor(diff/3600) + 'ч назад'; return new Date(d).toLocaleDateString(); 
                },
                callPartner(id, name) { if(confirm(`Позвонить ${name}?`)) window.dispatchEvent(new CustomEvent('start-direct-call', { detail: {id, name} })); }
            }
        }
    </script>
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>