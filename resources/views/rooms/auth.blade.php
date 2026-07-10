<x-app-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-900 px-4">
        <div class="max-w-md w-full bg-white rounded-3xl p-10 shadow-2xl">
            <div class="text-center mb-8">
                <div class="text-4xl mb-4">🔐</div>
                <h1 class="text-2xl font-black text-gray-800">Приватная комната</h1>
                <p class="text-sm text-gray-400 mt-2">Чтобы войти в <b>{{ $room->title }}</b>, введите пароль, установленный автором.</p>
            </div>

            <form id="authRoomForm" class="space-y-4">
                <input type="password" id="roomPass" placeholder="Пароль доступа" required 
                       class="w-full bg-gray-50 border-none rounded-2xl py-4 focus:ring-2 focus:ring-indigo-500">
                
                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                    Присоединиться к общению
                </button>
                
                <a href="{{ route('rooms.index') }}" class="block text-center text-xs font-bold text-gray-400 hover:text-gray-600 uppercase tracking-widest mt-4">
                    Вернуться к списку
                </a>
            </form>
        </div>
    </div>

    <script type="module">
        document.getElementById('authRoomForm').onsubmit = async (e) => {
            e.preventDefault();
            const password = document.getElementById('roomPass').value;
            try {
                await window.axios.post("{{ route('rooms.join', $room->uuid) }}", { password });
                window.location.reload(); // Просто перезагружаем, теперь сессия есть и контроллер пустит
            } catch (err) {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { msg: err.response?.data?.message || 'Неверный пароль', type: 'error' } 
                }));
            }
        }
    </script>
</x-app-layout>