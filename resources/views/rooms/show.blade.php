<x-app-layout>
    <div class="h-[calc(100vh-64px)] bg-[#050505] flex flex-col overflow-hidden text-white font-sans" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- TOP BAR -->
        <div class="p-6 flex justify-between items-center bg-black/40 backdrop-blur-2xl border-b border-white/5 z-20">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl shadow-[0_0_20px_rgba(79,70,229,0.4)] flex items-center justify-center text-xl">🏠</div>
                <div>
                    <h1 class="text-xs font-black uppercase tracking-[0.3em] text-white">{{ $room->title }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest" x-text="participants.length + ' online'"></p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="copyLink()" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[9px] font-black uppercase tracking-widest border border-white/10 transition-all">🔗 Ссылка</button>
                <button @click="showSettings = !showSettings" class="p-2.5 bg-white/5 hover:bg-white/10 rounded-xl border border-white/10 transition-all">⚙️</button>
            </div>
        </div>

        <!-- DYNAMIC VIDEO GRID -->
        <div class="flex-1 p-8 grid gap-6 items-center justify-center auto-rows-fr overflow-y-auto scrollbar-hide" :style="gridStyle">
            
            <!-- MY VIDEO -->
            <div class="relative aspect-video bg-[#0a0a0a] rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl group transition-all duration-500">
                <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                <div class="absolute bottom-6 left-6 flex items-center gap-3 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10">
                    <span class="text-[10px] font-black uppercase tracking-widest text-white/90">{{ auth()->user()->name }} (Host)</span>
                    <div class="flex gap-2">
                        <span x-show="!micEnabled" class="text-[10px]">🔇</span>
                        <span x-show="!camEnabled" class="text-[10px]">🚫</span>
                    </div>
                </div>
                <!-- Звуковой индикатор -->
                <div x-show="micEnabled" class="absolute top-6 right-6 flex gap-1 items-end h-3">
                    <div class="w-1 bg-indigo-500 animate-[pulse_1s_infinite] h-full"></div>
                    <div class="w-1 bg-indigo-500 animate-[pulse_0.7s_infinite] h-2"></div>
                    <div class="w-1 bg-indigo-500 animate-[pulse_1.2s_infinite] h-full"></div>
                </div>
            </div>

            <!-- REMOTE PEERS -->
            <template x-for="peer in peers" :key="peer.id">
                <div class="relative aspect-video bg-[#0a0a0a] rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl transition-all duration-700">
                    <video :id="'video-' + peer.id" autoplay playsinline class="w-full h-full object-cover"></video>
                    <div class="absolute bottom-6 left-6 flex items-center gap-3 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10">
                        <span class="text-[10px] font-black uppercase tracking-widest text-white/90" x-text="peer.name"></span>
                        <div class="flex gap-2">
                            <span x-show="!peer.mic" class="text-[10px]">🔇</span>
                            <span x-show="!peer.cam" class="text-[10px]">🚫</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- CONTROL BAR (FLOATING ISLAND) -->
        <div class="p-10 flex justify-center z-20">
            <div class="flex items-center gap-4 px-10 py-5 bg-[#121212]/80 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] shadow-[0_25px_80px_rgba(0,0,0,0.8)]">
                
                <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white'" 
                        class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all active:scale-90 hover:bg-white/10">
                    <span x-text="micEnabled ? '🎤' : '🔇'"></span>
                </button>
                
                <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white'" 
                        class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all active:scale-90 hover:bg-white/10">
                    <span x-text="camEnabled ? '📷' : '🚫'"></span>
                </button>

                <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-indigo-600 text-white shadow-[0_0_20px_rgba(79,70,229,0.4)]' : 'bg-white/5 text-white'" 
                        class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl hover:bg-white/10 transition-all">
                    📺
                </button>

                <div class="w-px h-10 bg-white/10 mx-2"></div>

                <a href="{{ route('rooms.index') }}" 
                   class="bg-red-600 hover:bg-red-700 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] transition-all active:scale-95 shadow-lg shadow-red-600/20">
                    Завершить
                </a>
            </div>
        </div>

        <!-- SETTINGS MODAL -->
        <div x-show="showSettings" x-transition x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-xl" @click.self="showSettings = false">
            <div class="bg-[#0a0a0a] border border-white/10 p-12 rounded-[3.5rem] w-[450px] shadow-2xl relative">
                <h4 class="text-2xl font-black mb-10 tracking-tighter">Настройки устройств</h4>
                <div class="space-y-8">
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] block mb-3">Камера</label>
                        <select x-model="selectedVideoId" @change="changeDevice()" class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-6 text-sm font-bold text-white focus:ring-indigo-500 appearance-none">
                            <template x-for="d in devices.video"><option :value="d.deviceId" x-text="d.label"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] block mb-3">Микрофон</label>
                        <select x-model="selectedAudioId" @change="changeDevice()" class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-6 text-sm font-bold text-white focus:ring-indigo-500 appearance-none">
                            <template x-for="d in devices.audio"><option :value="d.deviceId" x-text="d.label"></option></template>
                        </select>
                    </div>
                </div>
                <button @click="showSettings = false" class="w-full mt-10 bg-white text-black py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all">Применить</button>
            </div>
        </div>
    </div>

    <!-- JS Logic remains same but refined for 2.0 visuals -->
<script>
    function groupRoomComponent(roomUuid, myId, myName) {
        return {
            peers: [], 
            participants: [], 
            localStream: null, 
            screenStream: null,
            micEnabled: true, 
            camEnabled: true, 
            isScreenSharing: false, 
            showSettings: false,
            devices: { video: [], audio: [] },
            selectedVideoId: localStorage.getItem('selectedVideoId') || '',
            selectedAudioId: localStorage.getItem('selectedAudioId') || '',

            async init() {
                window.rtcConfig = { 
                    iceServers: @json(config('webrtc.ice_servers')),
                    iceCandidatePoolSize: 10 
                };

                await this.initMedia(true);
                await this.updateDevicesList();

                const channel = window.Echo.join(`room.${roomUuid}`);
                
                channel.here(users => {
                    this.participants = users;
                    // Инициируем соединение: оффер отправляет тот, чей ID больше
                    users.forEach(u => { 
                        if (u.id !== myId) {
                            this.initiateConnection(u.id, u.name, myId > u.id); 
                        }
                    });
                })
                .joining(u => { 
                    this.participants.push(u); 
                    // Новый человек зашел: оффер отправляет тот, чей ID больше
                    this.initiateConnection(u.id, u.name, myId > u.id); 
                })
                .leaving(u => { 
                    this.participants = this.participants.filter(p => p.id !== u.id); 
                    this.removePeer(u.id); 
                });

                window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                    if (e.data.roomUuid === roomUuid) {
                        this.handleSignal(e.data);
                    }
                });
            },

            // Исправление ошибки парсинга SDP
            sanitizeSdp(sdp) {
                if (typeof sdp !== 'string') return sdp;
                return sdp.split('\n')
                          .map(line => line.trim())
                          .filter(line => line.length > 0)
                          .join('\r\n') + '\r\n';
            },

            get gridStyle() {
                const count = this.peers.length + 1;
                let cols = count > 4 ? 3 : (count > 1 ? 2 : 1);
                return `grid-template-columns: repeat(${cols}, minmax(300px, 1fr))`;
            },

            async updateDevicesList() {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.devices.video = devices.filter(d => d.kind === 'videoinput');
                this.devices.audio = devices.filter(d => d.kind === 'audioinput');
            },

            async initMedia(firstTime = false) {
                try {
                    const constraints = {
                        video: this.selectedVideoId ? { deviceId: { exact: this.selectedVideoId } } : { width: 1280, height: 720 },
                        audio: this.selectedAudioId ? { deviceId: { exact: this.selectedAudioId } } : true
                    };
                    const newStream = await navigator.mediaDevices.getUserMedia(constraints);
                    
                    if (!firstTime && this.localStream) {
                        const vTrack = newStream.getVideoTracks()[0];
                        const aTrack = newStream.getAudioTracks()[0];
                        this.peers.forEach(p => {
                            const vSender = p.pc.getSenders().find(s => s.track?.kind === 'video');
                            if(vSender) vSender.replaceTrack(vTrack);
                            const aSender = p.pc.getSenders().find(s => s.track?.kind === 'audio');
                            if(aSender) aSender.replaceTrack(aTrack);
                        });
                    }
                    this.localStream = newStream;
                    this.$refs.localVideo.srcObject = this.localStream;
                    this.localStream.getVideoTracks()[0].enabled = this.camEnabled;
                    this.localStream.getAudioTracks()[0].enabled = this.micEnabled;
                } catch (e) { console.error("Media Error:", e); }
            },

            async initiateConnection(partnerId, partnerName, isInitiator) {
                if (this.peers.find(p => p.id === partnerId)) return;

                const pc = new RTCPeerConnection(window.rtcConfig);
                const peerObj = { id: partnerId, name: partnerName, pc: pc, mic: true, cam: true, iceQueue: [] };
                this.peers.push(peerObj);

                this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));

                pc.onicecandidate = e => { 
                    if (e.candidate) this.sendSignal(partnerId, { type: 'ice', candidate: e.candidate }); 
                };

                pc.ontrack = e => { 
                    this.$nextTick(() => { 
                        const v = document.getElementById('video-' + partnerId); 
                        if (v) v.srcObject = e.streams[0]; 
                    }); 
                };

                if (isInitiator) {
                    const offer = await pc.createOffer();
                    await pc.setLocalDescription(offer);
                    this.sendSignal(partnerId, { type: 'offer', sdp: offer });
                }
            },

            async handleSignal(data) {
                let peer = this.peers.find(p => p.id === data.from);
                if (!peer) return;

                try {
                    if (data.type === 'offer') {
                        const desc = new RTCSessionDescription({
                            type: 'offer',
                            sdp: this.sanitizeSdp(data.sdp.sdp)
                        });
                        await peer.pc.setRemoteDescription(desc);
                        const answer = await peer.pc.createAnswer();
                        await peer.pc.setLocalDescription(answer);
                        this.sendSignal(data.from, { type: 'answer', sdp: answer });
                        this.drainIce(peer);
                    } else if (data.type === 'answer') {
                        const desc = new RTCSessionDescription({
                            type: 'answer',
                            sdp: this.sanitizeSdp(data.sdp.sdp)
                        });
                        await peer.pc.setRemoteDescription(desc);
                        this.drainIce(peer);
                    } else if (data.type === 'ice') {
                        const cand = new RTCIceCandidate(data.candidate);
                        if (peer.pc.remoteDescription) {
                            await peer.pc.addIceCandidate(cand).catch(e=>{});
                        } else {
                            peer.iceQueue.push(cand);
                        }
                    } else if (data.type === 'media-status') {
                        peer[data.mediaType === 'video' ? 'cam' : 'mic'] = data.enabled;
                    }
                } catch (e) { console.error("Signal Handling Error:", e); }
            },

            drainIce(peer) { 
                while(peer.iceQueue.length > 0) {
                    peer.pc.addIceCandidate(peer.iceQueue.shift()).catch(e=>{});
                }
            },

            sendSignal(toId, payload) { 
                window.axios.post('/chat/signal', { 
                    partnerId: toId, 
                    data: { ...payload, from: myId, roomUuid: roomUuid } 
                }).catch(e => console.warn("Signal failed")); 
            },

            removePeer(id) { 
                const idx = this.peers.findIndex(p => p.id === id); 
                if (idx !== -1) { 
                    this.peers[idx].pc.close(); 
                    this.peers.splice(idx, 1); 
                } 
            },

            toggleMic() { 
                this.micEnabled = !this.micEnabled; 
                this.localStream.getAudioTracks()[0].enabled = this.micEnabled; 
                this.broadcastMediaStatus('audio', this.micEnabled); 
            },
            
            toggleCam() { 
                this.camEnabled = !this.camEnabled; 
                this.localStream.getVideoTracks()[0].enabled = this.camEnabled; 
                this.broadcastMediaStatus('video', this.camEnabled); 
            },

            broadcastMediaStatus(type, enabled) { 
                this.peers.forEach(p => this.sendSignal(p.id, { type: 'media-status', mediaType: type, enabled })); 
            },

            async toggleScreenShare() {
                if (!this.isScreenSharing) {
                    try {
                        this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                        const sTrack = this.screenStream.getVideoTracks()[0];
                        this.peers.forEach(p => { 
                            const s = p.pc.getSenders().find(s => s.track?.kind === 'video'); 
                            if(s) s.replaceTrack(sTrack); 
                        });
                        this.$refs.localVideo.srcObject = this.screenStream; 
                        this.isScreenSharing = true;
                        sTrack.onended = () => this.stopScreenShare();
                    } catch (e) { console.error(e); }
                } else this.stopScreenShare();
            },

            stopScreenShare() {
                const vTrack = this.localStream.getVideoTracks()[0];
                this.peers.forEach(p => { 
                    const s = p.pc.getSenders().find(s => s.track?.kind === 'video'); 
                    if(s) s.replaceTrack(vTrack); 
                });
                this.$refs.localVideo.srcObject = this.localStream; 
                this.isScreenSharing = false;
                if (this.screenStream) this.screenStream.getTracks().forEach(t => t.stop());
            },
            
        copyLink() { 
            navigator.clipboard.writeText(window.location.href); 
            window.dispatchEvent(new CustomEvent('toast', { 
                detail: { msg: 'Ссылка скопирована в буфер обмена', type: 'success' } 
            }));
        }
        }
    }
</script>
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        video { background: #000; transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</x-app-layout>