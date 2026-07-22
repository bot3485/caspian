<div @click="toggleFocus('remote')"
     :class="{
         'blitz-hell-logic': isBlitzActive,
         'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
         'h-[70%] md:h-full md:w-[75%] z-10': layoutFocus === 'remote',
         'h-[30%] md:h-full md:w-[25%] opacity-50 grayscale-[30%] hover:opacity-80': layoutFocus === 'local'
     }"
     class="elegant-glass led-container-fx relative rounded-[2.5rem] transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] cursor-pointer group overflow-hidden">
    
    <!-- Внутренний контейнер видео -->
    <div class="relative w-full h-full rounded-[2.4rem] overflow-hidden bg-[#050505] shadow-[inset_0_0_30px_rgba(0,0,0,0.8)] z-10">
        
        <!-- НОВОЕ: Внутреннее контурное LED кольцо (Синхронизировано с body.leds-on) -->
        <div class="inner-video-ring"></div>
            
        <video x-ref="remoteVideo" id="remoteVideo" autoplay playsinline webkit-playsinline 
            :class="[getFilterClass('remote'), { 'blitz-hell-video': isBlitzActive, 'opacity-30 blur-xl scale-110': isRemoteBlurred && !isBlitzActive }]"
            class="absolute inset-0 w-full h-full object-cover transition-all duration-1000 bg-[#050505] z-0">
        </video>

        <div x-show="isBlitzActive" class="absolute inset-0 bg-red-900/40 mix-blend-color-burn pointer-events-none z-10 animate-pulse" x-cloak></div>

        <!-- Privacy Mode -->
        <div x-show="isRemoteBlurred" x-transition:enter="transition opacity duration-500" x-transition:leave="transition opacity duration-500" class="absolute inset-0 z-20 bg-black/50 backdrop-blur-[40px] flex flex-col items-center justify-center" x-cloak>
            <div class="relative">
                <div class="absolute inset-0 bg-brand-indigo/30 rounded-full blur-3xl animate-pulse"></div>
                <span class="relative text-6xl mb-6 block drop-shadow-2xl">🙈</span>
            </div>
            <span class="text-[10px] font-black uppercase tracking-[0.6em] text-white/50 italic animate-pulse">Privacy Mode</span>
        </div>
        
        <!-- SEARCH OVERLAY -->
        <div x-show="state === 'searching' || state === 'idle'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505]/90 backdrop-blur-sm z-30">
            <div class="relative w-28 h-28 mb-8">
                <div class="absolute inset-0 border-[3px] border-brand-indigo/30 rounded-full animate-[ping_2s_cubic-bezier(0,0,0.2,1)_infinite]"></div>
                <div class="absolute inset-2 border-[2px] border-brand-cyan/20 rounded-full animate-[ping_3s_cubic-bezier(0,0,0.2,1)_infinite] animation-delay-500"></div>
                <div class="absolute inset-0 flex items-center justify-center text-5xl bg-black/50 rounded-full backdrop-blur-md shadow-[0_0_30px_rgba(99,102,241,0.2)] border border-white/5">🛰️</div>
            </div>
            <p class="text-[10px] md:text-xs font-black uppercase tracking-[0.6em] text-brand-indigo animate-pulse italic drop-shadow-[0_0_10px_rgba(99,102,241,0.5)]" x-text="state === 'searching' ? '{{ __('chatroulette.Searching') }}...' : '{{ __('chatroulette.Ready_To_Start') }}'"></p>
        </div>

        <!-- ИНТЕРФЕЙСНЫЕ ВИДЖЕТЫ -->
        <div x-show="state === 'idle' || state === 'searching'" class="absolute top-5 right-5 md:top-6 md:right-6 z-40 pointer-events-auto" x-data="{ countryDropdown: false }">
            <button @click.stop="countryDropdown = !countryDropdown" class="px-4 py-2.5 bg-black/70 backdrop-blur-2xl rgb-led-border border-transparent rounded-2xl flex items-center gap-2 text-[10px] font-black uppercase tracking-wider hover:border-brand-indigo/50 hover:bg-brand-indigo/10 transition-all shadow-2xl">
                <span x-text="countryNames[targetCountry] || '🌍 {{__('chatroulette.Global_Match')}}'"></span>
                <span class="text-[7px] text-gray-500 transition-transform duration-300" :class="countryDropdown ? 'rotate-180 text-brand-indigo' : ''">▼</span>
            </button>
            <div x-show="countryDropdown" @click.away="countryDropdown = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" class="absolute right-0 mt-3 bg-[#0a0a0a]/95 backdrop-blur-3xl rgb-led-border border-transparent rounded-[1.5rem] p-2 space-y-1 shadow-[0_30px_60px_rgba(0,0,0,0.8)] z-50 max-h-64 overflow-y-auto custom-scrollbar min-w-[160px]">
                <button @click.stop="updateTargetCountry('global'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-brand-indigo/90 text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                    <img src="https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg" class="w-4 h-3.5 object-cover" crossorigin="anonymous" loading="lazy" alt="global"> {{__('chatroulette.Global_Match')}}
                </button>
                <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent mx-2 my-1"></div>
                @foreach(['az', 'ge', 'am', 'ru', 'kz', 'uz', 'ua', 'tr', 'de', 'gb', 'fr', 'it', 'es', 'pl', 'us', 'ca'] as $code)
                    <button @click.stop="updateTargetCountry('{{$code}}'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                        <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/{{$code}}.svg" class="w-4 h-3 object-cover rounded-sm shadow-sm" crossorigin="anonymous" loading="lazy">
                        {{ strtoupper($code) }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- АВАТАР И ФЛАГ -->
        <div x-show="state === 'connected' && partnerData" class="absolute top-5 left-5 md:top-6 md:left-6 z-40 flex items-start pointer-events-none" x-cloak>
            <div class="relative w-14 h-14 md:w-16 md:h-16 shrink-0 pointer-events-auto block z-10 hover:scale-105 transition-transform duration-300" style="transform: translateZ(0);">
                
                <!-- Аура реагирует на leds-on -->
                <div x-show="partnerData" class="absolute -inset-3 rounded-full blur-[15px] pointer-events-none z-0 gender-aura"
                     :style="{ backgroundColor: partnerData?.ban_count > 0 ? '#ef4444' : (partnerData?.gender === 'female' ? '#ec4899' : '#3b82f6') }"></div>

                <!-- Кольцо реагирует на leds-on -->
                <div class="gender-ring-wrapper">
                    <div class="gender-ring"
                         :style="partnerData?.ban_count > 0 ? { background: 'conic-gradient(from 0deg, transparent, #ef4444, transparent)' } : { background: partnerData?.gender === 'female' ? 'conic-gradient(from 0deg, transparent, rgba(236,72,153,0.8), transparent)' : 'conic-gradient(from 0deg, transparent, rgba(59,130,246,0.8), transparent)' }"></div>
                </div>
                
                <!-- САМ ФЛАГ -->
                <button @click.stop.prevent="uiShowPartnerCard = !uiShowPartnerCard" type="button" class="relative w-full h-full rounded-[1.4rem] overflow-hidden border border-white/10 shadow-xl transition-all duration-300 active:scale-95 group bg-[#020202] focus:outline-none z-20">
                    <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors z-10"></div>
                    <img :src="partnerData?.country_flag" class="w-full h-full object-cover transition-all duration-700 opacity-90 group-hover:scale-110 group-hover:opacity-100" crossorigin="anonymous">
                </button>
                
            </div>
        </div>

        <div x-show="state === 'connected'" class="absolute top-24 right-5 md:top-24 md:right-6 z-40 flex items-center pointer-events-none" x-cloak>
            <div @click.stop="timerExpanded = !timerExpanded" class="group flex items-center gap-3 px-3 py-2.5 rounded-2xl border border-white/[0.05] bg-black/60 backdrop-blur-2xl cursor-pointer transition-all duration-500 hover:bg-white/10 hover:border-white/20 shadow-xl pointer-events-auto" :class="timerExpanded ? 'max-w-[150px] pr-5' : 'max-w-[48px] pr-3.5'">
                <div class="relative flex shrink-0">
                    <span class="text-sm transition-transform duration-500" :class="timerExpanded ? 'rotate-12 scale-110' : ''">⏱️</span>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-brand-indigo rounded-full shadow-[0_0_8px_#6366f1] animate-pulse border border-[#020202]"></span>
                </div>
                <div x-show="timerExpanded" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" class="flex flex-col">
                    <span class="text-[11px] font-black font-mono tracking-widest text-white/95 leading-none drop-shadow-md" x-text="formatCallTime()"></span>
                    <span class="text-[6px] font-black uppercase tracking-[0.2em] text-gray-500 mt-1">Duration</span>
                </div>
            </div>
        </div>

        <div x-show="state === 'connected'" class="absolute top-5 right-5 md:top-6 md:right-6 px-4 py-2.5 flex items-center gap-3 rounded-2xl border transition-all duration-500 backdrop-blur-2xl shadow-2xl z-40" :class="{ 'hud-no-network': partnerState === 'problem', 'hud-away': partnerState === 'away', 'bg-black/60 border-white/[0.05]': partnerState === 'active' }">
            <div class="relative flex h-2.5 w-2.5">
                <span class="absolute inline-flex h-full w-full rounded-full opacity-75" :class="{ 'animate-ping bg-red-500': partnerState === 'problem', 'animate-pulse bg-amber-500': partnerState === 'away', 'bg-green-500': partnerState === 'active' }"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 shadow-sm" :class="{ 'bg-red-600': partnerState === 'problem', 'bg-amber-500': partnerState === 'away', 'bg-green-500': partnerState === 'active' }"></span>
            </div>
            <div class="flex flex-col">
                <template x-if="partnerState === 'problem'"><span class="text-[9px] font-black tracking-[0.2em] uppercase text-white animate-pulse">{{ __('chatroulette.No_Signal') }}</span></template>
                <template x-if="partnerState === 'away'"><span class="text-[9px] font-black tracking-[0.2em] uppercase text-amber-500">{{ __('chatroulette.User_Away') }}</span></template>
                <template x-if="partnerState === 'active'">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-black tracking-[0.12em] uppercase font-mono" :class="ping < 150 ? 'text-green-400 drop-shadow-[0_0_5px_rgba(74,222,128,0.5)]' : 'text-red-400 drop-shadow-[0_0_5px_rgba(248,113,113,0.5)]'" x-text="ping + ' ms'"></span>
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest">{{ __('chatroulette.Latency') }}</span>
                    </div>
                </template>
            </div>
        </div>
        
    </div>
</div>