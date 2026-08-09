<div class="grid grid-cols-1 md:grid-cols-12 gap-6 auto-rows-min">
    <!-- VIDEO ROULETTE -->
    <a href="{{ route('chat') }}" class="md:col-span-6 group block led-frame rounded-[2.5rem] shadow-2xl transition-transform hover:-translate-y-1 duration-500 min-h-[350px]">
        <div class="led-content !bg-brand-indigo relative overflow-hidden p-8 flex flex-col justify-between shadow-[inset_0_0_40px_rgba(0,0,0,0.2)]">
            <!-- Внутреннее свечение для глубины -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-[80px] pointer-events-none"></div>
            
            <div class="relative z-10">
                <span class="px-4 py-1.5 rounded-full bg-white/20 text-[8px] font-black uppercase tracking-widest border border-white/20 animate-pulse">{{ __('dashboard.Good_Luck') }}</span>
                <h2 class="text-4xl sm:text-6xl font-black tracking-tighter uppercase italic leading-[0.85] mt-6 text-white">{{ __('dashboard.Video') }}<br>{{ __('dashboard.Roulette') }}</h2>
            </div>
            <div class="relative z-10 flex items-end justify-between">
                <p class="text-indigo-100 font-bold text-[10px] max-w-xs uppercase tracking-wider">{{ __('dashboard.Roulette_Desc') }}</p>
                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-xl group-hover:rotate-45 transition-transform duration-500 text-black shadow-2xl">➔</div>
            </div>
        </div>
    </a>

    <!-- LIVE SPACES -->
    <a href="{{ route('rooms.index') }}" class="md:col-span-6 group block led-frame rounded-[2.5rem] shadow-2xl transition-transform hover:-translate-y-1 duration-500 min-h-[350px]">
        <div class="led-content !bg-[#0a0a0a] relative overflow-hidden p-8 flex flex-col justify-between border border-white/[0.04] shadow-[inset_0_2px_20px_rgba(255,255,255,0.02)]">
            <div class="relative z-10">
                <span class="px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-[8px] font-black uppercase tracking-widest text-gray-300">{{ __('dashboard.Conference_Enclaves') }}</span>
                <h2 class="text-4xl sm:text-6xl font-black tracking-tighter uppercase italic leading-[0.85] mt-6 text-white group-hover:text-brand-indigo transition-colors duration-500">{{ __('dashboard.Rooms') }}</h2>
            </div>
            <div class="relative z-10 flex items-end justify-between">
                <p class="text-gray-500 font-bold text-[10px] max-w-xs uppercase tracking-wider">{{ __('dashboard.Rooms_Desc') }}</p>
                <div class="w-14 h-14 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center text-xl group-hover:bg-brand-indigo group-hover:text-white transition-all duration-500 text-white shadow-2xl">➔</div>
            </div>
        </div>
    </a>
</div>