<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 bg-black rounded-lg overflow-hidden h-96 flex items-center justify-center relative">
                    <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                    <div id="statusOverlay" class="absolute text-white font-bold text-xl">Камера активна</div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
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
        // 1. Инициализация камеры пользователя
        const localVideo = document.getElementById('localVideo');
        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(stream => { 
                localVideo.srcObject = stream; 
            })
            .catch(err => {
                console.error("Ошибка доступа к камере/микрофону:", err);
            });

        // 2. Логика работы кнопки поиска пары
        document.getElementById('startSearch').addEventListener('click', async (e) => {
            e.preventDefault(); // Защита от случайной отправки форм или редиректов
            
            const statusEl = document.getElementById('connectionStatus');
            statusEl.innerText = "Ищем пару...";

            try {
                const response = await window.axios.post('/chat/search');
                console.log("Ответ бэкенда:", response.data);
                statusEl.innerText = "Статус: " + response.data.status;
            } catch (error) {
                console.error("Ошибка при поиске:", error.response ? error.response.data : error.message);
                statusEl.innerText = "Ошибка запроса";
            }
        });

        // 3. Подписка на приватный WebSocket-канал через Laravel Echo
        window.addEventListener('load', () => {
            if (typeof window.Echo !== 'undefined') {
                console.log("Служба Echo успешно найдена, подключаемся к каналу...");
                
                // Включаем детальный дебаг в консоли браузера
                window.Pusher.logToConsole = true;

                // Подписываемся на приватный канал текущего юзера
                window.Echo.private('user.{{ auth()->id() }}')
                    // Перехват события с полным namespace (для Pusher вещания)
                    .listen('App\\Events\\MatchFoundEvent', (e) => {
                        console.log("🔥 Мэтч пойман (Полный класс):", e);
                        handleMatchFound(e);
                    })
                    // Перехват события без префикса (на случай урезания со стороны Echo)
                    .listen('MatchFoundEvent', (e) => {
                        console.log("🔥 Мэтч пойман (Короткий класс):", e);
                        handleMatchFound(e);
                    });
            } else {
                console.error("Критическая ошибка: объект window.Echo не инициализирован.");
            }
        });

        // Функция обработки успешного мэтча
        function handleMatchFound(data) {
            alert("Собеседник найден! Партнер ID: " + data.partnerId);
            document.getElementById('connectionStatus').innerText = "Собеседник найден! ID: " + data.partnerId;
            
            // Здесь дальше пойдет твоя логика инициализации WebRTC (Offer/Answer)
        }
    </script>
</x-app-layout>