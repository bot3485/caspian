<!-- TYPING INDICATOR -->
<div x-show="isPartnerTyping && state === 'connected'" 
     class="fixed bottom-48 md:bottom-40 left-1/2 -translate-x-1/2 z-[400] bg-brand-indigo/95 backdrop-blur-3xl px-6 py-3 rounded-2xl border border-white/20 shadow-[0_20px_50px_rgba(99,102,241,0.4)]" 
     x-cloak 
     x-transition:enter="transition ease-out duration-300 translate-y-10 opacity-0" 
     x-transition:enter-end="translate-y-0 opacity-100">
    <div class="flex items-center gap-4">
        <span class="text-[10px] font-black uppercase tracking-[0.25em] text-white" x-text="typingPartnerName + ' is typing' "></span>
        <div class="flex gap-1.5">
            <span class="w-1.5 h-1.5 bg-white rounded-full animate-bounce shadow-[0_0_5px_#fff]"></span>
            <span class="w-1.5 h-1.5 bg-white rounded-full animate-bounce [animation-delay:0.2s] shadow-[0_0_5px_#fff]"></span>
            <span class="w-1.5 h-1.5 bg-white rounded-full animate-bounce [animation-delay:0.4s] shadow-[0_0_5px_#fff]"></span>
        </div>
    </div>
</div>

<!-- INTERESTS MATCH WINDOW -->
<div x-show="showInterestMatch" 
     x-transition:enter="transition ease-out duration-500"
     x-transition:enter-start="opacity-0 translate-y-12 scale-90"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed top-1/3 left-1/2 -translate-x-1/2 z-[600] pointer-events-none"
     x-cloak>
    
    <div class="bg-brand-indigo border-4 border-white px-8 py-6 rounded-[3rem] shadow-[0_0_80px_rgba(0,0,0,1)] flex flex-col items-center gap-4 min-w-[320px]">
        <div class="relative">
            <div class="absolute inset-0 bg-brand-indigo rounded-full animate-ping opacity-25"></div>
            <div class="relative w-14 h-14 bg-brand-indigo/20 rounded-full flex items-center justify-center text-2xl border border-brand-indigo/50">
                🔥
            </div>
        </div>

        <div class="text-center">
            <h4 class="text-[10px] font-black uppercase tracking-[0.4em] text-brand-indigo mb-1">{{ __('chatroulette.Common_Universe_Found') }}</h4>
            <p class="text-lg font-black italic uppercase tracking-tighter text-white">{{ __('chatroulette.Matching_Interests') }}!</p>
        </div>

        <div class="flex flex-wrap justify-center gap-2 mt-2">
            <template x-for="tag in commonInterests" :key="tag">
                <span class="px-4 py-1.5 bg-white/10 border border-white/10 rounded-full text-[9px] font-black uppercase tracking-widest text-indigo-300" 
                      x-text="'#' + tag"></span>
            </template>
        </div>
        
        <div class="mt-2 text-[8px] font-bold text-gray-500 uppercase tracking-widest animate-pulse">
            {{ __('chatroulette.Start_Conversation') }}
        </div>
    </div>
</div>

<!-- ICEBREAKER PREMIUM OVERLAY -->
<div x-show="showIcebreakerOverlay" 
     x-transition:enter="transition cubic-bezier(0.34, 1.56, 0.64, 1) duration-600"
     x-transition:enter-start="opacity-0 scale-75 translate-y-20 blur-xl"
     x-transition:enter-end="opacity-100 scale-100 translate-y-0 blur-0"
     x-transition:leave="transition ease-in duration-400"
     x-transition:leave-end="opacity-0 scale-90 blur-lg"
     class="fixed bottom-44 left-1/2 -translate-x-1/2 z-[1500] w-full max-w-xl px-6 pointer-events-none"
     x-cloak>
    
    <div class="pointer-events-auto relative group">
        <div class="absolute -inset-1 bg-gradient-to-r from-brand-indigo/50 via-purple-500/50 to-brand-cyan/50 rounded-[2.5rem] blur opacity-30 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
        
        <div class="relative caspian-glass rounded-[2rem] p-1 border-white/10 shadow-[0_32px_64px_rgba(0,0,0,0.8)] overflow-hidden">
            <div class="bg-[#050505]/90 rounded-[1.8rem] p-6 md:p-8 flex flex-col items-center text-center gap-4">
                
                <div class="flex items-center gap-3">
                    <div class="h-px w-8 bg-brand-indigo/30"></div>
                    <span class="text-[9px] font-black uppercase tracking-[0.4em] text-brand-indigo animate-pulse">🎲 System Icebreaker</span>
                    <div class="h-px w-8 bg-brand-indigo/30"></div>
                </div>

                <h2 class="text-lg md:text-2xl font-black italic tracking-tight text-white/95 leading-tight" 
                    x-text="icebreakerQuestion">
                </h2>

                <div class="w-full h-0.5 bg-white/5 rounded-full mt-2 overflow-hidden">
                    <div class="h-full bg-brand-indigo shadow-[0_0_10px_#6366f1]"
                         x-show="showIcebreakerOverlay"
                         x-transition:enter="transition-all linear duration-[12000ms]"
                         x-transition:enter-start="w-full"
                         x-transition:enter-end="w-0">
                    </div>
                </div>

                <button @click="showIcebreakerOverlay = false" 
                        class="absolute top-4 right-4 text-gray-600 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>