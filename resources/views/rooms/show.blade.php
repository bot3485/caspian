<x-app-layout>
    <div class="h-[calc(100svh-80px)] bg-[#050505] flex flex-col overflow-hidden text-white font-sans relative" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- HEADER -->
        <div class="p-4 md:p-6 flex justify-between items-center bg-black/60 backdrop-blur-2xl border-b border-white/5 z-30 shrink-0">
            <div class="flex items-center gap-3 md:gap-5">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shrink-0 overflow-hidden">
                    <img src="{{ asset('roulette.jpg') }}" class="w-full h-full object-cover" alt="L">
                </div>
                <div class="min-w-0">
                    <h1 class="text-[10px] md:text-xs font-black uppercase tracking-[0.2em] truncate">{{ $room->title }}</h1>
                    <p class="text-[8px] md:text-[10px] font-bold text-gray-500 uppercase mt-1">
                        <span x-text="currentCount" class="text-indigo-400 font-black"></span> / 6 online
                    </p>
                </div>
            </div>
            <button @click="copyLink()" class="px-3 py-2 md:px-5 md:py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[8px] md:text-[9px] font-black uppercase tracking-widest border border-white/10 transition-all shrink-0">
                🔗 <span class="hidden sm:inline">Copy Link</span>
            </button>
        </div>

        <!-- GRID ВИДЕО: Занимает 100% высоты без скролла -->
        <div class="flex-1 flex items-center justify-center p-2 md:p-4 lg:p-6 overflow-hidden bg-[#050505]">
            <div class="grid gap-2 md:gap-4 w-full h-full max-w-7xl mx-auto place-content-center" :style="gridStyle">
                
                <!-- МОЕ ВИДЕО -->
                <div class="relative aspect-video bg-[#080808] rounded-xl md:rounded-[1.5rem] overflow-hidden border border-white/5 shadow-2xl transition-all duration-500 w-full h-full max-h-full">
                    <video x-ref="localVideo" autoplay muted playsinline webkit-playsinline class="w-full h-full object-cover" :class="!isScreenSharing && 'scale-x-[-1]'"></video>
                    <div class="absolute bottom-2 left-2 md:bottom-4 md:left-4 bg-black/60 backdrop-blur-xl px-2 py-1 md:px-3 md:py-1.5 rounded-lg md:rounded-xl border border-white/10 flex items-center gap-2">
                        <div class="w-1 h-1 md:w-1.5 md:h-1.5 rounded-full bg-indigo-500 animate-pulse"></div>
                        <span class="text-[7px] md:text-[9px] font-black uppercase tracking-widest" x-text="isScreenSharing ? 'Screen' : 'You'"></span>
                    </div>
                    <div x-show="!camEnabled" class="absolute inset-0 bg-[#0a0a0a] flex items-center justify-center">
                        <span class="text-xl md:text-3xl">🚫</span>
                    </div>
                </div>

                <!-- ВИДЕО УЧАСТНИКОВ -->
                <template x-for="peer in peers" :key="peer.id">
                    <div class="relative aspect-video bg-[#080808] rounded-xl md:rounded-[1.5rem] overflow-hidden border border-white/5 shadow-2xl transition-all duration-500 w-full h-full max-h-full">
                        <video :id="'video-' + peer.id" autoplay muted playsinline webkit-playsinline class="w-full h-full object-cover bg-black"></video>
                        <div class="absolute bottom-2 left-2 md:bottom-4 md:left-4 bg-black/60 backdrop-blur-xl px-3 py-1.5 rounded-lg md:rounded-xl border border-white/10">
                            <span class="text-[7px] md:text-[9px] font-black uppercase tracking-widest" x-text="peer.name"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- УПРАВЛЕНИЕ -->
        <div class="absolute bottom-4 md:bottom-8 left-1/2 -translate-x-1/2 z-[100]" x-data="{ controlsOpen: true }">
            <div class="flex items-center gap-2 p-1 md:p-2 bg-[#121212]/90 backdrop-blur-3xl border border-white/10 rounded-full shadow-2xl">
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
            channel: null,
            rtcConfig: { 
                iceServers: @js(config('webrtc.ice_servers')), 
                bundlePolicy: "balanced"
            },

            normalizeSdp(sdp) {
                if (typeof sdp !== 'string') return sdp;
                return sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n';
            },

            get gridStyle() {
                const count = this.peers.length + 1;
                const isMobile = window.innerWidth < 768;
                
                if (isMobile) {
                    if (count === 1) return `grid-template-columns: 1fr; width: 100%; height: 60vh;`;
                    if (count === 2) return `grid-template-columns: 1fr; grid-template-rows: repeat(2, 1fr); height: 100%;`;
                    if (count <= 4) return `grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; height: 100%;`;
                    return `grid-template-columns: 1fr 1fr; grid-template-rows: repeat(3, 1fr); height: 100%;`;
                }
                
                if (count === 1) return `grid-template-columns: 1fr; max-width: 850px; width: 100%; height: auto;`;
                if (count === 2) return `grid-template-columns: 1fr 1fr; max-width: 1200px; width: 100%;`;
                if (count === 3) return `grid-template-columns: repeat(3, 1fr); width: 100%;`;
                if (count === 4) return `grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; width: 100%; max-width: 1100px;`;
                return `grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(2, 1fr); width: 100%; max-height: 100%;`;
            },

            async init() {
                await this.initMedia();
                this.channel = window.Echo.join(`room.${roomUuid}`);

                const sync = (count) => {
                    this.currentCount = count;
                    window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {});
                };

                this.channel.here(users => {
                    sync(users.length);
                    users.forEach(u => { if (u.id !== myId) this.initiateConnection(u.id, u.name, myId > u.id); });
                }).joining(u => {
                    this.initiateConnection(u.id, u.name, myId > u.id);
                    sync(this.peers.length + 1);
                }).leaving(u => {
                    this.removePeer(u.id);
                    sync(this.peers.length + 1);
                });

                window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                    if (e.data.roomUuid === roomUuid) this.handleSignal(e.data);
                });
            },

            async initMedia() {
                try {
                    // Оптимизация для 5-6 человек: 15 FPS и 360p
                    this.localStream = await navigator.mediaDevices.getUserMedia({ 
                        video: { width: 640, height: 360, frameRate: 15 }, 
                        audio: true 
                    });
                    if (this.$refs.localVideo) this.$refs.localVideo.srcObject = this.localStream;
                } catch(e) { console.error("Camera Error:", e); }
            },

            async initiateConnection(partnerId, partnerName, isInitiator) {
                if (this.peers.find(p => p.id === partnerId)) return;
                
                const pc = new RTCPeerConnection(this.rtcConfig);
                const peerObj = { id: partnerId, name: partnerName, pc: pc, iceQueue: [] };
                this.peers.push(peerObj);

                this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));

                pc.onicecandidate = e => { if (e.candidate) this.sendSignal(partnerId, { type: 'ice', candidate: e.candidate }); };
                pc.ontrack = e => { 
                    this.$nextTick(() => { 
                        const v = document.getElementById('video-' + partnerId); 
                        if (v && e.streams[0]) {
                            v.srcObject = e.streams[0];
                            v.muted = true;
                            v.play().then(() => { setTimeout(() => { v.muted = false; }, 1000); }).catch(() => { v.muted = true; v.play(); });
                        }
                    }); 
                };

                // Ограничиваем битрейт при установке связи
                pc.oniceconnectionstatechange = () => {
                    if (pc.iceConnectionState === 'connected') this.limitBitrate(pc);
                };

                if (isInitiator) {
                    try {
                        const offer = await pc.createOffer();
                        await pc.setLocalDescription(offer); // FIX: инициируем локальное описание
                        this.sendSignal(partnerId, { type: 'offer', sdp: pc.localDescription.sdp });
                    } catch(e) { console.error("Offer Error:", e); }
                }
            },

            async handleSignal(data) {
                let peer = this.peers.find(p => p.id === data.from);
                if (!peer) return;
                try {
                    if (data.type === 'offer' || data.type === 'answer') {
                        const cleanSdp = this.normalizeSdp(data.sdp);
                        await peer.pc.setRemoteDescription(new RTCSessionDescription({ type: data.type, sdp: cleanSdp }));
                        if (data.type === 'offer') {
                            const answer = await peer.pc.createAnswer();
                            await peer.pc.setLocalDescription(answer); // FIX: инициируем локальное описание для ответа
                            this.sendSignal(data.from, { type: 'answer', sdp: peer.pc.localDescription.sdp });
                        }
                        while(peer.iceQueue.length > 0) { await peer.pc.addIceCandidate(new RTCIceCandidate(peer.iceQueue.shift())).catch(()=>{}); }
                    } else if (data.type === 'ice') {
                        if (peer.pc.remoteDescription) { await peer.pc.addIceCandidate(new RTCIceCandidate(data.candidate)).catch(()=>{}); }
                        else { peer.iceQueue.push(data.candidate); }
                    }
                } catch(e) { console.error("WebRTC Error:", e); }
            },

            // Ограничение битрейта до 200kbps для разгрузки сети при 6 юзерах
            limitBitrate(pc) {
                const senders = pc.getSenders();
                senders.forEach(sender => {
                    if (sender.track && sender.track.kind === 'video') {
                        const params = sender.getParameters();
                        if (!params.encodings) params.encodings = [{}];
                        params.encodings[0].maxBitrate = 200000; 
                        sender.setParameters(params).catch(e => console.warn(e));
                    }
                });
            },

            sendSignal(to, payload) { 
                window.axios.post('/chat/signal', { partnerId: to, data: { ...payload, from: myId, roomUuid: roomUuid } }).catch(()=>{}); 
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
                    } catch (e) { console.warn("Screen share cancelled"); }
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
        };
    }
</script>
</x-app-layout>