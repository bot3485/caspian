<x-app-layout>
    <div class="h-[calc(100svh-80px)] bg-[#020202] flex flex-col overflow-hidden text-white font-sans relative" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- HEADER: SPACE HUB -->
        <div class="p-4 md:p-6 flex justify-between items-center bg-black/40 backdrop-blur-2xl border-b border-white/[0.05] z-30 shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-brand-indigo/10 rounded-2xl flex items-center justify-center text-2xl shadow-inner border border-brand-indigo/20">🛸</div>
                <div class="min-w-0">
                    <h1 class="text-[10px] md:text-xs font-black uppercase tracking-[0.3em] text-white/90 truncate">{{ $room->title }}</h1>
                    <div class="flex items-center gap-2 mt-1.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_#22c55e]"></div>
                        <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">
                            Live: <span x-text="currentCount" class="text-white font-black"></span><span class="mx-0.5 text-gray-700">/</span>6
                        </p>
                    </div>
                </div>
            </div>
            <button @click="copyLink()" class="px-5 py-2.5 caspian-glass hover:bg-white/10 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] transition-all shrink-0">
                🔗 Share Space
            </button>
        </div>

        <!-- SMART VIDEO GRID -->
        <div class="flex-1 relative p-2 md:p-4 lg:p-6 overflow-hidden bg-[#020202]">
            <div class="w-full h-full grid gap-3 md:gap-6 transition-all duration-500 items-center justify-center mx-auto"
                 :class="getGridClass()">
                
                <!-- HOST VIDEO (YOU) -->
                <div class="relative aspect-video bg-[#050505] rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl transition-all duration-700">
                    <video x-ref="localVideo" 
                           autoplay muted playsinline webkit-playsinline
                           class="w-full h-full object-cover transition-transform duration-500" 
                           :class="isScreenSharing ? 'scale-x-100' : 'scale-x-[-1]'"> 
                    </video>
                    
                    <div class="absolute bottom-6 left-6 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10 flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-brand-indigo animate-pulse"></div>
                        <span class="text-[9px] font-black uppercase tracking-widest" 
                              x-text="isScreenSharing ? 'Broadcasting' : 'You (Host)'"></span>
                    </div>

                    <div class="absolute top-6 right-6 flex gap-2">
                        <div x-show="!micEnabled" class="w-10 h-10 flex items-center justify-center bg-red-600/20 text-red-500 backdrop-blur-md rounded-xl border border-red-500/20">🔇</div>
                    </div>
                </div>

                <!-- PEERS -->
                <template x-for="peer in peers" :key="peer.id">
                    <div class="relative aspect-video bg-[#050505] rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl w-full h-full">
                        <video :id="'video-' + peer.id" autoplay playsinline webkit-playsinline class="w-full h-full object-cover bg-black"></video>
                        <div class="absolute bottom-6 left-6 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10 flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full" :class="peer.connected ? 'bg-green-500 shadow-[0_0_8px_#22c55e]' : 'bg-amber-500 animate-pulse'"></div>
                            <span class="text-[9px] font-black uppercase tracking-widest" x-text="peer.name"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- CONTROLS -->
        <div class="absolute bottom-10 left-0 right-0 px-4 z-[100] flex justify-center" x-data="{ controlsOpen: true }">
            <div class="flex items-center gap-2 p-2 bg-[#121212]/95 backdrop-blur-3xl border border-white/10 rounded-full shadow-2xl">
                <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center transition-all hover:bg-white/10">
                    <span class="text-[10px]" x-text="controlsOpen ? '▼' : '⚡'"></span>
                </button>
                <div x-show="controlsOpen" x-transition class="flex items-center gap-2 pr-2">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg shadow-inner transition-all">
                        <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                    </button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg shadow-inner transition-all">
                        <span x-text="camEnabled ? '📷' : '🚫'"></span>
                    </button>
                    <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-brand-indigo' : 'bg-white/5'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all">
                        <span>📺</span>
                    </button>
                    <div class="w-px h-8 bg-white/10 mx-2"></div>
                    <a href="{{ route('rooms.index') }}" class="bg-red-600 text-white px-8 py-3.5 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-red-700 transition-all">Exit Hub</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function groupRoomComponent(roomUuid, myId, myName) {
            return {
                peers: [], 
                currentCount: 0,
                localStream: null, 
                screenStream: null,
                micEnabled: true, 
                camEnabled: true, 
                isScreenSharing: false,
                rtcConfig: { iceServers: @js(config('webrtc.ice_servers')), bundlePolicy: "balanced" },

                getGridClass() {
                    const count = this.peers.length + 1;
                    if (count === 1) return 'grid-cols-1 max-w-4xl';
                    if (count === 2) return 'grid-cols-1 md:grid-cols-2 max-w-6xl';
                    return 'grid-cols-2 md:grid-cols-3';
                },

                async init() {
                    const self = this;
                    await this.initMedia();
                    
                    const channel = window.Echo.join(`room.${roomUuid}`);

                    channel.here(users => {
                        this.currentCount = users.length;
                        this.syncOccupancy(users.length);
                        users.forEach(u => { if (u.id !== myId) self.initiateConnection(u.id, u.name, true); });
                    }).joining(u => {
                        // Используем системный счетчик участников Reverb для точности
                        this.currentCount = channel.subscription.members.count;
                        this.syncOccupancy(this.currentCount);
                        window.dispatchEvent(new CustomEvent('toast', {detail:{msg: u.name + ' entered the Space'}}));
                    }).leaving(u => {
                        self.removePeer(u.id);
                        // Минус один при выходе
                        this.currentCount = Math.max(0, channel.subscription.members.count - 1);
                        this.syncOccupancy(this.currentCount);
                    });

                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) self.handleSignal(e.data);
                    });

                    // ФИКС ДЛЯ ОБНУЛЕНИЯ: Отправляем Beacon при закрытии вкладки
                    window.addEventListener('beforeunload', () => {
                        if (this.currentCount <= 1) {
                            const data = new FormData();
                            data.append('count', 0);
                            data.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                            navigator.sendBeacon(`/rooms/${roomUuid}/sync-occupancy`, data);
                        }
                    });
                },

                async syncOccupancy(count) {
                    window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {});
                },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ video: { width: 1280, height: 720 }, audio: true });
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch(e) { console.error("Camera denied", e); }
                },

                async initiateConnection(partnerId, partnerName, isInitiator) {
                    if (this.peers.find(p => p.id === partnerId)) return;
                    const self = this;
                    const pc = new RTCPeerConnection(this.rtcConfig);
                    const peerObj = { id: partnerId, name: partnerName, pc: pc, iceQueue: [], connected: false };
                    this.peers.push(peerObj);
                    if (this.localStream) this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));
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
                    let peer = this.peers.find(p => p.id === signal.from);
                    if (!peer && signal.type === 'offer') {
                        await this.initiateConnection(signal.from, 'Instance ' + signal.from, false);
                        peer = this.peers.find(p => p.id === signal.from);
                    }
                    if (!peer) return;
                    try {
                        if (signal.type === 'offer') {
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(signal.sdp) }));
                            const answer = await peer.pc.createAnswer();
                            await peer.pc.setLocalDescription(answer);
                            this.sendSignal(signal.from, { type: 'answer', sdp: peer.pc.localDescription.sdp });
                            while(peer.iceQueue.length) await peer.pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{});
                        } else if (signal.type === 'answer') {
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(signal.sdp) }));
                            while(peer.iceQueue.length) await peer.pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{});
                        } else if (signal.type === 'ice') {
                            const cand = new RTCIceCandidate(signal.candidate);
                            if (peer.pc.remoteDescription) await peer.pc.addIceCandidate(cand).catch(()=>{});
                            else peer.iceQueue.push(cand);
                        }
                    } catch(e) { console.error("Signal Error", e); }
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
                    window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Link captured'}})); 
                }
            }
        }
    </script>
</x-app-layout>