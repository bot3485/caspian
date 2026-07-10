<x-app-layout>
    <div class="h-[calc(100vh-64px)] bg-[#050505] flex flex-col overflow-hidden text-white font-sans" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- HEADER -->
        <div class="p-6 flex justify-between items-center bg-black/40 backdrop-blur-2xl border-b border-white/5 z-20">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-lg shadow-indigo-500/10">🏠</div>
                <div>
                    <h1 class="text-xs font-black uppercase tracking-[0.3em]">{{ $room->title }}</h1>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1">
                        <span x-text="currentCount"></span> / 6 участников
                    </p>
                </div>
            </div>
            <button @click="copyLink()" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[9px] font-black uppercase tracking-widest border border-white/10 transition-all shadow-xl">🔗 Копировать ссылку</button>
        </div>

        <!-- GRID ВИДЕО -->
        <div class="flex-1 p-8 grid gap-6 items-center justify-center auto-rows-fr overflow-y-auto custom-scrollbar" :style="gridStyle">
            <!-- МОЕ ВИДЕО -->
            <div class="relative aspect-video bg-[#080808] rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl">
                <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover" :class="!isScreenSharing && 'scale-x-[-1]'"></video>
                <div class="absolute bottom-6 left-6 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10 flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></div>
                    <span class="text-[10px] font-black uppercase tracking-widest" x-text="isScreenSharing ? 'Трансляция экрана' : 'Я (Вы)'"></span>
                </div>
                <div x-show="!camEnabled" class="absolute inset-0 bg-[#080808] flex items-center justify-center">
                    <span class="text-4xl">🚫</span>
                </div>
            </div>

            <!-- ВИДЕО УЧАСТНИКОВ -->
            <template x-for="peer in peers" :key="peer.id">
                <div class="relative aspect-video bg-[#080808] rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl">
                    <video :id="'video-' + peer.id" autoplay playsinline class="w-full h-full object-cover"></video>
                    <div class="absolute bottom-6 left-6 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10">
                        <span class="text-[10px] font-black uppercase tracking-widest" x-text="peer.name"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- УПРАВЛЕНИЕ -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-[100]" x-data="{ controlsOpen: true }">
            <div class="flex items-center gap-2 p-2 bg-[#121212]/95 backdrop-blur-3xl border border-white/10 rounded-full shadow-2xl">
                <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center transition-all">
                    <span class="text-xs" x-text="controlsOpen ? '▼' : '⚡'"></span>
                </button>

                <div x-show="controlsOpen" x-transition class="flex items-center gap-2 pr-2">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-colors">
                        <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                    </button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-colors">
                        <span x-text="camEnabled ? '📷' : '🚫'"></span>
                    </button>
                    <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-indigo-600 shadow-lg' : 'bg-white/5'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all">
                        <span>📺</span>
                    </button>
                    <div class="w-px h-6 bg-white/10 mx-1"></div>
                    <a href="{{ route('rooms.index') }}" class="bg-red-600 text-white px-8 py-3.5 rounded-full font-black text-[10px] uppercase tracking-widest transition-all shadow-lg active:scale-95">Выйти</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function groupRoomComponent(roomUuid, myId, myName) {
            return {
                peers: [], 
                currentCount: 1,
                pulseTimer: null,
                localStream: null, 
                screenStream: null,
                micEnabled: true, 
                camEnabled: true, 
                isScreenSharing: false,
                channel: null,

                get gridStyle() {
                    const count = this.peers.length + 1;
                    const cols = count > 4 ? 3 : (count > 1 ? 2 : 1);
                    return `grid-template-columns: repeat(${cols}, minmax(280px, 1fr))`;
                },

                async init() {
                    window.rtcConfig = { 
                        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }, { urls: 'stun:stun1.l.google.com:19302' }], 
                        bundlePolicy: "balanced"
                    };
                    await this.initMedia();
                    
                    this.channel = window.Echo.join(`room.${roomUuid}`);

                    const sync = (count) => {
                        this.currentCount = count;
                        window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {});
                    };

                    this.channel.here(users => {
                        sync(users.length);
                        users.forEach(u => { if (u.id !== myId) this.initiateConnection(u.id, u.name, myId > u.id); });
                        this.pulseTimer = setInterval(() => { sync(this.peers.length + 1); }, 15000);
                    }).joining(u => {
                        this.initiateConnection(u.id, u.name, myId > u.id);
                        sync(this.peers.length + 1);
                    }).leaving(u => {
                        this.removePeer(u.id);
                        sync(this.peers.length + 1);
                    });

                    window.addEventListener('beforeunload', () => this.channel?.leave());

                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) this.handleSignal(e.data);
                    });
                },

                // МЕТОД ОЧИСТКИ (Вызывается Alpine при уходе со страницы)
                destroy() {
                    if(this.pulseTimer) clearInterval(this.pulseTimer);
                    if(this.channel) this.channel.leave();
                    if(this.localStream) this.localStream.getTracks().forEach(t => t.stop());
                    if(this.screenStream) this.screenStream.getTracks().forEach(t => t.stop());
                    this.peers.forEach(p => p.pc.close());
                },

                normalizeSdp(sdp) {
                    if (typeof sdp !== 'string') sdp = sdp.sdp || "";
                    return sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n';
                },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({video:true, audio:true});
                        this.$refs.localVideo.srcObject = this.localStream;
                    } catch(e) { console.error("Media Error:", e); }
                },

                initiateConnection(partnerId, partnerName, isInitiator) {
                    if (this.peers.find(p => p.id === partnerId)) return;
                    
                    const pc = new RTCPeerConnection(window.rtcConfig);
                    const peerObj = { id: partnerId, name: partnerName, pc: pc, iceQueue: [] };
                    this.peers.push(peerObj);

                    const stream = this.isScreenSharing ? this.screenStream : this.localStream;
                    if (stream) stream.getTracks().forEach(t => pc.addTrack(t, stream));

                    pc.onicecandidate = e => { 
                        if (e.candidate) this.sendSignal(partnerId, { type: 'ice', candidate: e.candidate }); 
                    };
                    
                    pc.ontrack = e => { 
                        this.$nextTick(() => { 
                            const v = document.getElementById('video-' + partnerId); 
                            if (v) {
                                v.srcObject = e.streams[0];
                                v.play().catch(err => console.warn(err));
                            }
                        }); 
                    };

                    if (isInitiator) {
                        pc.createOffer().then(o => { 
                            pc.setLocalDescription(o); 
                            this.sendSignal(partnerId, { type: 'offer', sdp: o.sdp }); 
                        });
                    }
                },

                async handleSignal(data) {
                    let peer = this.peers.find(p => p.id === data.from);
                    if (!peer && data.type === 'offer') {
                        this.initiateConnection(data.from, 'User', false);
                        peer = this.peers.find(p => p.id === data.from);
                    }
                    if (!peer) return;

                    try {
                        if (data.type === 'offer' || data.type === 'answer') {
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({
                                type: data.type,
                                sdp: this.normalizeSdp(data.sdp)
                            }));

                            if (data.type === 'offer') {
                                const answer = await peer.pc.createAnswer();
                                await peer.pc.setLocalDescription(answer);
                                this.sendSignal(data.from, { type: 'answer', sdp: answer.sdp });
                            }
                            
                            while(peer.iceQueue.length > 0) {
                                await peer.pc.addIceCandidate(new RTCIceCandidate(peer.iceQueue.shift())).catch(()=>{});
                            }
                        } else if (data.type === 'ice') {
                            if (peer.pc.remoteDescription) {
                                await peer.pc.addIceCandidate(new RTCIceCandidate(data.candidate)).catch(()=>{});
                            } else {
                                peer.iceQueue.push(data.candidate);
                            }
                        }
                    } catch(e) { console.error("Spaces WebRTC Error:", e); }
                },

                sendSignal(to, payload) { 
                    window.axios.post('/chat/signal', { 
                        partnerId: to, 
                        data: { ...payload, from: myId, roomUuid: roomUuid } 
                    }).catch(()=>{}); 
                },

                removePeer(id) {
                    const p = this.peers.find(x => x.id === id);
                    if (p) {
                        p.pc.close();
                        this.peers = this.peers.filter(x => x.id !== id);
                        const videoEl = document.getElementById('video-' + id);
                        if (videoEl) videoEl.srcObject = null;
                    }
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
                    window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Ссылка скопирована', type:'success'}})); 
                }
            }
        }
    </script>
</x-app-layout>