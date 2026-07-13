<x-app-layout>
    <div class="h-[calc(100svh-80px)] bg-[#050505] flex flex-col overflow-hidden text-white font-sans relative" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- HEADER -->
        <div class="p-4 md:p-6 flex justify-between items-center bg-black/60 backdrop-blur-2xl border-b border-white/5 z-30 shrink-0">
            <div class="flex items-center gap-3 md:gap-5">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shrink-0">🏠</div>
                <div class="min-w-0">
                    <h1 class="text-[10px] md:text-xs font-black uppercase tracking-[0.2em] truncate">{{ $room->title }}</h1>
                    <p class="text-[8px] md:text-[10px] font-bold text-gray-500 uppercase mt-1">
                        <span x-text="currentCount" class="text-indigo-400 font-black"></span> / 6 online
                    </p>
                </div>
            </div>
            <button @click="copyLink()" class="px-3 py-2 md:px-5 md:py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[8px] md:text-[9px] font-black uppercase tracking-widest border border-white/10 transition-all shrink-0">🔗 Copy Link</button>
        </div>

        <!-- GRID ВИДЕО -->
        <div class="flex-1 flex items-center justify-center p-2 md:p-4 lg:p-6 overflow-hidden bg-[#050505]">
            <div class="grid gap-2 md:gap-4 w-full h-full max-w-7xl mx-auto place-content-center" :style="gridStyle">
                
                <!-- МОЕ ВИДЕО -->
                <div class="relative aspect-video bg-[#080808] rounded-xl md:rounded-[2rem] overflow-hidden border border-white/5 shadow-2xl transition-all duration-500 w-full h-full">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]" :class="getFilterClass('local')"></video>
                    <div class="absolute bottom-4 left-4 bg-black/60 backdrop-blur-xl px-3 py-1.5 rounded-xl border border-white/10 flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></div>
                        <span class="text-[9px] font-black uppercase tracking-widest">You</span>
                    </div>
                </div>

                <!-- ВИДЕО УЧАСТНИКОВ -->
                <template x-for="peer in peers" :key="peer.id">
                    <div class="relative aspect-video bg-[#080808] rounded-xl md:rounded-[2rem] overflow-hidden border border-white/5 shadow-2xl transition-all duration-500 w-full h-full">
                        <video :id="'video-' + peer.id" autoplay playsinline webkit-playsinline class="w-full h-full object-cover bg-black"></video>
                        <div class="absolute bottom-4 left-4 bg-black/60 backdrop-blur-xl px-3 py-1.5 rounded-xl border border-white/10 flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full" :class="peer.connected ? 'bg-green-500' : 'bg-yellow-500 animate-pulse'"></div>
                            <span class="text-[9px] font-black uppercase tracking-widest" x-text="peer.name"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- УПРАВЛЕНИЕ -->
        <div class="absolute bottom-4 md:bottom-8 left-1/2 -translate-x-1/2 z-[100]" x-data="{ controlsOpen: true }">
            <div class="flex items-center gap-2 p-1 md:p-2 bg-[#121212]/95 backdrop-blur-3xl border border-white/10 rounded-full shadow-2xl">
                <button @click="controlsOpen = !controlsOpen" class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white/5 flex items-center justify-center transition-all">
                    <span class="text-[10px]" x-text="controlsOpen ? '▼' : '⚡'"></span>
                </button>
                <div x-show="controlsOpen" x-transition class="flex items-center gap-1 md:gap-2 pr-1 md:pr-2">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center text-lg transition-colors">
                        <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                    </button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center text-lg transition-colors">
                        <span x-text="camEnabled ? '📷' : '🚫'"></span>
                    </button>
                    <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-indigo-600' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center text-lg">
                        <span>📺</span>
                    </button>
                    <div class="w-px h-6 bg-white/10 mx-1"></div>
                    <a href="{{ route('rooms.index') }}" class="bg-red-600 text-white px-4 md:px-8 py-2 md:py-3.5 rounded-full font-black text-[9px] md:text-[10px] uppercase tracking-widest transition-all">Leave</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function groupRoomComponent(roomUuid, myId, myName) {
            return {
                peers: [], 
                currentCount: 1,
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

                get gridStyle() {
                    const count = this.peers.length + 1;
                    const isMobile = window.innerWidth < 768;
                    if (isMobile) {
                        return `grid-template-columns: 1fr; grid-template-rows: repeat(${count}, 1fr); height: 100%;`;
                    }
                    if (count === 1) return `grid-template-columns: 1fr; max-width: 800px; width: 100%;`;
                    return `grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); width: 100%;`;
                },

                async init() {
                    const self = this;
                    await this.initMedia();
                    
                    const channel = window.Echo.join(`room.${roomUuid}`);

                    const sync = (count) => {
                        this.currentCount = count;
                        window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {});
                    };

                    channel.here(users => {
                        sync(users.length);
                        users.forEach(u => { if (u.id !== myId) self.initiateConnection(u.id, u.name, myId > u.id); });
                    }).joining(u => {
                        self.initiateConnection(u.id, u.name, myId > u.id);
                        sync(self.peers.length + 1);
                    }).leaving(u => {
                        self.removePeer(u.id);
                        sync(self.peers.length + 1);
                    });

                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) self.handleSignal(e.data);
                    });

                    // Heartbeat
                    setInterval(() => {
                        if (this.currentCount > 0) sync(this.peers.length + 1);
                    }, 20000);
                },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ 
                            video: { width: 640, height: 360, frameRate: 15 }, 
                            audio: true 
                        });
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch(e) { console.error("Camera Error", e); }
                },

                async initiateConnection(partnerId, partnerName, isInitiator) {
                    if (this.peers.find(p => p.id === partnerId)) return;
                    
                    const self = this;
                    const pc = new RTCPeerConnection(this.rtcConfig);
                    const peerObj = { id: partnerId, name: partnerName, pc: pc, iceQueue: [], connected: false };
                    this.peers.push(peerObj);

                    this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));

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
                        try {
                            const offer = await pc.createOffer();
                            await pc.setLocalDescription(offer);
                            this.sendSignal(partnerId, { type: 'offer', sdp: pc.localDescription.sdp });
                        } catch(e) { console.error("Offer Error", e); }
                    }
                },

                async handleSignal(data) {
                    let peer = this.peers.find(p => p.id === data.from);
                    if (!peer && data.type === 'offer') {
                        await this.initiateConnection(data.from, 'User ' + data.from, false);
                        peer = this.peers.find(p => p.id === data.from);
                    }
                    if (!peer) return;

                    try {
                        if (data.type === 'offer') {
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(data.sdp) }));
                            const answer = await peer.pc.createAnswer();
                            await peer.pc.setLocalDescription(answer);
                            this.sendSignal(data.from, { type: 'answer', sdp: peer.pc.localDescription.sdp });
                            while(peer.iceQueue.length) await peer.pc.addIceCandidate(peer.iceQueue.shift());
                        } else if (data.type === 'answer') {
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(data.sdp) }));
                            while(peer.iceQueue.length) await peer.pc.addIceCandidate(peer.iceQueue.shift());
                        } else if (data.type === 'ice') {
                            const cand = new RTCIceCandidate(data.candidate);
                            if (peer.pc.remoteDescription && peer.pc.remoteDescription.type) await peer.pc.addIceCandidate(cand);
                            else peer.iceQueue.push(cand);
                        }
                    } catch(e) { console.error("Signal Error", e); }
                },

                normalizeSdp(sdp) {
                    if (typeof sdp !== 'string') return sdp;
                    return sdp.trim().split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n';
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

                copyLink() { 
                    navigator.clipboard.writeText(window.location.href); 
                    window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Link copied', type:'success'}})); 
                }
            }
        }
    </script>
</x-app-layout>