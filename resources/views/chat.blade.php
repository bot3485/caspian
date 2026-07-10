<x-app-layout>
    <div class="h-[calc(100svh-0px)] md:h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans selection:bg-indigo-500/30" 
         x-data="window.videoChatApp({{ auth()->id() }}, {{ json_encode(auth()->user()->interests ?? []) }})"
         @touchstart="touchStart = $event.touches[0].clientY"
         @touchend="handleSwipe($event)">
        
        <!-- ДЕКОРАТИВНЫЕ СВЕЧЕНИЯ -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <div class="relative h-full flex flex-col lg:flex-row">
            
            <!-- ЗОНА ВИДЕО -->
            <div class="flex-1 relative bg-black overflow-hidden h-full">
                
                <!-- ICE BREAKER OVERLAY (ОБЩИЕ ИНТЕРЕСЫ) -->
                <div x-show="showIceBreaker" x-cloak
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 scale-50 blur-2xl"
                     x-transition:enter-end="opacity-100 scale-100 blur-0"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-end="opacity-0 scale-150 blur-2xl"
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
                <div class="fixed bottom-36 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 pointer-events-none z-[200]">
                    <template x-for="popup in xpPopups" :key="popup.id">
                        <div x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-10"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-500"
                             x-transition:leave-end="opacity-0 -translate-y-20 scale-110"
                             class="bg-indigo-600 text-white px-6 py-3 rounded-full font-black text-[10px] uppercase tracking-widest shadow-2xl flex items-center gap-3 border border-indigo-400/30">
                            <span class="text-base">⚡</span>
                            <span x-text="'+' + popup.amount + ' XP за общение'"></span>
                        </div>
                    </template>
                </div>

                <!-- ВЕРХНЯЯ ПАНЕЛЬ: ПАРТНЕР -->
                <div x-show="state === 'connected' && partnerData" 
                    class="absolute top-4 left-4 z-[90] w-auto max-w-[calc(100%-80px)]" 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-4">
                    <div class="bg-black/60 backdrop-blur-2xl p-2.5 md:p-4 rounded-3xl border border-white/10 flex items-center gap-3 shadow-2xl">
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center font-black text-lg shadow-lg shrink-0" x-text="partnerData?.name?.[0]"></div>
                        <div class="min-w-0 pr-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm md:text-base font-black uppercase tracking-tighter truncate" x-text="partnerData?.name"></span>
                                <span class="bg-indigo-600 text-[7px] font-black px-1.5 py-0.5 rounded-full shrink-0" x-text="'LVL ' + (partnerData?.level || 1)"></span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 opacity-60">
                                <div class="w-1.5 h-1.5 rounded-full"
                                     :class="{
                                        'bg-green-500 shadow-[0_0_8px_#22c55e]': partnerState === 'active',
                                        'bg-amber-500': partnerState === 'away',
                                        'bg-red-500 animate-pulse': partnerState === 'offline'
                                     }"></div>
                                <div class="text-[8px] font-bold uppercase tracking-widest truncate" 
                                     x-text="partnerState === 'active' ? partnerData?.rank_name : (partnerState === 'away' ? 'Вне вкладки' : 'Обрыв связи')"></div>
                                <div class="w-1 h-1 rounded-full bg-white/20"></div>
                                <div class="text-[8px] font-black" x-text="ping + 'ms'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <button x-show="state === 'connected'" @click="mobileSidebarOpen = true" 
                        class="lg:hidden absolute top-4 right-4 z-[90] w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg border border-white/20 active:scale-90 transition-all">
                    <span class="relative text-xl">💬</span>
                </button>

                <video x-ref="remoteVideo" autoplay playsinline class="w-full h-full object-cover transition-all duration-700" :class="isBlurred ? 'blur-[80px] scale-105 opacity-40' : 'opacity-100'"></video>
                
                <!-- PIP СВОЁ ВИДЕО -->
                <div x-show="showSelfVideo" 
                     class="absolute bottom-40 md:bottom-10 md:left-10 right-4 w-28 md:w-64 aspect-[3/4] md:aspect-video bg-[#111] rounded-3xl overflow-hidden shadow-2xl border border-white/10 z-[80] transition-all duration-500">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]" :class="!camEnabled && 'opacity-0'"></video>
                    <div x-show="!camEnabled" class="absolute inset-0 flex items-center justify-center bg-gray-950/80"><span class="text-xl">🚫</span></div>
                </div>

                <!-- ЭКРАН ПОИСКА -->
                <div x-show="state === 'searching'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-50 px-6">
                    <div class="relative w-24 h-24 mb-12">
                        <div class="absolute inset-0 border-2 border-indigo-500/20 rounded-full animate-ping"></div>
                        <div class="absolute inset-0 flex items-center justify-center text-3xl">📡</div>
                    </div>
                    <div class="text-center space-y-4">
                        <h3 class="text-white font-black uppercase text-[10px] tracking-[0.5em] animate-pulse" 
                            x-text="isCallingFriend ? 'Вызываем друга...' : 'Алгоритм ищет пару...'"></h3>
                        <div x-show="!isCallingFriend && myInterests.length > 0" class="max-w-xs mx-auto opacity-40">
                            <div class="flex flex-wrap justify-center gap-1.5">
                                <template x-for="tag in myInterests">
                                    <span class="px-3 py-1 bg-white/5 border border-white/10 rounded-full text-[8px] font-bold" x-text="tag"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- УПРАВЛЕНИЕ -->
                <div class="absolute bottom-24 md:bottom-8 left-0 right-0 px-4 z-[100] flex flex-col items-center gap-3">
                    <div x-show="controlsOpen" x-transition class="flex items-center gap-2 p-1.5 bg-black/40 backdrop-blur-3xl border border-white/10 rounded-2xl shadow-2xl">
                        <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">🎤</button>
                        <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5 text-white' : 'bg-red-600 text-white'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">📷</button>
                        <button @click="isBlurred = !isBlurred" :class="isBlurred ? 'bg-indigo-600' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">🙈</button>
                        
                        <template x-if="state === 'connected'">
                            <div class="flex items-center gap-2">
                                <div class="w-px h-6 bg-white/10 mx-1"></div>
                                <button @click="toggleContact()" :class="isFriend ? 'bg-green-600/20 text-green-400' : 'bg-white/5 text-white'" class="h-10 md:h-12 px-4 rounded-xl border border-white/5 font-black text-[9px] uppercase tracking-widest transition-all">
                                    <span x-text="isFriend ? 'В друзьях ✓' : '+ Друг'"></span>
                                </button>
                                <button @click="report(partnerId)" class="w-10 h-10 md:w-12 md:h-12 bg-red-600/10 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-600 transition-all">🚩</button>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-2 p-2 bg-[#121212]/95 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] shadow-2xl">
                        <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center transition-all">
                            <span class="text-xs" x-text="controlsOpen ? '▼' : '⚡'"></span>
                        </button>
                        <div class="flex items-center gap-2 pr-1">
                            <template x-if="state === 'idle'">
                                <button @click="startSearch()" class="bg-indigo-600 px-10 h-12 rounded-full font-black text-[10px] uppercase tracking-widest shadow-lg">Начать поиск</button>
                            </template>
                            <template x-if="state === 'searching'">
                                <button @click="stopSearch()" class="bg-red-600 px-10 h-12 rounded-full font-black text-[10px] uppercase tracking-widest">Остановить</button>
                            </template>
                            <template x-if="state === 'connected'">
                                <div class="flex items-center gap-2">
                                    <button @click="stopSearch()" class="bg-red-600/20 text-red-500 px-6 h-12 rounded-full font-black text-[10px] uppercase tracking-widest transition-all">Стоп</button>
                                    <button @click="startSearch()" class="bg-white text-black px-10 h-12 rounded-full font-black text-[10px] uppercase tracking-widest shadow-xl transition-all">Далее ➔</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR (MESSENGER) -->
            <div :class="mobileSidebarOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'" 
                 class="fixed inset-0 z-[150] lg:relative lg:inset-auto w-full lg:w-[400px] flex flex-col bg-[#080808] border-l border-white/5 transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] pb-24 lg:pb-0">
                
                <!-- ШАПКА SIDEBAR (ТОЛЬКО МОБИЛЬНАЯ) -->
                <div class="lg:hidden flex items-center justify-between p-6 bg-[#0a0a0a] border-b border-white/5">
                    <div class="flex flex-col">
                        <span class="font-black uppercase text-[10px] tracking-widest text-indigo-400">Caspian Chat</span>
                        <span x-show="state === 'connected'" class="text-[8px] font-bold text-green-500 uppercase mt-0.5">Соединение активно ✓</span>
                    </div>
                    <button @click="mobileSidebarOpen = false" 
                            class="bg-white/5 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-white/10 active:scale-95 transition-all">
                        ← К видео
                    </button>
                </div>

                <div class="flex border-b border-white/5 bg-[#0a0a0a]">
                    <button @click="tab = 'chat'" :class="tab === 'chat' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest">Чат</button>
                    <button @click="tab = 'friends'; loadFriends()" :class="tab === 'friends' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest">Друзья</button>
                    <button @click="tab = 'history'; loadHistory()" :class="tab === 'history' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest">История</button>
                    <button @click="tab = 'blacklist'; loadBlocked()" :class="tab === 'blacklist' ? 'text-red-500 border-b-2 border-red-500' : 'text-gray-500'" class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest">ЧС</button>
                </div>
                
                <div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">
                    <!-- ВКЛАДКА: ЧАТ -->
                    <div x-show="tab === 'chat'" class="flex-1 flex flex-col overflow-hidden">
                        <template x-if="state === 'connected'">
                            <div class="flex-1 flex flex-col overflow-hidden">
                                <div class="flex-1 overflow-y-auto p-6 space-y-4" x-ref="chatBox">
                                    <template x-for="msg in messages">
                                        <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col">
                                            <div :class="msg.isMe ? 'bg-indigo-600' : 'bg-white/5 border border-white/5'" class="p-4 text-[13px] font-medium max-w-[85%] rounded-2xl shadow-lg" x-text="msg.text"></div>
                                        </div>
                                    </template>
                                </div>
                                
                                <div class="px-6 py-2 h-8" x-show="isPartnerTyping" x-transition>
                                    <span class="text-[8px] font-black text-indigo-400 uppercase tracking-widest animate-pulse" x-text="partnerData?.name + ' печатает...'"></span>
                                </div>

                                <div class="p-4 bg-[#0a0a0a] border-t border-white/5">
                                    <div class="flex gap-2 bg-black/40 p-2 rounded-2xl border border-white/10">
                                        <input type="text" x-model="chatInput" @input="sendTypingSignal()" @keyup.enter="sendMsg()" placeholder="Написать..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 px-4">
                                        <button @click="sendMsg()" class="bg-white text-black w-10 h-10 rounded-xl font-bold active:scale-95 transition-transform">➔</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="state !== 'connected'">
                            <div class="flex-1 flex items-center justify-center opacity-20 text-[10px] font-black uppercase tracking-widest">Нет активного чата</div>
                        </template>
                    </div>

                    <!-- ВКЛАДКА: ДРУЗЬЯ -->
                    <div x-show="tab === 'friends'" class="flex-1 overflow-y-auto p-4 space-y-2">
                        <template x-for="f in friendsList" :key="f.id">
                            <div class="p-3 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center font-black text-xs" x-text="f.name[0]"></div>
                                    <div>
                                        <div class="text-xs font-bold" x-text="f.name"></div>
                                        <div class="text-[7px] font-black uppercase" :class="onlineList.some(u => u.id === f.id) ? 'text-green-500' : 'text-gray-600'" x-text="onlineList.some(u => u.id === f.id) ? 'В сети' : f.last_seen_human"></div>
                                    </div>
                                </div>
                                <button @click="callFriend(f); mobileSidebarOpen = false" :disabled="!onlineList.some(u => u.id === f.id)" class="w-9 h-9 bg-indigo-500 text-white rounded-xl flex items-center justify-center text-sm disabled:opacity-20 transition-all shadow-lg">📞</button>
                            </div>
                        </template>
                    </div>

                    <!-- ВКЛАДКА: ИСТОРИЯ -->
                    <div x-show="tab === 'history'" class="flex-1 overflow-y-auto p-4 space-y-2">
                        <template x-for="h in historyList" :key="h.id + h.last_at">
                            <div class="p-3 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-white/5 text-gray-500 rounded-xl flex items-center justify-center font-black text-xs" x-text="h.name[0]"></div>
                                    <div><div class="text-xs font-bold" x-text="h.name"></div><div class="text-[7px] font-black uppercase text-gray-600" x-text="h.last_met_diff"></div></div>
                                </div>
                                <button @click="callFriend(h)" class="w-9 h-9 bg-indigo-500 text-white rounded-xl flex items-center justify-center text-sm">📞</button>
                            </div>
                        </template>
                    </div>

                    <!-- ВКЛАДКА: ЧС -->
                    <div x-show="tab === 'blacklist'" class="flex-1 overflow-y-auto p-4 space-y-2">
                        <template x-for="b in blockedList" :key="b.id">
                            <div class="p-3 bg-red-500/5 border border-red-500/10 rounded-2xl flex items-center justify-between">
                                <div class="text-xs font-bold" x-text="b.name"></div>
                                <button @click="unblock(b.id)" class="px-3 py-1.5 bg-white/5 hover:bg-white hover:text-black rounded-lg text-[8px] font-black uppercase transition-all">Разблокировать</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')), bundlePolicy: "max-bundle" };

window.videoChatApp = function(myId, myInterests) {
    return {
        tab: 'chat', mobileSidebarOpen: false, controlsOpen: true, touchStart: 0,
        state: 'idle', partnerId: null, partnerData: null, isFriend: false,
        partnerState: 'active', offlineTimer: null, isCallingFriend: false,
        pc: null, localStream: null, onlineList: [], friendsList: [], historyList: [], blockedList: [],
        micEnabled: true, camEnabled: true, isBlurred: false, showSelfVideo: true,
        messages: [], chatInput: '', ping: 0, statsInterval: null, heartbeatInterval: null,
        
        // ФИЧИ
        xpPopups: [], matchInterests: [], showIceBreaker: false, myInterests: myInterests,
        isPartnerTyping: false, typingTimeout: null, lastTypingSent: 0,
        msgSound: new Audio('/sounds/message.mp3'),

        async init() {
            window.Echo.join('online-status').here(u => this.onlineList = u).joining(u => this.onlineList.push(u)).leaving(u => this.onlineList = this.onlineList.filter(x => x.id !== u.id));
            window.Echo.private(`user.${myId}`).listen('.MatchFoundEvent', (e) => this.handleMatch(e)).listen('.WebRTCSignalEvent', (e) => this.handleSignal(e)).listen('.XpGainedEvent', (e) => this.showXpPopup(e.xpGained));
            
            document.addEventListener('visibilitychange', () => { if (this.partnerId) this.signal({ type: 'user-state-changed', state: document.hidden ? 'away' : 'active' }); });
            window.addEventListener('offline', () => { if (this.partnerId) this.signal({ type: 'user-state-changed', state: 'offline' }); });
            window.addEventListener('online', () => { if (this.partnerId) { this.signal({ type: 'user-state-changed', state: 'active' }); this.handleIceRestart(); } });
            
            await this.initMedia();
            this.loadFriends(); this.loadHistory(); this.loadBlocked();
            this.heartbeatInterval = setInterval(() => { if (this.state !== 'idle') window.axios.post('/ping').catch(()=>{}); }, 15000);
        },

        handleSwipe(e) {
            if (this.mobileSidebarOpen) return;
            const diff = this.touchStart - e.changedTouches[0].clientY;
            if (diff > 120) { if (this.state === 'connected' || this.state === 'idle') this.startSearch(); }
        },

        showXpPopup(amount) {
            const id = Date.now();
            this.xpPopups.push({ id, amount });
            setTimeout(() => { this.xpPopups = this.xpPopups.filter(p => p.id !== id); }, 4000);
        },

        sendTypingSignal() {
            if (Date.now() - this.lastTypingSent > 2000) {
                this.lastTypingSent = Date.now();
                this.signal({ type: 'typing' });
            }
        },

        sanitizeSdp(sdp) { if (typeof sdp !== 'string') return sdp; return sdp.split('\n').map(line => line.trim()).filter(line => line.length > 0).join('\r\n') + '\r\n'; },

        async handleSignal(e) {
            const msg = e.data; const senderId = Number(msg.from);
            if (msg.type === 'user-state-changed' && senderId === this.partnerId) { this.handlePartnerState(msg.state); return; }
            
            if (msg.type === 'typing' && senderId === this.partnerId) {
                this.isPartnerTyping = true;
                clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => { this.isPartnerTyping = false; }, 3000);
                return;
            }

            if (['peer-skipped', 'hang-up', 'peer-disconnected'].includes(msg.type)) { 
                this.reset(); if(msg.type === 'peer-skipped') this.startSearch(); return; 
            }
            if (!this.pc && (msg.type === 'offer' || msg.type === 'incoming-call')) { this.partnerId = senderId; this.state = 'connected'; this.initPC(); }
            
            try {
                if (msg.type === 'offer') {
                    const sdp = new RTCSessionDescription({ type: 'offer', sdp: this.sanitizeSdp(msg.sdp.sdp || msg.sdp) });
                    await this.pc.setRemoteDescription(sdp);
                    const ans = await this.pc.createAnswer();
                    await this.pc.setLocalDescription(ans);
                    this.signal({type:'answer', sdp: ans});
                } else if (msg.type === 'answer') {
                    const sdp = new RTCSessionDescription({ type: 'answer', sdp: this.sanitizeSdp(msg.sdp.sdp || msg.sdp) });
                    await this.pc.setRemoteDescription(sdp);
                } else if (msg.type === 'ice') { if (this.pc && msg.candidate) await this.pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(()=>{}); } 
                else if (msg.type === 'text') { 
                    this.messages.push({isMe:false, text: msg.text}); 
                    this.isPartnerTyping = false; 
                    this.scrollChat(); 
                    this.msgSound.play().catch(()=>{}); // ВОСПРОИЗВЕДЕНИЕ ЗВУКА
                }
            } catch(err) { console.error("Signal Error:", err); }
        },

        handlePartnerState(s) { this.partnerState = s; if (s === 'offline') this.startDisconnectTimer(); else this.stopDisconnectTimer(); },
        startDisconnectTimer() { this.stopDisconnectTimer(); this.offlineTimer = setTimeout(() => { if (this.partnerState === 'offline') { window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Собеседник потерял связь', type:'error'}})); this.startSearch(); } }, 30000); },
        stopDisconnectTimer() { if (this.offlineTimer) clearTimeout(this.offlineTimer); },

        initPC() {
            if (this.pc) return;
            this.pc = new RTCPeerConnection(window.rtcConfig);
            this.pc.onicecandidate = (e) => e.candidate && this.signal({type:'ice', candidate: e.candidate});
            this.pc.ontrack = (e) => { if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = e.streams[0]; };
            this.pc.oniceconnectionstatechange = () => {
                const s = this.pc.iceConnectionState;
                if (['disconnected', 'failed'].includes(s)) { this.handlePartnerState('offline'); if (s === 'failed') this.handleIceRestart(); } 
                else if (s === 'connected') { this.handlePartnerState('active'); }
            };
            if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
            this.startStats();
        },

        sendOffer() { this.initPC(); this.pc.createOffer().then(o => { this.pc.setLocalDescription(o); this.signal({type:'offer', sdp: o}); }); },
        handleIceRestart() { if (!this.pc || myId < this.partnerId) return; this.pc.createOffer({ iceRestart: true }).then(o => { this.pc.setLocalDescription(o); this.signal({ type: 'offer', sdp: o }); }); },

        async startSearch() { if(this.partnerId) this.signal({type:'peer-skipped'}); this.reset(); this.state = 'searching'; this.isCallingFriend = false; await window.axios.post('/chat/search'); },
        async callFriend(f) { this.reset(); this.partnerId = f.id; this.state = 'searching'; this.isCallingFriend = true; await window.axios.post('/chat/contact/call', { contactId: f.id }); },
        stopSearch() { this.reset(); window.axios.post('/chat/leave'); },

        reset() { 
            clearInterval(this.statsInterval); this.stopDisconnectTimer();
            if (this.pc) { this.pc.close(); this.pc = null; } 
            this.partnerId = null; this.partnerData = null; this.state = 'idle'; 
            this.messages = []; this.ping = 0; this.partnerState = 'active';
            this.isCallingFriend = false; this.showIceBreaker = false; this.isPartnerTyping = false;
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null; 
        },

        signal(data) { if (!this.partnerId) return; window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } }).catch(()=>{}); },
        sendMsg() { if (!this.chatInput.trim()) return; this.messages.push({isMe:true, text: this.chatInput}); this.signal({type:'text', text: this.chatInput}); this.chatInput = ''; this.scrollChat(); },
        async initMedia() { try { this.localStream = await navigator.mediaDevices.getUserMedia({video:true, audio:true}); this.$refs.localVideo.srcObject = this.localStream; } catch(e) {} },
        toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
        toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
        scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
        
        async loadFriends() { const r = await window.axios.get('/chat/contacts'); this.friendsList = r.data.contacts; },
        async loadHistory() { const r = await window.axios.get('/chat/history-all'); this.historyList = r.data.history; },
        async loadBlocked() { const r = await window.axios.get('/chat/blocked'); this.blockedList = r.data.blocked; },
        async toggleContact() { const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); this.isFriend = res.data.isFriend; this.loadFriends(); },
        async unblock(id) { await window.axios.post('/chat/unblock', { blockedId: id }); this.loadBlocked(); },
        async report(id) { if(!confirm('Заблокировать?')) return; await window.axios.post('/report', {reported_id:id, reason:'abuse'}); this.startSearch(); },
        
        startStats() { this.statsInterval = setInterval(async () => { if (this.pc?.connectionState === 'connected') { const stats = await this.pc.getStats(); stats.forEach(r => { if (r.type === 'candidate-pair' && r.state === 'succeeded') this.ping = Math.round(r.currentRoundTripTime * 1000); }); } }, 3000); },

        async handleMatch(e) { 
            this.reset(); 
            this.partnerId = Number(e.partnerData.id); this.partnerData = e.partnerData; 
            this.isFriend = !!e.isFriend; this.state = 'connected'; 
            if (e.partnerData.common_interests && e.partnerData.common_interests.length > 0) {
                this.matchInterests = e.partnerData.common_interests;
                this.showIceBreaker = true;
                setTimeout(() => { this.showIceBreaker = false; }, 4000);
            }
            this.initPC(); 
            if (myId > this.partnerId) setTimeout(() => this.sendOffer(), 1200); 
            this.loadHistory();
        }
    }
};
</script>
</x-app-layout>