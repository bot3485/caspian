<x-app-layout>
    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- ЛЕВАЯ ЧАСТЬ: ВИДЕО И ЧАТ -->
                <div class="lg:col-span-3 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Локальное видео (Вы) -->
                        <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-lg h-[450px] flex items-center justify-center relative border border-gray-800">
                            <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                            
                            <div class="absolute top-4 right-4 flex gap-2 z-10">
                                <button id="toggleMicBtn" class="bg-gray-900/80 p-2.5 rounded-xl border border-gray-700 text-white hover:bg-gray-800 transition">
                                    🎤 <span id="micStatusText" class="text-xs ml-1">Mute</span>
                                </button>
                                <button id="toggleCamBtn" class="bg-gray-900/80 p-2.5 rounded-xl border border-gray-700 text-white hover:bg-gray-800 transition">
                                    📷 <span id="camStatusText" class="text-xs ml-1">Mute</span>
                                </button>
                            </div>

                            <div class="absolute bottom-4 left-4 bg-gray-900/70 backdrop-blur-md text-white px-3 py-1 text-xs font-semibold rounded-full border border-gray-700">
                                Вы (ID: {{ auth()->id() }})
                            </div>
                        </div>
                        
                        <!-- Удаленное видео (Собеседник) -->
                        <div id="remoteVideoContainer" class="bg-gray-900 rounded-2xl overflow-hidden shadow-lg h-[450px] flex items-center justify-center relative border border-gray-800">
                            <video id="remoteVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                            
                            <div id="remoteMediaAlerts" class="absolute flex flex-col gap-2 items-center justify-center pointer-events-none z-10">
                                <span id="alertRemoteCam" class="hidden bg-red-600/90 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md border border-red-500">Камера партнера выключена</span>
                                <span id="alertRemoteMic" class="hidden bg-amber-600/90 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md border border-amber-500">Микрофон партнера выключен</span>
                            </div>

                            <div id="remoteStatus" class="absolute text-gray-400 font-medium text-sm bg-gray-900/80 px-4 py-2 rounded-full border border-gray-800">
                                Собеседник не подключен
                            </div>
                            <div id="partnerLabel" class="hidden absolute bottom-4 left-4 bg-indigo-600/80 backdrop-blur-md text-white px-3 py-1 text-xs font-semibold rounded-full border border-indigo-500">
                                Незнакомец
                            </div>
                        </div>
                    </div>

                    <!-- БЛОК УПРАВЛЕНИЯ И ТЕКСТОВЫХ ЧАТОВ -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Контроллер поиска -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between md:col-span-1 h-[250px]">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-2">Рулетка</h3>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-150 mb-3">
                                    <span class="text-[10px] font-semibold text-gray-400 block uppercase tracking-wider mb-0.5">Статус</span>
                                    <div id="connectionStatus" class="text-gray-600 font-bold text-sm leading-tight">Готов к поиску</div>
                                </div>
                                <button id="contactBtn" class="hidden w-full items-center justify-center text-xs font-semibold py-1.5 px-3 rounded-xl transition border mb-3">
                                    <span id="contactBtnText">В контакты</span>
                                </button>
                            </div>
                            <div class="space-y-1.5">
                                <button id="startSearch" class="w-full bg-blue-600 text-white text-sm font-semibold py-2.5 px-4 rounded-xl hover:bg-blue-700 transition">Начать поиск</button>
                                <button id="skipAction" class="hidden w-full bg-gray-950 text-white text-sm font-semibold py-2.5 px-4 rounded-xl hover:bg-black transition">Далее ➔</button>
                                <button id="stopSearch" class="hidden w-full bg-red-50 text-red-600 text-xs font-semibold py-2 rounded-xl hover:bg-red-100 transition">Стоп</button>
                            </div>
                        </div>

                        <!-- Чат Рулетки -->
                        <div id="rouletteChatBox" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 md:col-span-2 flex-col h-[250px]">
                            <div class="p-3 border-b border-gray-100 bg-gray-50 rounded-t-2xl flex items-center">
                                <span class="text-xs font-bold text-gray-700">💬 Чат с незнакомцем</span>
                            </div>
                            <div id="rouletteMessages" class="p-3 overflow-y-auto flex-1 space-y-2 text-xs"></div>
                            <div class="p-2 border-t border-gray-100 flex gap-2">
                                <input type="text" id="rouletteInput" placeholder="Написать..." class="flex-1 bg-gray-50 border-none rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500">
                                <button id="sendRouletteBtn" class="bg-blue-600 text-white px-4 py-1.5 rounded-xl text-xs font-semibold">Send</button>
                            </div>
                        </div>

                        <!-- Мессенджер -->
                        <div id="messengerBox" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 md:col-span-2 flex-col h-[250px]">
                            <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-700">Друг: <span id="chatWithLabel" class="text-indigo-600">...</span></span>
                                    <span id="typingIndicator" class="text-[10px] text-green-500 font-semibold hidden animate-pulse">печатает...</span>
                                </div>
                                <button id="closeChatBtn" class="text-gray-400 hover:text-gray-600 text-xs font-bold">✕</button>
                            </div>
                            <div id="chatMessages" class="p-3 overflow-y-auto flex-1 space-y-2 text-xs"></div>
                            <div class="p-2 border-t border-gray-100 flex gap-2">
                                <input type="text" id="textMessageInput" placeholder="Ваше сообщение..." class="flex-1 bg-gray-50 border-none rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500">
                                <button id="sendMessageBtn" class="bg-indigo-600 text-white px-4 py-1.5 rounded-xl text-xs font-semibold">Send</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ ЧАСТЬ: КОНТАКТЫ -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[716px]">
                    <h2 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4">Контакты</h2>
                    <div id="contactListContainer" class="flex-1 overflow-y-auto space-y-2">
                        <div class="text-xs text-gray-300 text-center py-10 italic">Загрузка списка...</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

<script type="module">
    // --- 1. КОНФИГУРАЦИЯ И ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ ---
    window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')) };
    const currentUserId = {{ auth()->id() }};
    
    let localStream = null;
    let peerConnection = null;
    let globalPartnerId = null;
    let activeChatContactId = null;
    let iceCandidatesQueue = [];
    let onlineUserIds = new Set();
    let unreadCounters = {};

    // Элементы UI
    const connectionStatus = document.getElementById('connectionStatus');
    const remoteVideo = document.getElementById('remoteVideo');
    const remoteStatus = document.getElementById('remoteStatus');
    const startSearchBtn = document.getElementById('startSearch');
    const skipActionBtn = document.getElementById('skipAction');
    const stopSearchBtn = document.getElementById('stopSearch');
    const rouletteChatBox = document.getElementById('rouletteChatBox');
    const messengerBox = document.getElementById('messengerBox');

    // --- 2. РАБОТА С КАМЕРОЙ ---
    async function initCamera() {
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            document.getElementById('localVideo').srcObject = localStream;
            return true;
        } catch (e) {
            connectionStatus.innerText = "Ошибка: Камера не найдена";
            return false;
        }
    }

    // --- 3. ЛОГИКА ПОИСКА (ИСПРАВЛЕНО) ---
    async function startNewSearch() {
        console.log("Запуск поиска...");
        closePeerConnection();
        globalPartnerId = null;
        
        toggleUIState('searching');
        connectionStatus.innerText = "Ищем собеседника...";
        
        if (!localStream) {
            const hasCamera = await initCamera();
            if (!hasCamera) return;
        }
        
        try {
            await window.axios.post('/chat/search');
        } catch (err) {
            console.error(err);
            connectionStatus.innerText = "Ошибка сервера поиска";
        }
    }

    function toggleUIState(state) {
        if (state === 'searching' || state === 'connected') {
            startSearchBtn.classList.add('hidden');
            skipActionBtn.classList.remove('hidden');
            stopSearchBtn.classList.remove('hidden');
            if (state === 'connected') {
                rouletteChatBox.classList.replace('hidden', 'flex');
                messengerBox.classList.add('hidden');
                document.getElementById('partnerLabel').classList.remove('hidden');
            }
        } else {
            startSearchBtn.classList.remove('hidden');
            skipActionBtn.classList.add('hidden');
            stopSearchBtn.classList.add('hidden');
            rouletteChatBox.classList.replace('flex', 'hidden');
            document.getElementById('partnerLabel').classList.add('hidden');
            connectionStatus.innerText = "Готов к работе";
        }
    }

    // --- 4. WebRTC И СИГНАЛИНГ ---
    function createPeerConnection() {
        if (peerConnection) return;
        
        peerConnection = new RTCPeerConnection({
            ...window.rtcConfig,
            iceTransportPolicy: 'all'
        });

        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

        peerConnection.ontrack = (event) => {
            remoteVideo.srcObject = event.streams[0];
            remoteStatus.classList.add('hidden');
        };

        peerConnection.onicecandidate = (event) => {
            if (event.candidate && globalPartnerId) {
                sendSignalMessage({ type: 'ice-candidate', candidate: event.candidate });
            }
        };

        peerConnection.oniceconnectionstatechange = () => {
            const s = peerConnection.iceConnectionState;
            if (s === 'connected') connectionStatus.innerText = "Связь установлена";
            if (s === 'failed' || s === 'disconnected') connectionStatus.innerText = "Партнер отключился";
        };
    }

    function sanitizeSdp(sdp) {
        return sdp.trim().split('\n').map(line => line.trim()).join('\r\n') + '\r\n';
    }

    async function handleSignalingMessage(message) {
        if (message.type === 'peer-ready') {
            sendOffer();
            return;
        }

        try {
            if (message.type === 'webrtc-offer' || message.type === 'webrtc-answer') {
                if (!peerConnection) createPeerConnection();
                
                await peerConnection.setRemoteDescription({
                    type: message.sdpType,
                    sdp: sanitizeSdp(message.sdpString)
                });

                if (message.type === 'webrtc-offer') {
                    const answer = await peerConnection.createAnswer();
                    await peerConnection.setLocalDescription(answer);
                    sendSignalMessage({
                        type: 'webrtc-answer',
                        sdpType: answer.type,
                        sdpString: answer.sdp
                    });
                }
                drainIceQueue();
            } 
            else if (message.type === 'ice-candidate') {
                if (peerConnection && peerConnection.remoteDescription && peerConnection.remoteDescription.type) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                } else {
                    iceCandidatesQueue.push(message.candidate);
                }
            }
            else if (message.type === 'peer-media-status') {
                handlePartnerMedia(message.mediaType, message.enabled);
            }
            else if (message.type === 'roulette-text-msg') {
                appendRouletteMessage(false, message.text);
            }
            else if (message.type === 'peer-disconnected') {
                resetToIdle();
            }
        } catch (e) { console.error("Signaling error:", e); }
    }

    function drainIceQueue() {
        while (iceCandidatesQueue.length > 0) {
            const candidate = iceCandidatesQueue.shift();
            peerConnection.addIceCandidate(new RTCIceCandidate(candidate)).catch(() => {});
        }
    }

    async function sendOffer() {
        createPeerConnection();
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        sendSignalMessage({ type: 'webrtc-offer', sdpType: offer.type, sdpString: offer.sdp });
    }

    function sendSignalMessage(payload) {
        if (!globalPartnerId) return;
        window.axios.post('/chat/signal', { partnerId: globalPartnerId, data: payload });
    }

    // --- 5. МЕССЕНДЖЕР И КОНТАКТЫ ---
    function formatLastSeen(dateString) {
        if (!dateString) return 'давно';
        const date = new Date(dateString);
        const diff = Math.floor((new Date() - date) / 60000);
        if (diff < 1) return 'только что';
        if (diff < 60) return `${diff} мин. назад`;
        return date.toLocaleDateString();
    }

    window.loadContacts = async function() {
        const res = await window.axios.get('/chat/contacts');
        const container = document.getElementById('contactListContainer');
        container.innerHTML = "";

        res.data.contacts.forEach(c => {
            const isOnline = onlineUserIds.has(Number(c.id));
            const unread = unreadCounters[c.id] || 0;
            const node = document.createElement('div');
            node.className = "p-3 bg-white rounded-2xl border border-gray-100 shadow-sm cursor-pointer hover:bg-indigo-50 transition";
            node.innerHTML = `
                <div onclick="window.openFriendChat(${c.id}, '${c.name}')">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full ${isOnline ? 'bg-green-500' : 'bg-gray-300'}"></span>
                            <span class="text-xs font-bold text-gray-800">${c.name}</span>
                            ${unread > 0 ? `<span class="bg-red-500 text-white text-[9px] px-1.5 py-0.5 rounded-full animate-bounce">${unread}</span>` : ''}
                        </div>
                    </div>
                    <div class="text-[10px] text-gray-400 mt-1">${isOnline ? 'В сети' : 'Был: ' + formatLastSeen(c.last_seen)}</div>
                </div>
            `;
            container.appendChild(node);
        });
    }

    window.openFriendChat = (id, name) => {
        activeChatContactId = id;
        unreadCounters[id] = 0;
        document.getElementById('chatWithLabel').innerText = name;
        messengerBox.classList.replace('hidden', 'flex');
        rouletteChatBox.classList.add('hidden');
        document.getElementById('chatMessages').innerHTML = "Загрузка...";
        window.axios.get(`/chat/history/${id}`).then(res => {
            document.getElementById('chatMessages').innerHTML = "";
            res.data.messages.forEach(m => appendMessage(m.sender_id === currentUserId, m.message));
            window.loadContacts();
        });
    };

    function appendMessage(isMe, text) {
        const msg = document.createElement('div');
        msg.className = `p-2 rounded-xl max-w-[85%] ${isMe ? 'bg-indigo-600 text-white ml-auto' : 'bg-gray-100 text-gray-800'}`;
        msg.innerText = text;
        const chat = document.getElementById('chatMessages');
        chat.appendChild(msg);
        chat.scrollTop = chat.scrollHeight;
    }

    // --- 6. СОБЫТИЯ ECHO ---
    window.addEventListener('load', () => {
        window.Echo.private(`user.${currentUserId}`)
            .listen('.MatchFoundEvent', (e) => {
                globalPartnerId = Number(e.partnerId);
                toggleUIState('connected');
                if (currentUserId > globalPartnerId) {
                    setTimeout(() => sendSignalMessage({ type: 'peer-ready' }), 1000);
                }
            })
            .listen('.WebRTCSignalEvent', (e) => handleSignalingMessage(e.data))
            .listen('.MessageSentEvent', (e) => {
                if (activeChatContactId === e.messageData.sender_id) {
                    appendMessage(false, e.messageData.message);
                } else {
                    unreadCounters[e.messageData.sender_id] = (unreadCounters[e.messageData.sender_id] || 0) + 1;
                    window.loadContacts();
                }
            });

        window.Echo.join('online-status')
            .here(users => { users.forEach(u => onlineUserIds.add(Number(u.id))); window.loadContacts(); })
            .joining(u => { onlineUserIds.add(Number(u.id)); window.loadContacts(); })
            .leaving(u => { onlineUserIds.delete(Number(u.id)); window.loadContacts(); });
    });

    // --- УТИЛИТЫ ---
    function handlePartnerMedia(type, enabled) {
        if (type === 'video') document.getElementById('alertRemoteCam').classList.toggle('hidden', enabled);
        if (type === 'audio') document.getElementById('alertRemoteMic').classList.toggle('hidden', enabled);
    }

    function closePeerConnection() {
        if (peerConnection) { peerConnection.close(); peerConnection = null; }
        remoteVideo.srcObject = null;
        iceCandidatesQueue = [];
        remoteStatus.classList.remove('hidden');
    }

    function resetToIdle() {
        closePeerConnection();
        toggleUIState('idle');
    }

    function appendRouletteMessage(isMe, text) {
        const div = document.createElement('div');
        div.className = `p-1.5 rounded-lg max-w-[90%] ${isMe ? 'bg-blue-600 text-white ml-auto' : 'bg-gray-100 text-gray-800'}`;
        div.innerText = text;
        const cont = document.getElementById('rouletteMessages');
        cont.appendChild(div);
        cont.scrollTop = cont.scrollHeight;
    }

    // Кнопки
    startSearchBtn.onclick = startNewSearch;
    stopSearchBtn.onclick = () => { window.axios.post('/chat/leave', {partnerId: globalPartnerId}); resetToIdle(); };
    skipActionBtn.onclick = () => { 
        skipActionBtn.disabled = true;
        window.axios.post('/chat/leave', {partnerId: globalPartnerId}).then(() => {
            skipActionBtn.disabled = false;
            startNewSearch();
        });
    };
    document.getElementById('closeChatBtn').onclick = () => { messengerBox.classList.add('hidden'); activeChatContactId = null; };
    
    // Сообщения
    document.getElementById('sendRouletteBtn').onclick = () => {
        const input = document.getElementById('rouletteInput');
        if (!input.value.trim() || !globalPartnerId) return;
        sendSignalMessage({ type: 'roulette-text-msg', text: input.value });
        appendRouletteMessage(true, input.value);
        input.value = "";
    };

    document.getElementById('sendMessageBtn').onclick = () => {
        const input = document.getElementById('textMessageInput');
        if (!input.value.trim() || !activeChatContactId) return;
        window.axios.post('/chat/message/send', { receiver_id: activeChatContactId, message: input.value }).then(() => {
            appendMessage(true, input.value);
            input.value = "";
        });
    };
</script>
</x-app-layout>