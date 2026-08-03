<div class="absolute top-0 left-0 right-0 z-[110] p-4 pointer-events-none flex justify-between items-start h-[70px] transition-all duration-500 ease-in-out"
     :class="isMaximized ? 'opacity-0 -translate-y-4' : 'opacity-100 translate-y-0'">
    <div class="pointer-events-auto flex items-center gap-3 bg-[#0a0a0a]/80 backdrop-blur-2xl px-4 py-2 rounded-2xl border border-white/[0.08] shadow-[0_10px_30px_rgba(0,0,0,0.5)]">
        <div class="w-8 h-8 bg-gradient-to-br from-brand-indigo/20 to-purple-600/20 rounded-xl flex items-center justify-center text-sm border border-white/10">🛸</div>
        <div class="min-w-0 pr-2">
            <h1 class="text-[10px] md:text-xs font-black uppercase tracking-[0.2em] text-white/95 truncate">{{ $room->title }}</h1>
            <div class="flex items-center gap-1.5 mt-0.5">
                <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Live: <span x-text="currentCount" class="text-white font-black"></span>/6</p>
            </div>
        </div>
    </div>
    <div class="pointer-events-auto">
        <button @click="copyLink()" class="bg-[#0a0a0a]/80 backdrop-blur-2xl p-3 md:px-5 md:py-2.5 rounded-2xl hover:bg-white hover:text-black transition-all group flex items-center gap-2 shadow-xl border border-white/[0.08]">
            <span class="text-sm">🔗</span>
            <span class="hidden md:block text-[9px] font-black uppercase tracking-[0.2em]">{{ __('rooms.Copy_Link') }}</span>
        </button>
    </div>
</div>