<x-app-layout>
    <div class="relative min-h-[calc(100svh-80px)] p-4 md:p-8 lg:p-12 overflow-y-auto custom-scrollbar">
        <div class="max-w-[1400px] mx-auto">
            
            <!-- Bento Hero -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 auto-rows-min">
                
                <!-- Main Roulette Card -->
                <a href="{{ route('chat') }}" class="md:col-span-8 group relative min-h-[400px] rounded-[3rem] overflow-hidden bg-indigo-600 p-12 flex flex-col justify-between transition-all hover:shadow-[0_20px_80px_rgba(79,70,229,0.3)]">
                    <div class="absolute top-0 right-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-6">
                            <span class="px-4 py-1 rounded-full bg-white/20 backdrop-blur-md text-[9px] font-black uppercase tracking-widest">Active Now</span>
                        </div>
                        <h2 class="text-6xl md:text-8xl font-black tracking-tighter uppercase italic leading-[0.85] text-white">Video<br>Roulette</h2>
                    </div>
                    <div class="relative z-10 flex items-center justify-between">
                        <p class="text-indigo-100/60 font-bold text-sm max-w-xs uppercase tracking-tight">Experience high-speed P2P connections and smart matching.</p>
                        <div class="w-20 h-20 bg-white rounded-[2rem] flex items-center justify-center text-3xl group-hover:rotate-45 transition-transform duration-500">➔</div>
                    </div>
                </a>

                <!-- Profile Small Card -->
                <div class="md:col-span-4 caspian-glass rounded-[3rem] p-10 flex flex-col items-center justify-center text-center">
                    <div class="relative mb-6">
                        <div class="w-28 h-28 bg-gradient-to-br from-indigo-500 to-cyan-500 rounded-[2.5rem] flex items-center justify-center text-4xl font-black shadow-2xl">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="absolute -bottom-2 -right-2 bg-green-500 w-8 h-8 rounded-full border-[6px] border-[#020202]"></div>
                    </div>
                    <h3 class="text-2xl font-black uppercase tracking-tighter italic">{{ Auth::user()->name }}</h3>
                    <div class="mt-2 text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em]">{{ Auth::user()->rank_name }}</div>
                    
                    <div class="w-full mt-10 space-y-2">
                        <div class="flex justify-between text-[8px] font-black uppercase text-gray-500 tracking-widest">
                            <span>Level {{ Auth::user()->level }}</span>
                            <span>{{ Auth::user()->xp_progress }}% to next</span>
                        </div>
                        <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 transition-all duration-1000" style="width: {{ Auth::user()->xp_progress }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Features Grid -->
                <a href="{{ route('rooms.index') }}" class="md:col-span-4 caspian-glass rounded-[2.5rem] p-8 group hover:border-indigo-500/40 transition-all">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-purple-500/10 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">👥</div>
                        <div>
                            <h4 class="text-xl font-black uppercase italic tracking-tighter">Live Spaces</h4>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-1">Group conferences</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('leaderboard') }}" class="md:col-span-4 caspian-glass rounded-[2.5rem] p-8 group hover:border-amber-500/40 transition-all">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-amber-500/10 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">🏆</div>
                        <div>
                            <h4 class="text-xl font-black uppercase italic tracking-tighter">Leaderboard</h4>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-1">Community ranking</p>
                        </div>
                    </div>
                </a>

                <div class="md:col-span-4 caspian-glass rounded-[2.5rem] p-8 flex items-center gap-6">
                    <div class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-3xl">🛡️</div>
                    <div>
                        <h4 class="text-xl font-black uppercase italic tracking-tighter">Karma</h4>
                        <p class="text-[9px] text-emerald-500 font-black uppercase tracking-widest mt-1">{{ Auth::user()->karma }} Trust Points</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>