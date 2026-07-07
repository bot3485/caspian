<x-app-layout>
    <div class="bg-gray-950 min-h-screen flex flex-col overflow-hidden text-white font-sans" 
         x-data="groupRoomComponent('{{ $room->uuid }}', {{ auth()->id() }}, '{{ auth()->user()->name }}')">
        
        <!-- ВЕРХНЯЯ ПАНЕЛЬ -->
        <div class="p-4 flex justify-between items-center bg-black/40 backdrop-blur-xl border-b border-white/5 z-20">
            <div class="flex items-center gap-4">
                <div class="bg-indigo-600 p-2.5 rounded-xl shadow-lg text-sm">🏠</div>
                <div>
                    <h1 class="text-xs font-black uppercase tracking-widest text-white">{{ $room->title }}</h1>
                    <p class="text-[10px] font-bold text-indigo-400 font-mono" x-text="participants.length + ' участников в сети'"></p>
                </div>
            </div>
            <div class="flex gap-2 relative">
                <button @click.stop="showSettings = !showSettings" class="bg-white/5 hover:bg-white/10 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase border border-white/10 transition-all">
                    ⚙️ Настройки
                </button>
                <button @click.stop="copyLink()" class="bg-white/5 hover:bg-white/10 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase border border-white/10 transition-all">
                    🔗 Ссылка
                </button>

                <div x-show="showSettings" x-transition x-cloak @click.away="showSettings = false"
                     class="absolute top-12 right-0 w-64 bg-gray-900 rounded-3xl shadow-2xl p-6 z-30 border border-white/10">
                    <div class="space-y-4">
                        <div>
                            <label class="text-[9px] font-bold text-gray-500 block mb-1 uppercase">Камера</label>
                            <select x-model="selectedVideoId" @change="changeDevice()" class="w-full bg-black border-white/10 rounded-xl text-[11px] text-white font-bold focus:ring-indigo-500">
                                <template x-for="d in devices.video">
                                    <option :value="d.deviceId" x-text="d.label"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-gray-500 block mb-1 uppercase">Микрофон</label>
                            <select x-model="selectedAudioId" @change="changeDevice()" class="w-full bg-black border-white/10 rounded-xl text-[11px] text-white font-bold focus:ring-indigo-500">
                                <template x-for="d in devices.audio">
                                    <option :value="d.deviceId" x-text="d.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- СЕТКА ВИДЕО -->
        <div id="videoGrid" class="flex-1 p-6 grid gap-6 items-center justify-center auto-rows-fr overflow-y-auto" :style="gridStyle">
            <!-- МОЕ ВИДЕО -->
            <div class="relative aspect-video bg-gray-900 rounded-[2rem] overflow-hidden border border-white/10 shadow-2xl transition-all duration-500 group">
                <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                <div class="absolute bottom-5 left-5 flex items-center gap-3 bg-black/60 backdrop-blur-xl px-4 py-1.5 rounded-2xl border border-white/10">
                    <span class="text-[10px] font-black uppercase tracking-tighter">{{ auth()->user()->name }} (Вы)</span>
                    <div class="flex gap-2 text-[10px]"><span x-show="!micEnabled">🔇</span><span x-show="!camEnabled">🚫</span></div>
                </div>
            </div>

            <!-- ПАРТНЕРЫ -->
            <template x-for="peer in peers" :key="peer.id">
                <div class="relative aspect-video bg-gray-900 rounded-[2rem] overflow-hidden border border-white/10 shadow-2xl transition-all duration-500">
                    <video :id="'video-' + peer.id" autoplay playsinline class="w-full h-full object-cover"></video>
                    <div class="absolute bottom-5 left-5 flex items-center gap-3 bg-black/60 backdrop-blur-xl px-4 py-1.5 rounded-2xl border border-white/10">
                        <span class="text-[10px] font-black uppercase tracking-tighter" x-text="peer.name"></span>
                        <div class="flex gap-2 text-[10px]"><span x-show="!peer.mic">🔇</span><span x-show="!peer.cam">🚫</span></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- КНОПКИ УПРАВЛЕНИЯ -->
        <div class="p-6 bg-black/60 backdrop-blur-3xl border-t border-white/5 flex justify-center items-center gap-4 z-20">
            <button type="button" @click.stop="toggleMic()" :class="micEnabled ? 'bg-gray-800' : 'bg-red-600'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all active:scale-90">
                <span x-text="micEnabled ? '🎤' : '🔇'"></span>
            </button>
            <button type="button" @click.stop="toggleCam()" :class="camEnabled ? 'bg-gray-800' : 'bg-red-600'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all active:scale-90">
                <span x-text="camEnabled ? '📷' : '🚫'"></span>
            </button>
            <button type="button" @click.stop="toggleScreenShare()" :class="isScreenSharing ? 'bg-indigo-600' : 'bg-gray-800'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl transition-all active:scale-90">📺</button>
            
            <div class="h-10 w-px bg-white/10 mx-2"></div>
            
            <a href="{{ route('rooms.index') }}" class="px-8 py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all">ВЫЙТИ</a>
        </div>
    </div>

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
                    window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')) };
                    await this.initMedia(true);
                    await this.updateDevicesList();

                    // Подключаемся к Presence-каналу комнаты
                    const channel = window.Echo.join(`room.${roomUuid}`);
                    
                    channel.here(users => {
                        this.participants = users;
                        // Те, кто уже в комнате, инициируют соединение с новичком
                        users.forEach(u => { 
                            if (u.id !== myId) this.initiateConnection(u.id, u.name, true); 
                        });
                    })
                    .joining(u => { 
                        this.participants.push(u); 
                        // Когда кто-то заходит, мы (кто уже там) создаем соединение
                        this.initiateConnection(u.id, u.name, false); 
                    })
                    .leaving(u => { 
                        this.participants = this.participants.filter(p => p.id !== u.id); 
                        this.removePeer(u.id); 
                    });

                    // Слушаем сигналы именно для этой комнаты
                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) {
                            this.handleSignal(e.data);
                        }
                    });
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
                        
                        // Применяем текущие состояния вкл/выкл
                        this.localStream.getVideoTracks()[0].enabled = this.camEnabled;
                        this.localStream.getAudioTracks()[0].enabled = this.micEnabled;

                    } catch (e) { console.error("Media Error:", e); }
                },

                async changeDevice() {
                    localStorage.setItem('selectedVideoId', this.selectedVideoId);
                    localStorage.setItem('selectedAudioId', this.selectedAudioId);
                    await this.initMedia();
                },

                sanitizeSdp(sdp) {
                    return sdp ? sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : "";
                },

                get gridStyle() {
                    const count = this.peers.length + 1;
                    let cols = count > 4 ? 3 : (count > 1 ? 2 : 1);
                    return `grid-template-columns: repeat(${cols}, minmax(300px, 1fr))`;
                },

                async initiateConnection(partnerId, partnerName, isInitiator) {
                    if (this.peers.find(p => p.id === partnerId)) return;

                    const pc = new RTCPeerConnection(window.rtcConfig);
                    const peerObj = { id: partnerId, name: partnerName, pc: pc, mic: true, cam: true, iceQueue: [] };
                    this.peers.push(peerObj);

                    // Добавляем наши треки в соединение
                    this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));

                    pc.onicecandidate = e => { 
                        if (e.candidate) this.sendSignal(partnerId, { type: 'ice', candidate: e.candidate }); 
                    };

                    pc.ontrack = e => { 
                        this.$nextTick(() => { 
                            const v = document.getElementById('video-' + partnerId); 
                            if (v && v.srcObject !== e.streams[0]) v.srcObject = e.streams[0]; 
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
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({type:'offer', sdp: this.sanitizeSdp(data.sdp.sdp)}));
                            const answer = await peer.pc.createAnswer();
                            await peer.pc.setLocalDescription(answer);
                            this.sendSignal(data.from, { type: 'answer', sdp: answer });
                            this.drainIce(peer);
                        } else if (data.type === 'answer') {
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({type:'answer', sdp: this.sanitizeSdp(data.sdp.sdp)}));
                            this.drainIce(peer);
                        } else if (data.type === 'ice') {
                            if (peer.pc.remoteDescription) {
                                await peer.pc.addIceCandidate(new RTCIceCandidate(data.candidate)).catch(e=>{});
                            } else {
                                peer.iceQueue.push(data.candidate);
                            }
                        } else if (data.type === 'media-status') {
                            peer[data.mediaType === 'video' ? 'cam' : 'mic'] = data.enabled;
                        }
                    } catch (e) { console.warn("Signal Handle Error:", e); }
                },

                drainIce(peer) { 
                    while(peer.iceQueue.length > 0) {
                        peer.pc.addIceCandidate(new RTCIceCandidate(peer.iceQueue.shift())).catch(e=>{});
                    }
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

                sendSignal(toId, payload) { 
                    window.axios.post('/chat/signal', { 
                        partnerId: toId, 
                        data: { ...payload, from: myId, roomUuid: roomUuid } 
                    }).catch(e => {
                        console.error("Signal 403 or Error:", e);
                    }); 
                },

                removePeer(id) { 
                    const idx = this.peers.findIndex(p => p.id === id); 
                    if (idx !== -1) { 
                        this.peers[idx].pc.close(); 
                        this.peers.splice(idx, 1); 
                    } 
                },
                
                copyLink() { 
                    navigator.clipboard.writeText(window.location.href); 
                    alert("Ссылка на комнату скопирована!"); 
                }
            }
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>