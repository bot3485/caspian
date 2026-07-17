<x-app-layout>
    <!-- 
      Главный контейнер: строго 100dvh минус высота твоей навигации (обычно 64px или 80px).
      Использование dvh (dynamic viewport height) предотвращает баги на мобилках при скролле.
    -->
    <div class="h-[calc(100dvh-80px)] w-full bg-[#030305] flex flex-col overflow-hidden text-white font-sans relative" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')"
         @resize.window="windowWidth = window.innerWidth">
        
        <!-- АТМОСФЕРНЫЙ ФОН -->
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] bg-brand-indigo/10 rounded-full blur-[120px] pointer-events-none mix-blend-screen"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-purple-900/10 rounded-full blur-[150px] pointer-events-none mix-blend-screen"></div>

        <!-- HEADER: FLOATING SPACE HUB (Абсолют, но сетка под него не заедет из-за pt-[90px]) -->
        <div class="absolute top-0 left-0 right-0 z-[110] p-4 pointer-events-none flex justify-between items-start">
            <div class="pointer-events-auto flex items-center gap-3 bg-[#0a0a0a]/80 backdrop-blur-2xl px-4 py-2 rounded-2xl border border-white/[0.08] shadow-[0_10px_30px_rgba(0,0,0,0.5)]">
                <div class="w-8 h-8 bg-gradient-to-br from-brand-indigo/20 to-purple-600/20 rounded-xl flex items-center justify-center text-sm border border-white/10">
                    🛸
                </div>
                <div class="min-w-0 pr-2">
                    <h1 class="text-[10px] md:text-xs font-black uppercase tracking-[0.2em] text-white/95 truncate">{{ $room->title }}</h1>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                        <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">
                            Live: <span x-text="currentCount" class="text-white font-black"></span>/6
                        </p>
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
          САМАЯ ВАЖНАЯ ЧАСТЬ: КОНТЕЙНЕР СЕТКИ
          pt-[90px] защищает от верхнего хедера.
          pb-[100px] защищает от нижних кнопок.
          Это гарантия, что видео всегда внутри видимой зоны.
        -->
        <div class="w-full h-full pt-[90px] pb-[100px] px-2 md:px-6 relative z-10 overflow-y-auto custom-scrollbar flex flex-wrap gap-2 md:gap-4 transition-all duration-500 max-w-[1800px] mx-auto"
             :class="focusedId ? 'content-start justify-center' : 'content-center justify-center'">
            
            <!-- HOST VIDEO (YOU) -->
            <div @click="toggleFocus('me')"
                 class="relative overflow-hidden border transition-all duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] cursor-pointer group bg-[#050505] shadow-xl shrink-0"
                 :class="focusedId === 'me' ? 'border-brand-indigo/50 shadow-[0_0_40px_rgba(99,102,241,0.2)] z-[50] rounded-[2rem]' : 'border-white/[0.05] hover:border-white/20 rounded-[1.5rem] md:rounded-[2rem] z-10'"
                 :style="getBoxStyle('me')">
                
                <!-- 
                  Если в фокусе -> object-contain (показывает ВСЁ, не обрезая, сохраняет пропорции)
                  Если мелкое -> object-cover (чтобы карточки были заполнены)
                -->
                <video x-ref="localVideo" 
                       autoplay muted playsinline webkit-playsinline
                       class="w-full h-full transition-all duration-500" 
                       :class="[
                           isScreenSharing ? 'scale-x-100' : 'scale-x-[-1]',
                           focusedId === 'me' ? 'object-contain bg-black' : 'object-cover bg-[#050505]'
                       ]"> 
                </video>
                
                <!-- Градиент и бейдж прячем, когда окно развернуто, чтобы не перекрывать картинку -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/5 to-transparent pointer-events-none transition-opacity duration-300"
                     :class="focusedId === 'me' ? 'opacity-0' : 'opacity-80'"></div>
                
                <div class="absolute bottom-3 left-3 px-3 py-1.5 bg-black/40 backdrop-blur-xl rounded-full border border-white/10 flex items-center shadow-lg transition-opacity duration-300"
                     :class="focusedId === 'me' ? 'opacity-0' : 'opacity-100'">
                    <span class="text-[8px] font-black uppercase tracking-widest text-white/90">Вы (Host)</span>
                </div>
            </div>

            <!-- PEERS -->
            <template x-for="peer in peers" :key="peer.id">
                <div @click="toggleFocus(peer.id)"
                     class="relative overflow-hidden border transition-all duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] cursor-pointer group bg-[#050505] shadow-xl shrink-0"
                     :class="focusedId === peer.id ? 'border-brand-indigo/50 shadow-[0_0_40px_rgba(99,102,241,0.2)] z-[50] rounded-[2rem]' : 'border-white/[0.05] hover:border-white/20 rounded-[1.5rem] md:rounded-[2rem] z-10'"
                     :style="getBoxStyle(peer.id)">
                    
                    <video :id="'video-' + peer.id" 
                           autoplay playsinline webkit-playsinline 
                           class="w-full h-full transition-all duration-500"
                           :class="focusedId === peer.id ? 'object-contain bg-black' : 'object-cover bg-[#050505]'"></video>
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/5 to-transparent pointer-events-none transition-opacity duration-300"
                         :class="focusedId === peer.id ? 'opacity-0' : 'opacity-80'"></div>
                    
                    <div class="absolute bottom-3 left-3 px-3 py-1.5 bg-black/40 backdrop-blur-xl rounded-full border border-white/10 flex items-center gap-2 shadow-lg transition-opacity duration-300"
                         :class="focusedId === peer.id ? 'opacity-0' : 'opacity-100'">
                        <div class="w-1.5 h-1.5 rounded-full" :class="peer.connected ? 'bg-green-400' : 'bg-amber-500 animate-pulse'"></div>
                        <span class="text-[8px] font-black uppercase tracking-widest text-white/90" x-text="peer.name"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- PREMIUM HUD CONTROLS (Абсолют снизу, сетка под него не попадет из-за pb-[100px]) -->
        <div class="absolute bottom-6 left-0 right-0 px-4 z-[120] flex justify-center pointer-events-none">
            <div class="pointer-events-auto flex items-center gap-1 p-2 bg-[#0a0a0a]/85 backdrop-blur-3xl border border-white/[0.08] rounded-full shadow-[0_20px_50px_rgba(0,0,0,0.8)]" x-data="{ controlsOpen: true }">
                
                <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/[0.03] hover:bg-white/10 flex items-center justify-center transition-transform duration-500" :class="controlsOpen ? 'rotate-180' : ''">
                    <span class="text-[10px] font-black text-gray-400">▼</span>
                </button>
                
                <div x-show="controlsOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-x-4 scale-95" class="flex items-center gap-1.5 pr-2">
                    
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/[0.03] text-white hover:bg-white/10' : 'bg-red-500/20 text-red-400 border border-red-500/30'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all border border-transparent">
                        <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                    </button>
                    
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/[0.03] text-white hover:bg-white/10' : 'bg-red-500/20 text-red-400 border border-red-500/30'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all border border-transparent">
                        <span x-text="camEnabled ? '📷' : '🚫'"></span>
                    </button>
                    
                    <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-brand-indigo/30 text-brand-indigo border border-brand-indigo/50' : 'bg-white/[0.03] text-white hover:bg-white/10'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all border border-transparent">
                        <span>📺</span>
                    </button>
                    
                    <div class="w-px h-8 bg-white/10 mx-1 rounded-full"></div>
                    
                    <a href="{{ route('rooms.index') }}" class="bg-red-600/10 border border-red-500/20 hover:bg-red-600 text-red-400 hover:text-white px-5 h-12 rounded-full flex items-center justify-center font-black text-[9px] uppercase tracking-[0.2em] transition-all">
                        {{ __('rooms.Exit_Room') }}
                    </a>
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
                rtcConfig: { iceServers: @js(config('webrtc.ice_servers')), bundlePolicy: "balanced" },

                startHeartbeat() {
                    this.heartbeatTimer = setInterval(() => {
                        if (this.currentCount >= 0) this.syncOccupancy(this.currentCount);
                    }, 20000);
                },

                // МАТЕМАТИЧЕСКИ ИДЕАЛЬНАЯ СЕТКА
                getBoxStyle(id) {
                    const isFocused = this.focusedId === id;
                    const someoneFocused = this.focusedId !== null;
                    const isMobile = this.windowWidth < 768;
                    const count = this.peers.length + 1; // Общее количество участников

                    // 1. УВЕЛИЧЕННОЕ ОКНО
                    // Берет максимум места по ширине, но оставляет чуть-чуть высоты для миниатюр внизу
                    if (isFocused) {
                        return isMobile
                            ? 'width: 100%; height: calc(100% - 100px); order: -1;' 
                            : 'width: 100%; height: calc(100% - 130px); order: -1;';
                    }

                    // 2. ВСЕ ОСТАЛЬНЫЕ ОКНА (когда какое-то открыто)
                    // Становятся аккуратными миниатюрами и падают вниз
                    if (someoneFocused) {
                        return isMobile
                            ? 'width: calc(33.3% - 6px); height: 90px; order: 1;' 
                            : 'width: 200px; height: 110px; order: 1;';
                    }

                    // 3. СТАНДАРТНАЯ СЕТКА ДЛЯ ВСЕХ (без фокуса)
                    // Идеально делит пространство от 1 до 6 человек.
                    if (isMobile) {
                        if (count === 1) return 'width: 100%; height: 100%;';
                        if (count === 2) return 'width: 100%; height: calc(50% - 4px);';
                        if (count <= 4) return 'width: calc(50% - 4px); height: calc(50% - 4px);';
                        // 5-6 человек
                        return 'width: calc(50% - 4px); height: calc(33.333% - 6px);';
                    } else {
                        if (count === 1) return 'width: 100%; height: 100%; max-width: 1400px;';
                        if (count === 2) return 'width: calc(50% - 8px); height: 100%;';
                        if (count <= 4) return 'width: calc(50% - 8px); height: calc(50% - 8px);';
                        // 5-6 человек
                        return 'width: calc(33.333% - 11px); height: calc(50% - 8px);';
                    }
                },

                toggleFocus(id) {
                    this.focusedId = (this.focusedId === id) ? null : id;
                },

                // Остальной WebRTC код оставляем без изменений...
                // (init, initiateConnection, handleSignal и т.д. такие же как и были)
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
                        setTimeout(() => { this.syncOccupancy(this.currentCount); }, 2000);
                        users.forEach(u => { if (u.id !== myId) self.initiateConnection(u.id, u.name, true); });
                    }).joining(u => {
                        this.currentCount = channel.subscription.members.count;
                        this.syncOccupancy(this.currentCount);
                        window.dispatchEvent(new CustomEvent('toast', {detail:{msg: u.name + ' {{ __('rooms.Joined') }}'}}));
                        self.initiateConnection(u.id, u.name, true);
                    }).leaving(u => {
                        self.removePeer(u.id);
                        this.currentCount = Math.max(0, channel.subscription.members.count - 1);
                        this.syncOccupancy(this.currentCount);
                    });

                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) self.handleSignal(e.data);
                    });
                    this.startHeartbeat();
                },

                async syncOccupancy(count) { window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {}); },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ video: { width: 1280, height: 720 }, audio: true });
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch(e) { console.error("Camera denied", e); }
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
                            const isPolite = Number(myId) < fromId;

                            if (offerCollision) {
                                if (!isPolite) return;
                                await pc.setLocalDescription({ type: "rollback" }).catch(() => {});
                            }

                            await pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(signal.sdp) }));
                            const answer = await pc.createAnswer();
                            await pc.setLocalDescription(answer);
                            
                            this.sendSignal(fromId, { type: 'answer', sdp: pc.localDescription.sdp });
                            
                            while(peer.iceQueue.length) { await pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{}); }
                        } else if (signal.type === 'answer') {
                            const pc = peer.pc;
                            if (pc.signalingState === "stable") return;
                            await pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(signal.sdp) }));
                            while(peer.iceQueue.length) { await pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{}); }
                        } else if (signal.type === 'ice') {
                            const cand = new RTCIceCandidate(signal.candidate);
                            if (peer.pc.remoteDescription && peer.pc.remoteDescription.type) {
                                await peer.pc.addIceCandidate(cand).catch(()=>{});
                            } else {
                                peer.iceQueue.push(cand);
                            }
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
                        this.replaceTrack(this.localStream.getVideoTracks()[0]);
                        this.$refs.localVideo.srcObject = this.localStream;
                    } else {
                        this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                        this.isScreenSharing = true;
                        const track = this.screenStream.getVideoTracks()[0];
                        this.replaceTrack(track);
                        this.$refs.localVideo.srcObject = this.screenStream;
                        track.onended = () => this.toggleScreenShare();
                    }
                },

                replaceTrack(newTrack) {
                    this.peers.forEach(p => {
                        const s = p.pc.getSenders().find(s => s.track?.kind === 'video');
                        if (s) s.replaceTrack(newTrack);
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