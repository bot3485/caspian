<div class="md:col-span-12 grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- LEADERBOARD -->
    <a href="{{ route('leaderboard') }}" class="led-frame rounded-[1.5rem] group block shadow-2xl transition-transform hover:-translate-y-1 duration-500 min-h-[104px]">
        <div class="led-content !bg-[#0a0a0a] p-6 flex items-center gap-5 border border-white/[0.04] shadow-[inset_0_2px_20px_rgba(255,255,255,0.02)]">
            <div class="w-14 h-14 bg-amber-500/10 rounded-xl flex items-center justify-center text-2xl border border-amber-500/20 group-hover:scale-105 transition-transform duration-300">🏆</div>
            <div>
                <h4 class="text-lg font-black uppercase italic tracking-tight text-white group-hover:text-amber-500 transition-colors">{{ __('dashboard.Leaderboard') }}</h4>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-widest mt-1">{{ __('dashboard.Leaderboard_Desc') }}</p>
            </div>
        </div>
    </a>

    <!-- SYSTEM TRUST -->
    <div class="led-frame rounded-[1.5rem] group block shadow-2xl transition-transform hover:-translate-y-1 duration-500 min-h-[104px]">
        <div class="led-content !bg-[#0a0a0a] p-6 flex items-center gap-5 border border-white/[0.04] shadow-[inset_0_2px_20px_rgba(255,255,255,0.02)]">
            <div class="w-14 h-14 bg-emerald-500/10 rounded-xl flex items-center justify-center text-2xl border border-emerald-500/20 group-hover:scale-105 transition-transform duration-300">🛡️</div>
            <div>
                <h4 class="text-lg font-black uppercase italic tracking-tight text-white group-hover:text-emerald-500 transition-colors">{{ __('dashboard.System_Trust') }}</h4>
                <p class="text-[8px] text-emerald-500 font-black uppercase tracking-widest mt-1">{{ Auth::user()->karma }} {{ __('dashboard.System_Trust_Desc') }}</p>
            </div>
        </div>
    </div>
</div>