<x-app-layout>
    <div class="py-6 bg-gray-50 min-h-screen" 
         x-data="chatApp()" 
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
                                <button @click="toggleMic()" :class="micEnabled ? 'bg-gray-900/80' : 'bg-red-600'" class="p-3 rounded-2xl border border-gray-700 text-white">
                                    <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                                </button>
                                <button @click="toggleCam()" :class="camEnabled ? 'bg-gray-900/80' : 'bg-red-600'" class="p-3 rounded-2xl border border-gray-700 text-white">
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
                            
                            <!-- Кнопки Безопасности -->
                            <div x-show="isInCall" class="absolute top-4 left-4 flex gap-2 z-20">
                                <button @click="isBlurred = !isBlurred" :class="isBlurred ? 'bg-amber-500' : 'bg-black/40'" class="p-3 rounded-2xl backdrop-blur-md border border-white/10 text-white" title="Скрыть изображение">🛡️</button>
                                <button @click="showReportModal = true" class="bg-red-600/80 hover:bg-red-600 p-3 rounded-2xl backdrop-blur-md border border-white/10 text-white" title="Пожаловаться">🚩</button>
                            </div>

                            <!-- Окно жалобы -->
                            <div x-show="showReportModal" x-transition x-cloak class="absolute inset-0 bg-black/90 backdrop-blur-xl z-40 flex items-center justify-center p-8">
                                <div class="text-center w-full max-w-xs">
                                    <h4 class="text-white font-black text-sm uppercase mb-6 tracking-tighter">На что жалуемся?</h4>
                                    <div class="space-y-2">
                                        <button @click="reportPartner('nudity')" class="w-full bg-white/10 hover:bg-white/20 text-white py-3 rounded-2xl text-xs font-bold">Непотребство</button>
                                        <button @click="reportPartner('harassment')" class="w-full bg-white/10 hover:bg-white/20 text-white py-3 rounded-2xl text-xs font-bold">Оскорбления</button>
                                        <button @click="reportPartner('spam')" class="w-full bg-white/10 hover:bg-white/20 text-white py-3 rounded-2xl text-xs font-bold">Реклама / Спам</button>
                                        <button @click="showReportModal = false" class="block w-full mt-4 text-gray-500 text-[10px] font-black uppercase tracking-widest">Отмена</button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="!isInCall" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900">
                                <div class="w-16 h-16 border-4 border-t-indigo-500 border-gray-800 rounded-full animate-spin mb-4" x-show="state === 'searching'"></div>
                                <span class="text-gray-500 font-bold uppercase text-[10px] tracking-widest" x-text="state === 'searching' ? 'Ищем пару...' : 'Камера выключена'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col justify-between min-h-[250px]">
                            <div>
                                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Статус</h3>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 mb-4">
                                    <div class="text-gray-800 font-black text-sm uppercase" x-html="statusHtml"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <button x-show="state === 'idle'" @click="startSearch()" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black shadow-lg text-xs">НАЧАТЬ ПОИСК</button>
                                <button x-show="state === 'connected' && !isDirectCall" @click="startSearch()" class="w-full bg-gray-900 text-white py-4 rounded-2xl font-black text-xs tracking-widest">СЛЕДУЮЩИЙ ➔</button>
                                <button x-show="isInCall" @click="hangUp()" class="w-full bg-red-100 text-red-600 py-4 rounded-2xl font-black text-xs">ЗАВЕРШИТЬ 📞</button>
                                <button x-show="state === 'searching'" @click="stopSearch()" class="w-full bg-red-50 text-red-500 py-4 rounded-2xl font-black text-xs">ОТМЕНА</button>
                            </div>
                        </div>

                        <!-- ЧАТ РУЛЕТКИ -->
                        <div x-show="state === 'connected' && !isDirectCall" class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 md:col-span-2 flex flex-col h-[280px] overflow-hidden">
                            <div class="p-4 border-b border-gray-50 bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">Быстрый чат</div>
                            <div class="flex-1 overflow-y-auto p-4 space-y-2" x-ref="rouletteChat">
                                <template x-for="msg in rouletteMessages">
                                    <div :class="msg.isMe ? 'bg-indigo-600 text-white ml-auto rounded-l-2xl rounded-tr-2xl' : 'bg-gray-100 text-gray-800 mr-auto rounded-r-2xl rounded-tl-2xl'" 
                                         class="p-3 text-xs font-bold max-w-[80%]" x-text="msg.text"></div>
                                </template>
                            </div>
                            <div class="p-3 bg-gray-50 flex gap-2">
                                <input type="text" x-model="rouletteInput" @keyup.enter="sendRouletteMsg()" placeholder="Текст..." class="flex-1 bg-white border-none rounded-xl px-4 text-xs font-bold">
                                <button @click="sendRouletteMsg()" class="bg-indigo-600 text-white p-3 rounded-xl">➔</button>
                            </div>
                        </div>

                        <!-- МЕССЕНДЖЕР -->
                        <div x-show="messengerOpen" x-data="messengerComponent({{ auth()->id() }})" 
                             class="bg-white rounded-[2.5rem] shadow-xl border border-indigo-100 md:col-span-2 flex flex-col h-[280px] overflow-hidden">
                            <div class="p-4 border-b border-indigo-50 bg-indigo-50/30 flex items-center justify-between">
                                <span class="text-xs font-black text-gray-800" x-text="chatPartnerName"></span>
                                <button @click="$dispatch('close-messenger')" class="text-gray-400 font-bold">✕</button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="msgContainer">
                                <template x-for="m in messages">
                                    <div :class="m.sender_id === {{ auth()->id() }} ? 'bg-indigo-500 text-white ml-auto' : 'bg-gray-100 text-gray-800'" 
                                         class="p-3 rounded-2xl text-xs font-bold max-w-[85%]" x-text="m.message"></div>
                                </template>
                            </div>
                            <div class="p-3 bg-white border-t border-gray-100 flex gap-2">
                                <input type="text" x-model="newMessage" @keyup.enter="send()" placeholder="Текст..." class="flex-1 bg-gray-50 border-none rounded-xl text-xs font-bold">
                                <button @click="send()" class="bg-indigo-600 text-white px-5 rounded-xl text-xs font-black">SEND</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- КОНТАКТЫ -->
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col h-[750px]" x-data="contactsListComponent()">
                    <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6">Список друзей</h2>
                    <div class="flex-1 overflow-y-auto space-y-3 scrollbar-hide">
                        <template x-for="c in contacts" :key="c.id">
                            <div @click="$dispatch('open-chat', {id: c.id, name: c.name})" 
                                 class="p-4 bg-white rounded-3xl border border-gray-50 flex items-center justify-between hover:border-indigo-200 transition-all cursor-pointer group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-50 rounded-2xl flex items-center justify-center text-sm font-black text-indigo-400" x-text="c.name[0]"></div>
                                    <div><div class="text-xs font-black text-gray-800" x-text="c.name"></div><div class="text-[9px] font-bold text-gray-400 uppercase" x-text="isOnline(c.id) ? 'Online' : 'Offline'"></div></div>
                                </div>
                                <button x-show="isOnline(c.id)" @click.stop="callPartner(c.id, c.name)" class="p-2.5 text-green-600 opacity-0 group-hover:opacity-100">📞</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')) };

        function chatApp() {
            return {
                messengerOpen: false, onlineUserIds: new Set(),
                init() {
                    window.Echo.join('online-status')
                        .here(users => { users.forEach(u => this.onlineUserIds.add(u.id)); this.$dispatch('contacts-updated'); })
                        .joining(u => { this.onlineUserIds.add(u.id); this.$dispatch('contacts-updated'); })
                        .leaving(u => { this.onlineUserIds.delete(u.id); this.$dispatch('contacts-updated'); });
                },
                openMessenger(id, name) { this.messengerOpen = true; this.$dispatch('load-chat-history', {id, name}); }
            }
        }

        function videoChatComponent(myId) {
            return {
                state: 'idle', statusHtml: 'Готов', isInCall: false, isDirectCall: false,
                partnerId: null, pc: null, localStream: null, micEnabled: true, camEnabled: true,
                rouletteMessages: [], rouletteInput: '', iceQueue: [],
                isBlurred: false, showReportModal: false,
                devices: { video: [], audio: [] }, selectedVideoId: localStorage.getItem('selectedVideoId') || '',
                selectedAudioId: localStorage.getItem('selectedAudioId') || '', showSettings: false,

                async init() {
                    await this.initMedia(true); await this.updateDevicesList();
                    window.Echo.private(`user.${myId}`)
                        .listen('.MatchFoundEvent', (e) => this.onMatchFound(e))
                        .listen('.WebRTCSignalEvent', (e) => this.onSignal(e));
                    window.addEventListener('direct-call', (e) => this.handleIncomingDirectCall(e.detail));
                },
                async updateDevicesList() {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    this.devices.video = devices.filter(d => d.kind === 'videoinput');
                    this.devices.audio = devices.filter(d => d.kind === 'audioinput');
                },
                async initMedia(firstTime = false) {
                    try {
                        const constraints = {
                            video: this.selectedVideoId ? { deviceId: { exact: this.selectedVideoId } } : true,
                            audio: this.selectedAudioId ? { deviceId: { exact: this.selectedAudioId } } : true
                        };
                        const newStream = await navigator.mediaDevices.getUserMedia(constraints);
                        if (!firstTime && this.localStream && this.pc) {
                            const vTrack = newStream.getVideoTracks()[0];
                            const aTrack = newStream.getAudioTracks()[0];
                            this.pc.getSenders().forEach(s => {
                                if(s.track.kind === 'video') s.replaceTrack(vTrack);
                                if(s.track.kind === 'audio') s.replaceTrack(aTrack);
                            });
                        }
                        this.localStream = newStream; this.$refs.localVideo.srcObject = this.localStream;
                    } catch (e) { if(this.selectedVideoId) { this.selectedVideoId = ''; this.initMedia(); } }
                },
                async changeDevice() { localStorage.setItem('selectedVideoId', this.selectedVideoId); localStorage.setItem('selectedAudioId', this.selectedAudioId); await this.initMedia(); },
                sanitizeSdp(sdp) { return sdp ? sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ""; },
                
                async startSearch() {
                    try {
                        this.resetConnection();
                        this.state = 'searching';
                        this.statusHtml = 'Поиск...';
                        await window.axios.post('/chat/search');
                    } catch (err) {
                        if (err.response?.status === 403) {
                            alert(err.response.data.error + " (Завершится " + err.response.data.until + ")");
                            this.resetConnection();
                        }
                    }
                },

                async reportPartner(reason) {
                    if (!this.partnerId) return;
                    await window.axios.post('/report', { reported_id: this.partnerId, reason: reason });
                    alert("Жалоба отправлена. Ищем другого собеседника.");
                    this.showReportModal = false;
                    this.startSearch();
                },

                onMatchFound(e) { this.partnerId = Number(e.partnerId); this.state = 'connected'; this.statusHtml = 'Пара найдена'; if (myId > this.partnerId) setTimeout(() => this.sendOffer(), 1000); },
                async sendOffer() { this.initPC(); const offer = await this.pc.createOffer(); await this.pc.setLocalDescription(offer); this.signal({ type: 'webrtc-offer', sdpType: offer.type, sdpString: offer.sdp }); },
                initPC() {
                    if (this.pc) return;
                    this.pc = new RTCPeerConnection(window.rtcConfig);
                    this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
                    this.pc.ontrack = (e) => { this.$refs.remoteVideo.srcObject = e.streams[0]; this.isInCall = true; this.statusHtml = '<span class="text-green-500">● В ЭФИРЕ</span>'; };
                    this.pc.onicecandidate = (e) => { if (e.candidate) this.signal({ type: 'ice-candidate', candidate: e.candidate }); };
                },
                async onSignal(e) {
                    const msg = e.data;
                    try {
                        if (msg.type === 'peer-ready') return this.sendOffer();
                        if (msg.type === 'webrtc-offer') {
                            this.initPC(); if (this.pc.signalingState !== "stable") return;
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'offer', sdp: this.sanitizeSdp(msg.sdpString)}));
                            const answer = await this.pc.createAnswer(); await this.pc.setLocalDescription(answer);
                            this.signal({ type: 'webrtc-answer', sdpType: answer.type, sdpString: answer.sdp });
                            this.drainIce();
                        }
                        if (msg.type === 'webrtc-answer') {
                            if (this.pc.signalingState !== "have-local-offer") return;
                            await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'answer', sdp: this.sanitizeSdp(msg.sdpString)}));
                            this.drainIce();
                        }
                        if (msg.type === 'ice-candidate') { if (this.pc?.remoteDescription) await this.pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(e=>{}); else this.iceQueue.push(msg.candidate); }
                        if (msg.type === 'roulette-text-msg') { this.rouletteMessages.push({ isMe: false, text: msg.text }); this.$nextTick(() => this.$refs.rouletteChat.scrollTop = 9999); }
                        if (msg.type === 'hang-up') this.resetConnection();
                    } catch(err) { console.warn(err); }
                },
                drainIce() { while(this.iceQueue.length > 0) this.pc.addIceCandidate(new RTCIceCandidate(this.iceQueue.shift())).catch(e=>{}); },
                sendRouletteMsg() { if (!this.rouletteInput || !this.partnerId) return; this.signal({ type: 'roulette-text-msg', text: this.rouletteInput }); this.rouletteMessages.push({ isMe: true, text: this.rouletteInput }); this.rouletteInput = ''; this.$nextTick(() => this.$refs.rouletteChat.scrollTop = 9999); },
                toggleMic() { this.micEnabled = !this.micEnabled; this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
                toggleCam() { this.camEnabled = !this.camEnabled; this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
                signal(data) { if (!this.partnerId) return; window.axios.post('/chat/signal', { partnerId: this.partnerId, data }); },
                resetConnection() { if (this.pc) { this.pc.close(); this.pc = null; } this.partnerId = null; this.isInCall = false; this.isDirectCall = false; this.rouletteMessages = []; this.iceQueue = []; this.state = 'idle'; this.statusHtml = 'Готов'; this.isBlurred = false; if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null; },
                stopSearch() { window.axios.post('/chat/leave'); this.resetConnection(); },
                hangUp() { this.signal({ type: 'hang-up' }); this.resetConnection(); },
                handleIncomingDirectCall(data) {
                    if (this.isInCall) { window.axios.post('/chat/signal', { partnerId: data.callerId, data: { type: 'call-rejected', reason: 'busy' } }); return; }
                    if (confirm(`Звонит ${data.name}. Принять?`)) { this.partnerId = data.callerId; this.isDirectCall = true; this.state = 'connected'; this.initPC(); this.signal({ type: 'peer-ready' }); }
                }
            }
        }

        function messengerComponent(myId) {
            return {
                messages: [], chatPartnerId: null, chatPartnerName: '', newMessage: '',
                init() {
                    window.addEventListener('load-chat-history', async (e) => {
                        this.chatPartnerId = e.detail.id; this.chatPartnerName = e.detail.name;
                        const res = await window.axios.get(`/chat/history/${this.chatPartnerId}`);
                        this.messages = res.data.messages; this.scrollToBottom();
                    });
                    window.Echo.private(`user.${myId}`).listen('.MessageSentEvent', (e) => {
                        if (this.chatPartnerId === e.messageData.sender_id) { this.messages.push(e.messageData); this.scrollToBottom(); }
                    });
                },
                async send() {
                    if (!this.newMessage) return;
                    const res = await window.axios.post('/chat/message/send', { receiver_id: this.chatPartnerId, message: this.newMessage });
                    this.messages.push(res.data.message); this.newMessage = ''; this.scrollToBottom();
                },
                scrollToBottom() { this.$nextTick(() => { if(this.$refs.msgContainer) this.$refs.msgContainer.scrollTop = 99999; }); }
            }
        }

        function contactsListComponent() {
            return {
                contacts: [],
                init() { this.load(); window.addEventListener('contacts-updated', () => this.load()); },
                async load() { const res = await window.axios.get('/chat/contacts'); this.contacts = res.data.contacts; },
                isOnline(id) { return this.$data.onlineUserIds.has(Number(id)); },
                callPartner(id, name) { this.$dispatch('direct-call', { callerId: id, name: name }); }
            }
        }
    </script>
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>