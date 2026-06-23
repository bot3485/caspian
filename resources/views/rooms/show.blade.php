<x-app-layout>
    <div class="bg-gray-950 min-h-screen flex flex-col overflow-hidden text-white font-sans">
        
        <!-- ВЕРХНЯЯ ПАНЕЛЬ -->
        <div class="p-4 flex justify-between items-center bg-black/40 backdrop-blur-xl border-b border-white/5 z-20">
            <div class="flex items-center gap-4">
                <div class="bg-indigo-600 p-2 rounded-xl shadow-lg text-sm">🏠</div>
                <div>
                    <h1 class="text-xs font-black uppercase tracking-widest text-white">{{ $room->title }}</h1>
                    <p id="participantCount" class="text-[10px] font-bold text-indigo-400 font-mono">Подключение...</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="copyInviteLink()" class="bg-white/5 hover:bg-white/10 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase border border-white/10 transition-all">
                    🔗 Копировать ссылку
                </button>
            </div>
        </div>

        <!-- СЕТКА ВИДЕО (MESH GRID) -->
        <div id="videoGrid" class="flex-1 p-4 grid gap-4 items-center justify-center auto-rows-fr overflow-y-auto"
             style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            
            <!-- МОЕ ВИДЕО (Всегда первое) -->
            <div id="video-container-me" class="video-card group relative aspect-video bg-gray-900 rounded-[2rem] overflow-hidden border border-white/10 shadow-2xl transition-all duration-500">
                <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                <div class="absolute bottom-5 left-5 flex items-center gap-3 bg-black/60 backdrop-blur-xl px-4 py-1.5 rounded-2xl border border-white/10">
                    <span class="text-[10px] font-black uppercase tracking-tighter">{{ auth()->user()->name }} (Вы)</span>
                    <div class="flex gap-2 text-[10px]">
                        <span id="myMicOff" class="hidden">🔇</span>
                        <span id="myCamOff" class="hidden">🚫</span>
                    </div>
                </div>
                <button onclick="toggleExpand('video-container-me')" class="absolute top-5 right-5 opacity-0 group-hover:opacity-100 bg-white/10 hover:bg-white/20 p-2.5 rounded-xl transition-all border border-white/10">↔️</button>
            </div>
        </div>

        <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
        <div class="p-6 bg-black/60 backdrop-blur-3xl border-t border-white/5 flex justify-center items-center gap-8 z-20">
            <button id="toggleMic" class="w-14 h-14 rounded-2xl bg-gray-800 hover:bg-indigo-600 flex items-center justify-center text-xl transition-all border border-white/5 active:scale-90">🎤</button>
            <button id="toggleCam" class="w-14 h-14 rounded-2xl bg-gray-800 hover:bg-indigo-600 flex items-center justify-center text-xl transition-all border border-white/5 active:scale-90">📷</button>
            
            <div class="h-10 w-px bg-white/10"></div>
            
            <a href="{{ route('rooms.index') }}" class="px-8 py-3 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg transition-all active:scale-95">
                Выйти
            </a>
        </div>
    </div>

    <style>
        .expanded {
            grid-column: span 2 !important;
            grid-row: span 2 !important;
            z-index: 10;
            border-color: rgba(99, 102, 241, 0.6) !important;
        }
        .video-card { min-width: 250px; transition: all 0.4s ease; }
        video { background: #000; }
    </style>

    <script type="module">
        const roomUuid = "{{ $room->uuid }}";
        const currentUserId = {{ auth()->id() }};
        window.rtcConfig = { iceServers: @json(config('webrtc.ice_servers')) };
        
        let localStream = null;
        let peers = {}; // { userId: { pc, queue, name } }
        let participantsData = {}; // { userId: name }
        let expandedId = null;

        const videoGrid = document.getElementById('videoGrid');

        // --- 1. ИНИЦИАЛИЗАЦИЯ ---
        async function init() {
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('localVideo').srcObject = localStream;
            } catch (e) { alert("Камера недоступна"); return; }

            const channel = window.Echo.join(`room.${roomUuid}`);

            channel.here((users) => {
                users.forEach(u => {
                    participantsData[u.id] = u.name;
                    if (u.id !== currentUserId) {
                        // Я зашел - я вызываю всех, кто уже тут
                        initiatePeerConnection(u.id, u.name, true);
                    }
                });
                updateCount(users.length);
            })
            .joining((u) => {
                participantsData[u.id] = u.name;
                updateCount(Object.keys(participantsData).length);
                // Мы не вызываем его, ждем когда он вызовет нас
            })
            .leaving((u) => {
                delete participantsData[u.id];
                updateCount(Object.keys(participantsData).length);
                removePeer(u.id);
            });

            window.Echo.private(`user.${currentUserId}`).listen('.WebRTCSignalEvent', async (e) => {
                if (e.data.roomUuid === roomUuid) await handleSignal(e.data);
            });
        }

        // --- 2. WebRTC ЯДРО ---
        function initiatePeerConnection(userId, userName, isInitiator) {
            if (peers[userId]) return;

            const pc = new RTCPeerConnection(window.rtcConfig);
            peers[userId] = { pc, queue: [], name: userName };

            localStream.getTracks().forEach(t => pc.addTrack(t, localStream));

            pc.onicecandidate = (e) => {
                if (e.candidate) sendSignal(userId, { type: 'ice', candidate: e.candidate });
            };

            pc.ontrack = (e) => {
                addVideoElement(userId, e.streams[0], userName);
            };

            if (isInitiator) {
                pc.onnegotiationneeded = async () => {
                    try {
                        const offer = await pc.createOffer();
                        await pc.setLocalDescription(offer);
                        sendSignal(userId, { type: 'offer', sdp: offer });
                    } catch (err) { console.error(err); }
                };
            }
        }

        async function handleSignal(data) {
            const fromId = data.from;
            const fromName = participantsData[fromId] || `User ${fromId}`;
            
            if (!peers[fromId]) initiatePeerConnection(fromId, fromName, false);
            const peer = peers[fromId];

            try {
                if (data.type === 'offer') {
                    await peer.pc.setRemoteDescription(new RTCSessionDescription({
                        type: data.sdp.type,
                        sdp: sanitizeSdp(data.sdp.sdp)
                    }));
                    const answer = await peer.pc.createAnswer();
                    await peer.pc.setLocalDescription(answer);
                    sendSignal(fromId, { type: 'answer', sdp: answer });
                    drainQueue(fromId);
                } 
                else if (data.type === 'answer') {
                    await peer.pc.setRemoteDescription(new RTCSessionDescription({
                        type: data.sdp.type,
                        sdp: sanitizeSdp(data.sdp.sdp)
                    }));
                    drainQueue(fromId);
                } 
                else if (data.type === 'ice') {
                    if (peer.pc.remoteDescription) peer.pc.addIceCandidate(new RTCIceCandidate(data.candidate));
                    else peer.queue.push(data.candidate);
                }
                else if (data.type === 'media-status') {
                    updateStatusIcon(fromId, data.mediaType, data.enabled);
                }
            } catch (e) { console.warn("Signal error", e); }
        }

        // --- 3. ИНТЕРФЕЙС (ИМЕНА И СЕТКА) ---
        function addVideoElement(userId, stream, userName) {
            if (document.getElementById(`video-container-${userId}`)) return;
            
            const div = document.createElement('div');
            div.id = `video-container-${userId}`;
            div.className = "video-card group relative aspect-video bg-gray-900 rounded-[2rem] overflow-hidden border border-white/5 shadow-2xl transition-all duration-500";
            div.innerHTML = `
                <video autoplay playsinline class="w-full h-full object-cover"></video>
                <div class="absolute bottom-5 left-5 flex items-center gap-3 bg-black/60 backdrop-blur-xl px-4 py-1.5 rounded-2xl border border-white/10">
                    <span class="text-[10px] font-black uppercase tracking-tighter">${userName}</span>
                    <div class="flex gap-2">
                        <span id="mic-off-${userId}" class="hidden">🔇</span>
                        <span id="cam-off-${userId}" class="hidden">🚫</span>
                    </div>
                </div>
                <button onclick="toggleExpand('video-container-${userId}')" class="absolute top-5 right-5 opacity-0 group-hover:opacity-100 bg-white/10 hover:bg-white/20 p-3 rounded-2xl backdrop-blur-md transition-all">↔️</button>
            `;
            div.querySelector('video').srcObject = stream;
            videoGrid.appendChild(div);
            recalculateGrid();
        }

        window.toggleExpand = (id) => {
            const el = document.getElementById(id);
            if (expandedId === id) {
                el.classList.remove('expanded');
                expandedId = null;
            } else {
                if (expandedId) document.getElementById(expandedId).classList.remove('expanded');
                el.classList.add('expanded');
                expandedId = id;
            }
        };

        function recalculateGrid() {
            const count = videoGrid.children.length;
            // Уменьшаем минимальный размер плитки, если людей много
            if (count > 4) {
                videoGrid.style.gridTemplateColumns = "repeat(auto-fit, minmax(280px, 1fr))";
            } else {
                videoGrid.style.gridTemplateColumns = "repeat(auto-fit, minmax(350px, 1fr))";
            }
        }

        // --- УТИЛИТЫ ---
        function sanitizeSdp(sdp) {
            return sdp.trim().split('\n').map(line => line.trim()).join('\r\n') + '\r\n';
        }
        function sendSignal(toId, payload) {
            window.axios.post('/chat/signal', { partnerId: toId, data: { ...payload, from: currentUserId, roomUuid: roomUuid } });
        }
        function drainQueue(userId) {
            const p = peers[userId];
            while (p.queue.length > 0) p.pc.addIceCandidate(new RTCIceCandidate(p.queue.shift())).catch(e=>{});
        }
        function removePeer(id) {
            if (peers[id]) { peers[id].pc.close(); delete peers[id]; }
            const el = document.getElementById(`video-container-${id}`);
            if (el) el.remove();
            recalculateGrid();
        }
        function updateCount(count) {
            document.getElementById('participantCount').innerText = `${count} участников в сети`;
        }
        function updateStatusIcon(userId, type, enabled) {
            const icon = document.getElementById(`${type === 'video' ? 'cam' : 'mic'}-off-${userId}`);
            if (icon) icon.classList.toggle('hidden', enabled);
        }

        // Управление кнопками Mute/Cam
        document.getElementById('toggleMic').onclick = () => {
            const t = localStream.getAudioTracks()[0];
            t.enabled = !t.enabled;
            document.getElementById('toggleMic').innerText = t.enabled ? '🎤' : '🔇';
            document.getElementById('myMicOff').classList.toggle('hidden', t.enabled);
            Object.keys(peers).forEach(id => sendSignal(id, { type: 'media-status', mediaType: 'audio', enabled: t.enabled }));
        };
        document.getElementById('toggleCam').onclick = () => {
            const t = localStream.getVideoTracks()[0];
            t.enabled = !t.enabled;
            document.getElementById('toggleCam').innerText = t.enabled ? '📷' : '🚫';
            document.getElementById('myCamOff').classList.toggle('hidden', t.enabled);
            Object.keys(peers).forEach(id => sendSignal(id, { type: 'media-status', mediaType: 'video', enabled: t.enabled }));
        };

        window.copyInviteLink = () => {
            navigator.clipboard.writeText(window.location.href);
            alert("Ссылка скопирована!");
        }

        init();
    </script>
</x-app-layout>