<x-app-layout>
    <!-- 
      Главный контейнер: ЖЕСТКАЯ БЛОКИРОВКА СКРОЛЛА (overflow-hidden)
      Высота строго равна 100dvh минус высота основного nav.
    -->
    <div class="h-[calc(100dvh-80px)] w-full bg-[#020203] flex flex-col overflow-hidden text-white font-sans relative" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')"
         @resize.window="windowWidth = window.innerWidth">
        
        <!-- АТМОСФЕРНЫЙ ФОН КИНОТЕАТРА -->
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] bg-brand-indigo/10 rounded-full blur-[140px] pointer-events-none mix-blend-screen transition-all duration-1000" :class="focusedId ? 'opacity-100 scale-110' : 'opacity-70'"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-purple-900/10 rounded-full blur-[150px] pointer-events-none mix-blend-screen"></div>

        <!-- HEADER (Независимый слой сверху) -->
        <div class="absolute top-0 left-0 right-0 z-[110] p-4 pointer-events-none flex justify-between items-start h-[70px]">
            <div class="pointer-events-auto flex items-center gap-3 bg-[#0a0a0a]/80 backdrop-blur-2xl px-4 py-2 rounded-2xl border border-white/[0.08] shadow-[0_10px_30px_rgba(0,0,0,0.5)]">
                <div class="w-8 h-8 bg-gradient-to-br from-brand-indigo/20 to-purple-600/20 rounded-xl flex items-center justify-center text-sm border border-white/10">🛸</div>
                <div class="min-w-0 pr-2">
                    <h1 class="text-[10px] md:text-xs font-black uppercase tracking-[0.2em] text-white/95 truncate">{{ $room->title }}</h1>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                        <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Live: <span x-text="currentCount" class="text-white font-black"></span>/6</p>
                    </div>
                </div>
            </div>
            <div class="pointer-events-auto">
                <button @click="copyLink()" class="bg-[#0a0a0a]/80 backdrop-blur-2xl p-3 md:px-5 md:py-2.5 rounded-2xl hover:bg-white hover:text-black transition-all group flex items-center gap-2 shadow-xl border border-white/[0.08]">
                    <span class="text-sm">🔗</span>
                    <span class="hidden md:block text-[9px] font-black uppercase tracking-[0.2em]">{{ __('rooms.Copy_Link') }}</span>
                </button>
            </div>
        </div>

        <!-- 
          ИДЕАЛЬНАЯ СЕТКА: АБСОЛЮТНАЯ ИЗОЛЯЦИЯ
          Этот контейнер начинается строго ПОД хедером (top: 80px) 
          и заканчивается строго НАД кнопками (bottom: 90px).
          Всё, что внутри, будет сжиматься, но никогда не создаст скролл.
        -->
        <div class="absolute top-[80px] bottom-[90px] left-2 right-2 md:left-6 md:right-6 flex flex-wrap items-center justify-center gap-2 md:gap-3 z-10 overflow-hidden transition-all duration-700"
             :class="focusedId ? 'content-start' : 'content-center'">
            
            <!-- 1. HOST VIDEO (ВЫ) -->
            <div @click="toggleFocus('me')"
                 class="relative overflow-hidden transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] cursor-pointer group shrink-0 flex items-center justify-center"
                 :class="focusedId === 'me' ? 'border border-white/10 shadow-[0_30px_60px_rgba(0,0,0,0.8),_0_0_60px_rgba(99,102,241,0.15)] z-[50] bg-black' : 'border border-white/[0.05] hover:border-white/30 bg-[#050505] z-10 shadow-xl'"
                 :style="getBoxStyle('me')">
                
                <video x-ref="localVideo" autoplay muted playsinline webkit-playsinline 
                       class="w-full h-full transition-all duration-700" 
                       :class="[isScreenSharing ? 'scale-x-100' : 'scale-x-[-1]', focusedId === 'me' ? 'object-contain' : 'object-cover']"></video>
                
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/5 to-transparent pointer-events-none transition-opacity duration-500" :class="focusedId === 'me' ? 'opacity-0' : 'opacity-80'"></div>
                <div class="absolute bottom-3 left-3 px-3 py-1.5 bg-black/50 backdrop-blur-xl rounded-full border border-white/10 flex items-center shadow-lg transition-all duration-500" :class="focusedId === 'me' ? 'opacity-0 scale-90' : 'opacity-100 scale-100'">
                    <span class="text-[8px] font-black uppercase tracking-widest text-white/90">Вы (Host)</span>
                </div>
            </div>

            <!-- 2. PEERS (УЧАСТНИКИ) -->
            <template x-for="peer in peers" :key="peer.id">
                <div @click="toggleFocus(peer.id)"
                     class="relative overflow-hidden transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] cursor-pointer group shrink-0 flex items-center justify-center"
                     :class="focusedId === peer.id ? 'border border-white/10 shadow-[0_30px_60px_rgba(0,0,0,0.8),_0_0_60px_rgba(99,102,241,0.15)] z-[50] bg-black' : 'border border-white/[0.05] hover:border-white/30 bg-[#050505] z-10 shadow-xl'"
                     :style="getBoxStyle(peer.id)">
                    
                    <video :id="'video-' + peer.id" autoplay playsinline webkit-playsinline 
                           class="w-full h-full transition-all duration-700"
                           :class="focusedId === peer.id ? 'object-contain' : 'object-cover'"></video>
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/5 to-transparent pointer-events-none transition-opacity duration-500" :class="focusedId === peer.id ? 'opacity-0' : 'opacity-80'"></div>
                    <div class="absolute bottom-3 left-3 px-3 py-1.5 bg-black/50 backdrop-blur-xl rounded-full border border-white/10 flex items-center gap-2 shadow-lg transition-all duration-500" :class="focusedId === peer.id ? 'opacity-0 scale-90' : 'opacity-100 scale-100'">
                        <div class="w-1.5 h-1.5 rounded-full" :class="peer.connected ? 'bg-green-400' : 'bg-amber-500 animate-pulse'"></div>
                        <span class="text-[8px] font-black uppercase tracking-widest text-white/90" x-text="peer.name"></span>
                    </div>
                </div>
            </template>

            <!-- 3. ПУСТЫЕ СЛОТЫ (ОЖИДАНИЕ) -->
            <template x-for="i in Math.max(0, 5 - peers.length)" :key="'empty-' + i">
                <div class="relative overflow-hidden border border-white/[0.03] bg-[#050505]/40 backdrop-blur-sm flex flex-col items-center justify-center shrink-0 transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]"
                     :style="getBoxStyle('empty-' + i)">
                     <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-white/[0.02] flex items-center justify-center mb-2 border border-white/[0.05] shadow-inner transition-transform group-hover:scale-110">
                         <svg class="w-3 h-3 md:w-4 md:h-4 text-white/10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                     </div>
                     <span class="text-[7px] md:text-[8px] font-black uppercase tracking-[0.2em] text-white/10">Ожидание</span>
                </div>
            </template>
        </div>

        <!-- ЭЛЕГАНТНАЯ DYNAMIC ISLAND HUD ПАНЕЛЬ (Независимый слой снизу) -->
        <div class="absolute bottom-6 left-0 right-0 px-4 z-[120] flex justify-center pointer-events-none">
            <div class="pointer-events-auto flex items-center bg-[#0a0a0a]/95 backdrop-blur-3xl border border-white/10 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.8)] overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]" 
                 :class="controlsOpen ? 'max-w-[500px] p-1.5' : 'max-w-[60px] p-1.5'"
                 x-data="{ controlsOpen: true }">
                
                <button @click="controlsOpen = !controlsOpen" 
                        class="w-12 h-12 relative rounded-full bg-white/[0.03] hover:bg-white/15 flex items-center justify-center transition-all duration-500 z-10 shrink-0 shadow-inner"
                        :class="controlsOpen ? 'bg-white/10' : ''">
                    <svg class="w-5 h-5 text-white transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]" :class="controlsOpen ? 'rotate-180' : 'rotate-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                
                <div class="flex items-center transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                     :class="controlsOpen ? 'opacity-100 translate-x-0 w-auto pl-1.5 pr-1.5' : 'opacity-0 -translate-x-10 w-0 px-0'">
                    <div class="flex items-center gap-1.5 w-[max-content]">
                        <button @click="toggleMic()" :class="micEnabled ? 'bg-white/[0.03] text-white hover:bg-white/10' : 'bg-red-500/20 text-red-400 border border-red-500/30 shadow-[0_0_15px_rgba(239,68,68,0.2)]'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all border border-transparent shrink-0">
                            <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                        </button>
                        <button @click="toggleCam()" :class="camEnabled ? 'bg-white/[0.03] text-white hover:bg-white/10' : 'bg-red-500/20 text-red-400 border border-red-500/30 shadow-[0_0_15px_rgba(239,68,68,0.2)]'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all border border-transparent shrink-0">
                            <span x-text="camEnabled ? '📷' : '🚫'"></span>
                        </button>
                        <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-brand-indigo/30 text-white border-brand-indigo/50 shadow-[0_0_15px_rgba(99,102,241,0.4)]' : 'bg-white/[0.03] text-white hover:bg-white/10'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all border border-transparent shrink-0">
                            <span>📺</span>
                        </button>
                        <button @click="settingsOpen = true" class="w-12 h-12 rounded-full bg-white/[0.03] text-white hover:bg-white/10 flex items-center justify-center text-lg transition-all border border-transparent shrink-0">
                            <span>⚙️</span>
                        </button>
                        <div class="w-px h-8 bg-white/10 mx-1 rounded-full shrink-0"></div>
                        <a href="{{ route('rooms.index') }}" class="bg-red-600/10 border border-red-500/20 hover:bg-red-600 text-red-400 hover:text-white px-5 h-12 rounded-full flex items-center justify-center font-black text-[9px] uppercase tracking-[0.2em] transition-all shrink-0">
                            Выход
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- МОДАЛЬНОЕ ОКНО НАСТРОЕК (Поверх всего) -->
        <div x-show="settingsOpen" style="display: none;" class="absolute inset-0 z-[200] flex items-center justify-center px-4 bg-black/60 backdrop-blur-md transition-opacity"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.away="settingsOpen = false" class="w-full max-w-sm bg-[#0a0a0a] border border-white/10 rounded-[2rem] p-6 shadow-[0_25px_50px_rgba(0,0,0,0.8)] transform transition-all">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-black uppercase tracking-widest text-white">Устройства</h3>
                    <button @click="settingsOpen = false" class="text-gray-400 hover:text-white transition-colors">✕</button>
                </div>
                <div class="space-y-5">
                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Микрофон</label>
                        <select x-model="selectedAudio" @change="applyDeviceChanges()" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:ring-1 focus:ring-brand-indigo outline-none cursor-pointer">
                            <template x-for="device in audioDevices" :key="device.deviceId"><option :value="device.deviceId" x-text="device.label" class="bg-[#0a0a0a] text-white"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Камера</label>
                        <select x-model="selectedVideo" @change="applyDeviceChanges()" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:ring-1 focus:ring-brand-indigo outline-none cursor-pointer">
                            <template x-for="device in videoDevices" :key="device.deviceId"><option :value="device.deviceId" x-text="device.label" class="bg-[#0a0a0a] text-white"></option></template>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function groupRoomComponent(roomUuid, myId, myName) {
            return {
                focusedId: null,
                peers: [], 
                currentCount: 0,
                localStream: null, 
                screenStream: null,
                micEnabled: true, 
                camEnabled: true, 
                isScreenSharing: false,
                windowWidth: window.innerWidth,
                
                settingsOpen: false,
                audioDevices: [],
                videoDevices: [],
                selectedAudio: '',
                selectedVideo: '',
                rtcConfig: { iceServers: @js(config('webrtc.ice_servers')), bundlePolicy: "balanced" },

                // МАТЕМАТИКА ЖЕСТКОЙ БЛОКИРОВКИ СКРОЛЛА
                getBoxStyle(id) {
                    const isFocused = this.focusedId === id;
                    const someoneFocused = this.focusedId !== null;
                    const isMobile = this.windowWidth < 768;

                    if (isFocused) {
                        // ЭКРАН КИНОТЕАТРА: Занимает 70% доступной высоты, ширина автоматическая.
                        return `
                            width: 100%; 
                            height: ${isMobile ? '65%' : '70%'}; 
                            order: -1; 
                            margin-bottom: ${isMobile ? '0.5rem' : '1rem'}; 
                            border-radius: ${isMobile ? '1rem' : '2rem'};
                        `;
                    }

                    if (someoneFocused) {
                        // ЗРИТЕЛИ: Оставшиеся 5 элементов (участники + пустые слоты) 
                        // Автоматически строятся в 2 ряда (3+2). 
                        // max-height страхует от вытягивания вниз, aspect-ratio держит 16:9.
                        return `
                            width: calc(33.333% - 8px); 
                            max-width: 240px; 
                            max-height: ${isMobile ? '14%' : '20%'}; 
                            aspect-ratio: 16/9; 
                            order: 1; 
                            border-radius: 0.75rem;
                        `;
                    }

                    // СТАНДАРТНАЯ СЕТКА (Нет фокуса)
                    if (isMobile) {
                        // Мобилки: 2 колонки, 3 ряда. max-height строго < 33%, чтобы не было скролла!
                        return `
                            width: calc(50% - 4px); 
                            max-height: calc(33.333% - 6px); 
                            aspect-ratio: 16/9; 
                            border-radius: 1rem;
                        `;
                    } else {
                        // ПК: 3 колонки, 2 ряда. max-height строго < 50%, чтобы не было скролла!
                        return `
                            width: calc(33.333% - 8px); 
                            max-height: calc(50% - 6px); 
                            aspect-ratio: 16/9; 
                            border-radius: 1.5rem;
                        `;
                    }
                },

                toggleFocus(id) {
                    if (String(id).includes('empty')) return;
                    this.focusedId = (this.focusedId === id) ? null : id;
                },

                // Остальная логика WebRTC (без изменений)
                async init() {
                    const self = this;
                    await this.initMedia();
                    window.addEventListener('beforeunload', () => {
                        const url = `/rooms/${this.roomUuid}/sync-occupancy`;
                        const data = new FormData();
                        data.append('count', Math.max(0, this.currentCount - 1));
                        navigator.sendBeacon(url, data);
                    });
                    const channel = window.Echo.join(`room.${roomUuid}`);
                    channel.here(users => {
                        this.currentCount = users.length;
                        this.syncOccupancy(users.length);
                        users.forEach(u => { if (u.id !== myId) self.initiateConnection(u.id, u.name, true); });
                    }).joining(u => {
                        this.currentCount = channel.subscription.members.count;
                        this.syncOccupancy(this.currentCount);
                        self.initiateConnection(u.id, u.name, true);
                    }).leaving(u => {
                        self.removePeer(u.id);
                        this.currentCount = Math.max(0, channel.subscription.members.count - 1);
                        this.syncOccupancy(this.currentCount);
                    });
                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) self.handleSignal(e.data);
                    });
                    setInterval(() => { if (this.currentCount >= 0) this.syncOccupancy(this.currentCount); }, 20000);
                },

                syncOccupancy(count) { window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {}); },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ video: { width: 1280, height: 720 }, audio: true });
                        this.$refs.localVideo.srcObject = this.localStream;
                        await this.loadDevices();
                    } catch(e) { console.error("Camera denied", e); }
                },

                async loadDevices() {
                    try {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        this.audioDevices = devices.filter(d => d.kind === 'audioinput');
                        this.videoDevices = devices.filter(d => d.kind === 'videoinput');
                        if (this.localStream) {
                            const activeAudio = this.localStream.getAudioTracks()[0];
                            const activeVideo = this.localStream.getVideoTracks()[0];
                            if (activeAudio) this.selectedAudio = activeAudio.getSettings().deviceId;
                            if (activeVideo) this.selectedVideo = activeVideo.getSettings().deviceId;
                        }
                    } catch (e) { console.error("Error loading devices", e); }
                },

                async applyDeviceChanges() {
                    try {
                        if (this.isScreenSharing) await this.toggleScreenShare();
                        const constraints = {
                            audio: this.selectedAudio ? { deviceId: { exact: this.selectedAudio } } : true,
                            video: this.selectedVideo ? { width: 1280, height: 720, deviceId: { exact: this.selectedVideo } } : { width: 1280, height: 720 }
                        };
                        const newStream = await navigator.mediaDevices.getUserMedia(constraints);
                        if (this.localStream) this.localStream.getTracks().forEach(t => t.stop());
                        
                        this.localStream = newStream;
                        this.$refs.localVideo.srcObject = this.localStream;
                        this.localStream.getAudioTracks()[0].enabled = this.micEnabled;
                        this.localStream.getVideoTracks()[0].enabled = this.camEnabled;
                        
                        this.replaceTrack(this.localStream.getVideoTracks()[0], 'video');
                        this.replaceTrack(this.localStream.getAudioTracks()[0], 'audio');
                    } catch (e) { console.error("Error changing devices", e); }
                },

                async initiateConnection(partnerId, partnerName, isInitiator) {
                    if (this.peers.find(p => p.id === partnerId)) return;
                    if (!this.localStream) { setTimeout(() => { this.initiateConnection(partnerId, partnerName, isInitiator); }, 300); return; }

                    const self = this;
                    const pc = new RTCPeerConnection(this.rtcConfig);
                    const peerObj = { id: partnerId, name: partnerName, pc: pc, iceQueue: [], connected: false };
                    this.peers.push(peerObj);
                    
                    this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));
                    pc.onicecandidate = e => { if (e.candidate) self.sendSignal(partnerId, { type: 'ice', candidate: e.candidate }); };
                    
                    pc.ontrack = e => { 
                        this.$nextTick(() => {
                            const v = document.getElementById('video-' + partnerId);
                            if (v) { v.srcObject = e.streams[0]; v.play().catch(()=>{}); }
                        });
                    };
                    
                    pc.oniceconnectionstatechange = () => {
                        const p = self.peers.find(x => x.id === partnerId);
                        if (p) p.connected = (pc.iceConnectionState === 'connected' || pc.iceConnectionState === 'completed');
                    };
                    
                    if (isInitiator) {
                        const offer = await pc.createOffer();
                        await pc.setLocalDescription(offer);
                        this.sendSignal(partnerId, { type: 'offer', sdp: pc.localDescription.sdp });
                    }
                },

                async handleSignal(data) {
                    const signal = data.type ? data : data.data; 
                    const fromId = Number(signal.from);
                    let peer = this.peers.find(p => p.id === fromId);
                    
                    if (!peer && signal.type === 'offer') {
                        await this.initiateConnection(fromId, 'User ' + fromId, false);
                        peer = this.peers.find(p => p.id === fromId);
                    }
                    if (!peer) return;

                    try {
                        if (signal.type === 'offer') {
                            const pc = peer.pc;
                            const offerCollision = (signal.type === "offer") && (pc.signalingState === "have-local-offer" || pc.localDescription);
                            if (offerCollision && Number(myId) >= fromId) return;
                            if (offerCollision && Number(myId) < fromId) await pc.setLocalDescription({ type: "rollback" }).catch(() => {});

                            await pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(signal.sdp) }));
                            const answer = await pc.createAnswer();
                            await pc.setLocalDescription(answer);
                            this.sendSignal(fromId, { type: 'answer', sdp: pc.localDescription.sdp });
                            while(peer.iceQueue.length) { await pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{}); }
                        } else if (signal.type === 'answer') {
                            if (peer.pc.signalingState === "stable") return;
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(signal.sdp) }));
                            while(peer.iceQueue.length) { await peer.pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{}); }
                        } else if (signal.type === 'ice') {
                            const cand = new RTCIceCandidate(signal.candidate);
                            if (peer.pc.remoteDescription && peer.pc.remoteDescription.type) await peer.pc.addIceCandidate(cand).catch(()=>{});
                            else peer.iceQueue.push(cand);
                        }
                    } catch(e) { console.error("Signal Error", e); }
                },

                normalizeSdp(sdp) { return sdp ? sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ''; },
                sendSignal(to, payload) { window.axios.post('/chat/signal', { partnerId: to, data: { ...payload, from: myId, roomUuid: roomUuid } }); },
                removePeer(id) {
                    const p = this.peers.find(x => x.id === id);
                    if (p) { p.pc.close(); this.peers = this.peers.filter(x => x.id !== id); }
                },

                toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
                toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
                
                async toggleScreenShare() {
                    if (this.isScreenSharing) {
                        this.isScreenSharing = false;
                        this.screenStream.getTracks().forEach(t => t.stop());
                        this.replaceTrack(this.localStream.getVideoTracks()[0], 'video');
                        this.$refs.localVideo.srcObject = this.localStream;
                    } else {
                        this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                        this.isScreenSharing = true;
                        const track = this.screenStream.getVideoTracks()[0];
                        this.replaceTrack(track, 'video');
                        this.$refs.localVideo.srcObject = this.screenStream;
                        track.onended = () => this.toggleScreenShare();
                    }
                },

                replaceTrack(newTrack, kind = 'video') {
                    this.peers.forEach(p => {
                        const s = p.pc.getSenders().find(s => s.track?.kind === kind);
                        if (s && newTrack) s.replaceTrack(newTrack);
                    });
                },

                copyLink() { 
                    navigator.clipboard.writeText(window.location.href); 
                    window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'{{ __('rooms.Link_Captured') }}'}})); 
                }
            }
        }
    </script>
</x-app-layout>