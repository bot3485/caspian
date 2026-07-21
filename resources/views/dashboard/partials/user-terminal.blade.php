<div class="md:col-span-6 bg-[#050505]/40 backdrop-blur-xl border border-white/[0.04] rounded-[2.5rem] p-8 flex flex-col items-center justify-center text-center shadow-2xl min-h-[280px]">
    <div class="relative mb-5">
        <div class="w-20 h-20 bg-gradient-to-br from-brand-indigo to-purple-600 rounded-3xl flex items-center justify-center text-3xl font-black shadow-2xl border border-white/25">
            {{ substr(Auth::user()->name, 0, 1) }}
        </div>
        <div class="absolute -bottom-1 -right-1 bg-green-500 w-5 h-5 rounded-full border-4 border-[#020202] shadow-[0_0_12px_#22c55e]"></div>
    </div>
    
    <h3 class="text-lg font-black uppercase tracking-tight italic">{{ Auth::user()->name }}</h3>
    
    <div class="mt-6 w-full max-w-[280px]">
        <div class="flex justify-between items-end mb-2 px-1">
            <div class="flex items-center gap-2">
                <span class="text-xl">{{ Auth::user()->prestige_badge['icon'] }}</span>
                <span class="text-[10px] font-black uppercase tracking-widest" style="color: {{ Auth::user()->prestige_badge['color'] }}">
                    {{ Auth::user()->prestige_badge['name'] }}
                </span>
            </div>
            <span class="text-[8px] font-bold text-gray-500 uppercase">{{ Auth::user()->site_minutes }} min</span>
        </div>
        <div class="h-1 w-full bg-white/5 rounded-full overflow-hidden border border-white/[0.03]">
            <div class="h-full bg-brand-indigo shadow-[0_0_10px_#6366f1]" style="width: {{ Auth::user()->xp_progress }}%"></div>
        </div>
    </div>
</div>