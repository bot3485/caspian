<x-app-layout>
    <div class="h-[calc(100vh-64px)] bg-[#050505] relative overflow-hidden text-white font-sans" 
         x-data="window.videoChatApp({{ auth()->id() }})">
        
        <!-- ДЕКОРАТИВНЫЕ СВЕЧЕНИЯ -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <div class="relative h-full flex flex-col lg:flex-row">
            <!-- ЗОНА ВИДЕО -->
            <div class="flex-1 relative bg-black overflow-hidden">
                
                <!-- КАРТОЧКА СОБЕСЕДНИКА -->
                <div x-show="state === 'connected' && partnerData" 
                    class="absolute top-6 left-6 z-[90] bg-black/40 backdrop-blur-2xl p-5 rounded-[2rem] border border-white/10 flex flex-col gap-3 min-w-[260px]" 
                    x-transition>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center font-black text-xl shadow-lg" x-text="partnerData?.name?.[0] || '?'"></div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-base font-black uppercase tracking-tighter" x-text="partnerData?.name"></span>
                                <span class="bg-indigo-500 text-[8px] font-black px-2 py-0.5 rounded-full" x-text="'LVL ' + (partnerData?.level || 1)"></span>
                            </div>
                            <div class="text-[9px] text-indigo-400 font-black uppercase tracking-[0.2em] mt-0.5" x-text="partnerData?.rank_name || 'Собеседник'"></div>
                        </div>
                    </div>
                </div>

                <!-- ИНДИКАТОР СЕТИ -->
                <div x-show="state === 'connected'" class="absolute top-6 right-6 z-[90] flex items-center gap-2 bg-black/20 backdrop-blur-xl px-3 py-1.5 rounded-full border border-white/10">
                    <div class="flex gap-0.5 items-end h-3">
                        <div class="w-1 rounded-full bg-current transition-all" :class="netScore >= 1 ? 'h-1.5 text-green-500' : 'h-1.5 text-white/20'"></div>
                        <div class="w-1 rounded-full bg-current transition-all" :class="netScore >= 2 ? 'h-2 text-green-500' : 'h-1.5 text-white/20'"></div>
                        <div class="w-1 rounded-full bg-current transition-all" :class="netScore >= 3 ? 'h-3 text-green-500' : 'h-1.5 text-white/20'"></div>
                    </div>
                    <span class="text-[9px] font-black text-white/70 uppercase tracking-tighter" x-text="ping + 'ms'"></span>
                </div>

                <!-- ОВЕРЛЕЙ: ПРОБЛЕМЫ СО СВЯЗЬЮ -->
                <div x-show="connectionIssue" 
                     class="absolute inset-0 z-[95] bg-black/60 backdrop-blur-md flex flex-col items-center justify-center transition-all" x-transition>
                    <div class="w-16 h-16 border-4 border-indigo-500/20 border-t-indigo-500 rounded-full animate-spin mb-4"></div>
                    <h3 class="text-white font-black uppercase text-xs tracking-[0.2em]" x-text="connectionIssue"></h3>
                    <p class="text-gray-400 text-[10px] mt-2 italic">Пытаемся восстановить поток...</p>
                </div>

                <!-- ОВЕРЛЕЙ: СОБЕСЕДНИК СВЕРНУЛ ПРИЛОЖЕНИЕ -->
                <div x-show="partnerStatus === 'away' && state === 'connected'" 
                     class="absolute inset-0 z-[94] bg-[#050505]/80 backdrop-blur-sm flex flex-col items-center justify-center" x-transition>
                    <span class="text-4xl mb-4">📱</span>
                    <h3 class="text-white font-black uppercase text-xs tracking-[0.2em]">Собеседник отошел</h3>
                    <p class="text-gray-400 text-[10px] mt-2">Приложение свернуто или открыта другая вкладка</p>
                </div>

                <!-- Главное видео -->
                <video x-ref="remoteVideo" autoplay playsinline class="w-full h-full object-cover transition-all duration-1000" :class="isBlurred ? 'blur-[100px] scale-110 opacity-40' : 'opacity-100'"></video>
                
                <!-- Анимация поиска -->
                <div x-show="state === 'searching'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-50">
                    <div class="flex flex-col items-center">
                        <div class="relative w-32 h-32 mb-10">
                            <div class="absolute inset-0 border-2 border-indigo-500/20 rounded-full animate-ping"></div>
                            <div class="absolute inset-0 flex items-center justify-center text-4xl">📡</div>
                        </div>
                        <h3 class="text-white font-black uppercase text-[11px] tracking-[0.5em] animate-pulse italic" 
                            x-text="isCallingFriend ? 'Звоним другу...' : 'Ищем кого-то...'"></h3>
                    </div>
                </div>

                <!-- PIP (Ваше видео) -->
                <div x-show="showSelfVideo" class="absolute bottom-10 left-10 w-48 md:w-64 aspect-video bg-[#111] rounded-[2rem] overflow-hidden shadow-2xl border border-white/10 z-[80]">
                    <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]" :class="!camEnabled && 'opacity-0'"></video>
                    <div x-show="!camEnabled" class="absolute inset-0 flex items-center justify-center bg-gray-900"><span class="text-3xl text-gray-700">🚫</span></div>
                </div>

                <!-- УПРАВЛЕНИЕ -->
                <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-[100]" x-data="{ controlsOpen: true }">
                    <div class="flex items-center gap-2 p-2 bg-[#121212]/95 backdrop-blur-3xl border border-white/10 rounded-full shadow-2xl transition-all duration-300">
                        
                        <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-lg shrink-0">
                            <span x-text="controlsOpen ? '▼' : '⚡'"></span>
                        </button>

                        <div x-show="controlsOpen" x-transition class="flex items-center gap-2 pr-2 shrink-0">
                            <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-full flex items-center justify-center">🎤</button>
                            <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-12 h-12 rounded-full flex items-center justify-center">📷</button>
                            <button @click="isBlurred = !isBlurred" :class="isBlurred ? 'bg-indigo-600' : 'bg-white/5'" class="w-12 h-12 rounded-full flex items-center justify-center">🙈</button>

                            <div class="w-px h-6 bg-white/10 mx-1"></div>

                            <template x-if="state === 'idle'">
                                <button @click="startSearch()" class="bg-indigo-600 px-8 py-3.5 rounded-full font-black text-[10px] uppercase tracking-widest transition-all shadow-xl">Начать поиск</button>
                            </template>
                            
                            <template x-if="state === 'searching'">
                                <button @click="stopSearch()" class="bg-white/10 px-8 py-3.5 rounded-full font-black text-[10px] uppercase tracking-widest">Отмена</button>
                            </template>
                            
                            <template x-if="state === 'connected'">
                                <div class="flex gap-2 items-center">
                                    <button @click="report(partnerId)" class="w-12 h-12 bg-red-600/10 text-red-500 rounded-full flex items-center justify-center hover:bg-red-600 transition-all" title="Пожаловаться и заблокировать">🚩</button>
                                    
                                    <button @click="toggleContact()" 
                                            :class="isFriend ? 'bg-green-600/20 text-green-400 border-green-500/30' : 'bg-white/5 text-gray-300 border-white/10'" 
                                            class="h-12 px-5 rounded-full border flex items-center gap-2 transition-all font-black text-[10px] uppercase tracking-widest whitespace-nowrap group">
                                        <span x-text="isFriend ? '✅ В друзьях' : '⭐ Добавить'"></span>
                                    </button>

                                    <button @click="stopSearch()" class="bg-red-600/20 text-red-500 px-6 py-3.5 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all">Стоп</button>
                                    <button @click="startSearch()" class="bg-white text-black hover:bg-indigo-600 hover:text-white px-10 py-3.5 rounded-full font-black text-[10px] uppercase tracking-widest transition-all shadow-xl shrink-0 whitespace-nowrap">Далее ➔</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ ПАНЕЛЬ -->
            <div class="w-full lg:w-[400px] flex flex-col bg-[#080808] border-l border-white/5" x-data="{ tab: 'chat' }">
                <div class="flex border-b border-white/5 bg-[#0a0a0a]">
                    <button @click="tab = 'chat'" :class="tab === 'chat' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-6 text-[10px] font-black uppercase tracking-widest transition-all">Чат</button>
                    <button @click="tab = 'friends'; loadFriends()" :class="tab === 'friends' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-6 text-[10px] font-black uppercase tracking-widest transition-all">Друзья</button>
                    <button @click="tab = 'history'; loadHistory()" :class="tab === 'history' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" class="flex-1 py-6 text-[10px] font-black uppercase tracking-widest transition-all">История</button>
                </div>
                
                <!-- ВКЛАДКА: ЧАТ -->
                <div x-show="tab === 'chat'" class="flex-1 flex flex-col overflow-hidden">
                    <template x-if="state !== 'connected'">
                        <div class="flex-1 flex flex-col items-center justify-center p-12 text-center text-gray-600">
                            <span class="text-3xl mb-4">💬</span>
                            <p class="text-[10px] font-black uppercase tracking-widest">Чат заблокирован</p>
                        </div>
                    </template>

                    <template x-if="state === 'connected'">
                        <div class="flex-1 flex flex-col overflow-hidden">
                            <div class="flex-1 overflow-y-auto p-8 space-y-4 scrollbar-hide" x-ref="chatBox">
                                <template x-for="msg in messages">
                                    <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col">
                                        <div :class="msg.isMe ? 'bg-indigo-600 rounded-2xl rounded-tr-none' : 'bg-white/5 border border-white/5 rounded-2xl rounded-tl-none'" class="p-4 text-[13px] font-medium max-w-[85%]" x-text="msg.text"></div>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Кнопка жалобы в чате -->
                            <div class="px-6 py-2 flex justify-end">
                                <button @click="report(partnerId)" class="text-[9px] font-black uppercase text-red-500/40 hover:text-red-500 transition-colors">🚩 Пожаловаться</button>
                            </div>

                            <div class="p-6 bg-[#0a0a0a] border-t border-white/5">
                                <div class="flex gap-3 bg-black/40 p-2.5 rounded-2xl border border-white/10 focus-within:border-indigo-500/50 transition-colors">
                                    <input type="text" x-model="chatInput" @input="sendTypingSignal()" @keyup.enter="sendMsg()" :placeholder="'Написать ' + (partnerData?.name || '...') + '...'" class="flex-1 bg-transparent border-none text-sm focus:ring-0">
                                    <button @click="sendMsg()" class="bg-white text-black w-12 h-12 rounded-xl font-bold hover:bg-indigo-500 hover:text-white transition-all transform active:scale-95">➔</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- ВКЛАДКА: ДРУЗЬЯ -->
                <div x-show="tab === 'friends'" class="flex-1 overflow-y-auto p-6 space-y-3">
                    <template x-for="friend in friendsList" :key="friend.id">
                        <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between group hover:border-white/10 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center font-black" x-text="friend.name[0]"></div>
                                <div>
                                    <div class="text-xs font-bold text-white" x-text="friend.name"></div>
                                    <div class="text-[8px] font-black uppercase tracking-widest" :class="onlineList.some(u => u.id === friend.id) ? 'text-green-500' : 'text-gray-500'" x-text="onlineList.some(u => u.id === friend.id) ? 'В сети' : friend.last_seen_human"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="report(friend.id)" class="w-8 h-8 rounded-lg bg-red-600/10 text-red-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all" title="Блокировать">🚩</button>
                                <button @click="callFriend(friend)" :disabled="!onlineList.some(u => u.id === friend.id)" :class="onlineList.some(u => u.id === friend.id) ? 'bg-indigo-500' : 'bg-gray-800 opacity-30 cursor-not-allowed'" class="w-10 h-10 text-white rounded-xl shadow-lg transition-all flex items-center justify-center">📞</button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- ВКЛАДКА: ИСТОРИЯ -->
                <div x-show="tab === 'history'" class="flex-1 overflow-y-auto p-6 space-y-3">
                    <template x-for="item in historyList" :key="item.id">
                        <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-white/5 text-gray-500 rounded-xl flex items-center justify-center font-black" x-text="item.name[0]"></div>
                                <div>
                                    <div class="text-xs font-bold text-white" x-text="item.name"></div>
                                    <div class="text-[8px] font-black uppercase text-gray-500" x-text="'Общались ' + item.last_met_diff"></div>
                                    <div x-show="onlineList.some(u => u.id === item.id)" class="text-[7px] font-black text-green-500 uppercase mt-0.5">● Сейчас в сети</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="report(item.id)" class="w-8 h-8 rounded-lg bg-red-600/10 text-red-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">🚩</button>
                                <button @click="callFriend(item)" :disabled="!onlineList.some(u => u.id === item.id)" :class="onlineList.some(u => u.id === item.id) ? 'bg-indigo-500' : 'bg-gray-800 opacity-30'" class="w-10 h-10 text-white rounded-xl flex items-center justify-center">📞</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

<script>
window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')), bundlePolicy: "max-bundle" };

window.videoChatApp = function(myId) {
    return {
        state: 'idle', partnerId: null, partnerData: null, isFriend: false,
        pc: null, localStream: null, iceQueue: [], onlineList: [], friendsList: [], historyList: [],
        micEnabled: true, camEnabled: true, isBlurred: false, showSelfVideo: true,
        messages: [], chatInput: '', isPartnerTyping: false, typingTimeout: null, lastTypingSent: 0,
        isCallingFriend: false,
        msgSound: new Audio('/sounds/message.mp3'),
        
        connectionIssue: false,
        partnerStatus: 'active',
        ping: 0,
        netScore: 3,
        statsInterval: null,

        async init() {
            window.Echo.join('online-status').here(u => this.onlineList = u).joining(u => this.onlineList.push(u)).leaving(u => this.onlineList = this.onlineList.filter(x => x.id !== u.id));
            window.Echo.private(`user.${myId}`).listen('.MatchFoundEvent', (e) => this.handleMatch(e)).listen('.WebRTCSignalEvent', (e) => this.handleSignal(e));
            
            this.initNetworkHandlers();
            await this.initMedia();
            this.loadFriends();
            this.loadHistory();

            const urlParams = new URLSearchParams(window.location.search);
            const acceptId = urlParams.get('accept_call');
            if (acceptId) {
                this.partnerId = Number(acceptId);
                this.state = 'connected';
                this.initPC();
                setTimeout(() => this.sendOffer(), 1500);
                window.history.replaceState({}, '', '/chat');
            }
        },

        initNetworkHandlers() {
            window.addEventListener('offline', () => { this.connectionIssue = 'Интернет отключен'; this.signal({ type: 'network-status', status: 'offline' }); });
            window.addEventListener('online', () => { this.connectionIssue = false; if (this.pc) this.handleIceRestart(); });
            document.addEventListener('visibilitychange', () => { this.signal({ type: 'app-visibility', status: document.hidden ? 'away' : 'active' }); });
        },

        async loadFriends() { 
            const res = await window.axios.get('/chat/contacts'); 
            this.friendsList = res.data.contacts; 
        },

        async loadHistory() {
            const res = await window.axios.get('/chat/history-all');
            this.historyList = res.data.history;
        },

        async callFriend(friend) { 
            this.reset();
            this.partnerId = friend.id;
            this.partnerData = { ...friend, rank_name: 'Связь...' };
            this.state = 'searching';
            this.isCallingFriend = true;
            await window.axios.post('/chat/contact/call', { contactId: friend.id }); 
        },

        async handleMatch(e) {
            this.isCallingFriend = false;
            this.partnerId = Number(e.partnerData.id); 
            this.partnerData = e.partnerData;
            this.isFriend = !!e.isFriend; 
            this.state = 'connected';
            this.initPC(); 
            if (myId > this.partnerId) setTimeout(() => this.sendOffer(), 1000);
            this.loadHistory(); // Обновляем историю при новом матче
        },

        async handleSignal(e) {
            const msg = e.data;
            const senderId = Number(msg.from);

            if (msg.type === 'app-visibility') { this.partnerStatus = msg.status; return; }
            if (msg.type === 'network-status') { this.connectionIssue = (msg.status === 'offline') ? 'У партнера проблемы' : false; return; }
            if (this.partnerId && senderId !== this.partnerId && !['offer', 'answer', 'ice'].includes(msg.type)) return;
            
            try {
                if (['peer-skipped', 'hang-up', 'peer-disconnected'].includes(msg.type)) { this.reset(); if(msg.type === 'peer-skipped') this.startSearch(); return; }
                if (msg.type === 'offer' || msg.type === 'answer') {
                    await this.pc.setRemoteDescription(new RTCSessionDescription({type: msg.type, sdp: this.sanitizeSdp(msg.sdp?.sdp || msg.sdp)}));
                    if (msg.type === 'offer') {
                        const ans = await this.pc.createAnswer();
                        await this.pc.setLocalDescription(ans);
                        this.signal({type:'answer', sdp: ans});
                    }
                } else if (msg.type === 'ice') {
                    const cand = new RTCIceCandidate(msg.candidate);
                    if (this.pc && this.pc.remoteDescription) await this.pc.addIceCandidate(cand).catch(()=>{});
                    else this.iceQueue.push(cand);
                } else if (msg.type === 'text') { 
                    this.messages.push({isMe:false, text: msg.text}); 
                    window.dispatchEvent(new CustomEvent('play-msg-sound')); 
                    this.scrollChat(); 
                }
            } catch(e) { console.error("RTC Error:", e); }
        },

        initPC() {
            if (this.pc) return;
            this.pc = new RTCPeerConnection(window.rtcConfig);
            this.pc.onconnectionstatechange = () => {
                if (this.pc.connectionState === 'disconnected') this.connectionIssue = 'Связь потеряна';
                if (this.pc.connectionState === 'connected') { this.connectionIssue = false; this.startStats(); }
                if (this.pc.connectionState === 'failed') this.handleIceRestart();
            };
            this.pc.onicecandidate = (e) => { if(e.candidate) this.signal({type:'ice', candidate: e.candidate}); };
            this.pc.ontrack = (e) => { this.$refs.remoteVideo.srcObject = e.streams[0]; };
            if (this.localStream) this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
        },

        async handleIceRestart() {
            if (!this.pc || this.state !== 'connected') return;
            try {
                const offer = await this.pc.createOffer({ iceRestart: true });
                await this.pc.setLocalDescription(offer);
                this.signal({ type: 'offer', sdp: offer });
            } catch (e) { this.startSearch(); }
        },

        startStats() {
            if (this.statsInterval) clearInterval(this.statsInterval);
            this.statsInterval = setInterval(async () => {
                if (!this.pc || this.pc.connectionState !== 'connected') return;
                const stats = await this.pc.getStats();
                stats.forEach(report => {
                    if (report.type === 'candidate-pair' && report.state === 'succeeded') {
                        const rtt = report.currentRoundTripTime || report.roundTripTime;
                        if (rtt !== undefined) {
                            this.ping = Math.round(rtt * 1000) || 1;
                            this.netScore = this.ping < 150 ? 3 : (this.ping < 400 ? 2 : 1);
                        }
                    }
                });
            }, 3000);
        },

        sendOffer() { this.initPC(); this.pc.createOffer().then(o => { this.pc.setLocalDescription(o); this.signal({type:'offer', sdp: o}); }); },
        sanitizeSdp(s) { return s.split('\n').map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n'; },
        
        signal(data) { 
            if (!this.partnerId || this.state === 'idle') return; 
            window.axios.post('/chat/signal', { partnerId: this.partnerId, data: { ...data, from: myId } })
                .catch(e => { if (e.response?.status === 403) this.reset(); }); 
        },
        
        async startSearch() { if(this.partnerId) this.signal({type:'peer-skipped'}); this.reset(); this.state = 'searching'; await window.axios.post('/chat/search'); },
        stopSearch() { this.reset(); window.axios.post('/chat/leave'); this.state = 'idle'; },
        
        reset() { 
            if (this.statsInterval) clearInterval(this.statsInterval);
            if (this.pc) { this.pc.close(); this.pc = null; }
            this.partnerId = null; this.partnerData = null; this.state = 'idle'; 
            this.messages = []; this.connectionIssue = false; this.partnerStatus = 'active'; this.ping = 0;
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null; 
        },
        
        async initMedia() { try { this.localStream = await navigator.mediaDevices.getUserMedia({video:true, audio:true}); this.$refs.localVideo.srcObject = this.localStream; } catch(e) {} },
        toggleMic() { this.micEnabled = !this.micEnabled; if(this.localStream) this.localStream.getAudioTracks()[0].enabled = this.micEnabled; },
        toggleCam() { this.camEnabled = !this.camEnabled; if(this.localStream) this.localStream.getVideoTracks()[0].enabled = this.camEnabled; },
        sendMsg() { if (!this.chatInput.trim()) return; this.messages.push({isMe:true, text: this.chatInput}); this.signal({type:'text', text: this.chatInput}); this.chatInput = ''; this.scrollChat(); },
        scrollChat() { this.$nextTick(() => { if(this.$refs.chatBox) this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight; }); },
        
        async report(id) { 
            if(!id || !confirm('Пожаловаться и заблокировать этого пользователя? Вы больше никогда не встретитесь.')) return; 
            await window.axios.post('/report', {reported_id: id, reason:'abuse'});
            if (id === this.partnerId) { this.startSearch(); } 
            else { this.loadFriends(); this.loadHistory(); }
            window.dispatchEvent(new CustomEvent('toast', {detail:{msg:'Пользователь заблокирован', type:'success'}}));
        },

        async toggleContact() { 
            try {
                const res = await window.axios.post('/chat/contact/add', { contactId: this.partnerId }); 
                this.isFriend = res.data.isFriend; this.loadFriends(); 
            } catch (e) {}
        },
        copyLink() { navigator.clipboard.writeText(window.location.href); }
    }
};
</script>

<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
</style>
</x-app-layout>