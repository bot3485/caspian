<x-app-layout>
    <div class="h-[calc(100svh-80px)] bg-[#020202] flex flex-col overflow-hidden text-white font-sans relative" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- Фоновые лучи для атмосферы космической комнаты -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-brand-indigo/5 rounded-full blur-[100px] pointer-events-none"></div>

        <!-- HEADER: FLOATING SPACE HUB -->
        <div class="absolute top-0 left-0 right-0 z-[110] p-4 md:p-6 pointer-events-none">
            <div class="max-w-[1600px] mx-auto flex justify-between items-start">
                
                <!-- Информация о комнате -->
                <div class="pointer-events-auto flex items-center gap-3.5 bg-black/50 backdrop-blur-md px-4.5 py-2.5 rounded-2xl border border-white/[0.04] shadow-2xl transition-all duration-300">
                    <div class="w-8 h-8 bg-brand-indigo/10 rounded-lg flex items-center justify-center text-base border border-brand-indigo/20">🛸</div>
                    <div class="min-w-0">
                        <h1 class="text-[9px] font-black uppercase tracking-[0.25em] text-white/95 truncate">{{ $room->title }}</h1>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <div class="w-1 h-1 rounded-full bg-green-500 animate-pulse shadow-[0_0_6px_#22c55e]"></div>
                            <p class="text-[7.5px] font-bold text-gray-400 uppercase tracking-widest">
                                Live: <span x-text="currentCount" class="text-white font-black"></span><span class="mx-0.5 text-gray-600">/</span>6
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Кнопка Поделиться -->
                <div class="pointer-events-auto flex items-center">
                    <button @click="copyLink()" 
                            class="bg-black/50 backdrop-blur-md p-3 md:px-5 md:py-2.5 rounded-2xl hover:bg-brand-indigo hover:text-white transition-all duration-300 group flex items-center gap-2 shadow-2xl border border-white/[0.04]">
                        <span class="text-sm">🔗</span>
                        <span class="hidden md:block text-[8px] font-black uppercase tracking-[0.2em] mt-[1px]">{{ __('rooms.Copy_Link') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- SMART ADAPTIVE GRID -->
        <div class="flex-1 relative p-4 overflow-hidden bg-[#020202] flex items-center justify-center">
            <div class="w-full h-full flex flex-wrap gap-4 justify-center items-center content-center max-w-[1600px] mx-auto transition-all duration-500">
                
                <!-- HOST VIDEO (YOU) -->
                <div @click="toggleFocus('me')"
                     class="relative overflow-hidden rounded-[1.75rem] md:rounded-[2.5rem] border transition-all duration-700 cursor-pointer group bg-[#050505] shadow-2xl"
                     :class="focusedId === 'me' ? 'border-brand-indigo ring-4 ring-brand-indigo/15 z-[50]' : 'border-white/[0.03] hover:border-white/15 z-10'"
                     :style="getBoxStyle('me')">
                    
                    <video x-ref="localVideo" 
                           autoplay muted playsinline webkit-playsinline
                           class="w-full h-full object-cover transition-transform duration-700" 
                           :class="isScreenSharing ? 'scale-x-100' : 'scale-x-[-1]'"> 
                    </video>
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent opacity-80"></div>
                    
                    <div class="absolute bottom-4 left-5 flex items-center gap-2">
                        <div class="px-2.5 py-1 bg-brand-indigo/10 backdrop-blur-md rounded-lg border border-brand-indigo/20 flex items-center">
                            <span class="text-[8px] font-black uppercase tracking-widest text-white mt-[1px]">{{ auth()->user()->name }}</span>
                        </div>
                    </div>
                </div>

                <!-- PEERS -->
                <template x-for="peer in peers" :key="peer.id">
                    <div @click="toggleFocus(peer.id)"
                         class="relative overflow-hidden rounded-[1.75rem] md:rounded-[2.5rem] border transition-all duration-700 cursor-pointer group bg-[#050505] shadow-2xl"
                         :class="focusedId === peer.id ? 'border-brand-indigo ring-4 ring-brand-indigo/15 z-[50]' : 'border-white/[0.03] hover:border-white/15 z-10'"
                         :style="getBoxStyle(peer.id)">
                        
                        <video :id="'video-' + peer.id" autoplay playsinline webkit-playsinline class="w-full h-full object-cover bg-black"></video>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent opacity-80"></div>
                        
                        <div class="absolute bottom-4 left-5 px-3 py-1.5 bg-black/40 backdrop-blur-md rounded-lg border border-white/[0.04] flex items-center gap-2">
                            <div class="w-1 h-1 rounded-full" :class="peer.connected ? 'bg-green-500 shadow-[0_0_6px_#22c55e]' : 'bg-amber-500 animate-pulse'"></div>
                            <span class="text-[8px] font-black uppercase tracking-widest text-white/90" x-text="peer.name"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- CONTROLS CONTAINER -->
        <div class="absolute bottom-8 left-0 right-0 px-4 z-[120] flex justify-center pointer-events-none">
            <div class="pointer-events-auto flex items-center gap-2 p-1.5 bg-black/60 backdrop-blur-xl border border-white/[0.05] rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.6)]" x-data="{ controlsOpen: true }">
                <!-- Кнопка сворачивания (Минималистичная точка вместо стрелки) -->
                <button @click="controlsOpen = !controlsOpen" class="w-10 h-10 rounded-xl bg-white/[0.02] hover:bg-white/5 flex items-center justify-center transition-all">
                    <span class="text-[8px] tracking-widest font-black" x-text="controlsOpen ? '▼' : '▲'"></span>
                </button>
                
                <!-- Сами Кнопки с приятным скольжением -->
                <div x-show="controlsOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="flex items-center gap-2 pr-1.5">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/[0.02] text-white hover:bg-white/10' : 'bg-red-600/20 text-red-500 border border-red-500/25'" class="w-10 h-10 rounded-xl flex items-center justify-center text-sm transition-all border border-white/[0.03]">
                        <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                    </button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/[0.02] text-white hover:bg-white/10' : 'bg-red-600/20 text-red-500 border border-red-500/25'" class="w-10 h-10 rounded-xl flex items-center justify-center text-sm transition-all border border-white/[0.03]">
                        <span x-text="camEnabled ? '📷' : '🚫'"></span>
                    </button>
                    <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-brand-indigo/20 text-brand-indigo border-brand-indigo/30' : 'bg-white/[0.02] text-white hover:bg-white/10'" class="w-10 h-10 rounded-xl flex items-center justify-center text-sm transition-all border border-white/[0.03]">
                        <span>📺</span>
                    </button>
                    <div class="w-px h-6 bg-white/10 mx-1"></div>
                    <a href="{{ route('rooms.index') }}" class="bg-red-600/10 border border-red-600/20 hover:bg-red-600 text-red-500 hover:text-white px-5 py-2.5 rounded-xl font-black text-[8px] uppercase tracking-wider transition-all">{{ __('rooms.Exit_Room') }}</a>
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
                rtcConfig: { iceServers: @js(config('webrtc.ice_servers')), bundlePolicy: "balanced" },

                getBoxStyle(id) {
                    const isFocused = this.focusedId === id;
                    const someoneFocused = this.focusedId !== null;
                    const isMobile = window.innerWidth < 768;

                    // 1. ЕСЛИ ОКНО УВЕЛИЧЕНО
                    if (isFocused) {
                        return isMobile 
                            ? 'width: 100%; aspect-ratio: 16/9; z-index: 50;' 
                            : 'width: 90%; max-height: 65vh; aspect-ratio: 16/9; z-index: 50; order: -1;'; 
                    }

                    // 2. ЕСЛИ КТО-ТО ДРУГОЙ В ФОКУСЕ (остальные маленькие)
                    if (someoneFocused) {
                        return isMobile
                            ? 'width: 30%; aspect-ratio: 1/1; opacity: 0.7;'
                            : 'width: 200px; aspect-ratio: 16/9; opacity: 0.8;';
                    }

                    // 3. ОБЫЧНЫЙ РЕЖИМ
                    if (isMobile) {
                        return 'width: 46%; aspect-ratio: 1/1;';
                    }
                    
                    // На ноутбуках - сбалансированный размер
                    const count = this.peers.length + 1;
                    if (count === 1) return 'width: 70%; max-height: 60vh; aspect-ratio: 16/9;';
                    if (count === 2) return 'width: 45%; max-height: 50vh; aspect-ratio: 16/9;';
                    
                    return 'width: 380px; max-height: 40vh; aspect-ratio: 16/9;';
                },

                toggleFocus(id) {
                    this.focusedId = (this.focusedId === id) ? null : id;
                },

                async init() {
                    const self = this;
                    
                    // 1. Ждем ГАРАНТИРОВАННОГО получения медиа-потока
                    await this.initMedia();
                    
                    // 2. Только после этого заходим в комнату сокетов, когда мы готовы слать/принимать потоки
                    const channel = window.Echo.join(`room.${roomUuid}`);
                    
                    channel.here(users => {
                        this.currentCount = users.length;
                        this.syncOccupancy(users.length);
                        
                        // Инициируем соединение с теми, кто уже в комнате
                        users.forEach(u => { 
                            if (u.id !== myId) {
                                self.initiateConnection(u.id, u.name, true); 
                            }
                        });
                    }).joining(u => {
                        this.currentCount = channel.subscription.members.count;
                        this.syncOccupancy(this.currentCount);
                        window.dispatchEvent(new CustomEvent('toast', {detail:{msg: u.name + ' {{ __('rooms.Joined') }}'}}));
                        
                        // Когда заходит новый участник, МЫ (кто уже внутри) инициируем с ним связь
                        self.initiateConnection(u.id, u.name, true);
                    }).leaving(u => {
                        self.removePeer(u.id);
                        this.currentCount = Math.max(0, channel.subscription.members.count - 1);
                        this.syncOccupancy(this.currentCount);
                    });

                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) self.handleSignal(e.data);
                    });
                },

                async syncOccupancy(count) {
                    window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {});
                },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ 
                            video: { width: 1280, height: 720 }, 
                            audio: true 
                        });
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch(e) { console.error("Camera denied", e); }
                },

                async initiateConnection(partnerId, partnerName, isInitiator) {
                    if (this.peers.find(p => p.id === partnerId)) return;
                    
                    // Если локальные медиа еще не готовы, откладываем инициализацию соединения
                    if (!this.localStream) {
                        setTimeout(() => {
                            this.initiateConnection(partnerId, partnerName, isInitiator);
                        }, 300);
                        return;
                    }

                    const self = this;
                    const pc = new RTCPeerConnection(this.rtcConfig);
                    const peerObj = { id: partnerId, name: partnerName, pc: pc, iceQueue: [], connected: false };
                    this.peers.push(peerObj);
                    
                    // Добавляем наши треки в коннект
                    this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));
                    
                    pc.onicecandidate = e => { 
                        if (e.candidate) self.sendSignal(partnerId, { type: 'ice', candidate: e.candidate }); 
                    };
                    
                    pc.ontrack = e => { 
                        this.$nextTick(() => {
                            const v = document.getElementById('video-' + partnerId);
                            if (v) { 
                                v.srcObject = e.streams[0]; 
                                v.play().catch(()=>{}); 
                            }
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
                            
                            // Защита от коллизий (Параллельный Offer)
                            const signalingState = pc.signalingState;
                            const offerCollision = (signal.type === "offer") && 
                                (signalingState === "have-local-offer" || pc.localDescription);
                            
                            // Мы "вежливые", если наш ID меньше ID партнера
                            const isPolite = Number(myId) < fromId;

                            if (offerCollision) {
                                if (!isPolite) {
                                    // Мы "невежливые" — игнорируем встречный offer, наш важнее
                                    console.log(`[WebRTC] Collision detected. Ignoring offer from ${fromId} (We are impolite)`);
                                    return;
                                }
                                // Мы "вежливые" — откатываем свой локальный offer, чтобы принять чужой
                                console.log(`[WebRTC] Collision detected. Rolling back local description for ${fromId} (We are polite)`);
                                await pc.setLocalDescription({ type: "rollback" }).catch(() => {});
                            }

                            await pc.setRemoteDescription(new RTCSessionDescription({ 
                                type: 'offer', 
                                sdp: this.normalizeSdp(signal.sdp) 
                            }));
                            
                            const answer = await pc.createAnswer();
                            await pc.setLocalDescription(answer);
                            
                            this.sendSignal(fromId, { type: 'answer', sdp: pc.localDescription.sdp });
                            
                            while(peer.iceQueue.length) {
                                await pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{});
                            }

                        } else if (signal.type === 'answer') {
                            const pc = peer.pc;
                            
                            // ПРОВЕРКА: Если мы уже в состоянии 'stable', игнорируем запоздавший answer
                            if (pc.signalingState === "stable") {
                                console.warn(`[WebRTC] Received answer from ${fromId} while in stable state. Ignored.`);
                                return;
                            }

                            await pc.setRemoteDescription(new RTCSessionDescription({ 
                                type: 'answer', 
                                sdp: this.normalizeSdp(signal.sdp) 
                            }));
                            
                            while(peer.iceQueue.length) {
                                await pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{});
                            }

                        } else if (signal.type === 'ice') {
                            const cand = new RTCIceCandidate(signal.candidate);
                            if (peer.pc.remoteDescription && peer.pc.remoteDescription.type) {
                                await peer.pc.addIceCandidate(cand).catch(()=>{});
                            } else {
                                peer.iceQueue.push(cand);
                            }
                        }
                    } catch(e) { 
                        console.error("Signal Error", e); 
                    }
                },

                normalizeSdp(sdp) {
                    return sdp ? sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : '';
                },

                sendSignal(to, payload) { 
                    window.axios.post('/chat/signal', { partnerId: to, data: { ...payload, from: myId, roomUuid: roomUuid } }); 
                },

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