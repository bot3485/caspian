<x-app-layout>
    <div class="h-[calc(100svh-0px)] md:h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans selection:bg-indigo-500/30" 
         x-data="window.videoChatApp({{ auth()->id() }}, {{ json_encode(auth()->user()->interests ?? []) }})"
         @touchstart="touchStart = $event.touches[0].clientY"
         @touchend="handleSwipe($event)"
         @click="unlockAudio()">
        
        <!-- ДЕКОРАТИВНЫЕ СВЕЧЕНИЯ -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <div class="relative h-full flex flex-col lg:flex-row">
            
            <!-- ЗОНА ВИДЕО -->
            <div class="flex-1 relative bg-black overflow-hidden h-full flex items-center justify-center">
                
                <!-- ICE BREAKER -->
                <div x-show="showIceBreaker" x-cloak x-transition.opacity
                     class="absolute inset-0 flex items-center justify-center z-[120] pointer-events-none px-6">
                    <div class="bg-indigo-600 p-1 rounded-[3rem] shadow-[0_0_100px_rgba(99,102,241,0.5)]">
                        <div class="bg-[#050505] backdrop-blur-3xl p-10 rounded-[2.8rem] text-center border border-white/10">
                            <div class="text-4xl mb-4">🤝</div>
                            <h2 class="text-2xl font-black uppercase italic tracking-tighter mb-2">Общие интересы!</h2>
                            <div class="flex flex-wrap justify-center gap-2 mt-4">
                                <template x-for="interest in matchInterests">
                                    <span class="px-4 py-2 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase" x-text="interest"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- XP POPUPS -->
                <div class="fixed bottom-40 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 pointer-events-none z-[200]">
                    <template x-for="popup in xpPopups" :key="popup.id">
                        <div x-transition class="bg-indigo-600 text-white px-6 py-3 rounded-full font-black text-[10px] uppercase tracking-widest shadow-2xl flex items-center gap-3 border border-indigo-400/30">
                            <span class="text-base">⚡</span>
                            <span x-text="'+' + popup.amount + ' XP'"></span>
                        </div>
                    </template>
                </div>

                <!-- ВЕРХНЯЯ ПАНЕЛЬ ПАРТНЕРА -->
                <div x-show="state === 'connected' && partnerData" class="absolute top-4 left-4 z-[90] w-auto max-w-[calc(100%-80px)]" x-transition>
                    <div class="bg-black/60 backdrop-blur-2xl p-3 md:p-4 rounded-3xl border border-white/10 flex items-center gap-3 shadow-2xl">
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center font-black text-lg shadow-lg shrink-0" x-text="partnerData?.name?.[0]"></div>
                        <div class="min-w-0 pr-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm md:text-base font-black uppercase tracking-tighter truncate" x-text="partnerData?.name"></span>
                                <span class="bg-indigo-600 text-[7px] font-black px-1.5 py-0.5 rounded-full" x-text="'LVL ' + (partnerData?.level || 1)"></span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 opacity-60">
                                <div class="w-1.5 h-1.5 rounded-full" :class="partnerState === 'active' ? 'bg-green-500 shadow-[0_0_8px_#22c55e]' : 'bg-red-500'"></div>
                                <div class="text-[8px] font-black uppercase tracking-widest" x-text="partnerState === 'active' ? partnerData?.rank_name : 'Связь потеряна...'"></div>
                                <div class="text-[8px] font-black" x-text="'• ' + ping + 'ms'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ОСНОВНОЕ ВИДЕО -->
                <video x-ref="remoteVideo" autoplay playsinline 
                       class="w-full h-full object-cover transition-all duration-700" 
                       :class="(isBlurredByPartner || isBlurred) ? 'blur-[80px] scale-105 opacity-40' : 'opacity-100'"
                       :style="beautyFilter ? 'filter: brightness(1.05) contrast(0.95) saturate(1.1) blur(0.4px);' : ''"></video>
                
                <!-- PIP СВОЁ ВИДЕО -->
                <div x-show="showSelfVideo" class="absolute bottom-32 md:bottom-10 md:left-10 right-4 w-28 md:w-64 aspect-[3/4] md:aspect-video bg-[#111] rounded-3xl overflow-hidden shadow-2xl border border-white/10 z-[80] transition-all">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]" :class="!camEnabled && 'opacity-0'"></video>
                    <div x-show="!camEnabled" class="absolute inset-0 flex items-center justify-center bg-gray-950/80"><span class="text-xl">🚫</span></div>
                </div>

                <!-- ЭКРАН ПОИСКА -->
                <div x-show="state === 'searching'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-[110] px-6">
                    <div class="relative w-24 h-24 mb-8">
                        <div class="absolute inset-0 border-2 border-indigo-500/20 rounded-full animate-ping"></div>
                        <div class="absolute inset-0 flex items-center justify-center text-3xl">📡</div>
                    </div>
                    <h3 class="text-white font-black uppercase text-[10px] tracking-[0.5em] animate-pulse" x-text="isCallingFriend ? 'Вызываем друга...' : 'Ищем собеседника...'"></h3>
                </div>

                <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
                <div class="fixed bottom-safe left-0 right-0 px-4 z-[130] flex flex-col items-center gap-3 mb-6 pointer-events-auto">
                    
                    <div x-show="controlsOpen" x-transition class="flex items-center gap-1.5 p-1.5 bg-black/60 backdrop-blur-3xl border border-white/10 rounded-2xl shadow-2xl">
                        <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">🎤</button>
                        <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">📷</button>
                        <button @click="isBlurred = !isBlurred" :class="isBlurred ? 'bg-indigo-600 shadow-lg' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">🙈</button>
                        <button @click="toggleBeauty()" :class="beautyFilter ? 'bg-pink-600 shadow-lg' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">✨</button>
                        <button @click="getDevices()" class="w-10 h-10 md:w-12 md:h-12 bg-white/5 rounded-xl flex items-center justify-center transition-all">⚙️</button>
                        
                        <template x-if="state === 'connected'">
                            <div class="flex items-center gap-1.5">
                                <div class="w-px h-6 bg-white/10 mx-1"></div>
                                <button @click="toggleContact()" :class="isFriend ? 'bg-green-600/20 text-green-400' : 'bg-white/5 text-white'" class="h-10 md:h-12 px-4 rounded-xl border border-white/5 font-black text-[9px] uppercase tracking-widest transition-all">
                                    <span x-text="isFriend ? 'В друзьях ✓' : '+ Друг'"></span>
                                </button>
                                <button @click="report(partnerId)" class="w-10 h-10 md:w-12 md:h-12 bg-red-600/10 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-600 transition-all">🚩</button>
                            </div>
                        </template>
                    </div>

                    <div class="w-full max-w-md bg-[#121212]/95 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] p-2 flex items-center justify-between shadow-2xl">
                        <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center transition-transform" :class="controlsOpen && 'rotate-180'">
                            <span class="text-[8px]" x-text="controlsOpen ? '▼' : '⚡'"></span>
                        </button>
                        
                        <div class="flex-1 flex justify-center px-4">
                            <template x-if="state === 'idle'">
                                <button @click="startSearch()" class="bg-indigo-600 w-full h-12 rounded-full font-black text-[10px] uppercase tracking-widest shadow-lg active:scale-95 transition-all">Начать поиск</button>
                            </template>
                            <template x-if="state === 'searching'">
                                <button @click="stopSearch()" class="bg-red-600 w-full h-12 rounded-full font-black text-[10px] uppercase tracking-widest animate-pulse">Остановить</button>
                            </template>
                            <template x-if="state === 'connected'">
                                <div class="flex items-center gap-2 w-full">
                                    <button @click="stopSearch()" class="bg-red-600/20 text-red-500 px-6 h-12 rounded-full font-black text-[10px] uppercase tracking-widest active:scale-95 transition-all">Стоп</button>
                                    <button @click="startSearch()" class="bg-white text-black flex-1 h-12 rounded-full font-black text-[10px] uppercase tracking-widest shadow-xl active:scale-95 transition-all">Далее ➔</button>
                                </div>
                            </template>
                        </div>

                        <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center text-lg relative">
                            💬
                            <div x-show="isPartnerTyping" class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-500 rounded-full animate-ping"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- МЕССЕНДЖЕР -->
            <div x-show="mobileSidebarOpen" class="fixed inset-0 z-[240] bg-black/60 backdrop-blur-sm lg:hidden" @click="mobileSidebarOpen = false" x-cloak x-transition.opacity></div>

            <div :class="mobileSidebarOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'" 
                 class="fixed inset-x-0 bottom-0 z-[250] h-[85vh] lg:h-full lg:relative lg:inset-auto lg:w-[400px] flex flex-col bg-[#080808] border-t lg:border-t-0 lg:border-l border-white/5 rounded-t-[3rem] lg:rounded-none transition-transform duration-500 overflow-hidden text-white">
                
                <div class="bg-[#0a0a0a] flex items-center justify-between px-6 py-4 border-b border-white/5 lg:hidden">
                    <button @click="mobileSidebarOpen = false" class="flex items-center gap-2 group"><span class="w-8 h-8 bg-white/5 rounded-full flex items-center justify-center text-indigo-500 font-bold">✕</span></button>
                    <div class="w-12 h-1 bg-white/10 rounded-full text-[9px] font-black uppercase text-gray-500">Caspian Messenger</div><div class="w-8"></div>
                </div>

                <div class="flex border-b border-white/5 bg-[#0a0a0a] px-2 shrink-0">
                    <button @click="tab = 'chat'" :class="tab === 'chat' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest">Чат</button>
                    <button @click="tab = 'friends'; loadFriends()" :class="tab === 'friends' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest">Друзья</button>
                    <button @click="tab = 'history'; loadHistory()" :class="tab === 'history' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest">История</button>
                    <button @click="tab = 'blacklist'; loadBlocked()" :class="tab === 'blacklist' ? 'text-red-500 border-b-2 border-red-500' : 'text-gray-500'" class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest">ЧС</button>
                </div>
                
                <div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">
                    <div x-show="tab === 'chat'" class="flex-1 flex flex-col overflow-hidden">
                        <template x-if="state === 'connected'">
                            <div class="flex-1 flex flex-col overflow-hidden">
                                <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" x-ref="chatBox">
                                    <template x-for="msg in messages" :key="msg.timestamp">
                                        <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col">
                                            <div :class="msg.isMe ? 'bg-indigo-600' : 'bg-white/5 border border-white/5'" class="p-4 text-[13px] font-medium max-w-[85%] rounded-2xl shadow-xl" x-text="msg.text"></div>
                                        </div>
                                    </template>
                                </div>
                                <div class="px-6 py-2 h-8" x-show="isPartnerTyping" x-transition><span class="text-[8px] font-black text-indigo-400 animate-pulse uppercase">Печатает...</span></div>
                                <div class="p-4 bg-[#0a0a0a] border-t border-white/5 pb-12 md:pb-6">
                                    <div class="flex gap-2 bg-black/40 p-2 rounded-2xl">
                                        <input type="text" x-model="chatInput" @input="sendTypingSignal()" @keyup.enter="sendMsg()" placeholder="Написать..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 px-4 h-12 text-white">
                                        <button @click="sendMsg()" class="bg-indigo-600 text-white w-12 h-12 rounded-xl">➔</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="state !== 'connected'"><div class="flex-1 flex flex-col items-center justify-center opacity-30 p-10 text-center"><div class="text-5xl mb-6">💬</div><div class="text-[10px] font-black uppercase tracking-widest">Начните поиск, чтобы открыть чат</div></div></template>
                    </div>

                    <div x-show="tab === 'friends'" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                        <template x-for="f in friendsList" :key="f.id">
                            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-3xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-2xl flex items-center justify-center font-black text-xs" x-text="f.name[0]"></div>
                                    <div>
                                        <div class="text-xs font-bold" x-text="f.name"></div>
                                        <div class="text-[7px] font-black uppercase text-green-500" x-show="onlineList.some(u => u.id === f.id)">В сети</div>
                                    </div>
                                </div>
                                <button @click="callFriend(f); mobileSidebarOpen = false" :disabled="!onlineList.some(u => u.id === f.id)" class="w-10 h-10 bg-indigo-500 text-white rounded-xl flex items-center justify-center disabled:opacity-20">📞</button>
                            </div>
                        </template>
                    </div>

                    <div x-show="tab === 'history'" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                        <template x-for="h in historyList" :key="h.id + h.last_at">
                            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-3xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-white/5 text-gray-500 rounded-2xl flex items-center justify-center font-black text-xs" x-text="h.name[0]"></div>
                                    <div><div class="text-xs font-bold" x-text="h.name"></div><div class="text-[7px] font-black uppercase text-gray-600" x-text="h.last_met_diff"></div></div>
                                </div>
                                <button @click="callFriend(h)" class="w-10 h-10 bg-white/10 text-white rounded-xl flex items-center justify-center">📞</button>
                            </div>
                        </template>
                    </div>

                    <div x-show="tab === 'blacklist'" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                        <template x-for="b in blockedList" :key="b.id">
                            <div class="p-4 bg-red-500/5 border border-red-500/10 rounded-3xl flex items-center justify-between">
                                <div class="text-xs font-bold text-red-200" x-text="b.name"></div>
                                <button @click="unblock(b.id)" class="px-4 py-2 bg-white/5 hover:bg-white hover:text-black rounded-xl text-[8px] font-black uppercase">Разблокировать</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- МОДАЛКА НАСТРОЕК -->
        <div x-show="showDeviceModal" x-cloak class="fixed inset-0 z-[400] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md" x-transition>
            <div class="bg-[#0f0f0f] border border-white/10 w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl" @click.away="showDeviceModal = false">
                <h3 class="text-lg font-black uppercase italic tracking-tighter mb-6 text-white text-center">Медиа настройки</h3>
                <div class="space-y-6">
                    <div>
                        <label class="text-[9px] font-black uppercase text-gray-500 mb-2 block">Камера</label>
                        <div class="max-h-40 overflow-y-auto space-y-2 custom-scrollbar">
                            <template x-for="d in devices.filter(x => x.kind === 'videoinput')">
                                <button @click="switchDevice('video', d.deviceId)" :class="selectedCam === d.deviceId ? 'border-indigo-500 bg-indigo-500/10 text-white' : 'border-white/5 bg-white/5 text-gray-400'" class="w-full text-left px-4 py-3 rounded-xl border text-[11px] font-bold truncate" x-text="d.label || 'Камера ' + d.deviceId.slice(0,5)"></button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="text-[9px] font-black uppercase text-gray-500 mb-2 block">Микрофон</label>
                        <div class="max-h-40 overflow-y-auto space-y-2 custom-scrollbar">
                            <template x-for="d in devices.filter(x => x.kind === 'audioinput')">
                                <button @click="switchDevice('audio', d.deviceId)" :class="selectedMic === d.deviceId ? 'border-indigo-500 bg-indigo-500/10 text-white' : 'border-white/5 bg-white/5 text-gray-400'" class="w-full text-left px-4 py-3 rounded-xl border text-[11px] font-bold truncate" x-text="d.label || 'Микрофон ' + d.deviceId.slice(0,5)"></button>
                            </template>
                        </div>
                    </div>
                </div>
                <button @click="showDeviceModal = false" class="w-full mt-8 bg-indigo-600 py-4 rounded-2xl font-black text-[10px] uppercase shadow-lg shadow-indigo-600/20 text-white">Готово</button>
            </div>
        </div>
    </div>

    <script>
    window.rtcConfig = { 
        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }, { urls: 'stun:stun1.l.google.com:19302' }], 
        bundlePolicy: "balanced" 
    };

    window.videoChatApp = function(myId, myInterests) {
        return {
            tab: 'chat', mobileSidebarOpen: false, controlsOpen: true, touchStart: 0,
            state: 'idle', partnerId: null, partnerData: null, isFriend: false,
            partnerState: 'active', offlineTimer: null, isCallingFriend: false,
            pc: null, localStream: null, onlineList: [], friendsList: [], historyList: [], blockedList: [],
            micEnabled: true, camEnabled: true, isBlurred: false, isBlurredByPartner: false,
            showSelfVideo: true, messages: [], chatInput: '', ping: 0, beautyFilter: localStorage.getItem('beauty_filter') === 'true',
            showDeviceModal: false, devices: [], selectedCam: '', selectedMic: '',
            xpPopups: [], matchInterests: [], showIceBreaker: false, myInterests: myInterests,
            isPartnerTyping: false, msgSound: new Audio('/sounds/message.mp3'),
            isNegotiating: false, makingOffer: false, dataChannel: null, audioUnlocked: false,
            iceQueue: [], currentLevel: {{ auth()->user()->level }}, lastTypingSent: 0,

            async init() {
                window.Echo.join('online-status').here(u => this.onlineList = u).joining(u => this.onlineList.push(u)).leaving(u => this.onlineList = this.onlineList.filter(x => x.id !== u.id));
                window.Echo.private(`user.${myId}`)
                    .listen('.MatchFoundEvent', (e) => this.handleMatch(e))
                    .listen('.WebRTCSignalEvent', (e) => this.handleSignal(e))
                    .listen('.MessageSentEvent', (e) => this.handleIncomingMsg(e))
                    .listen('.UserTypingEvent', (e) => {
                        if (e.senderId === this.partnerId) {
                            this.isPartnerTyping = true;
                            clearTimeout(this.typingTimeout);
                            this.typingTimeout = setTimeout(() => this.isPartnerTyping = false, 3000);
                        }
                    })
                    .listen('.XpGainedEvent', (e) => this.showXpPopup(e.xpGained, e.totalXp, e.currentLevel));
                
                this.$watch('isBlurred', value => { if (this.localStream) this.localStream.getVideoTracks().forEach(t => t.enabled = !value); this.signal({ type: 'privacy-toggled', enabled: value }); });
                window.addEventListener('online', () => { if (this.pc) this.pc.restartIce(); });
                await this.initMedia();
                this.loadFriends(); this.loadHistory(); this.loadBlocked();
                setInterval(() => { if (this.state !== 'idle') window.axios.post('/ping').catch(()=>{}); }, 15000);
            },

            unlockAudio() { if (!this.audioUnlocked) { this.msgSound.play().then(() => { this.msgSound.pause(); this.audioUnlocked = true; }).catch(() => {}); } },
            normalizeSdp(sdp) { if (typeof sdp !== 'string') sdp = sdp.sdp || ""; return sdp.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n'; },

            initPC() {
                if (this.pc) return;
                this.pc = new RTCPeerConnection(window.rtcConfig);
                this.pc.onicecandidate = (e) => e.candidate && this.signal({type:'ice', candidate: e.candidate});
                this.pc.ontrack = (e) => { if (this.$refs.remoteVideo) { this.$refs.remoteVideo.srcObject = e.streams[0]; this.$refs.remoteVideo.play().catch(()=>{}); } };
                this.pc.oniceconnectionstatechange = () => { if (this.pc.iceConnectionState === 'failed') this.pc.restartIce(); };
                if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
                this.startStats();
            },

            async handleSignal(e) {
                const msg = e.data; const senderId = Number(msg.from);
                if (msg.type === 'peer-disconnected') { this.reset(); this.startSearch(); return; }
                if (msg.type === 'privacy-toggled') { this.isBlurredByPartner = msg.enabled; return; }
                if (msg.type === 'receiver-ready') { this.state = 'connected'; this.initPC(); this.sendOffer(); return; }
                
                if (!this.pc && ['offer', 'ice'].includes(msg.type)) this.initPC();

                try {
                    if (msg.type === 'offer') {
                        await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'offer', sdp: this.normalizeSdp(msg.sdp)}));
                        const answer = await this.pc.createAnswer();
                        await this.pc.setLocalDescription(answer);
                        this.signal({ type: 'answer', sdp: answer.sdp });
                        this.processIceQueue();
                    } else if (msg.type === 'answer') {
                        await this.pc.setRemoteDescription(new RTCSessionDescription({type: 'answer', sdp: this.normalizeSdp(msg.sdp)}));
                        this.processIceQueue();
                    } else if (msg.type === 'ice' && msg.candidate) {
                        const cand = new RTCIceCandidate(msg.candidate);
                        if (this.pc?.remoteDescription) await this.pc.addIceCandidate(cand);
                        else this.iceQueue.push(msg.candidate);
                    }
                } catch(err) { console.error('Signaling Error:', err); }
            },

            async sendOffer() {
                if(!this.pc) return;
                const offer = await this.pc.createOffer();
                await this.pc.setLocalDescription(offer);
                this.signal({ type: 'offer', sdp: offer.sdp });
            },

            processIceQueue() { while(this.iceQueue.length > 0) { this.pc.addIceCandidate(new RTCIceCandidate(this.iceQueue.shift())).catch(()=>{}); } },

            handleIncomingMsg(e) {
                if (e.messageData.sender_id === this.partnerId) {
                    this.messages.push({isMe: false, text: e.messageData.message, timestamp: Date.now()});
                    this.scrollChat();
                    if(this.audioUnlocked) this.msgSound.play().catch(()=>{});
                }
            },

            async sendMsg() {
                if (!this.chatInput.trim() || !this.partnerId) return;
                const t = this.chatInput; this.chatInput = '';
                this.messages.push({isMe: true, text: t, timestamp: Date.now()});
                this.scrollChat();
                window.axios.post('/chat/message/send', { receiver_id: this.partnerId, message: t }).catch(()=>{});
            },

            sendTypingSignal() {
                if (Date.now() - this.lastTypingSent > 2000) {
                    this.lastTypingSent = Date.now();
                    window.axios.post('/chat/message/typing', { receiver_id: this.partnerId }).catch(()=>{});
                }
            },

            async startSearch() { if(this.partnerId) this.signal({type:'peer-skipped'}); this.reset(); this.state = 'searching'; await window.axios.post('/chat/search'); },
            async handleMatch(e) { this.reset(); this.partnerId = Number(e.partnerData.id); this.partnerData = e.partnerData; this.isFriend = !!e.isFriend; this.state = 'connected'; if (e.partnerData.common_interests?.length) { this.matchInterests = e.partnerData.common_interests; this.showIceBreaker = true; setTimeout(() => this.showIceBreaker = false, 4000); } this.initPC(); if (myId > this.partnerId) setTimeout(()=>this.sendOffer(), 1000); },
            stopSearch() { this.reset(); window.axios.post('/chat/leave'); },
            reset() { clearInterval(this.statsInterval); if (this.pc) { this.pc.close(); this.pc = null; } this.partnerId = null; this.partnerData = null; this.state = 'idle'; this.messages = []; this.iceQueue = []; this.isBlurredByPartner = false; if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null; },
            signal(data) { if (this.partnerId) window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } }).catch(()=>{}); },
            async initMedia() { try { this.localStream = await navigator.mediaDevices.getUserMedia({video:true, audio:true}); this.$refs.localVideo.srcObject = this.localStream; } catch(e){ alert("Ошибка камеры"); } },
            toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
            toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
            toggleBeauty() { this.beautyFilter = !this.beautyFilter; localStorage.setItem('beauty_filter', this.beautyFilter); },
            scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
            showXpPopup(a, tx, cl) { const id = Date.now(); this.xpPopups.push({ id, amount: a }); if(cl > this.currentLevel) { window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Новый уровень: '+cl, type:'success'}})); this.currentLevel = cl; } setTimeout(() => this.xpPopups = this.xpPopups.filter(p => p.id !== id), 4000); },
            handleSwipe(e) { if (!this.mobileSidebarOpen && (this.touchStart - e.changedTouches[0].clientY > 100)) this.startSearch(); },
            getDevices() { navigator.mediaDevices.enumerateDevices().then(d => { this.devices = d.filter(x => x.kind.includes('input')); this.showDeviceModal = true; }); },
            async switchDevice(kind, id) {
                const c = kind === 'video' ? { video: { deviceId: id } } : { audio: { deviceId: id } };
                const s = await navigator.mediaDevices.getUserMedia(c);
                const t = s.getTracks()[0];
                if (this.pc) { const send = this.pc.getSenders().find(x => x.track.kind === t.kind); if (send) send.replaceTrack(t); }
                const old = this.localStream.getTracks().find(x => x.kind === t.kind);
                this.localStream.removeTrack(old); this.localStream.addTrack(t);
                if (kind === 'video') { this.selectedCam = id; this.$refs.localVideo.srcObject = this.localStream; }
            },
            async toggleContact() { const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); this.isFriend = res.data.isFriend; this.loadFriends(); },
            async report(id) { if(confirm('Заблокировать?')) { await window.axios.post('/report', {reported_id:id, reason:'abuse'}); this.startSearch(); } },
            loadFriends() { window.axios.get('/chat/contacts').then(r => this.friendsList = r.data.contacts); },
            loadHistory() { window.axios.get('/chat/history-all').then(r => this.historyList = r.data.history); },
            loadBlocked() { window.axios.get('/chat/blocked').then(r => this.blockedList = r.data.blocked); },
            async unblock(id) { await window.axios.post('/chat/unblock', { blockedId: id }); this.loadBlocked(); },
            async callFriend(f) { this.partnerId = f.id; this.state = 'searching'; this.isCallingFriend = true; await window.axios.post('/chat/contact/call', { contactId: f.id }); },
            startStats() { this.statsInterval = setInterval(async () => { if (this.pc?.iceConnectionState === 'connected') { const s = await this.pc.getStats(); s.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); }
        }
    };
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 10px; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</x-app-layout>