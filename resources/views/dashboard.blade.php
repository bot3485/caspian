<x-app-layout>
    <div x-data="{ onlineCount: 0 }" 
         x-init="window.Echo.join('online-status').here(u => onlineCount = u.length).joining(u => onlineCount++).leaving(u => onlineCount--)"
         class="h-full overflow-y-auto custom-scrollbar bg-[#050505] text-white selection:bg-indigo-500/30 pb-32 lg:pb-10">
        <!-- Фон с эффектом градиента -->
        <div class="fixed inset-0 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-600/10 blur-[120px] rounded-full"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/10 blur-[120px] rounded-full"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8 relative">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
                <div class="text-center md:text-left">
                    <h1 class="text-4xl md:text-6xl font-black tracking-tighter uppercase italic leading-none">
                        Caspian <span class="text-indigo-500 not-italic">v2.8</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.4em] mt-2">Next-Gen Video Ecosystem</p>
                </div>
                
                <!-- Статус онлайн (Global) -->
                <div class="flex items-center gap-4 bg-white/5 border border-white/10 px-6 py-3 rounded-2xl backdrop-blur-xl">
                    <div class="relative">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse shadow-[0_0_12px_#22c55e]"></div>
                    </div>
                    <div class="text-left">
                        <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest leading-none mb-1">Live Network</p>
                        <p class="text-sm font-black italic" x-text="onlineCount + ' Online'"></p>
                    </div>
                </div>
            </div>

            <!-- Bento Grid Layout -->
            <div class="grid grid-cols-1 md:grid-cols-6 lg:grid-cols-12 gap-4 md:gap-6 auto-rows-[160px]">
                
                <!-- Main Action: Roulette (Large Card) -->
                <a href="{{ route('chat') }}" class="md:col-span-6 lg:col-span-8 row-span-2 group relative overflow-hidden rounded-[2.5rem] bg-indigo-600 p-8 md:p-10 flex flex-col justify-between transition-all hover:shadow-[0_0_50px_rgba(79,70,229,0.3)] hover:-translate-y-1">
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-6">
                            <span class="text-4xl">🎲</span>
                            <span class="bg-black/20 px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">Smart Matchmaking</span>
                        </div>
                        <h2 class="text-5xl md:text-7xl font-black tracking-tighter uppercase italic leading-[0.85]">Видео<br>Рулетка</h2>
                    </div>
                    <div class="relative z-10 flex items-center justify-between">
                        <p class="text-indigo-100/60 text-sm font-medium max-w-[280px] hidden sm:block">Найди идеального собеседника за считанные секунды с помощью AI-подбора.</p>
                        <span class="bg-white text-indigo-600 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-2xl">➔</span>
                    </div>
                    <!-- Decor -->
                    <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-white/10 blur-[80px] rounded-full transition-transform duration-700 group-hover:scale-150"></div>
                </a>

                <!-- User Profile Card (Sidebar Card) -->
                <div class="md:col-span-3 lg:col-span-4 row-span-2 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                    <div class="relative z-10 w-full">
                        <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-[2rem] flex items-center justify-center text-4xl font-black shadow-2xl mx-auto mb-4 transform group-hover:rotate-6 transition-transform">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <h3 class="text-2xl font-black tracking-tight uppercase">{{ Auth::user()->name }}</h3>
                        <p class="text-indigo-400 text-[10px] font-black uppercase tracking-widest mt-1">{{ Auth::user()->rank_name }}</p>
                        
                        <div class="mt-8 space-y-4 w-full">
                            <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-gray-500 px-1">
                                <span x-text="'Level ' + {{ Auth::user()->level }}"></span>
                                <span x-text="{{ Auth::user()->xp_progress }} + '%'"></span>
                            </div>
                            <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden p-0.5 border border-white/5">
                                <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000 shadow-[0_0_15px_rgba(99,102,241,0.5)]"
                                    :class="{{ Auth::user()->xp_progress }} > 90 ? 'animate-pulse bg-white' : ''"
                                    style="width: {{ Auth::user()->xp_progress }}%">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-6">
                            <div class="bg-white/5 p-3 rounded-2xl text-center">
                                <div class="text-xl font-black italic">{{ Auth::user()->karma }}</div>
                                <div class="text-[7px] font-black uppercase text-gray-600 tracking-tighter">Karma</div>
                            </div>
                            <div class="bg-white/5 p-3 rounded-2xl text-center">
                                <div class="text-xl font-black italic">{{ Auth::user()->level }}</div>
                                <div class="text-[7px] font-black uppercase text-gray-600 tracking-tighter">Level</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Spaces (Horizontal Card) -->
                <a href="{{ route('rooms.index') }}" class="md:col-span-3 lg:col-span-4 row-span-1 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-6 flex items-center gap-6 group hover:border-indigo-500/30 transition-all">
                    <div class="w-16 h-16 bg-purple-600/10 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">👥</div>
                    <div>
                        <h3 class="text-xl font-black uppercase italic tracking-tighter">Spaces</h3>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Комнаты до 6 человек</p>
                    </div>
                </a>

                <!-- Leaderboard (Vertical Card) -->
                <a href="{{ route('leaderboard') }}" class="md:col-span-3 lg:col-span-4 row-span-1 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-6 flex items-center gap-6 group hover:border-amber-500/30 transition-all">
                    <div class="w-16 h-16 bg-amber-600/10 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">🏆</div>
                    <div>
                        <h3 class="text-xl font-black uppercase italic tracking-tighter">Ranking</h3>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Таблица лидеров</p>
                    </div>
                </a>

                <!-- Quick Stats (Small Cards) -->
                <div class="md:col-span-3 lg:col-span-4 row-span-1 bg-white/[0.02] border border-white/5 rounded-[2.5rem] p-6 flex flex-col justify-center">
                    <div class="text-3xl font-black italic tracking-tighter">{{ number_format(\App\Models\User::sum('total_minutes')) }}</div>
                    <p class="text-[9px] font-black text-indigo-400/60 uppercase tracking-[0.2em]">Total Mins On-Air</p>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>