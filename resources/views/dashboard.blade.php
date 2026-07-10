<x-app-layout>
    <div class="py-6 md:py-12 bg-[#050505] min-h-screen text-white relative overflow-hidden" 
         x-data="{ onlineCount: 0 }" 
         x-init="window.Echo.join('online-status').here(u => onlineCount = u.length).joining(u => onlineCount++).leaving(u => onlineCount--)">
        
        <!-- ДЕКОРАТИВНЫЕ СВЕЧЕНИЯ НА ФОНЕ -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-600/10 blur-[120px] rounded-full pointer-events-none"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/10 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 pb-32">
            
            <!-- ЗАГОЛОВОК ДЛЯ МОБИЛОК -->
            <div class="md:hidden mb-6 text-center">
                <h1 class="text-3xl font-black tracking-tighter uppercase italic">Caspian <span class="text-indigo-500 text-xl not-italic">2.0</span></h1>
                <div class="flex items-center justify-center gap-2 mt-1">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse shadow-[0_0_8px_#22c55e]"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500" x-text="onlineCount + ' Online'"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 md:gap-8">
                
                <!-- ЛЕВАЯ КОЛОНКА (ПРОФИЛЬ - НА МОБИЛКАХ ПЕРВАЯ) -->
                <div class="lg:col-span-1 order-first lg:order-last">
                    <div class="bg-[#080808]/60 backdrop-blur-3xl border border-white/5 rounded-[2.5rem] p-6 md:p-8 flex flex-col items-center text-center sticky top-24 shadow-2xl">
                        <div class="relative group mb-4">
                            <div class="absolute inset-0 bg-indigo-500 blur-2xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
                            <div class="relative w-20 h-20 md:w-24 md:h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-[2rem] flex items-center justify-center text-3xl font-black shadow-xl transform group-hover:scale-105 transition-transform">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-black tracking-tight uppercase">{{ Auth::user()->name }}</h3>
                        <p class="text-indigo-400 text-[9px] font-black uppercase tracking-[0.2em] mt-1">{{ Auth::user()->rank_name }}</p>
                        
                        <!-- LVL PROGRESS -->
                        <div class="w-full mt-6 pt-6 border-t border-white/5 space-y-3">
                            <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-gray-500 px-1">
                                <span>Level {{ Auth::user()->level }}</span>
                                <span>{{ Auth::user()->xp_progress }}%</span>
                            </div>
                            <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden p-0.5 border border-white/5">
                                <div class="bg-gradient-to-r from-indigo-600 to-purple-500 h-full rounded-full shadow-[0_0_15px_rgba(99,102,241,0.4)] transition-all duration-1000" 
                                     style="width: {{ Auth::user()->xp_progress }}%"></div>
                            </div>
                        </div>

                        <!-- QUICK STATS -->
                        <div class="grid grid-cols-2 gap-3 w-full mt-6">
                            <div class="bg-white/[0.03] p-3 rounded-2xl border border-white/5">
                                <div class="text-lg font-black">{{ Auth::user()->karma }}</div>
                                <div class="text-[7px] font-black text-gray-500 uppercase tracking-widest">Karma</div>
                            </div>
                            <div class="bg-white/[0.03] p-3 rounded-2xl border border-white/5">
                                <div class="text-lg font-black">{{ Auth::user()->total_minutes }}</div>
                                <div class="text-[7px] font-black text-gray-500 uppercase tracking-widest">Mins</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ГЛАВНАЯ СЕТКА ДЕЙСТВИЙ -->
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">
                    
                    <!-- КАРТА: РУЛЕТКА -->
                    <a href="{{ route('chat') }}" class="group relative overflow-hidden rounded-[2.5rem] md:rounded-[3.5rem] bg-indigo-600 p-8 md:p-12 transition-all hover:shadow-[0_0_80px_rgba(79,70,229,0.3)] hover:-translate-y-1 active:scale-[0.98]">
                        <div class="relative z-20 flex flex-col h-full justify-between">
                            <div>
                                <div class="w-14 h-14 md:w-16 md:h-16 bg-white/20 backdrop-blur-2xl rounded-2xl md:rounded-[1.5rem] flex items-center justify-center text-3xl md:text-4xl mb-8 group-hover:rotate-12 transition-transform duration-500">🎲</div>
                                <h2 class="text-4xl md:text-6xl font-black leading-[0.9] tracking-tighter mb-4 uppercase italic">Видео<br>Рулетка</h2>
                                <p class="text-indigo-100/60 text-xs md:text-sm font-medium max-w-[240px] mb-8">Умный подбор собеседников по вашим интересам.</p>
                            </div>
                            
                            <div>
                                <span class="inline-flex items-center gap-3 bg-white text-indigo-600 px-8 py-4 rounded-2xl font-black text-[10px] md:text-xs uppercase tracking-widest group-hover:gap-5 transition-all shadow-2xl">
                                    Найти пару 
                                    <span class="text-lg leading-none">➔</span>
                                </span>
                            </div>
                        </div>
                        <!-- Декор фона -->
                        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-black/20 blur-3xl rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    </a>

                    <!-- КАРТА: SPACES -->
                    <a href="{{ route('rooms.index') }}" class="group relative overflow-hidden rounded-[2.5rem] md:rounded-[3.5rem] bg-[#0a0a0a] border border-white/5 p-8 md:p-12 transition-all hover:border-indigo-500/50 hover:bg-[#0d0d0d] active:scale-[0.98]">
                        <div class="relative z-20 flex flex-col h-full justify-between">
                            <div>
                                <div class="w-14 h-14 md:w-16 md:h-16 bg-indigo-500/10 rounded-2xl md:rounded-[1.5rem] flex items-center justify-center text-3xl md:text-4xl mb-8 group-hover:scale-110 transition-transform duration-500">👥</div>
                                <h2 class="text-4xl md:text-6xl font-black leading-[0.9] tracking-tighter mb-4 uppercase italic">Live<br>Spaces</h2>
                                <p class="text-gray-500 text-xs md:text-sm font-medium max-w-[240px] mb-8">Групповые комнаты для компаний до 6 человек.</p>
                            </div>
                            
                            <div>
                                <span class="inline-flex items-center gap-3 bg-white/5 text-gray-400 group-hover:bg-white group-hover:text-black px-8 py-4 rounded-2xl font-black text-[10px] md:text-xs uppercase tracking-widest transition-all shadow-xl">
                                    Все комнаты 
                                    <span class="text-lg leading-none">➔</span>
                                </span>
                            </div>
                        </div>
                        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-indigo-600/5 blur-3xl rounded-full"></div>
                    </a>

                    <!-- ТАБЛИЦА ЛИДЕРОВ (ШИРОКАЯ) -->
                    <a href="{{ route('leaderboard') }}" class="md:col-span-2 group relative overflow-hidden rounded-[2.5rem] md:rounded-[3rem] bg-gradient-to-r from-[#080808] to-[#050505] border border-white/5 p-8 md:p-10 flex flex-col md:flex-row items-center justify-between gap-8 hover:border-purple-500/30 transition-all active:scale-[0.99]">
                        <div class="relative z-20 text-center md:text-left">
                            <span class="text-purple-500 font-black text-[10px] uppercase tracking-[0.4em] mb-2 block">Top Ranking</span>
                            <h2 class="text-3xl md:text-4xl font-black uppercase italic tracking-tighter">Hall of Fame</h2>
                            <p class="text-gray-500 text-xs mt-2 font-medium">Станьте легендой Caspian и получите Elite статус.</p>
                        </div>
                        <div class="relative z-20 flex -space-x-4">
                            @php $topUsers = \App\Models\User::orderBy('xp', 'desc')->take(3)->get(); @endphp
                            @foreach($topUsers as $topUser)
                                <div class="w-14 h-14 md:w-16 md:h-16 rounded-full border-4 border-[#050505] bg-gray-800 flex items-center justify-center font-black text-xl shadow-2xl overflow-hidden">
                                    {{ substr($topUser->name, 0, 1) }}
                                </div>
                            @endforeach
                            <div class="w-14 h-14 md:w-16 md:h-16 rounded-full border-4 border-[#050505] bg-indigo-600 flex items-center justify-center font-black text-[10px] shadow-2xl">
                                +{{ max(0, \App\Models\User::count() - 3) }}
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ГЛОБАЛЬНАЯ СТАТИСТИКА (НИЖНИЙ БЛОК) -->
            <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-4 pb-20">
                <div class="p-6 bg-white/[0.02] border border-white/5 rounded-[2rem] hover:bg-white/[0.04] transition-colors">
                    <div class="text-2xl font-black tracking-tighter" x-text="onlineCount">0</div>
                    <div class="text-[8px] font-black text-gray-600 uppercase tracking-[0.2em] mt-1">Online Now</div>
                </div>
                <div class="p-6 bg-white/[0.02] border border-white/5 rounded-[2rem] hover:bg-white/[0.04] transition-colors">
                    <div class="text-2xl font-black tracking-tighter">{{ number_format(\App\Models\User::count()) }}</div>
                    <div class="text-[8px] font-black text-gray-600 uppercase tracking-[0.2em] mt-1">Users Joined</div>
                </div>
                <div class="p-6 bg-white/[0.02] border border-white/5 rounded-[2rem] hover:bg-white/[0.04] transition-colors">
                    <div class="text-2xl font-black tracking-tighter">{{ number_format(\App\Models\User::sum('total_minutes') / 60) }}h</div>
                    <div class="text-[8px] font-black text-gray-600 uppercase tracking-[0.2em] mt-1">Total Airtime</div>
                </div>
                <div class="p-6 bg-white/[0.02] border border-white/5 rounded-[2rem] hover:bg-white/[0.04] transition-colors">
                    <div class="text-2xl font-black tracking-tighter">{{ \App\Models\Room::count() }}</div>
                    <div class="text-[8px] font-black text-gray-600 uppercase tracking-[0.2em] mt-1">Active Spaces</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<style>
    [x-cloak] { display: none !important; }
</style>