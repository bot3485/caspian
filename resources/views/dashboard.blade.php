<x-app-layout>
    <div class="py-10 bg-[#050505] min-h-screen text-white selection:bg-indigo-500/30" 
         x-data="{ onlineList: [] }" 
         x-init="window.Echo.join('online-status')
                    .here(u => onlineList = u)
                    .joining(u => onlineList.push(u))
                    .leaving(u => onlineList = onlineList.filter(x => x.id !== u.id))">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- ГОРЯЧАЯ ПАНЕЛЬ (Header) -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div>
                    <h1 class="text-5xl font-black tracking-tighter bg-gradient-to-r from-white via-white to-gray-500 bg-clip-text text-transparent">
                        CASPIAN <span class="text-indigo-500">2.0</span>
                    </h1>
                    <p class="text-gray-500 font-medium mt-1 uppercase text-[10px] tracking-[0.3em]">Интеллектуальный хаб общения</p>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Кнопка перехода в Лидерборд -->
                    <a href="{{ route('leaderboard') }}" class="hidden md:flex items-center gap-2 bg-white/5 border border-white/10 px-5 py-3 rounded-2xl hover:bg-white/10 transition-all group">
                        <span class="text-xl group-hover:scale-110 transition-transform">🏆</span>
                        <span class="text-[10px] font-black uppercase tracking-widest">Лидеры</span>
                    </a>

                    <div class="flex items-center gap-3 bg-white/5 border border-white/10 p-2 rounded-2xl backdrop-blur-md">
                        <div class="flex -space-x-3 overflow-hidden p-1">
                            <!-- Живые аватары из Reverb -->
                            <template x-for="user in onlineList.slice(0, 5)" :key="user.id">
                                <div class="inline-block h-8 w-8 rounded-full ring-2 ring-[#050505] bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-[10px] font-black uppercase" x-text="user.name[0]"></div>
                            </template>
                            <template x-if="onlineList.length > 5">
                                <div class="inline-block h-8 w-8 rounded-full ring-2 ring-[#050505] bg-gray-800 flex items-center justify-center text-[8px] font-black" x-text="'+' + (onlineList.length - 5)"></div>
                            </template>
                        </div>
                        <div class="pr-4 pl-2">
                            <div class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">Сейчас в сети</div>
                            <div class="text-sm font-black" x-text="onlineList.length + ' активных'">0 активных</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BENTO GRID -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <!-- КАРТОЧКА: ВИДЕОРУЛЕТКА (2x2) -->
                <a href="{{ route('chat') }}" class="md:col-span-2 md:row-span-2 group relative overflow-hidden rounded-[3rem] bg-indigo-600 flex flex-col justify-end p-10 transition-all hover:shadow-[0_0_50px_-12px_rgba(79,70,229,0.5)]">
                    <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent via-black/20 to-black/60 z-10"></div>
                    <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-[80px] group-hover:scale-150 transition-transform duration-700"></div>
                    
                    <div class="relative z-20">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-2xl rounded-2xl flex items-center justify-center text-4xl mb-6 group-hover:rotate-12 transition-transform">🎲</div>
                        <h2 class="text-4xl font-black leading-none tracking-tighter mb-4 text-white">СЛУЧАЙНЫЙ<br>ПОИСК</h2>
                        <p class="text-indigo-100/70 text-sm font-medium max-w-[240px] mb-6">Алгоритм подберет собеседника на основе ваших интересов.</p>
                        <div class="inline-flex items-center gap-3 bg-white text-indigo-600 px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest group-hover:bg-indigo-50 transition-colors">
                            Начать встречу →
                        </div>
                    </div>
                </a>

                <!-- КАРТОЧКА: УРОВЕНЬ И XP (Живое обновление) -->
                <div class="md:col-span-1 bg-[#111] border border-white/5 rounded-[2.5rem] p-8 flex flex-col justify-between"
                     x-data="{ xp: {{ Auth::user()->xp }}, lvl: {{ Auth::user()->level }} }"
                     @xp-gained.window="if($event.detail.userId === {{ Auth::id() }}) { xp = $event.detail.totalXp; lvl = $event.detail.currentLevel }">
                    <div class="flex justify-between items-start">
                        <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Ваш Ранг</div>
                        <div class="text-indigo-500 font-black text-xs" x-text="'LVL ' + lvl">LVL {{ Auth::user()->level }}</div>
                    </div>
                    <div>
                        <!-- Опыт текущего уровня -->
                        <div class="text-5xl font-black mb-2" x-text="xp % 1000"></div>
                        <div class="w-full bg-white/5 h-1.5 rounded-full overflow-hidden">
                            <!-- Прогресс-бар -->
                            <div class="bg-indigo-500 h-full shadow-[0_0_10px_#6366f1] transition-all duration-1000" 
                                 :style="'width:' + (xp % 1000 / 10) + '%'"></div>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-500 font-bold leading-relaxed">Набирайте XP, общаясь в рулетке. Опыт начисляется каждую минуту.</p>
                </div>

                <!-- КАРТОЧКА: ИНТЕРЕСЫ (Безопасный цикл) -->
                <div class="md:col-span-2 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8">
                    <div class="flex justify-between items-center mb-6">
                        <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Ваши интересы</div>
                        <a href="{{ route('profile.edit') }}" class="text-indigo-500 text-[10px] font-black uppercase hover:underline">Изменить</a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @php $userInterests = Auth::user()->interests; @endphp
                        @if(is_array($userInterests) && count($userInterests) > 0)
                            @foreach($userInterests as $tag)
                                <span class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-xs font-bold hover:border-indigo-500/50 transition-colors cursor-default">#{{ $tag }}</span>
                            @endforeach
                        @else
                            <p class="text-gray-600 text-xs italic">Добавьте теги в профиле для умного подбора</p>
                        @endif
                    </div>
                </div>

                <!-- КАРТОЧКА: ГРУППОВЫЕ КОМНАТЫ -->
                <a href="{{ route('rooms.index') }}" class="md:col-span-2 bg-white/5 border border-white/10 backdrop-blur-md rounded-[2.5rem] p-8 hover:bg-white/10 transition-all flex items-center justify-between group">
                    <div class="flex items-center gap-6">
                        <div class="text-4xl group-hover:scale-110 transition-transform">👥</div>
                        <div>
                            <h3 class="text-xl font-black text-white uppercase tracking-tighter">Live Spaces</h3>
                            <p class="text-gray-500 text-xs font-medium">Присоединяйтесь к групповым дискуссиям.</p>
                        </div>
                    </div>
                    <div class="text-indigo-400 text-2xl pr-4 group-hover:translate-x-2 transition-transform">➔</div>
                </a>
            </div>

            <!-- НИЖНИЙ РЯД: АКТИВНОСТЬ -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2 bg-[#111] border border-white/5 rounded-[2.5rem] p-8 flex items-center gap-8">
                    <div class="hidden sm:flex w-24 h-24 rounded-full border-4 border-indigo-500/20 items-center justify-center relative">
                         <div class="absolute inset-0 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                         <div class="text-sm font-black text-white">{{ Auth::user()->total_minutes }}м</div>
                    </div>
                    <div>
                        <h4 class="text-lg font-black tracking-tight">Ваше время в эфире</h4>
                        <p class="text-gray-500 text-xs font-medium mb-4">Общее время, проведенное в видео-звонках один на один.</p>
                        <div class="flex gap-1 items-end h-8">
                            @foreach([10,20,15,30,40,25,50] as $h)
                                <div class="flex-1 bg-indigo-500/10 rounded-sm h-[{{ $h }}%]"></div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-600/10 border border-indigo-500/20 rounded-[2.5rem] p-8 relative overflow-hidden group">
                    <div class="relative z-10">
                        <h4 class="text-sm font-black text-indigo-400 uppercase tracking-widest mb-2">Обновление 2.0</h4>
                        <p class="text-white text-xs font-bold leading-relaxed opacity-80">Мы добавили систему рангов и умный поиск по интересам. Наслаждайтесь общением!</p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 text-6xl opacity-10 group-hover:scale-125 transition-transform rotate-12">🚀</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>