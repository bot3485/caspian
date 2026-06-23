<x-app-layout>
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Уведомления об ошибках (например, неверный пароль) -->
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-2xl font-bold text-sm shadow-sm border border-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex justify-between items-center mb-10">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Активные комнаты</h1>
                <button onclick="document.getElementById('createRoomModal').showModal()" 
                        class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                    + Создать комнату
                </button>
            </div>

            <!-- Сетка комнат -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($rooms as $room)
                    <div class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-6">
                                <span class="text-3xl p-3 bg-indigo-50 rounded-2xl">🏠</span>
                                @if($room->password)
                                    <span class="bg-amber-100 text-amber-700 text-[10px] font-black px-2.5 py-1 rounded-lg uppercase tracking-wider">🔐 Пароль</span>
                                @else
                                    <span class="bg-green-100 text-green-700 text-[10px] font-black px-2.5 py-1 rounded-lg uppercase tracking-wider">🌍 Public</span>
                                @endif
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">{{ $room->title }}</h3>
                            <p class="text-xs text-gray-400 mb-8 font-medium">Организатор: {{ $room->creator->name }}</p>
                        </div>
                        
                        <!-- ОДНА КНОПКА: Ссылка ведет в контроллер, который сам проверит пароль -->
                        <a href="{{ route('rooms.show', $room->uuid) }}" 
                           class="block w-full text-center bg-gray-900 text-white py-4 rounded-2xl font-bold hover:bg-black transition-all active:scale-95">
                            Войти в конференцию
                        </a>
                    </div>
                @empty
                    <div class="col-span-full text-center py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-200">
                        <p class="text-gray-400 font-bold">Активных комнат пока нет...</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Модалка создания комнаты (только одна) -->
    <dialog id="createRoomModal" class="rounded-[2rem] p-0 backdrop:bg-gray-900/60 border-none shadow-2xl overflow-hidden">
        <div class="w-[400px] p-10 bg-white">
            <div class="mb-8">
                <h2 class="text-2xl font-black text-gray-800">Создать комнату</h2>
                <p class="text-sm text-gray-400">Настройте параметры вашей встречи</p>
            </div>

            <form id="createRoomForm" class="space-y-5">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Название комнаты</label>
                    <input type="text" name="title" placeholder="Например: Пятничный созвон" required 
                           class="w-full bg-gray-50 border-none rounded-2xl py-4 px-5 focus:ring-2 focus:ring-indigo-500 text-sm font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Пароль (необязательно)</label>
                    <input type="password" name="password" placeholder="Минимум 4 символа" 
                           class="w-full bg-gray-50 border-none rounded-2xl py-4 px-5 focus:ring-2 focus:ring-indigo-500 text-sm font-bold">
                </div>

                <div class="flex items-center gap-3 p-1">
                    <input type="checkbox" name="is_public" value="1" checked id="check_public" 
                           class="w-5 h-5 rounded-lg border-gray-200 text-indigo-600 focus:ring-indigo-500">
                    <label for="check_public" class="text-sm font-bold text-gray-600 cursor-pointer">Показывать в общем списке</label>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="this.closest('dialog').close()" 
                            class="flex-1 py-4 text-sm font-black text-gray-400 hover:text-gray-600 transition uppercase tracking-widest">Отмена</button>
                    <button type="submit" id="submitCreateBtn" 
                            class="flex-1 bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition active:scale-95 disabled:opacity-50">
                        Создать
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script type="module">
        // Логика создания комнаты
        document.getElementById('createRoomForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitCreateBtn');
            const formData = new FormData(e.target);
            
            const data = {
                title: formData.get('title'),
                password: formData.get('password'),
                is_public: formData.get('is_public') ? 1 : 0
            };

            btn.disabled = true;
            btn.innerText = "Создаем...";

            try {
                const res = await window.axios.post('/rooms', data);
                window.location.href = res.data.redirect;
            } catch (err) {
                btn.disabled = false;
                btn.innerText = "Создать";
                alert(err.response?.data?.message || "Ошибка при создании комнаты");
            }
        };
    </script>
</x-app-layout>