    <script>
        function groupRoomComponent(roomUuid, myId, myName, myNumericId) {
            return {
                myHashid: myId,
                myNumericId: myNumericId, 
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
                    
                    // --- ФИКС ЗВУКА ---
                    // При клике на карточку браузер снимает блокировку Autoplay.
                    // Находим элемент видео и принудительно включаем звук.
                    if (id !== 'me') {
                        const videoEl = document.getElementById('video-' + id);
                        if (videoEl && videoEl.muted) {
                            videoEl.muted = false;
                            window.dispatchEvent(new CustomEvent('toast', {detail:{msg: '🔊 Звук включен'}}));
                        }
                    }
                    // ------------------

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

                    const unlockAllAudio = () => {
                        self.peers.forEach(peer => {
                            const videoEl = document.getElementById('video-' + peer.id);
                            if (videoEl && videoEl.muted) {
                                videoEl.muted = false;
                            }
                        });
                    };
                    window.addEventListener('click', unlockAllAudio, { passive: true });
                    window.addEventListener('touchstart', unlockAllAudio, { passive: true });
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
                        
                        users.forEach(u => { 
                            if (String(u.id) !== String(this.myHashid)) {
                                this.initiateConnection(u.id, u.name, true); 
                            }
                        });
                    }).joining(u => {
                        this.currentCount = channel.subscription.members.count;
                        this.syncOccupancy(this.currentCount);
                        // Кто-то зашел. Я его вижу, но я НЕ инициирую соединение.
                        // Я просто готовлю "слушателя" (isInitiator = false)
                        // Он сам мне пришлет offer, так как я у него в списке .here()
                        this.initiateConnection(u.id, u.name, false); 
                    }).leaving(u => {
                        this.removePeer(u.id);
                        this.currentCount = Math.max(0, channel.subscription.members.count - 1);
                        this.syncOccupancy(this.currentCount);
                    });
                    window.Echo.private(`user.${this.myNumericId}`).listen('.WebRTCSignalEvent', (e) => {
                                    if (e.data.roomUuid === roomUuid) self.handleSignal(e.data);
                                });
setInterval(() => {
                        if (!window._peerStreams) return;
                        this.peers.forEach(peer => {
                            const videoEl = document.getElementById('video-' + peer.id);
                            const stream = window._peerStreams[peer.id];
                            
                            if (videoEl && stream && videoEl.srcObject !== stream) {
                                console.log("[Watchdog] Восстановление потока для:", peer.id);
                                videoEl.srcObject = stream;
                                // ДОБАВИТЬ ВОТ ЭТУ СТРОЧКУ:
                                videoEl.muted = false;
                                
                                videoEl.play().catch(() => {
                                    videoEl.muted = true;
                                    videoEl.play();
                                });
                            }
                        });
                    }, 2000);
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
    // Приводим ID к строке для корректного поиска
    const pId = String(partnerId);
    if (this.peers.find(p => String(p.id) === pId)) return;
    
    // Ждем появления локального потока, если он еще инициализируется
    let attempts = 0;
    while (!this.localStream && attempts < 25) {
        await new Promise(r => setTimeout(r, 200));
        attempts++;
    }
    if (!this.localStream) return;

    const pc = new RTCPeerConnection(this.rtcConfig);
    const peerObj = { 
        id: pId, 
        name: partnerName, 
        pc: pc, 
        iceQueue: [], 
        signalQueue: [], 
        isProcessingQueue: false, 
        connected: false,
        makingOffer: false
    };
    this.peers.push(peerObj);
    
    this.localStream.getTracks().forEach(t => pc.addTrack(t, this.localStream));

    pc.onicecandidate = e => { 
        if (e.candidate) this.sendSignal(pId, { type: 'ice', candidate: e.candidate }); 
    };
    
pc.ontrack = e => { 
        const remoteStream = e.streams[0];
        if (!window._peerStreams) window._peerStreams = {};
        window._peerStreams[pId] = remoteStream;

        const tryAttach = (retries = 0) => {
            const videoEl = document.getElementById('video-' + pId);
            if (videoEl) {
                if (videoEl.srcObject !== remoteStream) {
                    videoEl.srcObject = remoteStream;
                }
                
                // Снимаем мут для немедленного воспроизведения звука
                videoEl.muted = false;
                
                // ВАЖНО: Мы удалили videoEl.load(), так как он сбрасывает разрешение на автоплей!
                
                const playPromise = videoEl.play();
                if (playPromise !== undefined) {
                    playPromise.catch(() => {
                        console.warn("[Rooms] Autoplay blocked for user", pId);
                        // Запасной план сработает только если человек зашел по прямой ссылке
                        // и еще ни разу не кликнул по странице
                        videoEl.muted = true; 
                        videoEl.play();
                        window.dispatchEvent(new CustomEvent('toast', {detail:{msg: `Нажмите на экран, чтобы включить звук ${partnerName}`}}));
                    });
                }
            } else if (retries < 15) {
                setTimeout(() => tryAttach(retries + 1), 300);
            }
        };
        tryAttach();
    };
    
    pc.oniceconnectionstatechange = () => {
        const p = this.peers.find(x => String(x.id) === pId);
        if (p) p.connected = (pc.iceConnectionState === 'connected' || pc.iceConnectionState === 'completed');
    };

    if (isInitiator) {
        try {
            peerObj.makingOffer = true;
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            this.sendSignal(pId, { type: 'offer', sdp: pc.localDescription.sdp });
        } catch (e) { console.error("Offer error", e); }
        finally { peerObj.makingOffer = false; }
    }
},

async processSignalQueue(peer) {
    if (peer.isProcessingQueue || peer.signalQueue.length === 0) return;
    peer.isProcessingQueue = true;
    
    const signal = peer.signalQueue.shift();
    const pc = peer.pc;

    try {
        if (signal.type === 'offer') {
            // Perfect Negotiation: младший строковый ID уступает
            const offerCollision = (peer.makingOffer || pc.signalingState !== "stable");
            const isPolite = String(myId).toLowerCase() < String(peer.id).toLowerCase();

            if (offerCollision && !isPolite) {
                peer.isProcessingQueue = false;
                this.processSignalQueue(peer);
                return;
            }

            if (offerCollision && isPolite) {
                await pc.setLocalDescription({ type: "rollback" }).catch(() => {});
            }

            await pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(signal.sdp) }));
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            this.sendSignal(peer.id, { type: 'answer', sdp: pc.localDescription.sdp });

        } else if (signal.type === 'answer') {
            if (pc.signalingState === "have-local-offer") {
                await pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(signal.sdp) }));
            }
        } else if (signal.type === 'ice' && signal.candidate) {
            if (pc.remoteDescription && pc.remoteDescription.type) {
                await pc.addIceCandidate(new RTCIceCandidate(signal.candidate)).catch(() => {});
            } else {
                peer.iceQueue.push(signal.candidate);
            }
        }

        // Применяем ICE из очереди после установки RemoteDescription
        if (pc.remoteDescription && pc.remoteDescription.type && peer.iceQueue.length > 0) {
            while(peer.iceQueue.length) {
                await pc.addIceCandidate(new RTCIceCandidate(peer.iceQueue.shift())).catch(()=>{});
            }
        }
    } catch (e) {
        console.error("WebRTC Error:", e);
    } finally {
        peer.isProcessingQueue = false;
        this.processSignalQueue(peer);
    }
},


async processSignalQueue(peer) {
    if (peer.isProcessingQueue || peer.signalQueue.length === 0) return;

    peer.isProcessingQueue = true;
    const signal = peer.signalQueue.shift();
    const pc = peer.pc;

    try {
        if (signal.type === 'offer') {
            // Решение конфликтов (Collision Negotiation)
            const offerCollision = (pc.signalingState !== "stable");
            const ignoreOffer = offerCollision && (Number(myId) > Number(signal.from));

            if (ignoreOffer) {
                console.warn("⚠️ Collision: Ignoring offer from lower ID");
                return;
            }

            if (offerCollision) {
                await pc.setLocalDescription({ type: "rollback" }).catch(() => {});
            }

            await pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(signal.sdp) }));
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            this.sendSignal(peer.id, { type: 'answer', sdp: pc.localDescription.sdp });

        } else if (signal.type === 'answer') {
            if (pc.signalingState === "have-local-offer") {
                await pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(signal.sdp) }));
            }
        } else if (signal.type === 'ice' && signal.candidate) {
            try {
                if (pc.remoteDescription && pc.remoteDescription.type) {
                    await pc.addIceCandidate(new RTCIceCandidate(signal.candidate));
                } else {
                    peer.iceQueue.push(signal.candidate);
                }
            } catch (e) {}
        }

        // Применяем отложенные ICE кандидаты, если описание установилось
        if (pc.remoteDescription && pc.remoteDescription.type && peer.iceQueue.length > 0) {
            while(peer.iceQueue.length) {
                await pc.addIceCandidate(new RTCIceCandidate(peer.iceQueue.shift())).catch(()=>{});
            }
        }

    } catch (e) {
        console.error("Queue Error:", e);
    } finally {
        peer.isProcessingQueue = false;
        // Рекурсивно проверяем, не пришло ли что-то еще пока мы работали
        this.processSignalQueue(peer);
    }
},

async handleSignal(data) {
    const signal = data.type ? data : data.data; 
    const fromId = String(signal.from);

    // Защита от дублей пакетов
    let signalId = signal.id || `${signal.type}_${fromId}_${signal.sdp ? signal.sdp.length : (signal.candidate ? signal.candidate.candidate : Date.now())}`;
    if (!window._processedSignals) window._processedSignals = new Set();
    if (window._processedSignals.has(signalId)) return;
    window._processedSignals.add(signalId);
    setTimeout(() => window._processedSignals.delete(signalId), 5000);

    let peer = this.peers.find(p => String(p.id) === fromId);
    
    if (!peer && signal.type === 'offer') {
        await this.initiateConnection(fromId, 'User ' + fromId, false);
        peer = this.peers.find(p => String(p.id) === fromId);
    }

    if (peer) {
        peer.signalQueue.push(signal);
        this.processSignalQueue(peer);
    }
},

                normalizeSdp(sdp) { return sdp ? sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n' : ''; },
sendSignal(to, payload) { 
                window.axios.post('/chat/signal', { 
                    partnerId: String(to), 
                    data: { 
                        ...payload, 
                        from: String(myId), // Это наш hashid (изменен в Шаге 2)
                        roomUuid: roomUuid 
                    } 
                }); 
            },
                removePeer(id) {
                    if (window._peerStreams) delete window._peerStreams[id]; // Очищаем поток
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