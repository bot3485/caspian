export default (initData) => ({
    // --- ПЕРЕМЕННЫЕ ИЗ PHP ---
    myId: initData.myId,
    myHashid: initData.myHashid,
    myInterests: initData.myInterests,
    iceServers: initData.iceServers,
    translations: initData.translations,
    hasNewFriendRequest: false,
    unreadMessagesCount: 0,
    statsInterval: null,
    heartbeatInterval: null,
    videoTagInterval: null,
    watchdogTimer: null,
    typingTimer: null,
    iceTimer: null,
    blitzTimer: null,

    // --- СЛУЖЕБНЫЕ ФЛАГИ И ОЧЕРЕДИ (Предотвращают ReferenceError и баги прокси) ---
    isProcessingSignal: false,
    makingOffer: false,
    wasDisconnected: false,
    iceQueue: [],
    friendRequestSent: false,
    processedEvents: null,
    lastTypingSent: 0,

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

    // --- МАССИВЫ ---
    friendsList: [],
    historyList: [],
    blockedList: [],
    messages: [],
    friendMessages: [],
    videoDevices: [],
    audioDevices: [],
    commonInterests: [],

    // --- ФЛАГИ И НАСТРОЙКИ ---
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
    partnerCamEnabled: true, 
    partnerMicEnabled: true, 
    partnerFilters: { beauty: false, cinema: false },
    rtcConfig: { 
        iceServers: initData.iceServers, 
        bundlePolicy: "balanced", 
        iceCandidatePoolSize: 10,
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
            
            if (this.partnerFilters?.cinema || this.cinemaFilter) {
                ctx.filter = 'grayscale(100%) contrast(125%)';
            }
            
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const link = document.createElement('a');
            link.download = `Caspian_Snap_${new Date().getTime()}.jpeg`;
            link.href = canvas.toDataURL('image/jpeg', 0.9);
            link.click();
            
            this.vibrate(50);
            this.toast('📸 Снимок сохранен!');
            
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
            this.loadContacts();
            
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
            const constraints = {
                video: { deviceId: { exact: this.selectedVideoId }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false 
            };
            const newStream = await navigator.mediaDevices.getUserMedia(constraints);
            const newVideoTrack = newStream.getVideoTracks()[0];

            if (this.localStream) {
                const oldVideoTrack = this.localStream.getVideoTracks()[0];
                if (oldVideoTrack) {
                    oldVideoTrack.stop();
                    this.localStream.removeTrack(oldVideoTrack);
                }
                this.localStream.addTrack(newVideoTrack);
            } else {
                this.localStream = newStream;
            }

            const videoEl = this.$refs.localVideo || document.getElementById('localVideo');
            if (videoEl) {
                videoEl.srcObject = this.localStream;
                await videoEl.play().catch(() => {});
            }

            if (this.pc) {
                const videoSender = this.pc.getSenders().find(s => s.track?.kind === 'video');
                if (videoSender) {
                    await videoSender.replaceTrack(newVideoTrack);
                }
            }

            this.toast(this.__('hardware_synced'));
            this.deviceModalOpen = false;
        } catch (e) {
            console.error("Video device change error:", e);
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
        try {
            const constraints = {
                video: false, 
                audio: { deviceId: { exact: this.selectedAudioId } }
            };
            const newStream = await navigator.mediaDevices.getUserMedia(constraints);
            const newAudioTrack = newStream.getAudioTracks()[0];

            if (this.localStream) {
                const oldAudioTrack = this.localStream.getAudioTracks()[0];
                if (oldAudioTrack) {
                    oldAudioTrack.stop();
                    this.localStream.removeTrack(oldAudioTrack);
                }
                this.localStream.addTrack(newAudioTrack);
                newAudioTrack.enabled = this.micEnabled;
            } else {
                this.localStream = newStream;
            }

            if (this.pc) {
                const audioSender = this.pc.getSenders().find(s => s.track?.kind === 'audio');
                if (audioSender) {
                    await audioSender.replaceTrack(newAudioTrack);
                }
            }

            this.toast(this.__('hardware_synced'));
            this.deviceModalOpen = false;
        } catch (e) {
            console.error("Audio device change error:", e);
            this.toast(this.__('device_error'));
        }
    },

    async init() {
        this.refreshBadges();

        this.$watch('globalSidebarOpen', (value) => {
            if (value) {
                this.hasNewNotification = false;
            }
        });
        
        const onceUnlock = () => {
            this.unlockAudio();
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
                if (this.callContext === 'personal') return;
                this.vibrate(20); 
                this.handleMatch(e);
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
            .listen('.WebRTCSignalEvent', (e) => this.handleSignal(e))
            .listen('.MessageSentEvent', (e) => this.handleIncomingMsg(e))
            .listen('.UserTypingEvent', (e) => this.handleTyping(e));

        window.addEventListener('contact-removed', (e) => {
            if (String(this.partnerId) === String(e.detail.contactId)) {
                this.isFriend = false;
            }
        });

        if (window.location.pathname === '/chat') {
            this.$nextTick(async () => {
                await this.initMedia();
                const urlParams = new URLSearchParams(window.location.search);
                const callWith = urlParams.get('call_with');
                const isAccepted = urlParams.get('accepted') === '1';
                if (urlParams.has('accept_call')) this.setupPersonalCall(urlParams.get('accept_call'), true);
                else if (urlParams.has('call_to')) this.setupPersonalCall(urlParams.get('call_to'), false);
                if (callWith) {
                            setTimeout(() => {
                                this.setupPersonalCall(callWith, isAccepted);
                            }, 1000); // Даем Echo 1 секунду на старт
                        }
                if (this.videoTagInterval) clearInterval(this.videoTagInterval);
                this.videoTagInterval = setInterval(() => { this.refreshVideoTags(); }, 2000);
            });
        }
        
        this.loadContacts(); 
        this.loadHistory(); 
        this.loadBlocked();
        this.startStats();
        this.startHeartbeat();
    },

    refreshGlobalUnread() {
        this.loadContacts().then(() => {
            this.hasNewFriendRequest = this.friendsList.some(f => f.status === 'pending' && f.is_incoming);
            
            this.unreadMessagesCount = this.friendsList
                .filter(f => f.status === 'accepted')
                .reduce((sum, f) => sum + (f.unread_count || 0), 0);
        });
    },

    refreshBadges() {
        this.loadContacts().then(() => {
            this.hasNewFriendRequest = this.friendsList.some(f => f.is_incoming && f.status === 'pending');
        });
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
        if (this.localStream) {
            const videoEl = this.$refs.localVideo || document.getElementById('localVideo');
            if (videoEl && videoEl.srcObject !== this.localStream) {
                videoEl.srcObject = this.localStream;
            }
            return;
        }

        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 }, 
                    height: { ideal: 480 }, 
                    frameRate: { max: 30 } 
                }, 
                audio: true 
            });

            const videoEl = this.$refs.localVideo || document.getElementById('localVideo');

            if (videoEl) {
                videoEl.srcObject = this.localStream;
                
                setTimeout(() => {
                    videoEl.play().catch(e => {
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
        this.syncMediaState(); 
    },
    
    toggleCam() { 
        this.camEnabled = !this.camEnabled; 
        if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; 
        this.syncMediaState(); 
    },

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
        
        this.iceQueue = [];
        this.pc = new RTCPeerConnection(this.rtcConfig);

        // this.pc.onnegotiationneeded = async () => {
        //     try {
        //         console.log("[WebRTC] Negotiation needed...");
        //         await this.sendOffer();
        //     } catch (err) {
        //         console.error("[WebRTC] Negotiation Error:", err);
        //     }
        // };

        this.pc.onicecandidate = (e) => { 
            if (e.candidate) this.signal({ type: 'ice', candidate: e.candidate }); 
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
                        if (this.isPolite()) {
                            this.sendOffer({ iceRestart: true });
                        }
                    }
                }, 2000);
            }
            if (this.pc.connectionState === 'failed' || this.pc.connectionState === 'disconnected') {
                if (this.watchdogTimer) clearInterval(this.watchdogTimer);
            }
        };

        this.pc.ontrack = (e) => {
            const remoteStream = e.streams[0] || new MediaStream([e.track]);
            const videoEl = document.getElementById('remoteVideo') || this.$refs.remoteVideo;
            
            if (videoEl && remoteStream) {
                if (videoEl.srcObject !== remoteStream) {
                    videoEl.srcObject = remoteStream;
                }

                this.remoteMuted = false;
                videoEl.muted = false;

                videoEl.play().catch(err => {
                    console.error("[WebRTC] Ошибка воспроизведения со звуком:", err);
                });
            }
        };

        this.pc.oniceconnectionstatechange = () => {
            const iceState = this.pc.iceConnectionState;
            console.log("[WebRTC] ICE State Changed:", iceState);

            if (['disconnected', 'failed'].includes(iceState)) {
                this.partnerState = 'problem';
                this.wasDisconnected = true; 
                console.log("[WebRTC] Network path lost. Starting recovery timer...");
                
                setTimeout(() => {
                    const currentState = this.pc.iceConnectionState;
                    if (['disconnected', 'failed'].includes(currentState)) {
                        console.log("[WebRTC] Proactive ICE Restart initiated after dropout.");
                        this.sendOffer({ iceRestart: true });
                    }
                }, 3000);
            } 
            else if (['connected', 'completed'].includes(iceState)) {
                this.partnerState = 'active';

                this.$nextTick(() => {
                    const remoteVid = this.$refs.remoteVideo || document.getElementById('remoteVideo');
                    if (remoteVid) {
                        if (this.wasDisconnected && remoteVid.srcObject) {
                            const stream = remoteVid.srcObject;
                            remoteVid.srcObject = null;
                            remoteVid.srcObject = stream;
                            this.wasDisconnected = false;
                        }

                        remoteVid.play().catch(() => {
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
        // Тот, кто отправляет оффер (myHashid > partnerId), является "невежливым" (impolite), 
        // а тот, кто ждет (myHashid < partnerId), является "вежливым" (polite) и должен уступить при коллизии.
        return String(this.myHashid) < String(this.partnerId);
    },
    async handleSignal(e) {
        const m = e.data;
        const fromId = String(m.from);
        
        if (this.partnerId && String(this.partnerId) !== String(fromId)) {
            console.warn("Signal from stranger ignored");
            return; 
        }

        if (window.location.pathname.includes('/rooms/') && 
            ['offer', 'answer', 'ice'].includes(m.type)) {
            return; 
        }

        if (m.type === 'media-state') {
            this.partnerCamEnabled = m.cam;
            this.partnerMicEnabled = m.mic;
            return;
        }

        if (m.type === 'contact-removed') {
            // Удаляем пользователя из локального списка друзей
            this.friendsList = this.friendsList.filter(f => String(f.id) !== String(m.contactId));
            
            // Если чат с ним был открыт прямо сейчас — закрываем его и перекидываем в историю
            if (this.activeFriend && String(this.activeFriend.id) === String(m.contactId)) {
                this.activeFriend = null;
                this.tab = 'history'; 
                this.toast('Connection protocol terminated by user.');
            } else {
                this.toast('A user has removed you from contacts.');
            }
            this.refreshGlobalUnread();
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
        this.tab = 'chat';
        
        this.initPC();

        // СТРОГОЕ ПРАВИЛО: Оффер создает ТОЛЬКО один из участников. 
        // Используем комбинацию сравнения ID и случайной задержки, чтобы гарантированно не было пересечений.
        const myStr = String(this.myHashid);
        const partnerStr = String(this.partnerId);

        if (myStr !== partnerStr) {
            // Если мой ID "меньше", я жду оффер. Если "больше" — я отправляю оффер.
            if (myStr > partnerStr) {
                setTimeout(() => {
                    // Дополнительная проверка: отправляем оффер только если состояние все еще 'connected' и нет активного оффера
                    if (this.state === 'connected' && this.pc && this.pc.signalingState === 'stable') {
                        this.sendOffer();
                    }
                }, 800);
            }
        }

        if (this.localStream) {
                const senders = this.pc.getSenders();
                this.localStream.getTracks().forEach(track => {
                    if (!senders.some(s => s.track && s.track.kind === track.kind)) {
                        this.pc.addTrack(track, this.localStream);
                    }
                });
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

        try {
            if (m.type === 'offer') {
                if (this.isProcessingSignal) return; 
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
                
                while(this.iceQueue.length) {
                    await this.pc.addIceCandidate(this.iceQueue.shift()).catch(() => {});
                }
                this.isProcessingSignal = false;

            } else if (m.type === 'answer') {
                if (this.isProcessingSignal) return; 
                this.isProcessingSignal = true;
                
                if (this.pc.signalingState === 'have-local-offer') {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: this.normalizeSdp(m.sdp) }));
                    while(this.iceQueue.length) {
                        await this.pc.addIceCandidate(this.iceQueue.shift()).catch(() => {});
                    }
                }
                this.isProcessingSignal = false;

            } else if (m.type === 'ice') {
                const candidate = new RTCIceCandidate(m.candidate);
                
                if (!this.pc || !this.pc.remoteDescription || !this.pc.remoteDescription.type) {
                    if (!this.iceQueue) this.iceQueue = []; 
                    this.iceQueue.push(candidate);
                } else {
                    await this.pc.addIceCandidate(candidate).catch(e => {
                        console.warn("[WebRTC] ICE Error", e);
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

        if (String(this.myHashid) > String(this.partnerId)) {
            setTimeout(() => this.sendOffer(), 1000);
        }

        this.startCallTimer();
    },

    async setupPersonalCall(id, isAccepted) {
        // 1. Сразу переводим состояние, чтобы Alpine.js начал отрисовывать блоки <video> в DOM
        this.callContext = 'personal';
        this.partnerId = String(id);
        this.state = 'connecting';
        this.micEnabled = true; 
        this.camEnabled = true; 

        // Очищаем URL от параметров звонка, чтобы они не висели
        if (window.location.search.includes('call_with')) {
            window.history.replaceState({}, '', '/chat');
        }

        // 2. Ждем один цикл Alpine.js, чтобы элементы <video x-ref="..."> гарантированно появились в DOM
        await this.$nextTick();

        try {
            if (!this.pc) this.initPC();

            // 3. Запускаем медиа (теперь $refs.localVideo уже существует)
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

            // 4. Даем сокетам Echo 1.5 секунды на восстановление соединения после редиректа перед отправкой сигналов
            await new Promise(resolve => setTimeout(resolve, 1500));

            if (isAccepted) {
                this.state = 'connected';
                this.signal({ type: 'call-accepted' });
            } else {
                const r = await window.axios.post('/chat/contact/call', { contactId: id });

                if (r.data.status === 'busy') {
                    this.stopCall(false);
                    this.toast(this.__('user_busy'));
                    return;
                }

                this.state = 'connected'; // Сразу переводим в connected для отображения видео
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
            this.loadContacts(); 
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
        const callerId = this.incomingCall.fromId;
        this.incomingCall = null;
        
        // Перенаправляем на чат с автоматическим стартом звонка
        window.location.href = `/chat?call_with=${callerId}&accepted=1`;
    },

    rejectCall() {
        this.stopRingtone(); 
        if (this.incomingCall) this.signalTo(this.incomingCall.fromId, { type: 'hang-up' });
        this.incomingCall = null;
    },

    stopCall(notify = true) {
        if (notify && this.partnerId) this.signal({ type: 'hang-up' });
        this.stopRingtone();
        const shouldRefreshHistory = this.callContext === 'roulette' && this.partnerId;
        this.reset();
        window.axios.post('/chat/leave').then(() => {
            if (shouldRefreshHistory) {
                this.loadHistory();
            }
        });
        this.toast(this.__('call_ended'));
    },

    reset() {
        this.ringtone.pause();
        this.stopCallTimer();
        this.isPartnerProfileOpen = false;
        this.uiShowPartnerCard = false;

        if (this.typingTimer) { clearTimeout(this.typingTimer); this.typingTimer = null; }
        if (this.statsInterval) { clearInterval(this.statsInterval); this.statsInterval = null; }
        if (this.heartbeatInterval) { clearInterval(this.heartbeatInterval); this.heartbeatInterval = null; }
        if (this.videoTagInterval) { clearInterval(this.videoTagInterval); this.videoTagInterval = null; }
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
        // Блокируем двойные клики
        if (this.isProcessingContact || !this.partnerData) return;

        // Анти-спам: проверяем локальный статус "В ожидании"
        if (this.partnerData.is_pending) {
            this.toast('Protocol already pending. Please wait for approval.');
            return;
        }

        this.isProcessingContact = true;

        try {
            if (this.isFriend) {
                const confirmed = confirm(this.t('remove_friend_sure'));
                if (!confirmed) {
                    this.isProcessingContact = false;
                    return;
                }
                
                await window.axios.post('/chat/contact/remove', { contactId: this.partnerData.id });
                this.isFriend = false;
                this.partnerData.is_pending = false; 
                this.toast(this.t('contact_unlinked'));
                
            } else {
                const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerData.id });
                
                if (res.data.status === 'already_sent') {
                    this.partnerData.is_pending = true;
                    this.toast('Protocol already pending.');
                } else if (res.data.status === 'accepted') {
                    this.isFriend = true;
                    this.toast('Protocol established!');
                } else {
                    // Запрос успешно ушел
                    this.partnerData.is_pending = true; // Лочим кнопку
                    this.friendRequestSent = true; 
                    this.toast('Handshake Transmitted');
                }
            }
        } catch (e) {
            console.error(e);
            this.toast('Transmission failed. Retry.');
        } finally {
            this.isProcessingContact = false;
        }
    },

    handleFriendRequest(senderId, action) {
        const url = action === 'accept' ? '/chat/contact/accept' : '/chat/contact/decline';

        window.axios.post(url, { senderId: senderId })
            .then(() => {
                if (action === 'accept') {
                    if (this.activeFriend) {
                        this.activeFriend.status = 'accepted';
                    }
                    let friendInList = this.friendsList.find(f => String(f.id) === String(senderId));
                    if (friendInList) {
                        friendInList.status = 'accepted';
                    }
                    this.friendMessages = this.friendMessages.filter(m => m.message !== 'SYSTEM_FRIEND_REQUEST');
                } else {
                    this.activeFriend = null;
                }
            })
            .catch(error => {
                console.error("Friend request handling error:", error);
            });
    },

    async requestFriendFromChat() {
        // Защита от спам-кликов и повторных отправок
        if (this.isProcessingContact || !this.activeFriend || this.activeFriend.status === 'pending') return;
        
        this.isProcessingContact = true;
        
        try {
            const res = await window.axios.post('/chat/contact/add', { contactId: this.activeFriend.id });
            
            if (res.data.status === 'already_sent') {
                this.activeFriend.status = 'pending';
                this.toast('Protocol already pending.');
            } else if (res.data.status === 'accepted') {
                // Если запрос был взаимным (собеседник тоже отправил его ранее)
                this.activeFriend.status = 'accepted';
                this.toast('Protocol established!');
            } else {
                // Успешная отправка нового запроса
                this.activeFriend.status = 'pending';
                this.toast('Handshake Transmitted');
            }
            
            this.refreshGlobalUnread();
        } catch (e) {
            console.error("Failed to add friend from chat:", e);
            this.toast('Transmission failed. Retry.');
        } finally {
            this.isProcessingContact = false;
        }
    },

    handleFriendRequest(senderId, action) {
        // Предотвращаем спам кликами по кнопкам "Принять/Отклонить"
        if (this.isProcessingContact) return;
        this.isProcessingContact = true;
        
        const url = action === 'accept' ? '/chat/contact/accept' : '/chat/contact/decline';

        window.axios.post(url, { senderId: senderId })
            .then(() => {
                if (action === 'accept') {
                    // Реактивно обновляем активный чат, если он открыт
                    if (this.activeFriend && String(this.activeFriend.id) === String(senderId)) {
                        this.activeFriend.status = 'accepted';
                        this.friendMessages = this.friendMessages.filter(m => m.message !== 'SYSTEM_FRIEND_REQUEST');
                    }
                    
                    // Реактивно обновляем контакт в списке
                    let friendInList = this.friendsList.find(f => String(f.id) === String(senderId));
                    if (friendInList) {
                        friendInList.status = 'accepted';
                        friendInList.is_incoming = false;
                    }
                    this.toast('Protocol Accepted! 🤝');
                } else {
                    // Логика отклонения
                    if (this.activeFriend && String(this.activeFriend.id) === String(senderId)) {
                        this.activeFriend = null; // Закрываем чат
                        this.tab = 'history';     // Перекидываем в историю
                    }
                    // Убираем из списка входящих
                    this.friendsList = this.friendsList.filter(f => String(f.id) !== String(senderId));
                    this.toast('Protocol Declined.');
                }
                
                this.refreshGlobalUnread(); // Пересчитываем бейджи уведомлений
            })
            .catch(error => {
                console.error("Friend request handling error:", error);
                this.toast('Action failed. Connection issue.');
            })
            .finally(() => {
                this.isProcessingContact = false;
            });
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
        if (this.partnerId && this.state === 'connected') {
            return window.axios.post('/chat/signal', { 
                partnerId: String(this.partnerId), 
                data: { 
                    ...data, 
                    from: this.myHashid 
                } 
            }).catch(err => {
                if (err.response?.status === 422) {
                    console.error("Validation Error details:", err.response.data.errors);
                }
            });
        }
        return Promise.resolve();
    },

    signalTo(toId, data) { 
        if (!toId) return Promise.resolve();
        return window.axios.post('/chat/signal', { 
            partnerId: String(toId), 
            data: { ...data, from: this.myHashid } 
        }); 
    },

    normalizeSdp(sdp) { 
        return typeof sdp === 'string' ? sdp.trim().split('\n').map(l => l.trim()).join('\r\n') + '\r\n' : sdp; 
    },
    
    startHeartbeat() { 
        if (this.heartbeatInterval) clearInterval(this.heartbeatInterval);
        this.heartbeatInterval = setInterval(() => window.axios.post('/ping'), 15000); 
    },
    
    startStats() { 
        if (this.statsInterval) clearInterval(this.statsInterval);
        this.statsInterval = setInterval(async () => { 
            if (this.pc?.iceConnectionState === 'connected') { 
                const s = await this.pc.getStats(); 
                s.forEach(r => { 
                    if (r.type === 'candidate-pair' && r.state === 'succeeded') { 
                        this.ping = Math.round(r.currentRoundTripTime * 1000); 
                    } 
                }); 
            } 
        }, 3000); 
    },
    
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

    loadContacts() {
        return window.axios.get('/chat/contacts').then(r => {
            this.friendsList = r.data.contacts;
            this.hasNewFriendRequest = this.friendsList.some(f => f.is_incoming && f.status === 'pending');
            this.unreadMessagesCount = this.friendsList.reduce((sum, f) => sum + (f.unread_count || 0), 0);
        });
    },

    async loadHistory() {
        try {
            // Проверьте, какой у вас точный URL в Laravel маршрутах для getInteractionHistory
            const res = await window.axios.get('/chat/history-all'); 
            this.historyList = res.data.history;
        } catch (e) {
            console.error("Error loading history:", e);
        }
    },
    
    loadBlocked() { 
        window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); 
    },
    
    scrollChat() { 
        this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); 
    },
    
    scrollFriendChat() { 
        this.$nextTick(() => { if(this.$refs.friendChatBox) this.$refs.friendChatBox.scrollTop = this.$refs.friendChatBox.scrollHeight; }); 
    },
    
    hasUnread() {
        return this.hasNewFriendRequest || 
               this.unreadMessagesCount > 0 || // Исправлено
               this.friendsList.some(f => f.has_new_message) ||
               this.historyList.some(h => h.unread_count > 0);
    },

    hasUnreadFriends() {
        return this.hasNewFriendRequest || this.friendsList.some(f => f.status === 'pending' && f.is_incoming);
    },

    hasUnreadHistory() {
        return this.historyList.some(h => h.unread_count > 0);
    },
    
    hasUnreadMessages() {
        return this.unreadMessagesCount > 0 || this.friendsList.some(f => f.has_new_message && f.status === 'accepted');
    },
    
    openFriendChat(friendId) {
        if (!friendId) {
            console.error("Caspian DEBUG: Ошибка - передан пустой ID!");
            return;
        }
        
        const id = String(friendId);

        let friend = this.friendsList.find(f => String(f.id) === id);
        let historyItem = this.historyList.find(x => String(x.id) === id);
        
        let target = friend || historyItem;
        let isFriend = !!friend && friend.status === 'accepted';

        if (friend && friend.unread_count > 0) {
            this.unreadMessagesCount -= friend.unread_count;
            friend.unread_count = 0;
            window.axios.post('/chat/mark-as-read', { contactId: friendId });
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
    
    sendTyping() {
        if (!this.activeFriend) return;
        
        let now = Date.now();
        if (this.lastTypingSent && now - this.lastTypingSent < 2000) return;
        this.lastTypingSent = now;

        window.axios.post('/chat/message/typing', { 
            receiver_id: this.activeFriend.id 
        });
    },
    
    sendTypingSignal() { 
        const rid = this.activeFriend ? this.activeFriend.id : this.partnerId; 
        if (rid) window.axios.post('/chat/message/typing', { receiver_id: rid }); 
    },
    
    callFriend(f) { 
        if (!f.is_online) {
            this.toast('User is offline');
            return; 
        }
        
        // Делаем полноценный редирект на /chat с параметром вызова для звонящего (accepted=0)
        window.location.href = `/chat?call_with=${f.id}&accepted=0`;
    },

    toast(message) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { msg: message } }));
    },
    
    vibrate(pattern) {
        if (navigator.vibrate) navigator.vibrate(pattern);
    },
    
    __(key) {
        return this.translations[key] || key;
    }
});