<div class="grid grid-cols-1 md:grid-cols-12 gap-6 auto-rows-min">
    <!-- VIDEO ROULETTE -->
    <a href="{{ route('chat') }}" class="md:col-span-6 group relative min-h-[350px] led-frame rounded-[2.5rem] overflow-hidden bg-brand-indigo p-8 flex flex-col justify-between transition-all duration-500 border border-white/10 shadow-2xl">
        <div class="relative z-10">
            <span class="px-4 py-1.5 rounded-full bg-white/15 backdrop-blur-md text-[8px] font-black uppercase tracking-widest border border-white/10 animate-pulse">{{ __('dashboard.Good_Luck') }}</span>
            <h2 class="text-4xl sm:text-6xl font-black tracking-tighter uppercase italic leading-[0.85] mt-6">{{ __('dashboard.Video') }}<br>{{ __('dashboard.Roulette') }}</h2>
        </div>
        <div class="relative z-10 flex items-end justify-between">
            <p class="text-indigo-100/60 font-bold text-[10px] max-w-xs uppercase tracking-wider">{{ __('dashboard.Roulette_Desc') }}</p>
            <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-xl group-hover:rotate-45 transition-transform duration-500 text-black shadow-2xl">➔</div>
        </div>
    </a>

    <!-- LIVE SPACES -->
    <a href="{{ route('rooms.index') }}" class="md:col-span-6 group relative min-h-[350px] rounded-[2.5rem] overflow-hidden bg-[#050505]/40 backdrop-blur-xl border border-white/[0.04] p-8 flex flex-col justify-between transition-all duration-500 hover:border-brand-indigo/30">
        <div class="relative z-10">
            <span class="px-4 py-1.5 rounded-full bg-white/[0.02] border border-white/[0.05] text-[8px] font-black uppercase tracking-widest">{{ __('dashboard.Conference_Enclaves') }}</span>
            <h2 class="text-4xl sm:text-6xl font-black tracking-tighter uppercase italic leading-[0.85] mt-6 text-white/90 group-hover:text-brand-indigo">{{ __('dashboard.Rooms') }}</h2>
        </div>
        <div class="relative z-10 flex items-end justify-between">
            <p class="text-gray-500 font-bold text-[10px] max-w-xs uppercase tracking-wider">{{ __('dashboard.Rooms_Desc') }}</p>
            <div class="w-14 h-14 bg-white/[0.02] border border-white/[0.06] rounded-2xl flex items-center justify-center text-xl group-hover:bg-brand-indigo group-hover:text-white transition-all text-white shadow-2xl">➔</div>
        </div>
    </a>
</div>