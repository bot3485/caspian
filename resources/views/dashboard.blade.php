<x-app-layout>
    <div class="py-10 bg-[#050505] min-h-screen text-white selection:bg-indigo-500/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- ГОРЯЧАЯ ПАНЕЛЬ (Header) -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div>
                    <h1 class="text-5xl font-black tracking-tighter bg-gradient-to-r from-white via-white to-gray-500 bg-clip-text text-transparent">
                        CASPIAN <span class="text-indigo-500">2.0</span>
                    </h1>
                    <p class="text-gray-500 font-medium mt-1 uppercase text-[10px] tracking-[0.3em]">Интеллектуальный хаб общения</p>
                </div>

                <div class="flex items-center gap-3 bg-white/5 border border-white/10 p-2 rounded-2xl backdrop-blur-md">
                    <div class="flex -space-x-3 overflow-hidden p-1">
                        @foreach(range(1,3) as $i)
                            <div class="inline-block h-8 w-8 rounded-full ring-2 ring-[#050505] bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-[10px] font-bold">U{{$i}}</div>
                        @endforeach
                    </div>
                    <div class="pr-4 pl-2" x-data="{ onlineCount: 0 }" x-init="window.Echo.join('online-status').here(u => onlineCount = u.length).joining(u => onlineCount++).leaving(u => onlineCount--)">
                        <div class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">Сейчас в сети</div>
                        <div class="text-sm font-black" x-text="onlineCount + ' активных'">0 активных</div>
                    </div>
                </div>
            </div>

            <!-- BENTO GRID -->
            <div class="grid grid-cols-1 md:grid-cols-4 grid-rows-2 gap-4 h-full md:h-[650px]">
                
                <!-- КАРТОЧКА: ВИДЕОРУЛЕТКА (2x2) -->
                <a href="{{ route('chat') }}" class="md:col-span-2 md:row-span-2 group relative overflow-hidden rounded-[3rem] bg-indigo-600 flex flex-col justify-end p-10 transition-all hover:shadow-[0_0_50px_-12px_rgba(79,70,229,0.5)]">
                    <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent via-black/20 to-black/60 z-10"></div>
                    <!-- Анимированный фон -->
                    <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-[80px] group-hover:scale-150 transition-transform duration-700"></div>
                    
                    <div class="relative z-20">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-2xl rounded-2xl flex items-center justify-center text-4xl mb-6 group-hover:rotate-12 transition-transform">🎲</div>
                        <h2 class="text-4xl font-black leading-none tracking-tighter mb-4">СЛУЧАЙНЫЙ<br>ПОИСК</h2>
                        <p class="text-indigo-100/70 text-sm font-medium max-w-[240px] mb-6">Алгоритм подберет собеседника на основе ваших интересов и кармы.</p>
                        <div class="inline-flex items-center gap-3 bg-white text-indigo-600 px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest group-hover:bg-indigo-50 transition-colors">
                            Начать встречу
                            <span class="text-lg">→</span>
                        </div>
                    </div>
                </a>

                <!-- КАРТОЧКА: УРОВЕНЬ И XP -->
                <div class="md:col-span-1 bg-[#111] border border-white/5 rounded-[2.5rem] p-8 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Ваш Ранг</div>
                        <div class="text-indigo-500 font-black text-xs">LVL {{ Auth::user()->level }}</div>
                    </div>
                    <div>
                        <div class="text-5xl font-black mb-2">{{ Auth::user()->current_level_xp }}<span class="text-sm text-gray-600">/{{ Auth::user()->next_level_xp }}</span></div>
                        <div class="w-full bg-white/5 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full shadow-[0_0_10px_#6366f1]" style="width: {{ Auth::user()->xp_progress }}%"></div>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-500 font-bold leading-relaxed">Набирайте XP, общаясь в рулетке. Каждый новый уровень открывает уникальные стили профиля.</p>
                </div>

                <!-- КАРТОЧКА: ГРУППОВЫЕ КОМНАТЫ -->
                <a href="{{ route('rooms.index') }}" class="md:col-span-1 bg-white/5 border border-white/10 backdrop-blur-md rounded-[2.5rem] p-8 hover:bg-white/10 transition-all flex flex-col group">
                    <div class="text-3xl mb-4 group-hover:scale-110 transition-transform">👥</div>
                    <h3 class="text-xl font-black leading-tight">КОМНАТЫ</h3>
                    <p class="text-gray-500 text-xs mt-2 font-medium flex-1">Присоединяйтесь к обсуждениям или создайте свою приватную встречу.</p>
                    <div class="text-indigo-400 text-[10px] font-black uppercase tracking-widest mt-4">Войти ➔</div>
                </a>

                <!-- КАРТОЧКА: ИНТЕРЕСЫ / ТЕГИ -->
                <div class="md:col-span-2 bg-gradient-to-br from-[#111] to-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8">
                    <div class="flex justify-between items-center mb-6">
                        <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Ваши интересы</div>
                        <button class="text-indigo-500 text-[10px] font-black uppercase hover:underline">Изменить</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @php $tags = ['Gamer', 'Coding', 'Art', 'Music', 'Movies', 'Crypto']; @endphp
                        @foreach($tags as $tag)
                            <span class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-xs font-bold hover:border-indigo-500/50 transition-colors cursor-default">#{{ $tag }}</span>
                        @endforeach
                        <span class="px-4 py-2 bg-indigo-500/10 border border-indigo-500/30 rounded-xl text-xs font-bold text-indigo-400">+ Добавить</span>
                    </div>
                </div>
            </div>

            <!-- НИЖНИЙ РЯД: АКТИВНОСТЬ -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2 bg-[#111] border border-white/5 rounded-[2.5rem] p-8 flex items-center gap-8">
                    <div class="hidden sm:flex w-24 h-24 rounded-full border-4 border-indigo-500/20 items-center justify-center relative">
                         <div class="absolute inset-0 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                         <div class="text-xs font-black">42м</div>
                    </div>
                    <div>
                        <h4 class="text-lg font-black tracking-tight">Статистика недели</h4>
                        <p class="text-gray-500 text-xs font-medium mb-4">Вы провели в 1.5 раза больше времени в общении, чем на прошлой неделе.</p>
                        <div class="flex gap-1 items-end h-12">
                            @foreach([30,45,20,60,10,35,40] as $h)
                                <div class="flex-1 bg-indigo-500/20 hover:bg-indigo-500 transition-all rounded-sm" style="height: {{$h}}%"></div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-600/10 border border-indigo-500/20 rounded-[2.5rem] p-8 relative overflow-hidden group">
                    <div class="relative z-10">
                        <h4 class="text-sm font-black text-indigo-400 uppercase tracking-widest mb-2">Обновление</h4>
                        <p class="text-white text-xs font-bold leading-relaxed">В версии 2.0 мы улучшили стабильность P2P-соединения в групповых комнатах.</p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 text-6xl opacity-10 group-hover:scale-125 transition-transform rotate-12">🚀</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        body { background-color: #050505; }
        /* Плавный скроллбар для темной темы */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #050505; }
        ::-webkit-scrollbar-thumb { background: #222; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #333; }
    </style>
</x-app-layout>