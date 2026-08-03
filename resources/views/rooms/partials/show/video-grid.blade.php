<div class="absolute flex flex-wrap items-center justify-center gap-2 md:gap-3 overflow-hidden transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]"
     :class="[
         focusedId ? 'content-start' : 'content-center',
         isMaximized ? 'top-0 bottom-0 left-0 right-0 z-[150] bg-black' : 'top-[80px] bottom-[90px] left-2 right-2 md:left-6 md:right-6 z-10'
     ]">
    
    <!-- 1. HOST VIDEO (ВЫ) -->
    <div x-show="!isMaximized || focusedId === 'me'"
         x-transition:enter="transition ease-out duration-500" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100"
         @click="toggleFocus('me')"
         class="relative overflow-hidden transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] cursor-pointer group shrink-0 flex items-center justify-center"
         :class="focusedId === 'me' ? 'border border-white/10 shadow-[0_30px_60px_rgba(0,0,0,0.8),_0_0_60px_rgba(99,102,241,0.15)] z-[50] bg-black' : 'border border-white/[0.05] hover:border-white/30 bg-[#050505] z-10 shadow-xl'"
         :style="getBoxStyle('me')">
        
        <video x-ref="localVideo" autoplay muted playsinline webkit-playsinline 
               class="w-full h-full transition-all duration-700 pointer-events-none" 
               :class="[isScreenSharing ? 'scale-x-100' : 'scale-x-[-1]', focusedId === 'me' ? 'object-contain' : 'object-cover']"></video>
        
        <button x-show="focusedId === 'me'" @click.stop="isMaximized = !isMaximized"
                class="absolute top-4 right-4 z-[60] w-10 h-10 bg-black/50 backdrop-blur-xl rounded-full border border-white/10 flex items-center justify-center hover:bg-white/10 transition-all pointer-events-auto">
            <svg x-show="!isMaximized" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
            <svg x-show="isMaximized" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h6m0 0v6m0-6l-7 7m17-11h-6m0 0V4m0 6l7-7M4 10h6m0 0V4m0 6l-7-7m17 11h-6m0 0v6m0-6l7 7"></path></svg>
        </button>

        <div class="absolute bottom-3 left-3 px-3 py-1.5 bg-black/50 backdrop-blur-xl rounded-full border border-white/10 flex items-center shadow-lg transition-all duration-500" :class="focusedId === 'me' ? 'opacity-0 scale-90' : 'opacity-100 scale-100'">
            <span class="text-[8px] font-black uppercase tracking-widest text-white/90">{{ __('rooms.You') }} (Host)</span>
        </div>
    </div>

    <!-- 2. PEERS (УЧАСТНИКИ) -->
    <template x-for="peer in peers" :key="peer.id">
        <div x-show="!isMaximized || focusedId === peer.id"
             @click="toggleFocus(peer.id)"
             class="relative overflow-hidden transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] cursor-pointer group shrink-0 flex items-center justify-center"
             :class="focusedId === peer.id ? 'border border-white/10 shadow-[0_30px_60px_rgba(0,0,0,0.8),_0_0_60px_rgba(99,102,241,0.15)] z-[50] bg-black' : 'border border-white/[0.05] hover:border-white/30 bg-[#050505] z-10 shadow-xl'"
             :style="getBoxStyle(peer.id)">
            
        <video :id="'video-' + peer.id" 
            autoplay 
            playsinline 
            webkit-playsinline 
            class="w-full h-full transition-all duration-700 pointer-events-none"
            :class="focusedId === peer.id ? 'object-contain' : 'object-cover'">
        </video>
            
            <button x-show="focusedId === peer.id" @click.stop="isMaximized = !isMaximized"
                    class="absolute top-4 right-4 z-[60] w-10 h-10 bg-black/50 backdrop-blur-xl rounded-full border border-white/10 flex items-center justify-center hover:bg-white/10 transition-all pointer-events-auto">
                <svg x-show="!isMaximized" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                <svg x-show="isMaximized" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h6m0 0v6m0-6l-7 7m17-11h-6m0 0V4m0 6l7-7M4 10h6m0 0V4m0 6l-7-7m17 11h-6m0 0v6m0-6l7 7"></path></svg>
            </button>

            <div class="absolute bottom-3 left-3 px-3 py-1.5 bg-black/50 backdrop-blur-xl rounded-full border border-white/10 flex items-center gap-2 shadow-lg transition-all duration-500" :class="focusedId === peer.id ? 'opacity-0 scale-90' : 'opacity-100 scale-100'">
                <div class="w-1.5 h-1.5 rounded-full" :class="peer.connected ? 'bg-green-400' : 'bg-amber-500 animate-pulse'"></div>
                <span class="text-[8px] font-black uppercase tracking-widest text-white/90" x-text="peer.name"></span>
            </div>
        </div>
    </template>

    <!-- 3. EMPTY SLOTS -->
    <template x-for="i in Math.max(0, 5 - peers.length)" :key="'empty-' + i">
        <div x-show="!isMaximized"
             class="relative overflow-hidden border border-white/[0.03] bg-[#050505]/40 backdrop-blur-sm flex flex-col items-center justify-center shrink-0 transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]"
             :style="getBoxStyle('empty-' + i)">
             <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-white/[0.02] flex items-center justify-center mb-2 border border-white/[0.05]">
                 <svg class="w-3 h-3 md:w-4 md:h-4 text-white/10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
             </div>
             <span class="text-[7px] md:text-[8px] font-black uppercase tracking-[0.2em] text-white/10">{{ __('rooms.Waiting') }}</span>
        </div>
    </template>
</div>