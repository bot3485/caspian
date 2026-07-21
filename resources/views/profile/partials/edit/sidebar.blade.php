<div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 text-center shadow-2xl">
    <div class="relative inline-block mb-6">
        <div class="w-32 h-32 bg-gradient-to-br from-brand-indigo to-purple-600 rounded-[2.5rem] flex items-center justify-center text-5xl font-black shadow-2xl border border-white/20">
            {{ substr($user->name, 0, 1) }}
        </div>
        <div class="absolute -bottom-2 -right-2 bg-green-500 w-8 h-8 rounded-2xl border-4 border-[#0a0a0a] shadow-[0_0_15px_#22c55e]"></div>
    </div>
    
    <h2 class="text-2xl font-black uppercase italic tracking-tighter">{{ $user->name }}</h2>
    <div class="mt-4 flex flex-col gap-2">
        <span class="text-brand-indigo text-[10px] font-black uppercase tracking-[0.4em]">{{ $user->rank_name }}</span>
        <div class="flex items-center justify-center gap-2 px-4 py-2 bg-white/5 rounded-xl border border-white/5">
            <span class="text-lg">{{ $user->prestige_badge['icon'] }}</span>
            <span class="text-[9px] font-black uppercase tracking-widest" style="color: {{ $user->prestige_badge['color'] }}">
                {{ $user->prestige_badge['name'] }}
            </span>
        </div>
    </div>
</div>