export default (initData) => ({
    // --- ПЕРЕМЕННЫЕ ИЗ PHP ---
    myId: initData.myId,
    myHashid: initData.myHashid,
    myInterests: initData.myInterests,
    iceServers: initData.iceServers,
    translations: initData.translations,

    // --- UI & TABS ---
    globalSidebarOpen: false, 
    tab: 'chat', 
    controlsOpen: false, 
    state: 'idle', 
    callContext: null,
    incomingCall: null, 
    ringtone: new Audio('/sounds/call.mp3'), 
    msgSound: new Audio('/sounds/message.mp3'),
    blitzSound: new Audio('/sounds/glitch.wav'),
    audioUnlocked: false,
    layoutFocus: 'split',
    actionsOpen: false,
    isPartnerProfileOpen: false,
    uiShowPartnerCard: false,
    showLevelUp: false,
    remoteMuted: false, // Флаг для кнопки заглушения
    
    currentLevel: initData.currentLevel,
    totalXp: initData.totalXp,
    
    hasNewNotification: false,
    callDuration: 0,
    callTimer: null,
    timerExpanded: false,
    isProcessingContact: false,

    // --- МАССИВЫ (Исправляет ошибку .some() is not a function) ---
    friendsList: [],
    historyList: [],
    blockedList: [],
    messages: [],
    friendMessages: [],
    videoDevices: [],
    audioDevices: [],
    commonInterests: [],

    // --- ФЛАГИ И НАСТРОЙКИ (Исправляет ReferenceError) ---
    isBlitzActive: false,
    blitzCooldown: 0,
    isRemoteBlurred: false,
    camEnabled: true,
    micEnabled: true,
    beautyFilter: false,
    cinemaFilter: false,
    deviceModalOpen: false,
    filterModalOpen: false,
    showInterestMatch: false,

    // --- ФОРМЫ И СЕЛЕКТЫ ---
    chatInput: '',
    friendChatInput: '',
    selectedVideoId: null,
    selectedAudioId: null,
    activeFriend: null,

    // --- PARTNER DATA ---
    partnerId: null, 
    partnerData: null, 
    isFriend: false, 
    partnerState: 'active',
    isPartnerTyping: false, 
    typingPartnerName: '', 
    ping: 0, 

    // --- WEBRTC ---
    pc: null, 
    localStream: null,
    partnerCamEnabled: true, // НОВОЕ: Знает ли собеседник, что мы включили камеру
    partnerMicEnabled: true, // НОВОЕ: Знает ли собеседник, что мы включили микрофон
    partnerFilters: { beauty: false, cinema: false },
    rtcConfig: { 
        iceServers: initData.iceServers, 
        bundlePolicy: "balanced", 
        iceCandidatePoolSize: 10, // ВРЕМЕННО: Заставит трафик идти ТОЛЬКО через TURN
    },

    // --- ЛОГИКА ТАРГЕТИНГА ---
    targetCountry: initData.targetCountry,
    targetGender: initData.targetGender,
    targetAgeMin: initData.targetAgeMin,
    targetAgeMax: initData.targetAgeMax,
    showIcebreakerOverlay: false,
    icebreakerQuestion: '',
    icebreakerTimer: null,
    icebreakerCooldown: 0,
    iceTimer: null, 

    countryNames: {
        'global': '🌍 ' + initData.translations.all,
        'az': '🇦🇿 Azerbaijan', 'ge': '🇬🇪 Georgia', 'am': '🇦🇲 Armenia',
        'ru': '🇷🇺 Russia', 'kz': '🇰🇿 Kazakhstan', 'uz': '🇺🇿 Uzbekistan',
        'ua': '🇺🇦 Ukraine', 'tr': '🇹🇷 Turkey', 'de': '🇩🇪 Germany',
        'es': '🇪🇸 Spain', 'pl': '🇵🇱 Poland', 'us': '🇺🇸 USA',
        'ca': '🇨🇦 Canada', 'fr': '🇫🇷 France', 'it': '🇮🇹 Italy', 'gb': '🇬🇧 UK'
    },

    async togglePiP() {
        const video = document.getElementById('remoteVideo');
        if (!video) return;
        
        try {
            if (document.pictureInPictureElement) {
                await document.exitPictureInPicture();
            } else if (document.pictureInPictureEnabled) {
                await video.requestPictureInPicture();
            }
        } catch (error) {
            console.error("PiP Error:", error);
            this.toast('PiP не поддерживается вашим браузером');
        }
    },

    // 2. Логика заглушения собеседника
    toggleRemoteAudio() {
        const video = document.getElementById('remoteVideo');
        if (!video) return;
        
        this.remoteMuted = !this.remoteMuted;
        video.muted = this.remoteMuted;
        
        if (this.remoteMuted) {
            this.toast('Собеседник заглушен 🔕');
        } else {
            this.toast('Звук включен 🔔');
        }
    },

    // 3. Логика Скриншота (Canvas)
    takeSnapshot() {
        const video = document.getElementById('remoteVideo');
        if (!video || video.readyState < 2 || this.state !== 'connected') {
            this.toast('Нет активного видео для снимка');
            return;
        }
        
        try {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            
            // Если включен Ч/Б фильтр, применяем его и на скриншот
            if (this.partnerFilters?.cinema || this.cinemaFilter) {
                ctx.filter = 'grayscale(100%) contrast(125%)';
            }
            
            // Рисуем текущий кадр
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Скачиваем
            const link = document.createElement('a');
            link.download = `Caspian_Snap_${new Date().getTime()}.jpeg`;
            link.href = canvas.toDataURL('image/jpeg', 0.9);
            link.click();
            
            // Тактильная и визуальная отдача
            this.vibrate(50);
            this.toast('📸 Снимок сохранен!');
            
            // Эффект белой вспышки на экране (опционально)
            const flash = document.createElement('div');
            flash.className = 'fixed inset-0 bg-white z-[9999] pointer-events-none transition-opacity duration-300 opacity-100';
            document.body.appendChild(flash);
            setTimeout(() => flash.classList.replace('opacity-100', 'opacity-0'), 50);
            setTimeout(() => flash.remove(), 400);

        } catch (error) {
            console.error("Snapshot Error:", error);
        }
    },

    async updateTargetCountry(country) {
        this.targetCountry = country;
        try {
            await window.axios.post('/profile', { _method: 'PATCH', target_country: country });
            this.toast(this.__('target_country_updated'));
        } catch (e) {
            this.toast(this.__('update_failed'));
        }
    },

    async applyFilters() {
        try {
            await window.axios.post('/profile', { 
                _method: 'PATCH',
                target_gender: this.targetGender,
                target_age_min: this.targetAgeMin,
                target_age_max: this.targetAgeMax
            });
            this.filterModalOpen = false;
            this.toast(this.__('target_gender_updated') + ' 🎯');
            
            if(this.state === 'searching') this.startSearch(); 
        } catch (e) {
            console.error("Filter Save Error:", e);
            this.toast(this.__('save_failed'));
        }
    },

    async removeContact(contactId) {
        if (!confirm(this.__('remove_friend_sure'))) return;
        
        try {
            await window.axios.post('/chat/contact/remove', { contactId });
            this.loadFriends();
            
            if (this.activeFriend && Number(this.activeFriend.id) === Number(contactId)) {
                this.activeFriend = null;
            }

            window.dispatchEvent(new CustomEvent('contact-removed', { 
                detail: { contactId: Number(contactId) } 
            }));
            
            this.toast(this.__('contact_unlinked') + ' ✕');
        } catch (e) {
            console.error("Error removing contact:", e);
        }
    },

    startCallTimer() {
        this.stopCallTimer();
        this.callDuration = 0;
        this.callTimer = setInterval(() => {
            if (this.state === 'connected') {
                this.callDuration++;
            } else {
                this.stopCallTimer();
            }
        }, 1000);
    },

    stopCallTimer() {
        if (this.callTimer) {
            clearInterval(this.callTimer);
            this.callTimer = null;
        }
        this.callDuration = 0;
    },

    formatCallTime() {
        const mins = Math.floor(this.callDuration / 60);
        const secs = this.callDuration % 60;
        return (mins < 10 ? '0' + mins : mins) + ":" + (secs < 10 ? '0' + secs : secs);
    },

    async sendIcebreaker() {
        if (this.state !== 'connected' || this.icebreakerCooldown > 0) return;

        try {
            const res = await window.axios.get('/icebreaker/random');
            const index = res.data.index;
            this.signal({ type: 'icebreaker', index: index });
            await this.displayIcebreaker(index);

            this.icebreakerCooldown = 60;
            this.iceTimer = setInterval(() => {
                this.icebreakerCooldown--;
                if (this.icebreakerCooldown <= 0) clearInterval(this.iceTimer);
            }, 1000);
        } catch (e) {
            console.error("Icebreaker failed", e);
        }
    },

    async displayIcebreaker(index) {
        try {
            const res = await window.axios.get(`/icebreaker/content/${index}`);
            
            if (this.icebreakerTimer) clearTimeout(this.icebreakerTimer);
            
            this.icebreakerQuestion = res.data.question;
            this.showIcebreakerOverlay = true;

            this.icebreakerTimer = setTimeout(() => {
                this.showIcebreakerOverlay = false;
            }, 12000); 
        } catch (e) {
            console.error("Icebreaker content fetch failed", e);
        }
    },

    triggerBlitz() {
        if (this.state !== 'connected' || this.isBlitzActive || this.blitzCooldown > 0) return;

        this.blitzCooldown = 60;
        
        if (this.blitzTimer) clearInterval(this.blitzTimer);
        
        this.blitzTimer = setInterval(() => {
            this.blitzCooldown--;
            if (this.blitzCooldown <= 0) {
                clearInterval(this.blitzTimer);
                this.blitzTimer = null;
            }
        }, 1000);

        this.signal({ type: 'blitz' });
        this.startBlitzEffect();
    },
    
    startBlitzEffect() {
        this.isBlitzActive = true;
        this.vibrate([100, 50, 100, 50, 200]);

        if (this.blitzSound) {
            this.blitzSound.volume = 1.0;
            this.blitzSound.play();
        }

        document.body.style.transition = 'none';
        
        let hellInterval = setInterval(() => {
            if (!this.isBlitzActive) {
                clearInterval(hellInterval);
                document.body.style.transform = '';
                document.body.style.filter = '';
                return;
            }

            const bgColor = Math.random() > 0.8 ? '#4a0000' : '#020202';
            document.body.style.backgroundColor = bgColor;
            
            const x = (Math.random() - 0.5) * 20;
            const y = (Math.random() - 0.5) * 20;
            document.body.style.transform = `translate(${x}px, ${y}px)`;
            
            if (Math.random() > 0.95) {
                document.body.style.filter = 'invert(1) contrast(2)';
            } else {
                document.body.style.filter = '';
            }
        }, 50);

        setTimeout(() => {
            this.isBlitzActive = false;
            document.body.style.backgroundColor = '#020202';
            document.body.style.transition = 'all 1s ease';
        }, 6666);
    },

    async rebootMobileCamera() {
        if (!this.localStream || !this.pc || this.state !== 'connected') return;
        
        console.log("[Android Fix] Аппаратное пробуждение камеры...");
        
        try {
            const oldTrack = this.localStream.getVideoTracks()[0];
            const deviceId = oldTrack ? oldTrack.getSettings().deviceId : null;

            const freshStream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    deviceId: deviceId ? { exact: deviceId } : undefined,
                    width: { ideal: 640 }, 
                    height: { ideal: 480 } 
                },
                audio: false 
            });

            const newTrack = freshStream.getVideoTracks()[0];

            const sender = this.pc.getSenders().find(s => s.track && s.track.kind === 'video');
            if (sender) {
                await sender.replaceTrack(newTrack);
                console.log("[WebRTC] Трек успешно заменен в канале");
            }

            oldTrack.stop();
            this.localStream.removeTrack(oldTrack);
            this.localStream.addTrack(newTrack);
            
            if (this.$refs.localVideo) {
                this.$refs.localVideo.srcObject = this.localStream;
            }

            this.signal({ type: 'request-keyframe' });

        } catch (e) {
            console.error("[Android Fix] Ошибка перезапуска камеры:", e);
        }
    },

    async openDeviceSettings() {
        const devices = await navigator.mediaDevices.enumerateDevices();
        this.videoDevices = devices.filter(d => d.kind === 'videoinput');
        this.audioDevices = devices.filter(d => d.kind === 'audioinput');

        if (this.localStream) {
            this.selectedVideoId = this.localStream.getVideoTracks()[0].getSettings().deviceId;
            this.selectedAudioId = this.localStream.getAudioTracks()[0].getSettings().deviceId;
        }
        
        this.deviceModalOpen = true;
    },

    async changeVideoDevice() {
        try {
            if (this.localStream) {
                this.localStream.getTracks().forEach(t => t.stop());
            }

            const constraints = {
                video: { deviceId: { ideal: this.selectedVideoId }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: { deviceId: { ideal: this.selectedAudioId } }
            };
            const newStream = await navigator.mediaDevices.getUserMedia(constraints);

            if (this.localStream) {
                this.localStream.getTracks().forEach(track => track.stop());
            }

            this.localStream = newStream;
            if (this.$refs.localVideo) {
                this.$refs.localVideo.srcObject = newStream;
                await this.$refs.localVideo.play();
            }

            if (this.pc) {
                const videoTrack = newStream.getVideoTracks()[0];
                const audioTrack = newStream.getAudioTracks()[0];
                
                const videoSender = this.pc.getSenders().find(s => s.track?.kind === 'video');
                const audioSender = this.pc.getSenders().find(s => s.track?.kind === 'audio');

                if (videoSender) await videoSender.replaceTrack(videoTrack);
                if (audioSender) await audioSender.replaceTrack(audioTrack);
            }

            this.toast(this.__('hardware_synced'));
            this.deviceModalOpen = false;
        } catch (e) {
            console.error(e);
            this.toast(this.__('device_error'));
        }
    },

    refreshVideoTags() {
        const localEl = document.getElementById('localVideo');
        if (localEl && this.localStream && localEl.srcObject !== this.localStream) {
            localEl.srcObject = this.localStream;
        }
    },

    async changeAudioDevice() {
        this.changeVideoDevice(); 
    },

    async init() {
        const self = this; 

        this.$watch('globalSidebarOpen', value => {
            if (value) {
                this.hasNewNotification = false;
            }
        });
        const onceUnlock = () => {
            self.unlockAudio();
            window.removeEventListener('touchstart', onceUnlock);
            window.removeEventListener('mousedown', onceUnlock);
        };
        window.addEventListener('touchstart', onceUnlock, { passive: true });
        window.addEventListener('mousedown', onceUnlock, { passive: true });
        
        this.ringtone.load();
        this.msgSound.load();
        
        if (this.blitzSound) this.blitzSound.load();

        window.Echo.private(`user.${this.myId}`)
            .listen('.MatchFoundEvent', (e) => {
                if (self.callContext === 'personal') return;
                self.vibrate(20); 
                self.handleMatch(e);
            })
            .listen('.XpGainedEvent', (e) => {
                if (e.currentLevel > this.currentLevel) {
                    this.currentLevel = e.currentLevel;
                    this.showLevelUp = true;
                    this.vibrate(50);
                    setTimeout(() => { this.showLevelUp = false; }, 5000);
                }
                this.totalXp = e.totalXp;
            })
            .listen('.WebRTCSignalEvent', (e) => self.handleSignal(e))
            .listen('.MessageSentEvent', (e) => self.handleIncomingMsg(e))
            .listen('.UserTypingEvent', (e) => self.handleTyping(e));

        window.addEventListener('contact-removed', (e) => {
            if (Number(this.partnerId) === Number(e.detail.contactId)) {
                this.isFriend = false;
            }
        });

        if (window.location.pathname === '/chat') {
            this.$nextTick(async () => {
                await self.initMedia();
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('accept_call')) self.setupPersonalCall(urlParams.get('accept_call'), true);
                else if (urlParams.has('call_to')) self.setupPersonalCall(urlParams.get('call_to'), false);
                setInterval(() => { this.refreshVideoTags(); }, 2000);
            });
        }
        this.loadFriends(); this.loadHistory(); this.loadBlocked();
        this.startStats();
        this.startHeartbeat();
    },

    formatDateTime(msg) {
        if (!msg) return '';
        
        const dateSource = msg.created_at || msg.timestamp;
        if (!dateSource) return '';

        const msgDate = new Date(dateSource);
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);

        const timeStr = msgDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        if (msgDate.toDateString() === today.toDateString()) {
            return timeStr;
        }

        if (msgDate.toDateString() === yesterday.toDateString()) {
            return `Вчера, ${timeStr}`;
        }

        if (msgDate.getFullYear() !== today.getFullYear()) {
            const dateWithYearStr = msgDate.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
            return `${dateWithYearStr}, ${timeStr}`;
        }

        const dateStr = msgDate.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
        return `${dateStr}, ${timeStr}`;
    },

async initMedia() {
    // 1. Если поток уже инициализирован, просто проверяем привязку и выходим
    if (this.localStream) {
        const videoEl = this.$refs.localVideo || document.getElementById('localVideo');
        if (videoEl && videoEl.srcObject !== this.localStream) {
            videoEl.srcObject = this.localStream;
        }
        return;
    }

    try {
        // 2. Запрашиваем доступ к камере и микрофону
        // Используем идеальные (ideal) значения, чтобы браузер сам подобрал ближайшее разрешение
        this.localStream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 }, 
                height: { ideal: 480 }, 
                frameRate: { max: 30 } 
            }, 
            audio: true 
        });

        // 3. Ищем элемент видео (сначала через $refs, затем по ID как фолбэк)
        const videoEl = this.$refs.localVideo || document.getElementById('localVideo');

        if (videoEl) {
            videoEl.srcObject = this.localStream;
            
            // 4. Используем небольшую задержку перед play()
            // Это решает проблему AbortError в Chrome и Safari при быстрой загрузке
            setTimeout(() => {
                videoEl.play().catch(e => {
                    // Ошибку прерывания (AbortError) игнорируем, она не влияет на результат
                    if (e.name !== 'AbortError') {
                        console.warn("Autoplay interaction required or blocked:", e);
                    }
                });
            }, 200);
        }
    } catch (e) { 
        console.error("Camera access error:", e);
        this.toast(this.__('camera_denied')); 
    }
},

    toggleFocus(target) {
        if (this.state !== 'connected') return;
        this.layoutFocus = (this.layoutFocus === target) ? 'split' : target;
    },
    toggleMic() { 
            this.micEnabled = !this.micEnabled; 
            if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; 
            this.syncMediaState(); // Отправляем статус
        },
    toggleCam() { 
        this.camEnabled = !this.camEnabled; 
        if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; 
        this.syncMediaState(); // Отправляем статус
    },

    // Метод отправки нашего статуса партнеру
    syncMediaState() {
        if (this.state === 'connected') {
            this.signal({ 
                type: 'media-state', 
                cam: this.camEnabled, 
                mic: this.micEnabled 
            });
        }
    },
    toggleBeauty() {
        this.beautyFilter = !this.beautyFilter;
        this.syncFilters();
        this.toast(this.beautyFilter ? this.__('contrast_filter_on') : this.__('contrast_filter_off'));
    },

    toggleCinema() {
        this.cinemaFilter = !this.cinemaFilter;
        this.syncFilters();
        this.toast(this.cinemaFilter ? this.__('monochrome_filter_on') : this.__('monochrome_filter_off'));
    },

    syncFilters() {
        this.signal({ 
            type: 'filter-sync', 
            filters: { beauty: this.beautyFilter, cinema: this.cinemaFilter } 
        });
    },
    
    getFilterClass(target) {
        const f = (target === 'local') 
            ? { b: this.beautyFilter, c: this.cinemaFilter } 
            : { b: this.partnerFilters.beauty, c: this.partnerFilters.cinema };
        
        if (f.b && f.c) return 'grayscale contrast-[1.3] brightness-110 transition-all duration-700';
        if (f.b) return 'contrast-[1.15] saturate-[1.3] brightness-110 transition-all duration-700';
        if (f.c) return 'grayscale contrast-125 sepia-[.15] transition-all duration-700';
        
        return 'transition-all duration-700';
    },

    initPC() {
        this.startVideoWatchdog();
        if (this.pc) this.pc.close();
        const self = this;
        this.iceQueue = [];
        this.pc = new RTCPeerConnection(this.rtcConfig);

        this.pc.onnegotiationneeded = async () => {
            try {
                console.log("[WebRTC] Negotiation needed...");
                await self.sendOffer();
            } catch (err) {
                console.error("[WebRTC] Negotiation Error:", err);
            }
        };

        this.pc.onicecandidate = (e) => { 
            if (e.candidate) self.signal({ type: 'ice', candidate: e.candidate }); 
        };

        this.pc.onconnectionstatechange = () => {
            console.log("[WebRTC] Connection State:", this.pc.connectionState);
            
            if (this.pc.connectionState === 'connected') {
                console.log("[WebRTC] Fully Connected. Checking track health...");
                this.startVideoWatchdog();
                setTimeout(() => {
                    const remoteVid = document.getElementById('remoteVideo') || this.$refs.remoteVideo;
                    if (remoteVid && remoteVid.readyState < 2) {
                        console.warn("[WebRTC] Video stuck at black screen. Nudging with ICE Restart...");
                        if (self.isPolite()) {
                            self.sendOffer({ iceRestart: true });
                        }
                    }
                }, 2000);
            }
            if (this.pc.connectionState === 'failed' || this.pc.connectionState === 'disconnected') {
                if (this.watchdogTimer) clearInterval(this.watchdogTimer);
            }
        };

        this.pc.ontrack = (e) => {
            const remoteStream = e.streams[0];
            const videoEl = document.getElementById('remoteVideo');
            
            if (videoEl && remoteStream) {
                if (videoEl.srcObject !== remoteStream) {
                    videoEl.srcObject = remoteStream;
                }

                setTimeout(() => {
                    const playPromise = videoEl.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(err => {
                            if (err.name === 'AbortError') return; 
                            videoEl.muted = true;
                            videoEl.play().then(() => {
                                setTimeout(() => { videoEl.muted = false; }, 100);
                            }).catch(e => {});
                        });
                    }
                }, 150);
            }
        };

        this.pc.oniceconnectionstatechange = () => {
            const iceState = self.pc.iceConnectionState;
            console.log("[WebRTC] ICE State Changed:", iceState);

            if (['disconnected', 'failed'].includes(iceState)) {
                this.partnerState = 'problem';
                this.wasDisconnected = true; 
                console.log("[WebRTC] Network path lost. Starting recovery timer...");
                
                setTimeout(() => {
                    const currentState = self.pc.iceConnectionState;
                    if (['disconnected', 'failed'].includes(currentState)) {
                        console.log("[WebRTC] Proactive ICE Restart initiated after dropout.");
                        self.sendOffer({ iceRestart: true });
                    }
                }, 3000);
            } 
            else if (['connected', 'completed'].includes(iceState)) {
                this.partnerState = 'active';

                this.$nextTick(() => {
                    const remoteVid = self.$refs.remoteVideo || document.getElementById('remoteVideo');
                    if (remoteVid) {
                        if (this.wasDisconnected && remoteVid.srcObject) {
                            const stream = remoteVid.srcObject;
                            remoteVid.srcObject = null;
                            remoteVid.srcObject = stream;
                            this.wasDisconnected = false;
                        }

                        remoteVid.play().catch(err => {
                            remoteVid.muted = true; 
                            remoteVid.play();
                        });
                    }
                });
            }
        };

        if (this.localStream) {
            this.localStream.getTracks().forEach(t => {
                this.pc.addTrack(t, this.localStream);
            });
        }
    },

    isPolite() {
        if (!this.partnerId) return true;
        return Number(this.myId) < Number(this.partnerId);
    },

    async handleSignal(e) {
        const m = e.data;
        const self = this;
        const fromHashid = String(m.from);
        
        if (window.location.pathname.includes('/rooms/') && 
            ['offer', 'answer', 'ice'].includes(m.type)) {
            return; 
        }

        if (m.type === 'media-state') {
            this.partnerCamEnabled = m.cam;
            this.partnerMicEnabled = m.mic;
            return;
        }

        if (m.type === 'you-are-blocked') { 
            this.stopCall(false); 
            this.toast(this.__('blacklisted'));
            setTimeout(() => window.location.href = '/dashboard', 3000);
            return; 
        }
        
        if (m.type === 'incoming-call') { 
            this.incomingCall = m; 
            this.isRinging = true;
            this.playSound(this.ringtone); 
            return; 
        }

        if (['hang-up', 'peer-disconnected', 'call-accepted'].includes(m.type)) {
            this.stopRingtone();
            if (m.type !== 'call-accepted') this.reset();
        }
        
        const strictMediaSignals = ['offer', 'answer', 'ice', 'request-keyframe', 'blitz', 'filter-sync'];
        if (strictMediaSignals.includes(m.type)) {
            if (!this.pc && m.type !== 'call-accepted') {
                console.warn("[WebRTC] Signal received but PeerConnection is null. Ignoring:", m.type);
                return; 
            }
        }
        
        if (m.type === 'status-sync') {
            this.partnerState = m.state;
            if (m.state === 'active') {
                console.log("[WebRTC] Partner is back. Checking stream health...");
                setTimeout(() => {
                    const videoEl = document.getElementById('remoteVideo');
                    if (videoEl && videoEl.readyState < 2 && this.state === 'connected') {
                        console.warn("[WebRTC] Stream is still frozen. Initiating ICE Restart...");
                        this.sendOffer({ iceRestart: true });
                    }
                }, 2500);
            }
            return;
        }

        if (m.type === 'request-keyframe') {
            console.log("[WebRTC] Partner requested keyframe.");
            if (this.pc) {
                this.pc.getSenders().forEach(sender => {
                    if (sender.track && sender.track.kind === 'video') {
                        sender.track.enabled = false;
                        sender.track.enabled = true;
                    }
                });
            }
            return;
        }

        if (m.type === 'filter-sync') { this.partnerFilters = m.filters; return; }
        if (['peer-disconnected', 'hang-up', 'peer-skipped'].includes(m.type)) { this.stopCall(false); return; }
        
        if (m.type === 'call-accepted') { 
            console.log("[WebRTC] Partner is ready. Initiating handshake...");
            this.state = 'connected'; 
            this.startCallTimer();
            if (!this.pc) this.initPC(); 

            if (this.localStream) {
                const senders = this.pc.getSenders();
                this.localStream.getTracks().forEach(track => {
                    if (!senders.some(s => s.track && s.track.kind === track.kind)) {
                        this.pc.addTrack(track, this.localStream);
                    }
                });
            }

            if (String(this.myHashid) < String(this.partnerId)) {
                console.log("[WebRTC] I am Lead, sending offer...");
                setTimeout(() => { this.sendOffer(); }, 500); 
            }
            return; 
        }
        
        if (m.type === 'icebreaker') {
            this.displayIcebreaker(m.index);
            return;
        }

        if (m.type === 'blitz') {
            this.startBlitzEffect(); 
            this.toast('⚡️ ' + this.__('system_overload'));
            return;
        }

if (m.type === 'roulette-chat') {
            if (this.audioUnlocked) this.msgSound.play().catch(()=>{});
            
            this.messages.push({
                isMe: false, 
                text: m.text, 
                timestamp: Date.now()
            }); 
            this.scrollChat(); 
            return;
        }

        // 🛑 1. ЗАЩИТА ОТ ДВОЙНЫХ ПАКЕТОВ REVERB (Тот самый фикс из комнат)
        let signalId = m.id;
        if (!signalId) {
            if (m.type === 'ice' && m.candidate) {
                signalId = `ice_${m.from}_${m.candidate.candidate}`;
            } else if (m.type === 'offer' || m.type === 'answer') {
                signalId = `${m.type}_${m.from}_${m.sdp ? m.sdp.length : 'none'}`;
            } else {
                signalId = `${m.type}_${m.from}_${Date.now()}`;
            }
        }

        if (!window._processedSignals) window._processedSignals = new Set();
        if (window._processedSignals.has(signalId)) return;
        window._processedSignals.add(signalId);
        setTimeout(() => window._processedSignals.delete(signalId), m.type === 'ice' ? 2000 : 10000);


        // 🛑 2. ГИБКАЯ БЛОКИРОВКА (Больше не убиваем ICE-кандидаты!)
        try {
            if (m.type === 'offer') {
                if (this.isProcessingSignal) return; // Блокируем только двойные офферы
                this.isProcessingSignal = true;
                
                const offerCollision = (this.makingOffer || (this.pc && this.pc.signalingState !== 'stable'));
                this.ignoreOffer = !this.isPolite() && offerCollision;
                
                if (this.ignoreOffer) {
                    console.warn("[WebRTC] Collision: Ignoring offer");
                    this.isProcessingSignal = false;
                    return;
                }

                if (m.iceRestart) {
                    console.log("[WebRTC] Partner initiated ICE Restart. Clearing old candidates...");
                    this.iceQueue = [];
                }

                if (offerCollision) {
                    console.log("[WebRTC] Collision: Polite peer rolling back local offer.");
                    await Promise.all([
                        this.pc.setLocalDescription({ type: "rollback" }),
                        this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }))
                    ]);
                } else {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.normalizeSdp(m.sdp) }));
                }
                
                const answer = await this.pc.createAnswer();
                await this.pc.setLocalDescription(answer);
                this.signal({ type: 'answer', sdp: this.pc.localDescription.sdp }); 
                
                // Применяем ICE, которые прилетели, пока мы думали над ответом
                while(this.iceQueue.length) {
                    await this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
                }
                this.isProcessingSignal = false;

            } else if (m.type === 'answer') {
                if (this.isProcessingSignal) return; // Блокируем двойные ансверы
                this.isProcessingSignal = true;
                
                if (this.pc.signalingState === 'have-local-offer') {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(m.sdp) }));
                    while(this.iceQueue.length) {
                        await this.pc.addIceCandidate(this.iceQueue.shift()).catch(e => {});
                    }
                }
                this.isProcessingSignal = false;

            } else if (m.type === 'ice') {
                // ICE обрабатываются ВСЕГДА, даже если isProcessingSignal === true
                const candidate = new RTCIceCandidate(m.candidate);
                
                if (!this.pc || !this.pc.remoteDescription || !this.pc.remoteDescription.type) {
                    // Если описание еще не готово — складываем в очередь
                    this.iceQueue.push(candidate);
                } else {
                    // Если готово — добавляем сразу
                    await this.pc.addIceCandidate(candidate).catch(e => {
                        if (!this.ignoreOffer) console.warn("[WebRTC] ICE Error", e);
                    });
                }
            }
        } catch (err) {
            console.error("[WebRTC] Signal Handling Critical Error:", err);
            this.isProcessingSignal = false;
        }
    },

    async handleMatch(e) {
        if (this.callContext === 'personal') return;
        this.isPartnerProfileOpen = false;
        this.uiShowPartnerCard = false;
        const partner = e.partnerData || e; 
        const newPartnerId = partner.id ? String(partner.id) : null;
        
        if (!newPartnerId) {
            console.error("[Caspian] Match received but partner ID is missing!", e);
            return;
        }

        if (this.partnerId === newPartnerId && this.state === 'connected') {
            return;
        }

        this.reset();
        this.partnerId = newPartnerId;
        this.callContext = 'roulette';
        
        this.partnerData = {
            id: this.partnerId,
            name: partner.name || 'Anonymous',
            gender: partner.gender, 
            age: partner.age, 
            level: partner.level || 1,
            badge: partner.badge,
            rank_name: partner.rank_name || 'Regular',
            karma: partner.karma || 0,
            country_code: partner.country_code || 'us',
            country_flag: partner.country_flag || 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg',
            interests: partner.interests || [],
            common_interests: partner.common_interests || [] ,
            blocked_count: partner.blocked_count || 0,
            ban_count: partner.ban_count || 0,
            vpn: partner.vpn,
        };
        
        this.isFriend = this.friendsList.some(f => String(f.id) === this.partnerId);

        let common = this.partnerData.common_interests;
        
        if (!common || common.length === 0) {
            const myInts = (this.myInterests || []).map(i => String(i).toLowerCase());
            const pInts = (this.partnerData.interests || []).map(i => String(i).toLowerCase());
            common = pInts.filter(i => myInts.includes(i));
        }

        this.commonInterests = common;

        if (this.commonInterests.length > 0) {
            this.showInterestMatch = true;
            setTimeout(() => { this.showInterestMatch = false; }, 6000);
        }

        this.state = 'connected';
        this.tab = 'chat';
        
        this.initPC();

        if (this.myId < this.partnerId) {
            setTimeout(() => this.sendOffer(), 1200);
        }

        this.startCallTimer();
    },

    async setupPersonalCall(id, isAccepted) {
        this.callContext = 'personal';
        this.partnerId = String(id);
        this.state = 'connecting';
        this.micEnabled = true; 
        this.camEnabled = true; 

        window.history.replaceState({}, '', '/chat');

        try {
            if (!this.pc) this.initPC();

            await this.initMedia();

            if (this.pc && this.localStream) {
                const senders = this.pc.getSenders();
                this.localStream.getTracks().forEach(track => {
                    if (!senders.some(s => s.track && s.track.kind === track.kind)) {
                        this.pc.addTrack(track, this.localStream);
                    }
                });
            }

            const res = await window.axios.get(`/chat/user-info/${id}`);
            this.partnerData = res.data;

            if (isAccepted) {
                this.state = 'connected';
                setTimeout(() => {
                    this.signal({ type: 'call-accepted' });
                }, 500);
            } else {
                const r = await window.axios.post('/chat/contact/call', { contactId: id });

                if (r.data.status === 'busy') {
                    this.stopCall(false);
                    this.toast(this.__('user_busy'));
                    return;
                }

                this.toast(this.__('calling') + '...');
            }
        } catch (e) {
            console.error("Call Setup Error:", e);
            this.stopCall(false);
        }
    },

    unblock(blockedId) {
        window.axios.post('/chat/unblock', { blockedId: blockedId })
        .then(() => {
            this.toast(this.__('unblocked'));
            this.loadBlocked(); 
            this.loadFriends(); 
            this.loadHistory(); 
        });
    },

    unlockAudio() {
        if (this.audioUnlocked) return;
        
        this.audioUnlocked = true;

        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                const ctx = new AudioContext();
                const oscillator = ctx.createOscillator();
                const gain = ctx.createGain();
                gain.gain.value = 0; 
                oscillator.connect(gain);
                gain.connect(ctx.destination);
                oscillator.start(0);
                oscillator.stop(0.1);
                if (ctx.state === 'suspended') ctx.resume();
            }

            [this.ringtone, this.msgSound, this.blitzSound].forEach(audio => {
                if(!audio) return;
                audio.muted = true; 
                audio.play().then(() => {
                    audio.pause();
                    audio.muted = false; 
                    audio.currentTime = 0;
                }).catch(() => {});
            });

            console.log("Caspian Audio: Stealth Unlock Done 🔓");
        } catch (e) {
            console.error("Audio unlock error", e);
        }
    },

    playSound(audioElement) {
        if (!this.audioUnlocked || !audioElement) return;

        audioElement.pause();
        audioElement.currentTime = 0;
        
        setTimeout(() => {
            const playPromise = audioElement.play();
            if (playPromise !== undefined) {
                playPromise.catch(err => {
                    if (err.name !== 'AbortError') console.warn("Playback prevented", err);
                });
            }
        }, 20);
    },

    stopRingtone() {
        this.isRinging = false;
        this.ringtone.pause();
        this.ringtone.currentTime = 0;
    },

    acceptCall() {
        this.stopRingtone(); 
        const fromId = this.incomingCall.fromId;
        window.location.href = `/chat?accept_call=${this.incomingCall.fromId}`; 
    },

    rejectCall() {
        this.stopRingtone(); 
        if (this.incomingCall) this.signalTo(this.incomingCall.fromId, { type: 'hang-up' });
        this.incomingCall = null;
    },

    stopCall(notify = true) {
        if (notify && this.partnerId) this.signal({ type: 'hang-up' });
        this.stopRingtone();
        this.reset();
        window.axios.post('/chat/leave');
        this.toast(this.__('call_ended'));
    },

    reset() {
        this.ringtone.pause();
        this.stopCallTimer();
        this.isPartnerProfileOpen = false;
        this.uiShowPartnerCard = false;
        if (this.pc) { this.pc.close(); this.pc = null; }
        if (this.$refs.remoteVideo) {
            this.$refs.remoteVideo.pause();
            this.$refs.remoteVideo.srcObject = null;
        }
        this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.callContext = null;
        this.partnerFilters = { beauty: false, cinema: false }; this.messages = [];
        
        if (this.watchdogTimer) {
            clearInterval(this.watchdogTimer);
            this.watchdogTimer = null;
        }

        if (this.iceTimer) { clearInterval(this.iceTimer); this.iceTimer = null; }
        if (this.blitzTimer) { clearInterval(this.blitzTimer); this.blitzTimer = null; }
        this.icebreakerCooldown = 0;
        this.blitzCooldown = 0;
        this.partnerCamEnabled = true; 
        this.partnerMicEnabled = true;
    },

    async toggleContact() {
        if (!this.partnerId) return;

        try {
            if (this.isFriend) {
                const res = await window.axios.post('/chat/contact/remove', { contactId: this.partnerId });

                if (res.data.success || res.data.action === 'removed') {
                    this.isFriend = false;
                    this.toast(this.__('contact_removed') + ' 🗑️');
                }
            } else {
                const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId });

                if (res.data.action === 'requested') {
                    this.isFriend = false; 
                    this.toast(this.__('request_encrypted') + ' 📡');
                } else if (res.data.action === 'added') {
                    this.isFriend = true;
                    this.toast(this.__('contact_added') + ' 🤝');
                } else if (res.data.action === 'exists') {
                    this.isFriend = true; 
                    this.toast(this.__('protocol_active'));
                }
            }
        } catch (e) {
            console.error('Toggle contact error:', e);
        }
    },

    async handleFriendRequest(senderId, action) {
        try {
            if (action === 'accept') {
                await window.axios.post('/chat/contact/accept', { senderId });
                this.toast(this.__('identity_verified') + ' ✓');
            } else {
                await window.axios.post('/chat/contact/decline', { senderId });
                this.toast(this.__('request_terminated'));
            }
            
            this.loadFriends();
            this.loadHistory();
            
            if (this.activeFriend && Number(this.activeFriend.id) === Number(senderId)) {
                this.openFriendChat(senderId);
            }
        } catch (e) {
            console.error("Friend request error:", e);
        }
    },

    handleIncomingMsg(e) {
        const m = e.messageData;
        const senderHashid = String(m.sender_id); 
        
        if (this.processedEvents && this.processedEvents.has('msg_' + m.id)) return;
        
        if (!this.globalSidebarOpen) {
            this.hasNewNotification = true;
            this.vibrate(30); 
        }
        if(!this.processedEvents) this.processedEvents = new Set();
        this.processedEvents.add('msg_' + m.id);
        
        if (m.message === 'SYSTEM_FRIEND_REQUEST' || m.message === 'SYSTEM_FRIEND_ACCEPTED') {
            this.loadFriends();
            this.loadHistory();
            return;
        }

        if (this.audioUnlocked) this.msgSound.play().catch(()=>{});

        const senderId = String(m.sender_id || (this.activeFriend ? this.activeFriend.id : ''));
        if (!senderId) return;

        if (this.activeFriend && String(this.activeFriend.id) === senderHashid) { 
            this.friendMessages.push(m); 
            this.scrollFriendChat(); 
            return;
        }

        const friend = this.friendsList.find(f => String(f.id) === senderId);

        if (friend) {
            friend.has_new_message = true; 
            friend.unread_count = (friend.unread_count || 0) + 1;
            
            this.friendsList = [
                friend,
                ...this.friendsList.filter(f => String(f.id) !== senderId)
            ];
        } else {
            this.loadFriends();
            this.loadHistory();

            this.toast(`Incoming Secure Connection Request`);
        }
    },

    handleTyping(e) {
        const senderId = String(e.senderId || e.fromId || '');
        if (senderId === this.partnerId || (this.activeFriend && senderId === this.activeFriend.id)) {
            this.isPartnerTyping = true;
            this.typingPartnerName = (this.partnerId === senderId) ? (this.partnerData?.name || 'Partner') : this.activeFriend.name;
            
            if (this.typingTimer) clearTimeout(this.typingTimer);
            
            this.typingTimer = setTimeout(() => { 
                this.isPartnerTyping = false; 
            }, 3000);
        }
    },

    async sendOffer(options = {}) { 
        if (!this.pc) return;
        
        const isRestart = options.iceRestart === true;

        if (this.makingOffer || (this.pc.signalingState !== 'stable' && !isRestart)) {
            return;
        }

        try {
            this.makingOffer = true;
            const offer = await this.pc.createOffer(options);
            
            if (this.pc.signalingState !== 'stable' && !isRestart) {
                return;
            }
            
            await this.pc.setLocalDescription(offer);
            
            await this.signal({ 
                type: 'offer', 
                sdp: this.pc.localDescription.sdp,
                iceRestart: isRestart 
            });
            
        } catch (err) {
            console.error("[WebRTC] Offer Network Error:", err);
            if (this.pc && this.pc.signalingState !== 'stable') {
                await this.pc.setLocalDescription({ type: "rollback" }).catch(()=>{});
            }
            if (isRestart) {
                setTimeout(() => this.sendOffer({ iceRestart: true }), 3000);
            }
        } finally {
            this.makingOffer = false;
        }
    },
    
    reportPartner() {
        if (!this.partnerId || !confirm(this.__('report_desc'))) return;

        const video = document.getElementById('remoteVideo');
        let screenshot = null;

        try {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            screenshot = canvas.toDataURL('image/jpeg', 0.7);
        } catch (e) {
            console.error("Failed to capture evidence", e);
        }

        window.axios.post('/report', { 
            reported_id: this.partnerId, 
            reason: 'general',
            image: screenshot 
        }).then(() => {
            this.toast(this.__('report_transmitted') + ' 🛡️');
            this.startSearch();
        });
    },

    signal(data) { 
        if (this.partnerId) {
            return window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: this.myId } }); 
        }
        return Promise.resolve();
    },

    signalTo(toId, data) { 
        return window.axios.post('/chat/signal', { partnerId: toId, data: { ...data, from: this.myId } }); 
    },

    normalizeSdp(sdp) { return typeof sdp === 'string' ? sdp.trim().split('\n').map(l => l.trim()).join('\r\n') + '\r\n' : sdp; },
    startHeartbeat() { setInterval(() => window.axios.post('/ping'), 15000); },
    startStats() { setInterval(async () => { if (this.pc?.iceConnectionState === 'connected') { const s = await this.pc.getStats(); s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); },
    
    handleVisibilityChange() {
        if (!this.partnerId) return;

        if (document.visibilityState === 'visible') {
            console.log("[App] Вернулись во вкладку. Восстановление...");
            
            this.signal({ type: 'status-sync', state: 'active' });

            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (isMobile) {
                setTimeout(() => this.rebootMobileCamera(), 300);
            }

            if (this.pc && (this.pc.iceConnectionState === 'disconnected' || this.pc.iceConnectionState === 'failed')) {
                console.warn("[Watchdog] Соединение потеряно в фоне. Рестарт сети...");
                this.sendOffer({ iceRestart: true });
            }

            const remoteVid = document.getElementById('remoteVideo');
            if (remoteVid) remoteVid.play().catch(()=>{});
            
        } else {
            this.signal({ type: 'status-sync', state: 'away' });
        }
    },

    startVideoWatchdog() {
        if (this.watchdogTimer) clearInterval(this.watchdogTimer);

        console.log("[Watchdog] Guard activated.");

        this.watchdogTimer = setInterval(() => {
            const video = document.getElementById('remoteVideo');
            
            if (this.state === 'connected' && video && video.readyState < 2) {
                console.warn("[Watchdog] Stream frozen or black screen detected. Auto-repairing...");
                this.sendOffer({ iceRestart: true });
            }
        }, 5000); 
    },

    async startSearch() { 
        this.vibrate(15); 
        this.reset(); 
        this.isPartnerProfileOpen = false; 
        this.state = 'searching'; 
        this.callContext = 'roulette'; 
        await window.axios.post('/chat/search'); 
    },

    loadFriends() {
        window.axios.get('/chat/contacts').then(r => {
            this.friendsList = r.data.contacts.sort((a, b) => {
                if (a.is_online !== b.is_online) {
                    return b.is_online ? 1 : -1;
                }
                return 0;
            });
        });
    },

    loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
    loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
    scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
    scrollFriendChat() { this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); },
    
    hasUnread() {
        return this.hasUnreadFriends() || this.hasUnreadHistory();
    },

    hasUnreadFriends() {
        return this.friendsList.some(f => f.unread_count > 0);
    },

    hasUnreadHistory() {
        return this.historyList.some(h => h.unread_count > 0);
    },
    
    openFriendChat(friendId) {
        if (!friendId) {
            console.error("Caspian DEBUG: Ошибка - передан пустой ID!");
            return;
        }

        const id = String(friendId); // Теперь это хеш-строка

        let target = this.friendsList.find(x => String(x.id) === id);
        let isFriend = !!target;

        if (!target) {
            target = this.historyList.find(x => String(x.id) === id);
        }

        this.activeFriend = target 
            ? { ...target } 
            : { id: id, name: 'User #' + id, status: 'none', is_online: false };

        this.tab = isFriend ? 'friends' : 'history';

        if (target) {
            target.unread_count = 0;
            if (isFriend) target.has_new_message = false;
        }

        this.friendMessages = []; 
        window.axios.get(`/chat/history/${id}`)
            .then(res => { 
                this.friendMessages = Array.isArray(res.data.messages) ? res.data.messages : []; 
                this.scrollFriendChat(); 
            })
            .catch(err => {
                console.error("Caspian DEBUG: Axios Error:", err);
                this.toast(this.__('failed_to_load_history'));
            }); 
    },

    async clearMessages(contactId) {
        if (!confirm('Clear all messages in this chat?')) return;
        try {
            await window.axios.post('/chat/clear-messages', { contactId });
            this.friendMessages = []; 
            this.toast(this.__('history_cleared') + ' 🗑️');
        } catch (e) { console.error(e); }
    },

    t(key) {
        const trans = {
            'male': this.__('male'),
            'female': this.__('female'),
            'all': this.__('all')
        };
        return trans[key] || key;
    },

    async sendMsg() { 
        if (!this.chatInput.trim() || !this.partnerId) return; 
        const t = this.chatInput; 
        this.chatInput = ''; 

        this.messages.push({isMe: true, text: t, timestamp: Date.now()}); 
        this.scrollChat(); 

        this.signal({ type: 'roulette-chat', text: t }).catch(err => {
            console.error("Failed to send roulette message:", err);
        });
    },

    async sendFriendMsg() { 
        if (!this.friendChatInput.trim() || !this.activeFriend) return; 
        const t = this.friendChatInput; 
        this.friendChatInput = ''; 

        const res = await window.axios.post('/chat/message/send', { 
            receiver_id: this.activeFriend.id, 
            message: t 
        }); 

        this.friendMessages.push(res.data.message); 
        this.scrollFriendChat(); 
    },

    sendTypingSignal() { const rid = this.activeFriend ? this.activeFriend.id : this.partnerId; if (rid) window.axios.post('/chat/message/typing', { receiver_id: rid }); },
    callFriend(f) { if (!f.is_online) return; window.location.href = '/chat?call_to=' + f.id; },

    // Вспомогательный метод для вызова тоста
    toast(message) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: message } }));
    },
    
    // Вспомогательный метод для тактильной отдачи (оставлен плейсхолдер для совместимости)
    vibrate(pattern) {
        if (navigator.vibrate) navigator.vibrate(pattern);
    },
    
    __(key) {
        return this.translations[key] || key;
    }
});