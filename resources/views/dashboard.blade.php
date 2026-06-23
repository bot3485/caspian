<x-app-layout>
    <div class="py-10 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Приветствие -->
            <div class="mb-10">
                <h1 class="text-4xl font-black text-gray-900 tracking-tight">
                    Привет, {{ Auth::user()->name }}! 👋
                </h1>
                <p class="text-gray-500 font-medium">Рады видеть тебя снова. Выбери способ общения на сегодня.</p>
            </div>

            <!-- Сетка основных разделов -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- КАРТОЧКА: ВИДЕОРУЛЕТКА -->
                <a href="{{ route('chat') }}" class="group relative bg-indigo-600 rounded-[2.5rem] p-8 overflow-hidden shadow-2xl shadow-indigo-200 hover:-translate-y-2 transition-all duration-300">
                    <div class="relative z-10 h-full flex flex-col justify-between">
                        <div>
                            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center text-3xl mb-6 backdrop-blur-md">🎲</div>
                            <h2 class="text-2xl font-black text-white leading-tight">Видео<br>Рулетка</h2>
                            <p class="text-indigo-100 text-sm mt-2 opacity-80 font-medium">Случайные встречи 1 на 1</p>
                        </div>
                        <div class="mt-8 flex items-center text-white font-bold text-sm">
                            Начать поиск <span class="ml-2 group-hover:translate-x-2 transition-transform">→</span>
                        </div>
                    </div>
                    <!-- Фоновый элемент -->
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
                </a>

                <!-- КАРТОЧКА: КОНФЕРЕНЦИИ -->
                <a href="{{ route('rooms.index') }}" class="group relative bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300">
                    <div class="h-full flex flex-col justify-between">
                        <div>
                            <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-3xl mb-6">👥</div>
                            <h2 class="text-2xl font-black text-gray-800 leading-tight">Групповые<br>Комнаты</h2>
                            <p class="text-gray-400 text-sm mt-2 font-medium">Конференции до 6 человек</p>
                        </div>
                        <div class="mt-8 flex items-center text-indigo-600 font-bold text-sm">
                            Смотреть список <span class="ml-2 group-hover:translate-x-2 transition-transform">→</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- НИЖНЯЯ ПАНЕЛЬ: БЫСТРЫЕ ДЕЙСТВИЯ -->
            <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Профиль -->
                <div class="bg-white p-6 rounded-[2rem] border border-gray-100 flex items-center gap-6 shadow-sm">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-indigo-100">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <h4 class="font-black text-gray-800 tracking-tight">{{ Auth::user()->name }}</h4>
                        <p class="text-xs text-gray-400">{{ Auth::user()->email }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition text-gray-400 hover:text-gray-800">
                        ⚙️
                    </a>
                </div>

                <!-- Совет от Caspian (для UX) -->
                <div class="lg:col-span-2 bg-gradient-to-r from-amber-50 to-orange-50 p-6 rounded-[2rem] border border-amber-100 flex items-center gap-6">
                    <div class="text-3xl text-amber-500">💡</div>
                    <div class="text-sm text-amber-900 leading-relaxed font-medium">
                        <b>Совет:</b> Чтобы видеосвязь была стабильнее, используйте браузеры на базе Chromium и убедитесь, что другие приложения не используют камеру в данный момент.
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>