<x-app-layout>
    <div class="relative min-h-[calc(100svh-80px)] p-4 md:p-8 lg:p-12 overflow-y-auto custom-scrollbar bg-[#020202] text-white">
        
        <!-- Background Glow FX -->
        <div class="absolute top-1/4 left-1/3 w-[500px] h-[500px] bg-brand-indigo/[0.03] rounded-full blur-[150px] pointer-events-none"></div>

        <div class="max-w-[1400px] mx-auto relative z-10">
            
            <!-- Bento Grid Structure -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 auto-rows-min">
                
                <!-- 1. VIDEO ROULETTE (Одинаковый крупный размер) -->
                <a href="{{ route('chat') }}" class="md:col-span-6 group relative min-h-[350px] rounded-[2.5rem] overflow-hidden bg-brand-indigo p-8 flex flex-col justify-between transition-all duration-500 hover:shadow-[0_20px_80px_rgba(99,102,241,0.2)] border border-white/10">
                    <!-- Subtle Carbon Texture Overlay -->
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-6">
                            <span class="px-4 py-1.5 rounded-full bg-white/15 backdrop-blur-md text-[8px] font-black uppercase tracking-widest border border-white/10 animate-pulse">Active Now</span>
                        </div>
                        <h2 class="text-4xl sm:text-6xl font-black tracking-tighter uppercase italic leading-[0.85] text-white">Video<br>Roulette</h2>
                    </div>
                    
                    <div class="relative z-10 flex items-end justify-between gap-6">
                        <p class="text-indigo-100/60 font-bold text-[10px] max-w-xs uppercase tracking-wider leading-relaxed">Experience high-speed P2P connections and intelligent interest-based matching.</p>
                        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-xl group-hover:rotate-45 transition-transform duration-500 shrink-0 shadow-2xl text-black">➔</div>
                    </div>
                </a>

                <!-- 2. LIVE SPACES (Одинаковый крупный размер с Roulette) -->
                <a href="{{ route('rooms.index') }}" class="md:col-span-6 group relative min-h-[350px] rounded-[2.5rem] overflow-hidden bg-[#050505]/40 backdrop-blur-xl border border-white/[0.04] p-8 flex flex-col justify-between transition-all duration-500 hover:border-brand-indigo/30 hover:shadow-[0_20px_80px_rgba(99,102,241,0.05)]">
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-6">
                            <span class="px-4 py-1.5 rounded-full bg-white/[0.02] border border-white/[0.05] text-[8px] font-black uppercase tracking-widest">Conference Enclaves</span>
                        </div>
                        <h2 class="text-4xl sm:text-6xl font-black tracking-tighter uppercase italic leading-[0.85] text-white/90 group-hover:text-brand-indigo transition-colors duration-500">Live<br>Spaces</h2>
                    </div>
                    
                    <div class="relative z-10 flex items-end justify-between gap-6">
                        <p class="text-gray-500 font-bold text-[10px] max-w-xs uppercase tracking-wider leading-relaxed">Deploy or join decentralized multi-user conferences with active screen-sharing.</p>
                        <div class="w-14 h-14 bg-white/[0.02] border border-white/[0.06] rounded-2xl flex items-center justify-center text-xl group-hover:bg-brand-indigo group-hover:text-white transition-all duration-500 shrink-0 text-white">➔</div>
                    </div>
                </a>

                <!-- 3. PROFILE TERMINAL BOX (Соразмерные средние блоки) -->
                <div class="md:col-span-6 bg-[#050505]/40 backdrop-blur-xl border border-white/[0.04] rounded-[2.5rem] p-8 md:p-10 flex flex-col items-center justify-center text-center shadow-[0_20px_50px_rgba(0,0,0,0.5)] min-h-[280px]">
                    <div class="relative mb-5">
                        <!-- Avatar Shield -->
                        <div class="w-20 h-20 bg-gradient-to-br from-brand-indigo to-purple-600 rounded-3xl flex items-center justify-center text-3xl font-black shadow-2xl border border-white/25">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="absolute -bottom-1 -right-1 bg-green-500 w-5 h-5 rounded-full border-4 border-[#020202] shadow-[0_0_12px_#22c55e]"></div>
                    </div>
                    
                    <h3 class="text-lg font-black uppercase tracking-tight italic">{{ Auth::user()->name }}</h3>
                    <div class="mt-1 text-brand-indigo text-[8px] font-black uppercase tracking-[0.35em]">{{ Auth::user()->rank_name }}</div>
                    
                    <!-- Progress Level Matrix -->
                    <div class="w-full max-w-md mt-6 space-y-2">
                        <div class="flex justify-between text-[7px] font-black uppercase text-gray-500 tracking-widest">
                            <span>Level {{ Auth::user()->level }}</span>
                            <span>{{ Auth::user()->xp_progress }}% Progress</span>
                        </div>
                        <div class="h-1.5 w-full bg-white/[0.03] border border-white/[0.05] rounded-full overflow-hidden">
                            <div class="h-full bg-brand-indigo transition-all duration-1000 shadow-[0_0_8px_#6366f1]" style="width: {{ Auth::user()->xp_progress }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- 4. GLOBAL STATS BOX (Соразмерный средний блок с Profile) -->
                <div class="md:col-span-6 bg-[#050505]/40 backdrop-blur-xl border border-white/[0.04] rounded-[2.5rem] p-8 md:p-10 flex flex-col justify-center items-center text-center shadow-[0_20px_50px_rgba(0,0,0,0.5)] min-h-[280px]">
                    <div class="w-16 h-16 bg-brand-indigo/10 rounded-2xl flex items-center justify-center text-3xl border border-brand-indigo/10 mb-4">🌍</div>
                    
                    <!-- Общее число зарегистрированных пользователей -->
                    <div class="text-4xl sm:text-5xl font-black tracking-tighter leading-none bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
                        {{ number_format(\App\Models\User::count()) }}
                    </div>
                    
                    <h4 class="text-[9px] font-black uppercase text-brand-indigo tracking-[0.35em] mt-3">Registered Citizens</h4>
                    <p class="text-[8.5px] text-gray-500 font-bold uppercase tracking-wider mt-2 max-w-[240px]">The ecosystem expands globally. Connect with peers all over the world.</p>
                </div>

                <!-- 5. LEADERBOARD (Соразмерные нижние блоки) -->
                <a href="{{ route('leaderboard') }}" class="md:col-span-6 bg-[#050505]/40 backdrop-blur-xl border border-white/[0.03] rounded-2xl p-6 md:p-8 group hover:border-amber-500/35 hover:bg-[#080808]/60 transition-all duration-300 flex items-center gap-5">
                    <div class="w-14 h-14 bg-amber-500/10 rounded-xl flex items-center justify-center text-2xl border border-amber-500/10 group-hover:scale-105 transition-transform duration-300">🏆</div>
                    <div>
                        <h4 class="text-lg font-black uppercase italic tracking-tight">Leaderboard</h4>
                        <p class="text-[8px] text-gray-500 font-bold uppercase tracking-widest mt-1">Global Rankings & Fame</p>
                    </div>
                </a>

                <!-- 6. SYSTEM TRUST (Соразмерные нижние блоки) -->
                <div class="md:col-span-6 bg-[#050505]/40 backdrop-blur-xl border border-white/[0.03] rounded-2xl p-6 md:p-8 flex items-center gap-5">
                    <div class="w-14 h-14 bg-emerald-500/10 rounded-xl flex items-center justify-center text-2xl border border-emerald-500/10">🛡️</div>
                    <div>
                        <h4 class="text-lg font-black uppercase italic tracking-tight">System Trust</h4>
                        <p class="text-[8px] text-emerald-500 font-black uppercase tracking-widest mt-1">{{ Auth::user()->karma }} Trust Points</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>