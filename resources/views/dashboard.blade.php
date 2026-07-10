<x-app-layout>
    <div class="relative min-h-screen text-white pb-32 lg:pb-10">
        <!-- Фон (фиксированный, чтобы не уезжал) -->
        <div class="fixed inset-0 pointer-events-none z-0">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-600/10 blur-[120px] rounded-full"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/10 blur-[120px] rounded-full"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8 relative z-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
                <div class="text-center md:text-left">
                    <h1 class="text-4xl md:text-6xl font-black tracking-tighter uppercase italic leading-none">
                        Caspian <span class="text-indigo-500 not-italic">v2.9</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.4em] mt-2">Next-Gen Video Ecosystem</p>
                </div>
            </div>

            <!-- Bento Grid -->
            <div class="grid grid-cols-1 md:grid-cols-6 lg:grid-cols-12 gap-4 md:gap-6 auto-rows-auto md:auto-rows-[160px]">
                
                <!-- Main Action: Roulette -->
                <a href="{{ route('chat') }}" class="md:col-span-6 lg:col-span-8 row-span-2 group relative overflow-hidden rounded-[2.5rem] bg-indigo-600 p-8 md:p-10 flex flex-col justify-between transition-all hover:shadow-[0_0_50px_rgba(79,70,229,0.3)] hover:-translate-y-1">
                    <div class="relative z-10">
                        <h2 class="text-5xl md:text-7xl font-black tracking-tighter uppercase italic leading-[0.85]">Видео<br>Рулетка</h2>
                    </div>
                    <div class="relative z-10 flex items-center justify-between mt-10">
                        <p class="text-indigo-100/60 text-sm font-medium max-w-[280px]">Найди идеального собеседника за считанные секунды.</p>
                        <span class="bg-white text-indigo-600 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-2xl">➔</span>
                    </div>
                </a>

                <!-- User Card -->
                <div class="md:col-span-3 lg:col-span-4 row-span-2 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 flex flex-col items-center justify-center text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-[2rem] flex items-center justify-center text-4xl font-black mb-4">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <h3 class="text-2xl font-black uppercase">{{ Auth::user()->name }}</h3>
                    <p class="text-indigo-400 text-[10px] font-black uppercase tracking-widest mt-1">{{ Auth::user()->rank_name }}</p>
                    
                    <div class="mt-8 w-full">
                        <div class="flex justify-between text-[9px] font-black uppercase text-gray-500 mb-2">
                            <span>Level {{ Auth::user()->level }}</span>
                            <span>{{ Auth::user()->xp_progress }}%</span>
                        </div>
                        <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden p-0.5">
                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" style="width: {{ Auth::user()->xp_progress }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Spaces -->
                <a href="{{ route('rooms.index') }}" class="md:col-span-3 lg:col-span-4 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-6 flex items-center gap-6 group hover:border-indigo-500/30 transition-all">
                    <div class="w-16 h-16 bg-purple-600/10 rounded-2xl flex items-center justify-center text-3xl">👥</div>
                    <div>
                        <h3 class="text-xl font-black uppercase italic tracking-tighter">Spaces</h3>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Групповые комнаты</p>
                    </div>
                </a>

                <!-- Leaderboard -->
                <a href="{{ route('leaderboard') }}" class="md:col-span-3 lg:col-span-4 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-6 flex items-center gap-6 group hover:border-amber-500/30 transition-all">
                    <div class="w-16 h-16 bg-amber-600/10 rounded-2xl flex items-center justify-center text-3xl">🏆</div>
                    <div>
                        <h3 class="text-xl font-black uppercase italic tracking-tighter">Ranking</h3>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Лидеры сообщества</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>