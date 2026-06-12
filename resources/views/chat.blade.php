<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 bg-black rounded-lg h-96 flex items-center justify-center relative">
                    <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                    <div id="statusOverlay" class="absolute text-white">Камера активна</div>
                </div>

                <div class="bg-white p-6 rounded shadow">
                    <button id="startSearch" class="w-full bg-blue-500 text-white py-2 rounded">Начать поиск</button>
                    <div id="connectionStatus" class="mt-4 font-bold">Ожидание...</div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        // Запуск камеры
        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(s => document.getElementById('localVideo').srcObject = s);

        console.log("Скрипт загружен!");
        document.getElementById('startSearch').addEventListener('click', () => {
        console.log("Кнопка нажата!");
        });

        document.getElementById('startSearch').addEventListener('click', async () => {
            document.getElementById('connectionStatus').innerText = "Ищем пару...";
            
            try {
                const res = await fetch('/chat/search', {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json' 
                    }
                });
                const data = await res.json();
                console.log("Ответ от сервера:", data);
            } catch (err) {
                console.error("Ошибка запроса:", err);
            }
        });

        // Слушатель событий Reverb
        window.Echo.private('user.{{ auth()->id() }}')
            .listen('MatchFoundEvent', (e) => {
                console.log("🔥 Мэтч получен:", e);
                alert("Собеседник найден! Партнер ID: " + e.partnerId);
            });
    </script>
    @endpush
</x-app-layout>