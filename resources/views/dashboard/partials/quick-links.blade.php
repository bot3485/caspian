<div class="md:col-span-12 grid grid-cols-1 md:grid-cols-2 gap-6">
    <a href="{{ route('leaderboard') }}" class="bg-[#050505]/40 backdrop-blur-xl border border-white/[0.03] rounded-2xl p-6 group hover:border-amber-500/35 transition-all flex items-center gap-5">
        <div class="w-14 h-14 bg-amber-500/10 rounded-xl flex items-center justify-center text-2xl border border-amber-500/10 group-hover:scale-105 transition-transform">🏆</div>
        <div>
            <h4 class="text-lg font-black uppercase italic tracking-tight">{{ __('dashboard.Leaderboard') }}</h4>
            <p class="text-[8px] text-gray-500 font-bold uppercase tracking-widest mt-1">{{ __('dashboard.Leaderboard_Desc') }}</p>
        </div>
    </a>

    <div class="bg-[#050505]/40 backdrop-blur-xl border border-white/[0.03] rounded-2xl p-6 flex items-center gap-5">
        <div class="w-14 h-14 bg-emerald-500/10 rounded-xl flex items-center justify-center text-2xl border border-emerald-500/10">🛡️</div>
        <div>
            <h4 class="text-lg font-black uppercase italic tracking-tight">{{ __('dashboard.System_Trust') }}</h4>
            <p class="text-[8px] text-emerald-500 font-black uppercase tracking-widest mt-1">{{ Auth::user()->karma }} {{ __('dashboard.System_Trust_Desc') }}</p>
        </div>
    </div>
</div>