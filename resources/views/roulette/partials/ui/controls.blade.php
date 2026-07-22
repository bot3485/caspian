<div class="fixed bottom-24 md:bottom-8 left-0 right-0 px-6 z-[500] flex flex-col items-center gap-5 pointer-events-none"
     :class="globalSidebarOpen ? 'max-md:opacity-0 max-md:translate-y-10 max-md:pointer-events-none' : 'opacity-100 transition-all duration-500'">
    
    <!-- TOOL GRID -->
    <div x-show="controlsOpen" 
         x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-500" x-transition:enter-start="opacity-0 translate-y-12 scale-90"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-end="opacity-0 translate-y-12 scale-90"
         class="pointer-events-auto w-full max-w-[380px] elegant-glass led-container-fx rounded-[2.5rem] p-4">
        
        <div class="grid grid-cols-3 gap-3 relative z-10">
            <button @click="openDeviceSettings()" class="btn-glass flex flex-col items-center justify-center gap-2 h-20 w-full group">
                <span class="text-xl group-hover:scale-110 transition-transform">⚙️</span>
                <span class="text-[7px] font-black uppercase tracking-widest text-gray-400 group-hover:text-white">{{ __('chatroulette.Hardware') }}</span>
            </button>
            <button @click="toggleMic()" :class="micEnabled ? 'btn-glass' : 'btn-glass btn-glass-danger'" class="flex flex-col items-center justify-center gap-2 h-20 w-full group">
                <span class="text-xl group-hover:scale-110 transition-transform" x-text="micEnabled ? '🎤' : '🔇'"></span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-80" :class="!micEnabled ? 'text-red-400' : 'text-gray-400'">{{ __('chatroulette.Mute') }}</span>
            </button>
            <button @click="toggleCam()" :class="camEnabled ? 'btn-glass' : 'btn-glass btn-glass-danger'" class="flex flex-col items-center justify-center gap-2 h-20 w-full group">
                <span class="text-xl group-hover:scale-110 transition-transform" x-text="camEnabled ? '📷' : '🚫'"></span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-80" :class="!camEnabled ? 'text-red-400' : 'text-gray-400'">{{ __('chatroulette.Hide_Yourself') }}</span>
            </button>
            
            <button @click="filterModalOpen = true" class="btn-glass flex flex-col items-center justify-center gap-2 h-20 w-full group">
                <span class="text-xl group-hover:scale-110 transition-transform">🎯</span>
                <span class="text-[7px] font-black uppercase tracking-widest text-gray-400 group-hover:text-white">{{ __('chatroulette.Target') }}</span>
            </button>
            <button @click="toggleBeauty()" :class="beautyFilter ? 'btn-glass shadow-[0_0_15px_rgba(219,39,119,0.3)] border-pink-500/30' : 'btn-glass'" class="flex flex-col items-center justify-center gap-1.5 h-16 w-full group">
                <span class="text-lg">✨</span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-60 text-gray-400">{{ __('chatroulette.Contrast') }}</span>
            </button>
            <button @click="toggleCinema()" :class="cinemaFilter ? 'btn-glass shadow-[0_0_15px_rgba(217,119,6,0.3)] border-amber-500/30' : 'btn-glass'" class="flex flex-col items-center justify-center gap-1.5 h-16 w-full group">
                <span class="text-lg">🎬</span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-60 text-gray-400">{{ __('chatroulette.Monochrome') }}</span>
            </button>
            
            <button @click="isRemoteBlurred = !isRemoteBlurred" :class="isRemoteBlurred ? 'btn-glass border-brand-indigo/40' : 'btn-glass'" class="flex flex-col items-center justify-center gap-2 h-20 w-full group">
                <span class="text-xl group-hover:scale-110 transition-transform">🙈</span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-80" :class="isRemoteBlurred ? 'text-brand-indigo' : 'text-gray-400'">{{ __('chatroulette.Hide_Interlocutor') }}</span>
            </button>
            
            <button @click="sendIcebreaker()" :disabled="icebreakerCooldown > 0 || state !== 'connected'" class="btn-glass flex flex-col items-center justify-center gap-2 h-20 w-full group" :class="icebreakerCooldown > 0 ? 'opacity-40 cursor-not-allowed' : ''">
                <span class="text-xl" :class="icebreakerCooldown > 0 ? '' : 'group-hover:animate-spin'" x-text="icebreakerCooldown > 0 ? '⏳' : '🎲'"></span>
                <span class="text-[7px] font-black uppercase tracking-widest" :class="icebreakerCooldown > 0 ? 'text-gray-600' : 'text-gray-400 group-hover:text-brand-indigo'" x-text="icebreakerCooldown > 0 ? icebreakerCooldown + 's' : '{{ __('chatroulette.Cube') }}'"></span>
            </button>

            <button @click="triggerBlitz()" :disabled="blitzCooldown > 0 || isBlitzActive || state !== 'connected'" class="btn-glass flex flex-col items-center justify-center gap-2 h-20 w-full group" :class="isBlitzActive ? 'btn-glass-danger animate-[pulse_0.5s_infinite]' : ''">
                <span class="text-xl" :class="blitzCooldown > 0 ? 'opacity-40' : 'group-hover:scale-[1.3] transition-transform'" x-text="blitzCooldown > 0 ? '⌛' : '⚡'"></span>
                <span class="text-[7px] font-black uppercase tracking-widest" :class="blitzCooldown > 0 ? 'text-gray-600' : 'text-gray-400 group-hover:text-yellow-500'" x-text="blitzCooldown > 0 ? blitzCooldown + 's' : '{{ __('chatroulette.Tension') }}'"></span>
            </button>

            <template x-if="callContext !== 'personal'">
                <div class="col-span-3 grid grid-cols-4 gap-2.5 mt-2 pt-3 border-t border-white/10">
                    <button @click="toggleContact()" 
                            :disabled="isProcessingContact"
                            :class="isFriend ? 'btn-glass btn-glass-danger' : 'btn-glass'" 
                            class="col-span-3 h-14 font-black text-[9px] md:text-[10px] uppercase tracking-[0.25em] flex items-center justify-center gap-2 disabled:opacity-50 transition-all">
                        <span x-text="isFriend ? '✕  {{ __('chatroulette.Remove_Friend') }}' : '+  {{ __('chatroulette.Add_Friend') }}'"></span>
                    </button>
                    <button @click="reportPartner()" class="col-span-1 h-14 btn-glass btn-glass-danger flex items-center justify-center text-lg group">
                        <span class="group-hover:scale-110 transition-transform">🚩</span>
                    </button>
                </div>
            </template>
            </template>
        </div>
    </div>

    <!-- MAIN ACTION BAR -->
    <div class="pointer-events-auto flex items-center transition-all duration-[600ms] ease-[cubic-bezier(0.34,1.56,0.64,1)]"
         :class="actionsOpen ? 'w-full max-w-[420px] elegant-glass led-container-fx rounded-[3rem] p-2' : 'w-16 h-16 elegant-glass rounded-full hover:scale-110'">
        
        <div x-show="actionsOpen" x-transition:enter="delay-300 opacity-0" class="shrink-0 pl-1">
            <button @click="controlsOpen = !controlsOpen" class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 btn-glass border-none" :class="controlsOpen ? 'bg-white/10 rotate-180' : ''">
                <span class="text-[9px] text-white" x-text="controlsOpen ? '▼' : '⚡'"></span>
            </button>
        </div>
        
        <div class="flex-1 flex justify-center px-3 overflow-hidden transition-all duration-500" x-show="actionsOpen" x-transition:enter="transition delay-300 duration-500 opacity-0 scale-95" x-transition:leave="transition duration-200 opacity-0 scale-90">
            <template x-if="callContext !== 'personal'">
                <div class="w-full flex gap-3">
                    <button x-show="state === 'idle'" @click="startSearch()" class="btn-action-primary w-full py-4 rounded-full text-[11px] font-black uppercase tracking-widest">{{ __('chatroulette.Start_Connect') }}</button>
                    <button x-show="state === 'searching'" @click="stopCall()" class="w-full py-4 btn-glass btn-glass-danger rounded-full font-black text-[10px] uppercase tracking-widest">{{ __('chatroulette.Abort') }}</button>
                    <div x-show="state === 'connected'" class="flex items-center gap-3 w-full">
                        <button @click="stopCall()" class="btn-glass px-6 py-4 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-red-500/20 hover:text-red-400 hover:border-red-500/30">{{ __('chatroulette.Stop') }}</button>
                        <button @click="startSearch()" class="btn-action-primary flex-1 py-4 rounded-full italic font-black text-[11px] uppercase tracking-widest">{{ __('chatroulette.Next') }} ➔</button>
                    </div>
                </div>
            </template>
        </div>

        <button @click="actionsOpen = !actionsOpen; if(!actionsOpen) controlsOpen = false" class="transition-all duration-500 shrink-0 flex items-center justify-center" :class="actionsOpen ? 'w-14 h-14 rounded-full btn-glass border-none mr-1' : 'w-full h-full rounded-full text-brand-indigo shadow-inner'">
            <template x-if="actionsOpen"><span class="text-[12px] font-bold text-white">⊙</span></template>
            <template x-if="!actionsOpen">
                <div class="relative flex items-center justify-center">
                    <span class="text-2xl drop-shadow-[0_0_15px_rgba(99,102,241,0.9)]">💠</span>
                </div>
            </template>
        </button>
    </div>
</div>