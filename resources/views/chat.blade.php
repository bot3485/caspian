<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-black rounded-lg overflow-hidden h-72 flex items-center justify-center relative">
                        <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                        <div class="absolute bottom-2 left-2 bg-black bg-opacity-50 text-white px-2 py-1 text-xs rounded">Вы</div>
                    </div>
                    
                    <div class="bg-black rounded-lg overflow-hidden h-72 flex items-center justify-center relative">
                        <video id="remoteVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                        <div id="remoteStatus" class="absolute text-gray-400 font-bold text-sm">Собеседник отключен</div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow h-fit">
                    <h2 class="text-xl font-bold mb-4">Управление чатом</h2>
                    <button id="startSearch" class="w-full bg-blue-500 text-white py-2 rounded mb-2 hover:bg-blue-600 transition">
                        Начать поиск
                    </button>
                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold text-gray-700">
                            Статус: <span id="connectionStatus" class="text-gray-500">Ожидание</span>
                        </h3>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script type="module">
        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');
        const connectionStatus = document.getElementById('connectionStatus');
        const remoteStatus = document.getElementById('remoteStatus');
        const startSearchBtn = document.getElementById('startSearch');

        const currentUserId = {{ auth()->id() }};
        let localStream = null;
        let peerConnection = null;
        let globalPartnerId = null;

        // Конфигурация STUN серверов Google для пробития NAT
        const rtcConfig = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        // 1. Включение камеры при загрузке страницы
        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(stream => { 
                localStream = stream;
                localVideo.srcObject = stream; 
                console.log("Локальная камера и микрофон успешно подключены.");
            })
            .catch(err => {
                console.error("❌ Ошибка доступа к камере:", err);
                connectionStatus.innerText = "Ошибка доступа к камере";
            });

        // 2. Клик по кнопке "Начать поиск"
        startSearchBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            console.log("Нажата кнопка поиска. Сбрасываем старые сессии...");
            connectionStatus.className = "text-blue-500 font-medium";
            connectionStatus.innerText = "В очереди поиска...";
            
            closePeerConnection();

            try {
                const response = await window.axios.post('/chat/search');
                console.log("Бэкенд принял запрос на поиск. Текущий статус:", response.data.status);
            } catch (error) {
                console.error("❌ Ошибка API поиска:", error);
                connectionStatus.innerText = "Ошибка запуска поиска";
            }
        });

        // 3. Подписка на сокеты Echo и обработка входящих событий
       window.addEventListener('load', () => {
            if (typeof window.Echo !== 'undefined') {
                
                //window.Pusher.logToConsole = true;
                console.log("Служба Echo инициализирована. Ожидаем события...");

                window.Echo.private(`user.${currentUserId}`)
                    // ДОБАВЛЯЕМ ТОЧКУ ПЕРЕД ИМЕНЕМ СОБЫТИЯ:
                    .listen('.MatchFoundEvent', (e) => {
                        console.log("🔥 Событие из сокета: MatchFoundEvent успешно поймано с точкой!", e);
                        handleMatchFound(e.partnerId);
                    })
                    // На всякий случай оставляем и без точки
                    .listen('MatchFoundEvent', (e) => {
                        console.log("🔥 Событие из сокета: MatchFoundEvent поймано без точки!", e);
                        handleMatchFound(e.partnerId);
                    })
                    // Сигналы WebRTC (уже с точкой, тут всё ок)
                    .listen('.WebRTCSignalEvent', async (e) => {
                        console.log(`📡 Получен входящий WebRTC сигнал [${e.data.type}] от партнера:`, e.data);
                        await handleSignalingMessage(e.data);
                    });
            } else {
                console.error("❌ Критическая ошибка: Laravel Echo не подключен.");
            }
        });

        // 4. Мэтч сработал — подготавливаем роли
        function handleMatchFound(partnerId) {
            const myId = Number(currentUserId);
            const peerId = Number(partnerId);
            globalPartnerId = peerId;

            console.log(`[Мэтчинг] Мой ID = ${myId}, ID Собеседника = ${peerId}`);
            
            connectionStatus.className = "text-green-500 font-bold";
            connectionStatus.innerText = "Пара найдена! Настройка связи...";
            remoteStatus.innerText = "Установка WebRTC соединения...";

            createPeerConnection();

            // Автоматическое распределение ролей: Инициатор тот, у кого ID больше
            if (myId > peerId) {
                console.log(`Роль: Инициатор (Caller), так как ${myId} > ${peerId}. Генерируем Offer...`);
                sendOffer();
            } else {
                console.log(`Роль: Получатель (Receiver), так как ${myId} < ${peerId}. Ожидаем Offer по WebSocket...`);
            }
        }

        // 5. Инициализация объекта RTCPeerConnection
        function createPeerConnection() {
            console.log("Создание объекта RTCPeerConnection с серверами STUN...");
            peerConnection = new RTCPeerConnection(rtcConfig);

            // Добавляем наши медиа-треки в соединение
            if (localStream) {
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                    console.log(`-> Локальный трек [${track.kind}] добавлен в PeerConnection.`);
                });
            }

            // Когда к нам пробивается медиа-поток собеседника — привязываем к тегу видео
            peerConnection.ontrack = (event) => {
                console.log("🎉 Успех! Получен удаленный видео/аудио поток от собеседника.");
                remoteVideo.srcObject = event.streams[0];
                remoteStatus.innerText = ""; 
            };

            // Когда наш браузер находит свой сетевой адрес (ICE) — шлем его через бэкенд партнеру
            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    console.log("⚡ Сгенерирован локальный ICE-candidate:", event.candidate.candidate);
                    sendSignalMessage({
                        type: 'ice-candidate',
                        candidate: event.candidate
                    });
                }
            };
        }

        // 6. Функция отправки WebRTC пакетов на бэкенд роут `/chat/signal`
        async function sendSignalMessage(payload) {
            console.log(`📤 Отправка Axios-сигнала [${payload.type}] для партнера ID: ${globalPartnerId}`);
            try {
                await window.axios.post('/chat/signal', {
                    partnerId: globalPartnerId,
                    data: payload
                });
            } catch (error) {
                console.error("❌ Ошибка отправки сигнала через Axios:", error);
            }
        }

       // 7. Генерация и отправка локального Offer (SDP)
        async function sendOffer() {
            try {
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);
                console.log("Локальный LocalDescription (Offer) успешно установлен.");
                
                // Передаем SDP в виде плоского объекта БЕЗ вложенностей
                sendSignalMessage({
                    type: 'webrtc-offer',
                    sdpType: offer.type,
                    sdpString: offer.sdp
                });
            } catch (err) {
                console.error("❌ Ошибка при создании Offer:", err);
            }
        }

        // 8. Обработка входящих сокетных сигналов от партнера
        async function handleSignalingMessage(message) {
            if (!peerConnection) {
                console.log("Объект PeerConnection отсутствовал при сигнале, создаем принудительно...");
                createPeerConnection();
            }

            try {
                if (message.type === 'webrtc-offer') {
                    console.log("Применяем входящий RemoteDescription (Offer)...");
                    
                    // ХАК-САНИТАЙЗЕР: Убираем возможные двойные переносы, выравниваем \r\n и жестко добавляем перенос в финал строки
                    let sanitizedSDP = message.sdpString
                        .replace(/\r\n\r\n/g, '\r\n') 
                        .trim() + '\r\n';

                    const description = new RTCSessionDescription({
                        type: message.sdpType,
                        sdp: sanitizedSDP
                    });
                    
                    await peerConnection.setRemoteDescription(description);
                    console.log("RemoteDescription (Offer) успешно применен.");
                    
                    console.log("Генерируем Answer на полученный Offer...");
                    const answer = await peerConnection.createAnswer();
                    await peerConnection.setLocalDescription(answer);
                    
                    console.log("Отправляем сгенерированный Answer партнеру...");
                    sendSignalMessage({
                        type: 'webrtc-answer',
                        sdpType: answer.type,
                        sdpString: answer.sdp
                    });
                    
                } else if (message.type === 'webrtc-answer') {
                    console.log("Применяем входящий RemoteDescription (Answer)...");
                    
                    // Санитизируем и Answer на стороне Инициатора
                    let sanitizedSDP = message.sdpString
                        .replace(/\r\n\r\n/g, '\r\n')
                        .trim() + '\r\n';

                    const description = new RTCSessionDescription({
                        type: message.sdpType,
                        sdp: sanitizedSDP
                    });
                    
                    await peerConnection.setRemoteDescription(description);
                    console.log("✅ Успех! Базовый SDP-обмен завершен. Каналы пробиваются...");
                    
                } else if (message.type === 'ice-candidate') {
                    if (peerConnection.remoteDescription && peerConnection.remoteDescription.type) {
                        console.log("Добавляем входящий ICE-candidate...");
                        await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                    } else {
                        console.log("⏳ RemoteDescription еще не применен. Откладываем ICE-candidate...");
                        
                        setTimeout(async () => {
                            if (peerConnection.remoteDescription && peerConnection.remoteDescription.type) {
                                try {
                                    await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                                    console.log("🔥 Отложенный ICE-candidate успешно добавлен.");
                                } catch (e) {
                                    console.error("Ошибки добавления отложенного ICE", e);
                                }
                            }
                        }, 1200);
                    }
                }
            } catch (err) {
                console.error("❌ Ошибка обработки сигнального WebRTC сообщения:", err);
            }
        }

        // Сброс стрима при закрытии
        function closePeerConnection() {
            if (peerConnection) {
                console.log("Закрываем текущий PeerConnection...");
                peerConnection.close();
                peerConnection = null;
            }
            remoteVideo.srcObject = null;
            remoteStatus.innerText = "Собеседник отключен";
        }
    </script>
</x-app-layout>