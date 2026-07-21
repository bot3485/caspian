    <script>
        function groupRoomComponent(roomUuid, myId, myName) {
            return {
                focusedId: null,
                isMaximized: false, // НОВОЕ: Флаг для режима во весь экран
                peers: [], 
                currentCount: 0,
                localStream: null, 
                screenStream: null,
                micEnabled: true, 
                camEnabled: true, 
                isScreenSharing: false,
                windowWidth: window.innerWidth,
                settingsOpen: false,
                audioDevices: [], videoDevices: [], selectedAudio: '', selectedVideo: '',
                rtcConfig: { iceServers: @js(config('webrtc.ice_servers')), bundlePolicy: "balanced" },

                // МАТЕМАТИКА ЖЕСТКОЙ БЛОКИРОВКИ СКРОЛЛА
getBoxStyle(id) {
                    const isFocused = this.focusedId === id;
                    const someoneFocused = this.focusedId !== null;
                    const isMobile = this.windowWidth < 768;

                    if (isFocused) {
                        // 1. АБСОЛЮТНЫЙ ПОЛНЫЙ ЭКРАН (Maximized)
                        if (this.isMaximized) {
                            return 'width: 100%; height: 100%; order: -1; margin: 0; border-radius: 0; max-width: none;';
                        }
                        // 2. РЕЖИМ КИНОТЕАТРА (Просто фокус)
                        return `
                            width: 100%; 
                            height: ${isMobile ? '65%' : '70%'}; 
                            order: -1; 
                            margin-bottom: ${isMobile ? '0.5rem' : '1rem'}; 
                            border-radius: ${isMobile ? '1rem' : '2rem'};
                            max-width: 1300px;
                        `;
                    }

                    if (someoneFocused) {
                        // 3. ЗРИТЕЛИ (Миниатюры)
                        return `
                            width: calc(33.333% - 8px); 
                            max-width: 240px; 
                            max-height: ${isMobile ? '14%' : '20%'}; 
                            aspect-ratio: 16/9; 
                            order: 1; 
                            border-radius: 0.75rem;
                        `;
                    }

                    // 4. СТАНДАРТНАЯ СЕТКА (Все равны)
                    if (isMobile) {
                        return 'width: calc(50% - 4px); max-height: calc(33.333% - 6px); aspect-ratio: 16/9; border-radius: 1rem;';
                    } else {
                        return 'width: calc(33.333% - 8px); max-height: calc(50% - 6px); aspect-ratio: 16/9; border-radius: 1.5rem;';
                    }
                },

                toggleFocus(id) {
                    if (String(id).includes('empty')) return;
                    
                    if (this.focusedId === id) {
                        this.focusedId = null;
                        this.isMaximized = false; // Сбрасываем при закрытии
                    } else {
                        this.focusedId = id;
                        this.isMaximized = false; // Начинаем всегда с режима кинотеатра
                    }
                },

                // Остальная логика WebRTC (без изменений)
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
                        users.forEach(u => { if (u.id !== myId) self.initiateConnection(u.id, u.name, true); });
                    }).joining(u => {
                        this.currentCount = channel.subscription.members.count;
                        this.syncOccupancy(this.currentCount);
                        self.initiateConnection(u.id, u.name, true);
                    }).leaving(u => {
                        self.removePeer(u.id);
                        this.currentCount = Math.max(0, channel.subscription.members.count - 1);
                        this.syncOccupancy(this.currentCount);
                    });
                    window.Echo.private(`user.${myId}`).listen('.WebRTCSignalEvent', (e) => {
                        if (e.data.roomUuid === roomUuid) self.handleSignal(e.data);
                    });
                    setInterval(() => { if (this.currentCount >= 0) this.syncOccupancy(this.currentCount); }, 20000);
                },

                syncOccupancy(count) { window.axios.post(`/rooms/${roomUuid}/sync-occupancy`, { count: count }).catch(() => {}); },

                async initMedia() {
                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ video: { width: 1280, height: 720 }, audio: true });
                        this.$refs.localVideo.srcObject = this.localStream;
                        await this.loadDevices();
                    } catch(e) { console.error("Camera denied", e); }
                },

                async loadDevices() {
                    try {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        this.audioDevices = devices.filter(d => d.kind === 'audioinput');
                        this.videoDevices = devices.filter(d => d.kind === 'videoinput');
                        if (this.localStream) {
                            const activeAudio = this.localStream.getAudioTracks()[0];
                            const activeVideo = this.localStream.getVideoTracks()[0];
                            if (activeAudio) this.selectedAudio = activeAudio.getSettings().deviceId;
                            if (activeVideo) this.selectedVideo = activeVideo.getSettings().deviceId;
                        }
                    } catch (e) { console.error("Error loading devices", e); }
                },

                async applyDeviceChanges() {
                    try {
                        if (this.isScreenSharing) await this.toggleScreenShare();
                        const constraints = {
                            audio: this.selectedAudio ? { deviceId: { exact: this.selectedAudio } } : true,
                            video: this.selectedVideo ? { width: 1280, height: 720, deviceId: { exact: this.selectedVideo } } : { width: 1280, height: 720 }
                        };
                        const newStream = await navigator.mediaDevices.getUserMedia(constraints);
                        if (this.localStream) this.localStream.getTracks().forEach(t => t.stop());
                        
                        this.localStream = newStream;
                        this.$refs.localVideo.srcObject = this.localStream;
                        this.localStream.getAudioTracks()[0].enabled = this.micEnabled;
                        this.localStream.getVideoTracks()[0].enabled = this.camEnabled;
                        
                        this.replaceTrack(this.localStream.getVideoTracks()[0], 'video');
                        this.replaceTrack(this.localStream.getAudioTracks()[0], 'audio');
                    } catch (e) { console.error("Error changing devices", e); }
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
                            if (offerCollision && Number(myId) >= fromId) return;
                            if (offerCollision && Number(myId) < fromId) await pc.setLocalDescription({ type: "rollback" }).catch(() => {});

                            await pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(signal.sdp) }));
                            const answer = await pc.createAnswer();
                            await pc.setLocalDescription(answer);
                            this.sendSignal(fromId, { type: 'answer', sdp: pc.localDescription.sdp });
                            while(peer.iceQueue.length) { await pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{}); }
                        } else if (signal.type === 'answer') {
                            if (peer.pc.signalingState === "stable") return;
                            await peer.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(signal.sdp) }));
                            while(peer.iceQueue.length) { await peer.pc.addIceCandidate(peer.iceQueue.shift()).catch(()=>{}); }
                        } else if (signal.type === 'ice') {
                            const cand = new RTCIceCandidate(signal.candidate);
                            if (peer.pc.remoteDescription && peer.pc.remoteDescription.type) await peer.pc.addIceCandidate(cand).catch(()=>{});
                            else peer.iceQueue.push(cand);
                        }
                    } catch(e) { console.error("Signal Error", e); }
                },

                normalizeSdp(sdp) { return sdp ? sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ''; },
                sendSignal(to, payload) { window.axios.post('/chat/signal', { partnerId: to, data: { ...payload, from: myId, roomUuid: roomUuid } }); },
                removePeer(id) {
                    const p = this.peers.find(x => x.id === id);
                    if (p) { 
                        p.pc.close(); 
                        this.peers = this.peers.filter(x => x.id !== id); 
                        // Если участник вышел пока мы смотрели его на весь экран - сбрасываем фокус
                        if (this.focusedId === id) {
                            this.focusedId = null;
                            this.isMaximized = false;
                        }
                    }
                },

                toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
                toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
                
                async toggleScreenShare() {
                    if (this.isScreenSharing) {
                        this.isScreenSharing = false;
                        this.screenStream.getTracks().forEach(t => t.stop());
                        this.replaceTrack(this.localStream.getVideoTracks()[0], 'video');
                        this.$refs.localVideo.srcObject = this.localStream;
                    } else {
                        this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                        this.isScreenSharing = true;
                        const track = this.screenStream.getVideoTracks()[0];
                        this.replaceTrack(track, 'video');
                        this.$refs.localVideo.srcObject = this.screenStream;
                        track.onended = () => this.toggleScreenShare();
                    }
                },

                replaceTrack(newTrack, kind = 'video') {
                    this.peers.forEach(p => {
                        const s = p.pc.getSenders().find(s => s.track?.kind === kind);
                        if (s && newTrack) s.replaceTrack(newTrack);
                    });
                },

                copyLink() { 
                    navigator.clipboard.writeText(window.location.href); 
                    window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'{{ __('rooms.Link_Captured') }}'}})); 
                }
            }
        }
    </script>