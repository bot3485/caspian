<div class="fixed bottom-24 md:bottom-8 left-0 right-0 px-6 z-[500] flex flex-col items-center gap-5 pointer-events-none"
     :class="globalSidebarOpen ? 'max-md:opacity-0 max-md:translate-y-10 max-md:pointer-events-none' : 'opacity-100 transition-all duration-500'">
    
    <!-- TOOL GRID -->
    <div x-show="controlsOpen" 
         x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-500" x-transition:enter-start="opacity-0 translate-y-12 scale-90"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-end="opacity-0 translate-y-12 scale-90"
         class="pointer-events-auto w-full max-w-[380px] bg-[#0a0a0a]/80 backdrop-blur-3xl rounded-[2.5rem] p-4 border border-white/10 shadow-[0_30px_80px_rgba(0,0,0,0.8)]">
        
        <div class="grid grid-cols-3 gap-2.5">
            <button @click="openDeviceSettings()" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] bg-white/[0.03] hover:bg-white/10 border border-white/5 hover:border-white/20 transition-all group">
                <span class="text-xl group-hover:scale-110 transition-transform">⚙️</span>
                <span class="text-[7px] font-black uppercase tracking-widest text-gray-500 group-hover:text-white">{{ __('chatroulette.Hardware') }}</span>
            </button>
            <button @click="toggleMic()" :class="micEnabled ? 'bg-white/[0.03] border-white/5' : 'bg-red-600/90 border-red-500 shadow-[0_0_20px_rgba(220,38,38,0.4)]'" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] border hover:border-white/20 transition-all group">
                <span class="text-xl group-hover:scale-110 transition-transform" x-text="micEnabled ? '🎤' : '🔇'"></span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-80" :class="!micEnabled ? 'text-white' : 'text-gray-500'">{{ __('chatroulette.Mute') }}</span>
            </button>
            <button @click="toggleCam()" :class="camEnabled ? 'bg-white/[0.03] border-white/5' : 'bg-red-600/90 border-red-500 shadow-[0_0_20px_rgba(220,38,38,0.4)]'" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] border hover:border-white/20 transition-all group">
                <span class="text-xl group-hover:scale-110 transition-transform" x-text="camEnabled ? '📷' : '🚫'"></span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-80" :class="!camEnabled ? 'text-white' : 'text-gray-500'">{{ __('chatroulette.Hide_Yourself') }}</span>
            </button>
            
            <button @click="filterModalOpen = true" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] bg-white/[0.03] hover:bg-brand-indigo/20 border border-white/5 hover:border-brand-indigo/30 transition-all group">
                <span class="text-xl group-hover:scale-110 transition-transform">🎯</span>
                <span class="text-[7px] font-black uppercase tracking-widest text-gray-500 group-hover:text-brand-indigo">{{ __('chatroulette.Target') }}</span>
            </button>
            <button @click="toggleBeauty()" :class="beautyFilter ? 'bg-pink-600 shadow-[0_0_20px_rgba(219,39,119,0.4)] border-pink-500' : 'bg-white/[0.03] border-white/5'" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] border hover:border-white/20 transition-all group">
                <span class="text-xl group-hover:scale-110 transition-transform">✨</span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-80" :class="beautyFilter ? 'text-white' : 'text-gray-500'">{{ __('chatroulette.Contrast') }}</span>
            </button>
            <button @click="toggleCinema()" :class="cinemaFilter ? 'bg-amber-600 shadow-[0_0_20px_rgba(217,119,6,0.4)] border-amber-500' : 'bg-white/[0.03] border-white/5'" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] border hover:border-white/20 transition-all group">
                <span class="text-xl group-hover:scale-110 transition-transform">🎬</span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-80" :class="cinemaFilter ? 'text-white' : 'text-gray-500'">{{ __('chatroulette.Monochrome') }}</span>
            </button>
            
            <button @click="isRemoteBlurred = !isRemoteBlurred" :class="isRemoteBlurred ? 'bg-brand-indigo shadow-[0_0_20px_rgba(99,102,241,0.5)] border-brand-indigo' : 'bg-white/[0.03] border-white/5'" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] border hover:border-white/20 transition-all group">
                <span class="text-xl group-hover:scale-110 transition-transform">🙈</span>
                <span class="text-[7px] font-black uppercase tracking-widest opacity-80" :class="isRemoteBlurred ? 'text-white' : 'text-gray-500'">{{ __('chatroulette.Hide_Interlocutor') }}</span>
            </button>
            
            <button @click="sendIcebreaker()" :disabled="icebreakerCooldown > 0 || state !== 'connected'" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] border transition-all group" :class="icebreakerCooldown > 0 ? 'bg-white/[0.01] border-white/5 opacity-40 cursor-not-allowed' : 'bg-white/[0.03] border-white/5 hover:bg-brand-indigo/20 hover:border-brand-indigo/30'">
                <span class="text-xl" :class="icebreakerCooldown > 0 ? '' : 'group-hover:animate-spin'" x-text="icebreakerCooldown > 0 ? '⏳' : '🎲'"></span>
                <span class="text-[7px] font-black uppercase tracking-widest" :class="icebreakerCooldown > 0 ? 'text-gray-600' : 'text-gray-500 group-hover:text-brand-indigo'" x-text="icebreakerCooldown > 0 ? icebreakerCooldown + 's' : '{{ __('chatroulette.Cube') }}'"></span>
            </button>

            <button @click="triggerBlitz()" :disabled="blitzCooldown > 0 || isBlitzActive || state !== 'connected'" class="flex flex-col items-center justify-center gap-2 h-20 rounded-[1.5rem] border transition-all group" :class="isBlitzActive ? 'bg-red-600 border-red-500 animate-[pulse_0.5s_infinite] shadow-[0_0_30px_rgba(220,38,38,0.8)]' : 'bg-white/[0.03] border-white/5 hover:bg-yellow-500/20 hover:border-yellow-500/40'">
                <span class="text-xl" :class="blitzCooldown > 0 ? 'opacity-40' : 'group-hover:scale-[1.3] transition-transform'" x-text="blitzCooldown > 0 ? '⌛' : '⚡'"></span>
                <span class="text-[7px] font-black uppercase tracking-widest" :class="blitzCooldown > 0 ? 'text-gray-600' : 'text-gray-500 group-hover:text-yellow-500'" x-text="blitzCooldown > 0 ? blitzCooldown + 's' : '{{ __('chatroulette.Tension') }}'"></span>
            </button>

            <template x-if="callContext !== 'personal'">
                <div class="col-span-3 grid grid-cols-4 gap-2.5 mt-2 pt-3 border-t border-white/10">
                    <button @click="toggleContact()" :class="isFriend ? 'bg-red-600/10 text-red-500 border border-red-500/30 hover:bg-red-600 hover:text-white hover:shadow-[0_0_20px_rgba(220,38,38,0.4)]' : 'bg-brand-indigo/20 text-brand-indigo border border-brand-indigo/30 hover:bg-brand-indigo hover:text-white hover:shadow-[0_0_20px_rgba(99,102,241,0.4)]'" class="col-span-3 h-14 rounded-2xl font-black text-[9px] md:text-[10px] uppercase tracking-[0.25em] transition-all flex items-center justify-center gap-2">
                        <span x-text="isFriend ? '✕  {{__('chatroulette.Remove_Friend')}} ' : '+  {{__('chatroulette.Add_Friend')}} '"></span>
                    </button>
                    <button @click="reportPartner()" class="col-span-1 h-14 bg-red-600/10 border border-red-500/30 text-red-500 rounded-2xl flex items-center justify-center hover:bg-red-600 hover:text-white hover:shadow-[0_0_20px_rgba(220,38,38,0.4)] transition-all text-lg group">
                        <span class="group-hover:scale-110 transition-transform">🚩</span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- MAIN ACTION BAR -->
    <div class="pointer-events-auto flex items-center transition-all duration-[600ms] ease-[cubic-bezier(0.34,1.56,0.64,1)]"
         :class="actionsOpen ? 'w-full max-w-[420px] bg-[#080808]/95 backdrop-blur-3xl border border-white/10 rounded-[3rem] p-2 shadow-[0_40px_80px_rgba(0,0,0,0.9)]' : 'w-16 h-16 bg-brand-indigo/20 backdrop-blur-xl border border-brand-indigo/40 rounded-full shadow-[0_0_30px_rgba(99,102,241,0.4)] hover:scale-110 hover:bg-brand-indigo/40'">
        
        <div x-show="actionsOpen" x-transition:enter="delay-300 opacity-0" class="shrink-0 pl-1">
            <button @click="controlsOpen = !controlsOpen" class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300" :class="controlsOpen ? 'bg-brand-indigo text-white rotate-180 shadow-[0_0_20px_rgba(99,102,241,0.5)]' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white'">
                <span class="text-[9px]" x-text="controlsOpen ? '▼' : '⚡'"></span>
            </button>
        </div>
        
        <div class="flex-1 flex justify-center px-3 overflow-hidden transition-all duration-500" x-show="actionsOpen" x-transition:enter="transition delay-300 duration-500 opacity-0 scale-95" x-transition:leave="transition duration-200 opacity-0 scale-90">
            <template x-if="callContext !== 'personal'">
                <div class="w-full flex gap-3">
                    <button x-show="state === 'idle'" @click="startSearch()" class="btn-primary w-full !py-4 !rounded-full !text-[11px] shadow-[0_0_30px_rgba(99,102,241,0.3)]">{{ __('chatroulette.Start_Connect') }}</button>
                    <button x-show="state === 'searching'" @click="stopCall()" class="w-full py-4 bg-red-600/10 hover:bg-red-600/20 text-red-500 rounded-full font-black text-[10px] uppercase tracking-widest border border-red-500/30 transition-all">{{ __('chatroulette.Abort') }}</button>
                    <div x-show="state === 'connected'" class="flex items-center gap-3 w-full">
                        <button @click="stopCall()" class="bg-white/5 text-gray-400 px-6 py-4 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-red-600/20 hover:text-red-500 hover:border-red-500/30 border border-transparent transition-all">{{ __('chatroulette.Stop') }}</button>
                        <button @click="startSearch()" class="btn-primary flex-1 !py-4 !rounded-full italic shadow-[0_0_30px_rgba(99,102,241,0.4)] text-[11px]">{{ __('chatroulette.Next') }} ➔</button>
                    </div>
                </div>
            </template>
            <template x-if="callContext === 'personal'">
                <button @click="stopCall()" class="bg-red-600 text-white w-full py-4 rounded-full font-black text-[10px] uppercase tracking-[0.3em] shadow-[0_0_30px_rgba(220,38,38,0.5)] hover:bg-red-500 transition-all">{{ __('chatroulette.End_Call') }}</button>
            </template>
        </div>

        <button @click="actionsOpen = !actionsOpen; if(!actionsOpen) controlsOpen = false" class="transition-all duration-500 shrink-0 flex items-center justify-center" :class="actionsOpen ? 'w-14 h-14 rounded-full bg-white/5 text-gray-500 hover:bg-white/10 hover:text-white mr-1' : 'w-full h-full rounded-full text-brand-indigo shadow-inner'">
            <template x-if="actionsOpen"><span class="text-[12px] font-bold">⊙</span></template>
            <template x-if="!actionsOpen">
                <div class="relative flex items-center justify-center">
                    <div class="absolute inset-0 bg-brand-indigo rounded-full animate-[ping_2s_ease-out_infinite] opacity-30"></div>
                    <span class="text-2xl drop-shadow-[0_0_15px_rgba(99,102,241,0.9)]">💠</span>
                </div>
            </template>
        </button>
    </div>
</div>