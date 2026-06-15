<x-app-layout>
    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <div class="lg:col-span-3 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-lg h-[450px] flex items-center justify-center relative border border-gray-800">
                            <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                            
                            <div class="absolute top-4 right-4 flex gap-2 z-10">
                                <button id="toggleMicBtn" class="bg-gray-900 bg-opacity-80 p-2.5 rounded-xl border border-gray-700 text-white hover:bg-gray-800 transition">
                                    🎤 <span id="micStatusText" class="text-xs ml-1">Mute Mic</span>
                                </button>
                                <button id="toggleCamBtn" class="bg-gray-900 bg-opacity-80 p-2.5 rounded-xl border border-gray-700 text-white hover:bg-gray-800 transition">
                                    📷 <span id="camStatusText" class="text-xs ml-1">Mute Cam</span>
                                </button>
                            </div>

                            <div class="absolute bottom-4 left-4 bg-gray-900 bg-opacity-70 backdrop-blur-md text-white px-3 py-1 text-xs font-semibold rounded-full border border-gray-700">
                                You (ID: {{ auth()->id() }})
                            </div>
                        </div>
                        
                        <div id="remoteVideoContainer" class="bg-gray-900 rounded-2xl overflow-hidden shadow-lg h-[450px] flex items-center justify-center relative border border-gray-800">
                            <video id="remoteVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                            <div id="remoteStatus" class="absolute text-gray-400 font-medium text-sm bg-gray-900 bg-opacity-80 px-4 py-2 rounded-full border border-gray-800">
                                Camera Feed Offline
                            </div>
                            <div id="partnerLabel" class="hidden absolute bottom-4 left-4 bg-indigo-600 bg-opacity-80 backdrop-blur-md text-white px-3 py-1 text-xs font-semibold rounded-full border border-indigo-500">
                                Stranger
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between md:col-span-1 h-[250px]">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-2">Roulette Controller</h3>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-150 mb-3">
                                    <span class="text-[10px] font-semibold text-gray-400 block uppercase tracking-wider mb-0.5">State</span>
                                    <div id="connectionStatus" class="text-gray-600 font-bold text-sm">Idle</div>
                                </div>
                                <button id="contactBtn" class="hidden w-full items-center justify-center text-xs font-semibold py-1.5 px-3 rounded-xl transition border mb-3">
                                    <span id="contactBtnText">Add to Contacts</span>
                                </button>
                            </div>
                            <div class="space-y-1.5">
                                <button id="startSearch" class="w-full bg-blue-600 text-white text-sm font-semibold py-2.5 px-4 rounded-xl hover:bg-blue-700 transition shadow-sm">Start Search</button>
                                <button id="skipAction" class="hidden w-full bg-gray-950 text-white text-sm font-semibold py-2.5 px-4 rounded-xl hover:bg-black transition shadow-sm">Skip ➔</button>
                                <button id="stopSearch" class="hidden w-full bg-red-100 text-red-600 text-xs font-semibold py-2 rounded-xl hover:bg-red-200 transition">Stop</button>
                            </div>
                        </div>

                        <div id="rouletteChatBox" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 md:col-span-2 flex-col h-[250px]">
                            <div class="p-3 border-b border-gray-100 bg-gray-50 rounded-t-2xl flex items-center">
                                <span class="text-xs font-bold text-gray-700">💬 Live Chat with Stranger</span>
                            </div>
                            <div id="rouletteMessages" class="p-3 overflow-y-auto flex-1 space-y-2 text-xs"></div>
                            <div class="p-2 border-t border-gray-100 flex gap-2">
                                <input type="text" id="rouletteInput" placeholder="Send a message to stranger..." class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500">
                                <button id="sendRouletteBtn" class="bg-blue-600 text-white px-4 py-1.5 rounded-xl text-xs font-semibold hover:bg-blue-700 transition">Send</button>
                            </div>
                        </div>

                        <div id="messengerBox" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 md:col-span-2 flex-col h-[250px]">
                            <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-700">Chat with: <span id="chatWithLabel" class="text-indigo-600">None</span></span>
                                    <span id="typingIndicator" class="text-[10px] text-green-500 font-semibold hidden animate-pulse">typing...</span>
                                </div>
                                <button id="closeChatBtn" class="text-gray-400 hover:text-gray-600 text-xs font-bold">✕ Close</button>
                            </div>
                            <div id="chatMessages" class="p-3 overflow-y-auto flex-1 space-y-2 text-xs"></div>
                            <div class="p-2 border-t border-gray-100 flex gap-2">
                                <input type="text" id="textMessageInput" placeholder="Type a message..." class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-xs focus:outline-none focus:border-indigo-500">
                                <button id="sendMessageBtn" class="bg-indigo-600 text-white px-4 py-1.5 rounded-xl text-xs font-semibold hover:bg-indigo-700 transition">Send</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[716px]">
                    <h2 class="text-base font-bold text-gray-800 mb-3 tracking-tight">My Contacts</h2>
                    <div id="contactListContainer" class="flex-1 overflow-y-auto space-y-2 pr-1">
                        <div class="text-xs text-gray-400 text-center py-6">Loading friend list...</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script type="module">
        const localVideo = document.getElementById('localVideo');
        let remoteVideo = document.getElementById('remoteVideo');
        const connectionStatus = document.getElementById('connectionStatus');
        const remoteStatus = document.getElementById('remoteStatus');
        const startSearchBtn = document.getElementById('startSearch');
        const skipActionBtn = document.getElementById('skipAction');
        const stopSearchBtn = document.getElementById('stopSearch');
        const contactBtn = document.getElementById('contactBtn');
        const contactBtnText = document.getElementById('contactBtnText');
        const partnerLabel = document.getElementById('partnerLabel');
        const contactListContainer = document.getElementById('contactListContainer');
        const messengerBox = document.getElementById('messengerBox');
        const chatWithLabel = document.getElementById('chatWithLabel');
        const chatMessages = document.getElementById('chatMessages');
        const textMessageInput = document.getElementById('textMessageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const closeChatBtn = document.getElementById('closeChatBtn');
        const typingIndicator = document.getElementById('typingIndicator');

        // Hardware Controls Elements
        const toggleMicBtn = document.getElementById('toggleMicBtn');
        const toggleCamBtn = document.getElementById('toggleCamBtn');
        const micStatusText = document.getElementById('micStatusText');
        const camStatusText = document.getElementById('camStatusText');

        // Roulette Chat Elements
        const rouletteChatBox = document.getElementById('rouletteChatBox');
        const rouletteMessages = document.getElementById('rouletteMessages');
        const rouletteInput = document.getElementById('rouletteInput');
        const sendRouletteBtn = document.getElementById('sendRouletteBtn');

        const currentUserId = {{ auth()->id() }};
        let localStream = null;
        let peerConnection = null;
        let globalPartnerId = null;
        let activeChatContactId = null;
        let onlineUserIds = new Set();
        let typingTimeout = null;

        const rtcConfig = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        function toggleUIState(state) {
            if (state === 'searching' || state === 'connected') {
                startSearchBtn.classList.add('hidden');
                skipActionBtn.classList.remove('hidden');
                stopSearchBtn.classList.remove('hidden');
                if (state === 'connected') {
                    rouletteChatBox.classList.remove('hidden');
                    rouletteChatBox.classList.add('flex');
                    messengerBox.classList.add('hidden'); // прячем мессенджер друзей
                }
            } else { // idle / disconnected
                startSearchBtn.classList.remove('hidden');
                skipActionBtn.classList.add('hidden');
                stopSearchBtn.classList.add('hidden');
                rouletteChatBox.classList.add('hidden');
                rouletteChatBox.classList.remove('flex');
                rouletteMessages.innerHTML = "";
            }
        }

        function initCamera() {
            return navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(stream => { 
                    localStream = stream;
                    localVideo.srcObject = stream; 
                    return true;
                })
                .catch(err => {
                    connectionStatus.innerText = "Camera Access Denied";
                    return false;
                });
        }

        toggleUIState('idle');
        initCamera();

        // HARDWARE MUTE LOGIC
        toggleMicBtn.addEventListener('click', () => {
            if (localStream && localStream.getAudioTracks().length > 0) {
                const track = localStream.getAudioTracks()[0];
                track.enabled = !track.enabled;
                micStatusText.innerText = track.enabled ? "Mute Mic" : "Unmute Mic";
                toggleMicBtn.className = track.enabled ? "bg-gray-900 bg-opacity-80 p-2.5 rounded-xl border border-gray-700 text-white hover:bg-gray-800 transition" : "bg-red-600 bg-opacity-90 p-2.5 rounded-xl border border-red-500 text-white hover:bg-red-700 transition";
            }
        });

        toggleCamBtn.addEventListener('click', () => {
            if (localStream && localStream.getVideoTracks().length > 0) {
                const track = localStream.getVideoTracks()[0];
                track.enabled = !track.enabled;
                camStatusText.innerText = track.enabled ? "Mute Cam" : "Unmute Cam";
                toggleCamBtn.className = track.enabled ? "bg-gray-900 bg-opacity-80 p-2.5 rounded-xl border border-gray-700 text-white hover:bg-gray-800 transition" : "bg-red-600 bg-opacity-90 p-2.5 rounded-xl border border-red-500 text-white hover:bg-red-700 transition";
            }
        });

        // ROULETTE IN-CALL LIVE CHAT LOGIC
        sendRouletteBtn.addEventListener('click', () => sendRouletteMessage());
        rouletteInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') sendRouletteMessage(); });

        function sendRouletteMessage() {
            const text = rouletteInput.value.trim();
            if (!text || !globalPartnerId) return;

            // Шлем сообщение текстом через WebRTC сигнальный репитер
            sendSignalMessage({
                type: 'roulette-text-msg',
                text: text
            });

            appendRouletteMessageNode(true, text);
            rouletteInput.value = "";
        }

        function appendRouletteMessageNode(isMe, text) {
            const msgNode = document.createElement('div');
            msgNode.className = `p-1.5 rounded-lg max-w-[85%] w-fit ${isMe ? 'bg-blue-600 text-white ml-auto' : 'bg-gray-150 text-gray-800'}`;
            msgNode.innerText = text;
            rouletteMessages.appendChild(msgNode);
            rouletteMessages.scrollTop = rouletteMessages.scrollHeight;
        }

        // CORES ACTIONS
        startSearchBtn.addEventListener('click', () => startNewSearch());

        skipActionBtn.addEventListener('click', async () => {
            skipActionBtn.disabled = true;
            const oldPartnerId = globalPartnerId;
            closePeerConnection();
            globalPartnerId = null;

            try { await window.axios.post('/chat/leave', { partnerId: oldPartnerId }); } catch (e) {}
            
            connectionStatus.className = "text-amber-500 font-bold animate-pulse";
            connectionStatus.innerText = "Disconnecting...";
            remoteStatus.classList.remove('hidden');
            remoteStatus.innerText = "Cleaning connection buffers...";

            setTimeout(() => { startNewSearch(); }, 400);
        });

        stopSearchBtn.addEventListener('click', async () => {
            const oldPartnerId = globalPartnerId;
            resetToIdleState();
            try { await window.axios.post('/chat/leave', { partnerId: oldPartnerId }); } catch (e) {}
        });

        contactBtn.addEventListener('click', async () => {
            if (!globalPartnerId) return;
            try {
                const response = await window.axios.post('/chat/contact/toggle', { contactId: globalPartnerId });
                updateContactButtonUI(response.data.action === 'added');
                loadContacts();
            } catch (e) {}
        });

        closeChatBtn.addEventListener('click', () => {
            messengerBox.classList.remove('flex');
            messengerBox.classList.add('hidden');
            activeChatContactId = null;
        });

        sendMessageBtn.addEventListener('click', () => sendTextMessage());
        textMessageInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') sendTextMessage(); });

        // FRIENDS TYPING SIGNAL (Debounced)
        textMessageInput.addEventListener('input', () => {
            if (!activeChatContactId) return;
            
            // Сразу уведомляем сокет, что юзер начал печатать
            if (!typingTimeout) {
                window.axios.post('/chat/message/typing', { receiver_id: activeChatContactId, is_typing: true });
            }

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                window.axios.post('/chat/message/typing', { receiver_id: activeChatContactId, is_typing: false });
                typingTimeout = null;
            }, 1500); // если юзер замолчал на 1.5 секунды, снимаем плашку typing
        });

        async function startNewSearch() {
            closePeerConnection();
            globalPartnerId = null;
            contactBtn.classList.add('hidden');
            partnerLabel.classList.add('hidden');
            
            toggleUIState('searching');
            skipActionBtn.disabled = false;

            connectionStatus.className = "text-blue-600 font-bold animate-pulse";
            connectionStatus.innerText = "Searching...";
            remoteStatus.classList.remove('hidden');
            remoteStatus.innerText = "Looking for active users online...";

            if (!localStream) {
                const ready = await initCamera();
                if (!ready) return;
            }

            window.axios.post('/chat/search');
        }

        function resetToIdleState() {
            closePeerConnection();
            globalPartnerId = null;
            contactBtn.classList.add('hidden');
            partnerLabel.classList.add('hidden');
            
            toggleUIState('idle');
            
            connectionStatus.className = "text-gray-600 font-bold animate-none";
            connectionStatus.innerText = "Idle";
            remoteStatus.classList.remove('hidden');
            remoteStatus.innerText = "Camera Feed Offline";
        }

        function updateContactButtonUI(isContact) {
            contactBtn.classList.remove('hidden', 'flex');
            contactBtn.classList.add('flex');
            if (isContact) {
                contactBtn.className = "flex w-full items-center justify-center gap-2 text-xs font-semibold py-1.5 px-3 rounded-xl transition border bg-red-50 border-red-200 text-red-600 hover:bg-red-100 mb-3";
                contactBtnText.innerText = "Remove Friend";
            } else {
                contactBtn.className = "flex w-full items-center justify-center gap-2 text-xs font-semibold py-1.5 px-3 rounded-xl transition border bg-indigo-50 border-indigo-200 text-indigo-600 hover:bg-indigo-100 mb-3";
                contactBtnText.innerText = "Add to Contacts";
            }
        }

        function loadContacts() {
            window.axios.get('/chat/contacts').then(res => {
                contactListContainer.innerHTML = "";
                if(res.data.contacts.length === 0) {
                    contactListContainer.innerHTML = `<div class="text-center text-xs text-gray-400 py-4">No contacts added yet.</div>`;
                    return;
                }
                res.data.contacts.forEach(friend => {
                    const isOnline = onlineUserIds.has(Number(friend.id));
                    const node = document.createElement('div');
                    node.className = "p-2.5 bg-gray-50 rounded-xl border border-gray-150 flex items-center justify-between hover:bg-gray-100 transition duration-150";
                    node.innerHTML = `
                        <div class="cursor-pointer flex-1" onclick="window.openFriendChat(${friend.id}, '${friend.name}')">
                            <div class="font-bold text-xs text-gray-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full ${isOnline ? 'bg-green-500' : 'bg-gray-300'}"></span>
                                ${friend.name}
                            </div>
                            <span class="text-[10px] text-gray-400">${isOnline ? 'Online' : 'Offline'}</span>
                        </div>
                        ${isOnline ? `<button onclick="window.callFriendDirect(${friend.id})" class="bg-green-600 text-white px-2 py-1 text-[10px] font-bold rounded-lg hover:bg-green-700">Call</button>` : ''}
                    `;
                    contactListContainer.appendChild(node);
                });
            });
        }

        window.openFriendChat = function(id, name) {
            activeChatContactId = id;
            chatWithLabel.innerText = `${name} (ID: ${id})`;
            
            rouletteChatBox.classList.add('hidden'); // прячем анонимный чат, если открыли друга
            messengerBox.classList.remove('hidden');
            messengerBox.classList.add('flex');
            
            chatMessages.innerHTML = "Loading history...";
            window.axios.get(`/chat/history/${id}`).then(res => {
                chatMessages.innerHTML = "";
                res.data.messages.forEach(msg => { appendMessageNode(msg.sender_id === currentUserId, msg.message); });
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
        };

        window.callFriendDirect = function(id) {
            closePeerConnection();
            globalPartnerId = id;
            connectionStatus.innerText = "Calling friend...";
            window.axios.post('/chat/contact/call', { contactId: id });
            createPeerConnection();
            setTimeout(() => { sendOffer(); }, 600);
        };

        function sendTextMessage() {
            const text = textMessageInput.value.trim();
            if(!text || !activeChatContactId) return;
            window.axios.post('/chat/message/send', { receiver_id: activeChatContactId, message: text }).then(res => {
                appendMessageNode(true, text);
                textMessageInput.value = "";
            });
        }

        function appendMessageNode(isMe, text) {
            const msgNode = document.createElement('div');
            msgNode.className = `p-2 rounded-xl max-w-[80%] w-fit ${isMe ? 'bg-indigo-600 text-white ml-auto' : 'bg-gray-150 text-gray-800'}`;
            msgNode.innerText = text;
            chatMessages.appendChild(msgNode);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // WEBSOCKETS ECHO LISTENERS
        window.addEventListener('load', () => {
            if (typeof window.Echo !== 'undefined') {
                window.Echo.join('online-status')
                    .here((users) => { users.forEach(u => onlineUserIds.add(Number(u.id))); loadContacts(); })
                    .joining((user) => { onlineUserIds.add(Number(user.id)); loadContacts(); })
                    .leaving((user) => { onlineUserIds.delete(Number(user.id)); loadContacts(); });

                window.Echo.private(`user.${currentUserId}`)
                    .listen('.MatchFoundEvent', (e) => { handleMatchFound(e.partnerId); })
                    .listen('.WebRTCSignalEvent', async (e) => {
                        // Кастомный перехват: Входящий звонок от друга
                        if (e.data.type === 'incoming-direct-call') {
                            if(confirm(`Incoming direct call from ${e.data.callerName}. Accept?`)) {
                                closePeerConnection();
                                globalPartnerId = e.data.callerId;
                                connectionStatus.innerText = "Connected to friend";
                                createPeerConnection();
                                sendSignalMessage({ type: 'peer-ready' });
                            }
                            return;
                        }

                        // Кастомный перехват: Живой текст от незнакомца внутри рулетки
                        if (e.data.type === 'roulette-text-msg') {
                            appendRouletteMessageNode(false, e.data.text);
                            return;
                        }

                        // Кастомный перехват: Статус "Друг пишет текст..."
                        if (e.data.type === 'friend-typing') {
                            if (activeChatContactId === e.data.sender_id) {
                                if (e.data.is_typing) {
                                    typingIndicator.classList.remove('hidden');
                                } else {
                                    typingIndicator.classList.add('hidden');
                                }
                            }
                            return;
                        }

                        await handleSignalingMessage(e.data);
                    })
                    .listen('.MessageSentEvent', (e) => {
                        if (activeChatContactId === e.messageData.sender_id) {
                            appendMessageNode(false, e.messageData.message);
                        } else {
                            alert(`New message received from user ID ${e.messageData.sender_id}!`);
                        }
                    });
            }
        });

        function handleMatchFound(partnerId) {
            const myId = Number(currentUserId);
            const peerId = Number(partnerId);
            globalPartnerId = peerId;

            connectionStatus.className = "text-green-600 font-bold animate-none";
            connectionStatus.innerText = "Connected";
            remoteStatus.innerText = "Bypassing NAT barriers...";
            partnerLabel.innerText = `Stranger (ID: ${peerId})`;
            partnerLabel.classList.remove('hidden');

            toggleUIState('connected');

            window.axios.post('/chat/contact/check', { contactId: peerId }).then(res => {
                updateContactButtonUI(res.data.isContact);
            });

            createPeerConnection();

            if (myId < peerId) {
                setTimeout(() => { sendSignalMessage({ type: 'peer-ready' }); }, 300);
            }
        }

        function createPeerConnection() {
            if (peerConnection) return;
            peerConnection = new RTCPeerConnection(rtcConfig);

            if (localStream) {
                localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));
            }

            peerConnection.ontrack = (event) => {
                if (event.streams && event.streams[0]) {
                    remoteVideo.srcObject = event.streams[0];
                    remoteStatus.classList.add('hidden'); 
                    remoteStatus.innerText = ""; 
                }
            };

            peerConnection.onicecandidate = (event) => {
                if (event.candidate && globalPartnerId) {
                    sendSignalMessage({ type: 'ice-candidate', candidate: event.candidate });
                }
            };
        }

        async function sendSignalMessage(payload) {
            if (!globalPartnerId) return;
            try { await window.axios.post('/chat/signal', { partnerId: globalPartnerId, data: payload }); } catch (e) {}
        }

        async function sendOffer() {
            if (!peerConnection) return;
            try {
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);
                sendSignalMessage({ type: 'webrtc-offer', sdpType: offer.type, sdpString: offer.sdp });
            } catch (err) {}
        }

        async function handleSignalingMessage(message) {
            if (message.type === 'peer-ready') {
                sendOffer();
                return;
            }

            if (message.type === 'peer-disconnected') {
                setTimeout(() => {
                    if (!globalPartnerId || globalPartnerId === message.oldPartnerId) resetToIdleState();
                }, 200);
                return;
            }

            if (!peerConnection) createPeerConnection();

            try {
                if (message.type === 'webrtc-offer') {
                    let sanitizedSDP = message.sdpString.replace(/\r\n\r\n/g, '\r\n').trim() + '\r\n';
                    await peerConnection.setRemoteDescription(new RTCSessionDescription({ type: message.sdpType, sdp: sanitizedSDP }));
                    const answer = await peerConnection.createAnswer();
                    await peerConnection.setLocalDescription(answer);
                    sendSignalMessage({ type: 'webrtc-answer', sdpType: answer.type, sdpString: answer.sdp });
                    
                } else if (message.type === 'webrtc-answer') {
                    let sanitizedSDP = message.sdpString.replace(/\r\n\r\n/g, '\r\n').trim() + '\r\n';
                    await peerConnection.setRemoteDescription(new RTCSessionDescription({ type: message.sdpType, sdp: sanitizedSDP }));
                    
                } else if (message.type === 'ice-candidate') {
                    if (peerConnection.remoteDescription && peerConnection.remoteDescription.type) {
                        await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                    } else {
                        setTimeout(async () => {
                            if (peerConnection.remoteDescription && peerConnection.remoteDescription.type) {
                                try { await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate)); } catch (e) {}
                            }
                        }, 1200);
                    }
                }
            } catch (err) {}
        }

        function closePeerConnection() {
            if (peerConnection) {
                peerConnection.ontrack = null;
                peerConnection.onicecandidate = null;
                peerConnection.getSenders().forEach(sender => { try { peerConnection.removeTrack(sender); } catch (e) {} });
                peerConnection.close();
                peerConnection = null;
            }
            remoteVideo.srcObject = null;
            remoteVideo.load();
            remoteStatus.classList.remove('hidden'); 
            remoteStatus.innerText = "Camera Feed Offline";
        }
    </script>
</x-app-layout>