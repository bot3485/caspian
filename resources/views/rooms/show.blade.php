<x-app-layout>
    <div class="bg-gray-900 min-h-screen p-4 md:p-8">
        <!-- Сетка видео (Mesh) -->
        <div id="videoGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-7xl mx-auto h-[80vh]">
            <!-- Мое видео (всегда первое) -->
            <div class="relative bg-gray-800 rounded-3xl overflow-hidden border border-gray-700 shadow-2xl">
                <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                <div class="absolute bottom-4 left-4 bg-black/50 backdrop-blur-md text-white px-3 py-1 text-xs rounded-full">Вы</div>
            </div>
        </div>

        <!-- Панель управления -->
        <div class="fixed bottom-10 left-1/2 -translate-x-1/2 bg-gray-800/80 backdrop-blur-xl px-8 py-4 rounded-full border border-white/10 flex gap-6 items-center shadow-2xl">
            <button id="toggleMic" class="text-white text-xl p-2 hover:bg-white/10 rounded-full transition">🎤</button>
            <button id="toggleCam" class="text-white text-xl p-2 hover:bg-white/10 rounded-full transition">📷</button>
            <div class="w-px h-8 bg-white/10"></div>
            <a href="{{ route('rooms.index') }}" class="bg-red-600 text-white px-6 py-2 rounded-full font-bold hover:bg-red-700 transition">
                Выйти
            </a>
        </div>
    </div>

<script type="module">
    const roomUuid = "{{ $room->uuid }}";
    const currentUserId = {{ auth()->id() }};
    window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')) };
    
    let localStream = null;
    let peers = {}; // Здесь храним { userId: { pc: RTCPeerConnection, queue: [] } }

    const videoGrid = document.getElementById('videoGrid');

    // 1. Санитайзер для SDP (Критически важно для фикса ошибки парсинга)
    function sanitizeSdp(sdp) {
        return sdp.trim().split('\n').map(line => line.trim()).join('\r\n') + '\r\n';
    }

    async function init() {
        console.log("Инициализация комнаты...");
        // Получаем доступ к камере
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            document.getElementById('localVideo').srcObject = localStream;
        } catch (e) {
            alert("Нет доступа к камере!");
            return;
        }

        // Подключаемся к комнате
        const channel = window.Echo.join(`room.${roomUuid}`);

        channel.here((users) => {
            console.log("В комнате уже есть:", users);
            users.forEach(user => {
                if (user.id !== currentUserId) {
                    // Я зашел - я инициатор для всех, кто уже ТАМ
                    initiatePeerConnection(user.id, true);
                }
            });
        })
        .joining((user) => {
            console.log("Зашел новый участник:", user.name);
            // Для новичка мы ничего не делаем, он сам нас вызовет
        })
        .leaving((user) => {
            console.log("Участник вышел:", user.name);
            removePeer(user.id);
        });

        // Слушаем сигналы
        window.Echo.private(`user.${currentUserId}`)
            .listen('.WebRTCSignalEvent', async (e) => {
                if (e.data.roomUuid !== roomUuid) return;
                await handleSignal(e.data);
            });
    }

    // Создание соединения с конкретным пользователем
    function initiatePeerConnection(userId, isInitiator) {
        if (peers[userId]) return;

        console.log(`Создаем соединение с ${userId}, Инициатор: ${isInitiator}`);
        const pc = new RTCPeerConnection(window.rtcConfig);
        
        // Храним и соединение, и очередь кандидатов именно для этого юзера
        peers[userId] = {
            pc: pc,
            queue: [] 
        };

        localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

        pc.onicecandidate = (event) => {
            if (event.candidate) {
                sendSignal(userId, { type: 'ice', candidate: event.candidate });
            }
        };

        pc.ontrack = (event) => {
            console.log(`Получено видео от ${userId}`);
            addVideoElement(userId, event.streams[0]);
        };

        // Если я инициатор - создаю Оффер
        if (isInitiator) {
            pc.onnegotiationneeded = async () => {
                try {
                    const offer = await pc.createOffer();
                    await pc.setLocalDescription(offer);
                    sendSignal(userId, { type: 'offer', sdp: offer });
                } catch (e) { console.error(e); }
            };
        }
    }

    async function handleSignal(data) {
        const fromId = data.from;
        
        // Если про этого юзера еще не знаем - создаем "принимающую" сторону
        if (!peers[fromId]) initiatePeerConnection(fromId, false);
        
        const peer = peers[fromId];
        const pc = peer.pc;

        try {
            if (data.type === 'offer') {
                console.log(`Получен Оффер от ${fromId}`);
                await pc.setRemoteDescription({
                    type: data.sdp.type,
                    sdp: sanitizeSdp(data.sdp.sdp)
                });
                
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                sendSignal(fromId, { type: 'answer', sdp: answer });
                
                // Промываем очередь кандидатов этого юзера
                drainQueue(fromId);
            } 
            else if (data.type === 'answer') {
                console.log(`Получен Ответ от ${fromId}`);
                await pc.setRemoteDescription({
                    type: data.sdp.type,
                    sdp: sanitizeSdp(data.sdp.sdp)
                });
                drainQueue(fromId);
            } 
            else if (data.type === 'ice') {
                if (pc.remoteDescription && pc.remoteDescription.type) {
                    await pc.addIceCandidate(new RTCIceCandidate(data.candidate));
                } else {
                    peer.queue.push(data.candidate); // В очередь конкретного юзера
                }
            }
        } catch (e) {
            console.error(`Ошибка связи с ${fromId}:`, e);
        }
    }

    function drainQueue(userId) {
        const peer = peers[userId];
        while (peer.queue.length > 0) {
            const candidate = peer.queue.shift();
            peer.pc.addIceCandidate(new RTCIceCandidate(candidate)).catch(e => {});
        }
    }

    function sendSignal(toId, payload) {
        window.axios.post('/chat/signal', {
            partnerId: toId,
            data: { ...payload, from: currentUserId, roomUuid: roomUuid }
        });
    }

    function addVideoElement(userId, stream) {
        if (document.getElementById(`video-${userId}`)) return;
        const container = document.createElement('div');
        container.id = `video-${userId}`;
        container.className = "relative bg-gray-800 rounded-3xl overflow-hidden border border-gray-700 shadow-2xl h-full";
        container.innerHTML = `
            <video autoplay playsinline class="w-full h-full object-cover"></video>
            <div class="absolute bottom-4 left-4 bg-black/50 backdrop-blur-md text-white px-3 py-1 text-xs rounded-full">Участник ${userId}</div>
        `;
        container.querySelector('video').srcObject = stream;
        videoGrid.appendChild(container);
    }

    function removePeer(userId) {
        if (peers[userId]) {
            peers[userId].pc.close();
            delete peers[userId];
        }
        const el = document.getElementById(`video-${userId}`);
        if (el) el.remove();
    }

    // Управление кнопками
    document.getElementById('toggleMic').onclick = () => {
        const audioTrack = localStream.getAudioTracks()[0];
        audioTrack.enabled = !audioTrack.enabled;
        document.getElementById('toggleMic').innerText = audioTrack.enabled ? '🎤' : '🔇';
    };
    document.getElementById('toggleCam').onclick = () => {
        const videoTrack = localStream.getVideoTracks()[0];
        videoTrack.enabled = !videoTrack.enabled;
        document.getElementById('toggleCam').innerText = videoTrack.enabled ? '📷' : '🚫';
    };

    // Запуск
    init();
</script>
</x-app-layout>