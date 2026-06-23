<x-app-layout>
    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- ВИДЕО-БЛОК -->
                <div class="lg:col-span-3 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Локальное видео -->
                        <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-lg h-[450px] flex items-center justify-center relative border border-gray-800">
                            <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                            <div class="absolute top-4 right-4 flex gap-2 z-10">
                                <button id="toggleMicBtn" class="bg-gray-900/80 p-2.5 rounded-xl text-white hover:bg-gray-800 transition">🎤 <span id="micStatusText" class="text-xs">Mute</span></button>
                                <button id="toggleCamBtn" class="bg-gray-900/80 p-2.5 rounded-xl text-white hover:bg-gray-800 transition">📷 <span id="camStatusText" class="text-xs">Mute</span></button>
                            </div>
                            <div class="absolute bottom-4 left-4 bg-gray-900/70 text-white px-3 py-1 text-xs rounded-full border border-gray-700">Вы (ID: {{ auth()->id() }})</div>
                        </div>
                        
                        <!-- Удаленное видео -->
                        <div id="remoteVideoContainer" class="bg-gray-900 rounded-2xl overflow-hidden shadow-lg h-[450px] flex items-center justify-center relative border border-gray-800">
                            <video id="remoteVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                            <div id="remoteStatus" class="absolute text-gray-400 text-sm font-medium">Ожидание...</div>
                            <div id="partnerLabel" class="hidden absolute bottom-4 left-4 bg-indigo-600/90 text-white px-3 py-1 text-xs rounded-full shadow-lg">Собеседник</div>
                            
                            <!-- Алерты (Mute/Cam) -->
                            <div id="remoteMediaAlerts" class="absolute flex flex-col gap-2 items-center justify-center pointer-events-none z-10">
                                <span id="alertRemoteCam" class="hidden bg-red-600/90 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md border border-red-500">Собеседник выключил камеру</span>
                                <span id="alertRemoteMic" class="hidden bg-amber-600/90 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md border border-amber-500">Собеседник выключил звук</span>
                            </div>
                        </div>
                    </div>

                    <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-[250px]">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-2">Управление</h3>
                                <div class="p-3 bg-gray-50 rounded-xl mb-3 border border-gray-100">
                                    <span class="text-[10px] font-semibold text-gray-400 block uppercase tracking-wider">Статус</span>
                                    <div id="connectionStatus" class="text-gray-600 font-bold text-sm leading-tight">Готов</div>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <button id="startSearch" class="w-full bg-blue-600 text-white py-2.5 rounded-xl font-bold hover:bg-blue-700 transition">Начать поиск</button>
                                <button id="skipAction" class="hidden w-full bg-gray-950 text-white py-2.5 rounded-xl font-bold hover:bg-black transition">Далее ➔</button>
                                <button id="hangUpBtn" class="hidden w-full bg-red-600 text-white py-2.5 rounded-xl font-bold shadow-lg shadow-red-100">Положить трубку 📞</button>
                                <button id="stopSearch" class="hidden w-full bg-red-50 text-red-600 py-2.5 rounded-xl font-bold hover:bg-red-100 transition">Стоп</button>
                            </div>
                        </div>

                        <!-- Чат Рулетки -->
                        <div id="rouletteChatBox" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 flex-col h-[250px]">
                            <div class="p-3 border-b bg-gray-50 rounded-t-2xl font-bold text-xs text-gray-700">💬 Чат с незнакомцем</div>
                            <div id="rouletteMessages" class="p-3 overflow-y-auto flex-1 space-y-2 text-xs"></div>
                            <div class="p-2 border-t flex gap-2">
                                <input type="text" id="rouletteInput" placeholder="Написать..." class="flex-1 bg-gray-50 border-none rounded-xl text-xs focus:ring-2 focus:ring-blue-500">
                                <button id="sendRouletteBtn" class="bg-blue-600 text-white px-4 py-1.5 rounded-xl font-bold">OK</button>
                            </div>
                        </div>

                        <!-- Мессенджер -->
                        <div id="messengerBox" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 flex-col h-[250px]">
                            <div class="p-3 border-b bg-gray-50 rounded-t-2xl flex justify-between items-center">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-700">Друг: <span id="chatWithLabel" class="text-indigo-600">...</span></span>
                                    <span id="typingIndicator" class="text-[10px] text-green-500 font-semibold hidden animate-pulse">печатает...</span>
                                </div>
                                <button id="closeChatBtn" class="text-gray-400">✕</button>
                            </div>
                            <div id="chatMessages" class="p-3 overflow-y-auto flex-1 space-y-2 text-xs"></div>
                            <div class="p-2 border-t flex gap-2">
                                <input type="text" id="textMessageInput" placeholder="Сообщение..." class="flex-1 bg-gray-50 border-none rounded-xl text-xs focus:ring-2 focus:ring-indigo-500">
                                <button id="sendMessageBtn" class="bg-indigo-600 text-white px-4 py-1.5 rounded-xl font-bold text-xs">SEND</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- КОНТАКТЫ -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[716px]">
                    <h2 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4">Контакты</h2>
                    <div id="contactListContainer" class="flex-1 overflow-y-auto space-y-2"></div>
                </div>

            </div>
        </div>
    </div>

    <script type="module">
        // --- 1. ПЕРЕМЕННЫЕ И КОНФИГУРАЦИЯ ---
        window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')) };
        const currentUserId = {{ auth()->id() }};
        
        let localStream = null;
        let peerConnection = null;
        let globalPartnerId = null;
        let activeChatContactId = null;
        let iceCandidatesQueue = [];
        let onlineUserIds = new Set();
        let unreadCounters = {};
        let isInCall = false;

        const connectionStatus = document.getElementById('connectionStatus');
        const remoteVideo = document.getElementById('remoteVideo');
        const remoteStatus = document.getElementById('remoteStatus');
        const startSearchBtn = document.getElementById('startSearch');
        const skipActionBtn = document.getElementById('skipAction');
        const stopSearchBtn = document.getElementById('stopSearch');
        const hangUpBtn = document.getElementById('hangUpBtn');
        const contactListContainer = document.getElementById('contactListContainer');

        // --- 2. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ---

        function formatLastSeen(dateString) {
            if (!dateString) return 'давно';
            const date = new Date(dateString);
            const now = new Date();
            const diffInMin = Math.floor((now - date) / 60000);
            if (diffInMin < 1) return 'только что';
            if (diffInMin < 60) return `${diffInMin} мин. назад`;
            const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            if (date.getDate() === now.getDate()) return `сегодня в ${timeStr}`;
            return date.toLocaleDateString() + ' ' + timeStr;
        }

        window.loadContacts = async function() {
            try {
                const res = await window.axios.get('/chat/contacts');
                contactListContainer.innerHTML = "";
                res.data.contacts.forEach(c => {
                    const isOnline = onlineUserIds.has(Number(c.id));
                    const unread = unreadCounters[c.id] || 0;
                    const node = document.createElement('div');
                    node.className = "p-3 bg-white rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between hover:border-indigo-300 transition cursor-pointer";
                    node.innerHTML = `
                        <div onclick="window.openFriendChat(${c.id}, '${c.name}')" class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full ${isOnline ? 'bg-green-500 animate-pulse' : 'bg-gray-300'}"></span>
                                <span class="text-xs font-bold text-gray-800">${c.name}</span>
                                ${unread > 0 ? `<span class="bg-red-500 text-white text-[9px] px-1.5 py-0.5 rounded-full">${unread}</span>` : ''}
                            </div>
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                ${isOnline ? '<span class="text-green-600 font-medium">В сети</span>' : formatLastSeen(c.last_seen)}
                            </div>
                        </div>
                        ${isOnline ? `
                            <button onclick="window.directCall(${c.id})" class="p-2 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            </button>
                        ` : ''}
                    `;
                    contactListContainer.appendChild(node);
                });
            } catch (e) { console.error("Contacts loading error", e); }
        }

        // --- 3. WebRTC ЯДРО ---

        async function initCamera() {
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('localVideo').srcObject = localStream;
                return true;
            } catch (e) {
                connectionStatus.innerText = "Ошибка камеры";
                return false;
            }
        }

        function createPeerConnection() {
            if (peerConnection) return;
            peerConnection = new RTCPeerConnection(window.rtcConfig);

            if (localStream) {
                localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));
            }

            peerConnection.ontrack = (event) => {
                remoteVideo.srcObject = event.streams[0];
                remoteStatus.classList.add('hidden');
                document.getElementById('partnerLabel').classList.remove('hidden');
                isInCall = true;
            };

            peerConnection.onicecandidate = (event) => {
                if (event.candidate && globalPartnerId) {
                    sendSignalMessage({ type: 'ice-candidate', candidate: event.candidate });
                }
            };
            
            peerConnection.oniceconnectionstatechange = () => {
                if (['failed', 'disconnected', 'closed'].includes(peerConnection.iceConnectionState)) {
                    resetToIdle();
                }
            };
        }

        function sanitizeSdp(sdp) {
            return sdp.trim().split('\n').map(line => line.trim()).join('\r\n') + '\r\n';
        }

        async function handleSignalingMessage(message) {
            // Реакция на готовность партнера
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
                    if (peerConnection?.remoteDescription?.type) {
                        await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                    } else {
                        iceCandidatesQueue.push(message.candidate);
                    }
                } 
                // --- НОВОЕ: ОБРАБОТКА ТЕКСТА В РУЛЕТКЕ ---
                else if (message.type === 'roulette-text-msg') {
                    appendRouletteMessage(false, message.text);
                }
                // --- НОВОЕ: ОБРАБОТКА СТАТУСОВ КАМЕРЫ ---
                else if (message.type === 'peer-media-status') {
                    if (message.mediaType === 'video') document.getElementById('alertRemoteCam').classList.toggle('hidden', message.enabled);
                    if (message.mediaType === 'audio') document.getElementById('alertRemoteMic').classList.toggle('hidden', message.enabled);
                }
                else if (message.type === 'hang-up') {
                    resetToIdle();
                    alert("Собеседник завершил вызов");
                }
            } catch (e) { console.error("WebRTC Error:", e); }
        }

        function drainIceQueue() {
            while (iceCandidatesQueue.length > 0) {
                const c = iceCandidatesQueue.shift();
                peerConnection.addIceCandidate(new RTCIceCandidate(c)).catch(() => {});
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

        // --- 4. ДЕЙСТВИЯ (КНОПКИ) ---

        window.startNewSearch = async function() {
            resetToIdle();
            toggleUIState('searching');
            connectionStatus.innerText = "Поиск...";
            if (!localStream) await initCamera();
            window.axios.post('/chat/search');
        }

        window.directCall = async function(id) {
            if (!confirm('Начать видеозвонок?')) return;
            resetToIdle();
            if (!localStream) await initCamera();
            
            globalPartnerId = Number(id);
            connectionStatus.innerText = "Вызов...";
            window.axios.post('/chat/contact/call', { contactId: id });
            createPeerConnection();
        }

        function resetToIdle() {
            if (peerConnection) { peerConnection.close(); peerConnection = null; }
            remoteVideo.srcObject = null;
            iceCandidatesQueue = [];
            isInCall = false;
            globalPartnerId = null;
            remoteStatus.classList.remove('hidden');
            document.getElementById('partnerLabel').classList.add('hidden');
            document.getElementById('alertRemoteCam').classList.add('hidden');
            document.getElementById('alertRemoteMic').classList.add('hidden');
            toggleUIState('idle');
        }

        function toggleUIState(state) {
            if (state === 'connected') {
                startSearchBtn.classList.add('hidden');
                stopSearchBtn.classList.add('hidden');
                skipActionBtn.classList.remove('hidden');
                hangUpBtn.classList.remove('hidden');
                document.getElementById('rouletteChatBox').classList.replace('hidden', 'flex');
            } else if (state === 'searching') {
                startSearchBtn.classList.add('hidden');
                skipActionBtn.classList.remove('hidden');
                stopSearchBtn.classList.remove('hidden');
                hangUpBtn.classList.add('hidden');
            } else {
                startSearchBtn.classList.remove('hidden');
                skipActionBtn.classList.add('hidden');
                stopSearchBtn.classList.add('hidden');
                hangUpBtn.classList.add('hidden');
                document.getElementById('rouletteChatBox').classList.replace('flex', 'hidden');
                connectionStatus.innerText = "Ожидание";
            }
        }

        // --- 5. СОКЕТЫ ECHO ---

        window.addEventListener('load', async () => {
            await initCamera(); // Включаем камеру сразу
            
            window.Echo.join('online-status')
                .here(users => { users.forEach(u => onlineUserIds.add(Number(u.id))); window.loadContacts(); })
                .joining(u => { onlineUserIds.add(Number(u.id)); window.loadContacts(); })
                .leaving(u => { onlineUserIds.delete(Number(u.id)); window.loadContacts(); });

            window.Echo.private(`user.${currentUserId}`)
                .listen('.MatchFoundEvent', (e) => {
                    globalPartnerId = Number(e.partnerId);
                    toggleUIState('connected');
                    if (currentUserId > globalPartnerId) {
                        setTimeout(() => sendSignalMessage({ type: 'peer-ready' }), 1000);
                    }
                })
                .listen('.WebRTCSignalEvent', (e) => {
                    if (e.data.type === 'incoming-direct-call') {
                        if (isInCall) {
                            window.axios.post('/chat/signal', { partnerId: e.data.callerId, data: { type: 'call-rejected', reason: 'busy' } });
                        } else if (confirm(`Звонит ${e.data.callerName}. Принять?`)) {
                            resetToIdle();
                            globalPartnerId = Number(e.data.callerId);
                            toggleUIState('connected');
                            createPeerConnection();
                            sendSignalMessage({ type: 'peer-ready' });
                        } else {
                            window.axios.post('/chat/signal', { partnerId: e.data.callerId, data: { type: 'call-rejected', reason: 'declined' } });
                        }
                    } else if (e.data.type === 'call-rejected') {
                        resetToIdle();
                        alert(e.data.reason === 'busy' ? "Собеседник занят" : "Вызов отклонен");
                    } else {
                        handleSignalingMessage(e.data);
                    }
                })
                .listen('.MessageSentEvent', (e) => {
                    if (activeChatContactId === e.messageData.sender_id) {
                        appendMessage(false, e.messageData.message);
                    } else {
                        unreadCounters[e.messageData.sender_id] = (unreadCounters[e.messageData.sender_id] || 0) + 1;
                        window.loadContacts();
                    }
                });
        });

        // --- 6. ЧАТ И СООБЩЕНИЯ ---

        window.openFriendChat = (id, name) => {
            activeChatContactId = id;
            unreadCounters[id] = 0;
            document.getElementById('chatWithLabel').innerText = name;
            messengerBox.classList.replace('hidden', 'flex');
            rouletteChatBox.classList.add('hidden');
            window.axios.get(`/chat/history/${id}`).then(res => {
                document.getElementById('chatMessages').innerHTML = "";
                res.data.messages.forEach(m => appendMessage(m.sender_id === currentUserId, m.message));
                window.loadContacts();
            });
        };

        function appendMessage(isMe, text) {
            const div = document.createElement('div');
            div.className = `p-2 rounded-xl max-w-[85%] ${isMe ? 'bg-indigo-600 text-white ml-auto' : 'bg-gray-100 text-gray-800'}`;
            div.innerText = text;
            const container = document.getElementById('chatMessages');
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function appendRouletteMessage(isMe, text) {
            const div = document.createElement('div');
            div.className = `p-1.5 rounded-lg max-w-[90%] ${isMe ? 'bg-blue-600 text-white ml-auto' : 'bg-gray-100 text-gray-800'}`;
            div.innerText = text;
            const container = document.getElementById('rouletteMessages');
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        // Кнопки
        startSearchBtn.onclick = window.startNewSearch;
        stopSearchBtn.onclick = () => { window.axios.post('/chat/leave', {partnerId: globalPartnerId}); resetToIdle(); };
        skipActionBtn.onclick = () => window.startNewSearch();
        hangUpBtn.onclick = () => { sendSignalMessage({ type: 'hang-up' }); resetToIdle(); };
        document.getElementById('closeChatBtn').onclick = () => { messengerBox.classList.add('hidden'); activeChatContactId = null; };

        document.getElementById('sendRouletteBtn').onclick = () => {
            const inp = document.getElementById('rouletteInput');
            if(!inp.value.trim() || !globalPartnerId) return;
            sendSignalMessage({ type: 'roulette-text-msg', text: inp.value });
            appendRouletteMessage(true, inp.value);
            inp.value = "";
        };

        document.getElementById('sendMessageBtn').onclick = () => {
            const inp = document.getElementById('textMessageInput');
            if(!inp.value.trim() || !activeChatContactId) return;
            window.axios.post('/chat/message/send', { receiver_id: activeChatContactId, message: inp.value }).then(() => {
                appendMessage(true, inp.value);
                inp.value = "";
            });
        };

        // Mute логика
        document.getElementById('toggleMicBtn').onclick = () => {
            const t = localStream.getAudioTracks()[0];
            t.enabled = !t.enabled;
            document.getElementById('micStatusText').innerText = t.enabled ? "Mute" : "Unmute";
            sendSignalMessage({ type: 'peer-media-status', mediaType: 'audio', enabled: t.enabled });
        };
        document.getElementById('toggleCamBtn').onclick = () => {
            const t = localStream.getVideoTracks()[0];
            t.enabled = !t.enabled;
            document.getElementById('camStatusText').innerText = t.enabled ? "Mute" : "Unmute";
            sendSignalMessage({ type: 'peer-media-status', mediaType: 'video', enabled: t.enabled });
        };
    </script>
</x-app-layout>