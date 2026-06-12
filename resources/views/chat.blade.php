<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 bg-black rounded-lg overflow-hidden h-96 flex items-center justify-center relative">
                    <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                    <div id="statusOverlay" class="absolute text-white font-bold text-xl">Нажмите "Начать поиск"</div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-bold mb-4">Управление</h2>
                    <button id="startSearch" class="w-full bg-blue-500 text-white py-2 rounded mb-2 hover:bg-blue-600">Начать поиск</button>
                    <button id="stopSearch" class="w-full bg-red-500 text-white py-2 rounded hidden hover:bg-red-600">Стоп</button>
                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold text-gray-700">Статус: <span id="connectionStatus" class="text-gray-500">Ожидание</span></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        // Запрос камеры при загрузке
        const localVideo = document.getElementById('localVideo');
        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(stream => { localVideo.srcObject = stream; })
            .catch(err => console.error("Ошибка камеры:", err));

        // Логика поиска
        document.getElementById('startSearch').addEventListener('click', async () => {
            document.getElementById('connectionStatus').innerText = "Поиск...";
            document.getElementById('startSearch').classList.add('hidden');
            document.getElementById('stopSearch').classList.remove('hidden');

            const response = await fetch('/chat/search', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json' 
                }
            });
            const data = await response.json();
            console.log("Статус поиска:", data);
        });

        // Слушаем сигнал о найденной паре
        window.Echo.private('user.{{ auth()->id() }}')
            .listen('MatchFoundEvent', (e) => {
                document.getElementById('connectionStatus').innerText = "Собеседник найден! ID: " + e.partnerId;
                alert("Пара найдена! Готовься к связи.");
                // Здесь позже мы запустим WebRTC
            });
    </script>
    @endpush
</x-app-layout>