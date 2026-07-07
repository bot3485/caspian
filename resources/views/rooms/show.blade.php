<x-app-layout>
    <div class="h-[calc(100vh-64px)] bg-[#050505] flex flex-col overflow-hidden text-white" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- HEADER -->
        <div class="p-6 flex justify-between items-center bg-black/40 backdrop-blur-2xl border-b border-white/5 z-20">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-lg">🏠</div>
                <div>
                    <h1 class="text-xs font-black uppercase tracking-[0.3em]">{{ $room->title }}</h1>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1" x-text="peers.length + 1 + ' online'"></p>
                </div>
            </div>
            <button @click="copyLink()" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[9px] font-black uppercase tracking-widest border border-white/10 transition-all">🔗 Копировать ссылку</button>
        </div>

        <!-- GRID -->
        <div class="flex-1 p-8 grid gap-6 items-center justify-center auto-rows-fr overflow-y-auto scrollbar-hide" :style="gridStyle">
            <!-- MY VIDEO -->
            <div class="relative aspect-video bg-black rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl">
                <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover" :class="!isScreenSharing && 'scale-x-[-1]'"></video>
                <div class="absolute bottom-6 left-6 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10">
                    <span class="text-[10px] font-black uppercase tracking-widest" x-text="isScreenSharing ? 'Демонстрация экрана' : 'Я (Вы)'"></span>
                </div>
            </div>

            <!-- REMOTE PEERS -->
            <template x-for="peer in peers" :key="peer.id">
                <div class="relative aspect-video bg-black rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl">
                    <video :id="'video-' + peer.id" autoplay playsinline class="w-full h-full object-cover"></video>
                    <div class="absolute bottom-6 left-6 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10">
                        <span class="text-[10px] font-black uppercase tracking-widest" x-text="peer.name"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- CONTROLS -->
        <div class="p-10 flex justify-center z-20">
            <div class="flex items-center gap-4 px-10 py-5 bg-[#121212]/80 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] shadow-2xl">
                <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl">
                    <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                </button>
                
                <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl">
                    <span x-text="camEnabled ? '📷' : '🚫'"></span>
                </button>

                <!-- КНОПКА ДЕМОНСТРАЦИИ ЭКРАНА -->
                <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-indigo-600 shadow-[0_0_15px_#6366f1]' : 'bg-white/5'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all">
                    <span>📺</span>
                </button>

                <div class="w-px h-10 bg-white/10 mx-2"></div>
                
                <a href="{{ route('rooms.index') }}" class="bg-red-600 hover:bg-red-700 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all">Покинуть</a>
            </div>
        </div>
    </div>

    <script>
        function groupRoomComponent(roomUuid, myId, myName) {
            return {
                peers: [], localStream: null, screenStream: null,
                micEnabled: true, camEnabled: true, isScreenSharing: false,
                get gridStyle() {
                    const count = this.peers.length + 1;
                    const cols = count > 4 ? 3 : (count > 1 ? 2 : 1);
                    return `grid-template-columns: repeat(${cols}, minmax(300px, 1fr))`;
                },
                async init() {
                    await this.initMedia();
                    const channel = window.Echo.join(`room.${roomUuid}`);
                    channel.here(users => {
                        users.forEach(u => { if (u.id !== myId) this.initiateConnection(u.id, u.name, myId > u.id); });
                    }).joining(u => {
                        this.initiateConnection(u.id, u.name, myId > u.id);
                    }).leaving(u => {
                        this.removePeer(u.id);
                    });

                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) this.handleSignal(e.data);
                    });
                },
                async initMedia() {
                    this.localStream = await navigator.mediaDevices.getUserMedia({video:true, audio:true});
                    this.$refs.localVideo.srcObject = this.localStream;
                },
                async toggleScreenShare() {
                    if (!this.isScreenSharing) {
                        try {
                            this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                            const sTrack = this.screenStream.getVideoTracks()[0];
                            
                            // Заменяем видео-трек у всех пиров
                            this.peers.forEach(p => {
                                const sender = p.pc.getSenders().find(s => s.track?.kind === 'video');
                                if (sender) sender.replaceTrack(sTrack);
                            });

                            this.$refs.localVideo.srcObject = this.screenStream;
                            this.isScreenSharing = true;

                            sTrack.onended = () => this.stopScreenShare();
                        } catch (e) { console.error("ScreenShare error:", e); }
                    } else {
                        this.stopScreenShare();
                    }
                },
                stopScreenShare() {
                    const vTrack = this.localStream.getVideoTracks()[0];
                    this.peers.forEach(p => {
                        const sender = p.pc.getSenders().find(s => s.track?.kind === 'video');
                        if (sender) sender.replaceTrack(vTrack);
                    });
                    this.$refs.localVideo.srcObject = this.localStream;
                    this.isScreenSharing = false;
                    if (this.screenStream) this.screenStream.getTracks().forEach(t => t.stop());
                },
                initiateConnection(partnerId, partnerName, isInitiator) {
                    if (this.peers.find(p => p.id === partnerId)) return;
                    const pc = new RTCPeerConnection(window.rtcConfig);
                    const peerObj = { id: partnerId, name: partnerName, pc: pc, iceQueue: [] };
                    this.peers.push(peerObj);

                    const stream = this.isScreenSharing ? this.screenStream : this.localStream;
                    stream.getTracks().forEach(t => pc.addTrack(t, stream));

                    pc.onicecandidate = e => { if (e.candidate) this.sendSignal(partnerId, { type: 'ice', candidate: e.candidate }); };
                    pc.ontrack = e => { 
                        this.$nextTick(() => { 
                            const v = document.getElementById('video-' + partnerId); 
                            if (v) v.srcObject = e.streams[0]; 
                        }); 
                    };

                    if (isInitiator) {
                        pc.createOffer().then(o => { pc.setLocalDescription(o); this.sendSignal(partnerId, { type: 'offer', sdp: o }); });
                    }
                },
                async handleSignal(data) {
                    let peer = this.peers.find(p => p.id === data.from);
                    if (!peer) return;
                    if (data.type === 'offer') {
                        await peer.pc.setRemoteDescription(new RTCSessionDescription({type:'offer', sdp:this.sanitizeSdp(data.sdp.sdp)}));
                        const a = await peer.pc.createAnswer();
                        await peer.pc.setLocalDescription(a);
                        this.sendSignal(data.from, { type: 'answer', sdp: a });
                        this.drainIce(peer);
                    } else if (data.type === 'answer') {
                        await peer.pc.setRemoteDescription(new RTCSessionDescription({type:'answer', sdp:this.sanitizeSdp(data.sdp.sdp)}));
                        this.drainIce(peer);
                    } else if (data.type === 'ice') {
                        if (peer.pc.remoteDescription) peer.pc.addIceCandidate(new RTCIceCandidate(data.candidate)).catch(()=>{});
                        else peer.iceQueue.push(data.candidate);
                    }
                },
                drainIce(peer) { while(peer.iceQueue.length > 0) peer.pc.addIceCandidate(new RTCIceCandidate(peer.iceQueue.shift())).catch(()=>{}); },
                sendSignal(to, payload) { window.axios.post('/chat/signal', { partnerId: to, data: { ...payload, from: myId, roomUuid: roomUuid } }); },
                removePeer(id) { 
                    const p = this.peers.find(x => x.id === id); 
                    if(p) { p.pc.close(); this.peers = this.peers.filter(x => x.id !== id); }
                },
                toggleMic() { this.micEnabled = !this.micEnabled; this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
                toggleCam() { this.camEnabled = !this.camEnabled; this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
                sanitizeSdp(s) { return s.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n'; },
                copyLink() { navigator.clipboard.writeText(window.location.href); window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Ссылка скопирована', type:'success'}})); }
            }
        }
    </script>
</x-app-layout>