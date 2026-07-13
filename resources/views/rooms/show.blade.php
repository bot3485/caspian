<x-app-layout>
    <div class="h-[calc(100svh-80px)] bg-[#050505] flex flex-col overflow-hidden text-white font-sans relative" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- HEADER -->
        <div class="p-4 md:p-6 flex justify-between items-center bg-black/60 backdrop-blur-2xl border-b border-white/5 z-30 shrink-0">
            <div class="flex items-center gap-3 md:gap-5">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shrink-0">🏠</div>
                <div class="min-w-0">
                    <h1 class="text-xs md:text-sm font-black uppercase tracking-[0.2em] truncate">{{ $room->title }}</h1>
                    <p class="text-[8px] md:text-[10px] font-bold text-gray-500 uppercase mt-1">
                        <span x-text="peers.length + 1" class="text-indigo-400 font-black"></span> / 6 online
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="copyLink()" class="px-3 py-2 md:px-5 md:py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[8px] md:text-[9px] font-black uppercase tracking-widest border border-white/10 transition-all shrink-0">🔗 Copy Link</button>
            </div>
        </div>

        <!-- SMART VIDEO GRID -->
        <div class="flex-1 relative p-2 md:p-4 lg:p-6 overflow-hidden bg-[#020202]">
            <div class="w-full h-full grid gap-2 md:gap-4 transition-all duration-500 items-center justify-center mx-auto"
                 :class="getGridClass()">
                
                <!-- MY VIDEO (Always first in the grid) -->
                <div class="relative w-full h-full bg-[#080808] rounded-2xl md:rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl group">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]" :class="getFilterClass('local')"></video>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="absolute bottom-4 left-4 flex items-center gap-2 bg-black/40 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></div>
                        <span class="text-[9px] font-black uppercase tracking-widest">You (Me)</span>
                    </div>
                    <div x-show="!micEnabled" class="absolute top-4 right-4 text-red-500 bg-black/40 p-2 rounded-lg">🔇</div>
                </div>

                <!-- PEERS VIDEOS -->
                <template x-for="peer in peers" :key="peer.id">
                    <div class="relative w-full h-full bg-[#080808] rounded-2xl md:rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl group">
                        <video :id="'video-' + peer.id" autoplay playsinline webkit-playsinline class="w-full h-full object-cover bg-black"></video>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="absolute bottom-4 left-4 flex items-center gap-2 bg-black/40 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10">
                            <div class="w-1.5 h-1.5 rounded-full" :class="peer.connected ? 'bg-green-500' : 'bg-yellow-500 animate-pulse'"></div>
                            <span class="text-[9px] font-black uppercase tracking-widest" x-text="peer.name"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- CONTROLS -->
        <div class="p-6 md:p-10 flex justify-center items-center bg-transparent z-50 pointer-events-none shrink-0">
            <div class="pointer-events-auto flex items-center gap-3 p-2 bg-[#121212]/90 backdrop-blur-3xl border border-white/10 rounded-full shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white'" class="w-12 h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center text-xl transition-all hover:scale-105 active:scale-95">
                    <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                </button>
                <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white'" class="w-12 h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center text-xl transition-all hover:scale-105 active:scale-95">
                    <span x-text="camEnabled ? '📷' : '🚫'"></span>
                </button>
                <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-indigo-600' : 'bg-white/5'" class="w-12 h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center text-xl transition-all hover:scale-105 active:scale-95">
                    <span>📺</span>
                </button>
                <div class="w-px h-8 bg-white/10 mx-2"></div>
                <a href="{{ route('rooms.index') }}" class="bg-red-600 hover:bg-red-700 text-white px-6 md:px-10 py-3 md:py-4 rounded-full font-black text-[10px] uppercase tracking-[0.2em] transition-all shadow-xl shadow-red-600/20 active:scale-95">Leave Space</a>
            </div>
        </div>
    </div>

    <script>
        function groupRoomComponent(roomUuid, myId, myName) {
            return {
                peers: [], 
                localStream: null, 
                screenStream: null,
                micEnabled: true, 
                camEnabled: true, 
                isScreenSharing: false,
                rtcConfig: { 
                    iceServers: @js(config('webrtc.ice_servers')), 
                    bundlePolicy: "balanced",
                    iceCandidatePoolSize: 10
                },

                getGridClass() {
                    const count = this.peers.length + 1;
                    const isMobile = window.innerWidth < 768;
                    
                    if (isMobile) return 'grid-cols-1 grid-rows-' + count;

                    if (count === 1) return 'grid-cols-1 max-w-4xl';
                    if (count === 2) return 'grid-cols-2 max-w-6xl';
                    if (count <= 4) return 'grid-cols-2 grid-rows-2 max-w-6xl';
                    return 'grid-cols-3 grid-rows-2 max-w-7xl';
                },

                async init() {
                    const self = this;
                    await this.initMedia();
                    
                    const channel = window.Echo.join(`room.${roomUuid}`);

                    channel.here(users => {
                        users.forEach(u => { 
                            if (u.id !== myId) self.initiateConnection(u.id, u.name, true); 
                        });
                        this.syncOccupancy(users.length);
                    }).joining(u => {
                        // Тот кто заходит, НЕ инициирует. Инициируют те, кто УЖЕ в комнате.
                        // Это классическая схема для Presence каналов.
                        self.syncOccupancy(self.peers.length + 2);
                        window.dispatchEvent(new CustomEvent('toast', {detail:{msg: u.name + ' joined'}}));
                    }).leaving(u => {
                        self.removePeer(u.id);
                        self.syncOccupancy(self.peers.length + 1);
                    });

                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) self.handleSignal(e.data);
                    });
                },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ 
                            video: { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { max: 30 } }, 
                            audio: true 
                        });
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch(e) { 
                        window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Camera Error', type:'error'}}));
                    }
                },

                async initiateConnection(partnerId, partnerName, isInitiator) {
                    if (this.peers.find(p => p.id === partnerId)) return;
                    
                    const self = this;
                    const pc = new RTCPeerConnection(this.rtcConfig);
                    const peerObj = { id: partnerId, name: partnerName, pc: pc, iceQueue: [], connected: false };
                    this.peers.push(peerObj);

                    if (this.localStream) {
                        this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));
                    }

                    pc.onicecandidate = e => { 
                        if (e.candidate) self.sendSignal(partnerId, { type: 'ice', candidate: e.candidate }); 
                    };

                    pc.ontrack = e => { 
                        this.$nextTick(() => {
                            const v = document.getElementById('video-' + partnerId);
                            if (v) {
                                v.srcObject = e.streams[0];
                                v.onloadedmetadata = () => v.play().catch(console.error);
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
    // Laravel Echo может оборачивать данные в .data, проверяем это
    const signal = data.type ? data : data.data; 
    
    let peer = this.peers.find(p => p.id === signal.from);
    
    // Если получили Offer от того, кого еще нет в списке - создаем соединение
    if (!peer && signal.type === 'offer') {
        await this.initiateConnection(signal.from, 'User ' + signal.from, false);
        peer = this.peers.find(p => p.id === signal.from);
    }
    
    if (!peer) return;

    try {
        if (signal.type === 'offer') {
            const description = new RTCSessionDescription({ 
                type: 'offer', 
                sdp: this.normalizeSdp(signal.sdp) 
            });
            await peer.pc.setRemoteDescription(description);
            
            const answer = await peer.pc.createAnswer();
            await peer.pc.setLocalDescription(answer);
            
            this.sendSignal(signal.from, { 
                type: 'answer', 
                sdp: peer.pc.localDescription.sdp 
            });
            
            // Обрабатываем накопленные ICE кандидаты
            while(peer.iceQueue.length) {
                await peer.pc.addIceCandidate(peer.iceQueue.shift()).catch(e => {});
            }
        } 
        else if (signal.type === 'answer') {
            const description = new RTCSessionDescription({ 
                type: 'answer', 
                sdp: this.normalizeSdp(signal.sdp) 
            });
            await peer.pc.setRemoteDescription(description);
            
            while(peer.iceQueue.length) {
                await peer.pc.addIceCandidate(peer.iceQueue.shift()).catch(e => {});
            }
        } 
        else if (signal.type === 'ice') {
            const candidate = new RTCIceCandidate(signal.candidate);
            // Если описание еще не установлено, кладем в очередь
            if (peer.pc.remoteDescription && peer.pc.remoteDescription.type) {
                await peer.pc.addIceCandidate(candidate).catch(e => {});
            } else {
                peer.iceQueue.push(candidate);
            }
        }
    } catch(e) { 
        console.error("WebRTC Error:", e); 
    }
},

normalizeSdp(sdp) {
    if (!sdp) return '';
    // WebRTC требует строго CRLF (\r\n) в конце каждой строки.
    // Убираем возможные двойные переносы и нормализуем разрывы строк.
    return sdp.split('\n')
              .map(line => line.trim())
              .filter(line => line.length > 0)
              .join('\r\n') + '\r\n';
},

                sendSignal(to, payload) { 
                    window.axios.post('/chat/signal', { 
                        partnerId: to, 
                        data: { ...payload, from: myId, roomUuid: roomUuid } 
                    }); 
                },

                removePeer(id) {
                    const p = this.peers.find(x => x.id === id);
                    if (p) { p.pc.close(); this.peers = this.peers.filter(x => x.id !== id); }
                },

                syncOccupancy(count) {
                    window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {});
                },

                toggleMic() { 
                    this.micEnabled = !this.micEnabled; 
                    if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; 
                },
                
                toggleCam() { 
                    this.camEnabled = !this.camEnabled; 
                    if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; 
                },
                
                async toggleScreenShare() {
                    if (this.isScreenSharing) {
                        this.isScreenSharing = false;
                        this.screenStream.getTracks().forEach(t => t.stop());
                        this.replaceVideoTrack(this.localStream.getVideoTracks()[0]);
                        this.$refs.localVideo.srcObject = this.localStream;
                    } else {
                        try {
                            this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                            this.isScreenSharing = true;
                            const screenTrack = this.screenStream.getVideoTracks()[0];
                            this.replaceVideoTrack(screenTrack);
                            this.$refs.localVideo.srcObject = this.screenStream;
                            screenTrack.onended = () => this.toggleScreenShare();
                        } catch (e) {}
                    }
                },

                replaceVideoTrack(newTrack) {
                    this.peers.forEach(peer => {
                        const sender = peer.pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) sender.replaceTrack(newTrack);
                    });
                },

                getFilterClass(target) { return ''; },

                copyLink() { 
                    navigator.clipboard.writeText(window.location.href); 
                    window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Link copied', type:'success'}})); 
                }
            }
        }
    </script>
</x-app-layout>