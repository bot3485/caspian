<div @click="toggleFocus('remote')"
     :class="{
         'blitz-hell-logic': isBlitzActive,
         'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
         'h-[70%] md:h-full md:w-[75%] z-10': layoutFocus === 'remote',
         'h-[30%] md:h-full md:w-[25%] opacity-50 grayscale-[30%] hover:opacity-80': layoutFocus === 'local'
     }"
     class="elegant-glass led-container-fx relative rounded-[2.5rem] transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] cursor-pointer group">
    
    <!-- ВНУТРЕННИЙ КОНТЕЙНЕР (Обрезает только видео) -->
    <div class="absolute inset-0 rounded-[2.4rem] overflow-hidden bg-[#050505] shadow-[inset_0_0_30px_rgba(0,0,0,0.8)] z-10">
        <div class="inner-video-ring"></div>
            
        <video x-ref="remoteVideo" id="remoteVideo" autoplay playsinline webkit-playsinline 
            :class="[getFilterClass('remote'), { 'blitz-hell-video': isBlitzActive, 'opacity-30 blur-xl scale-110': isRemoteBlurred && !isBlitzActive }]"
            class="absolute inset-0 w-full h-full object-cover transition-all duration-1000 bg-[#050505] z-0">
        </video>

        <div x-show="isBlitzActive" class="absolute inset-0 bg-red-900/40 mix-blend-color-burn pointer-events-none z-10 animate-pulse" x-cloak></div>

        <!-- РЕЖИМ ПРИВАТНОСТИ -->
        <div x-show="isRemoteBlurred" x-transition class="absolute inset-0 z-20 bg-black/50 backdrop-blur-[40px] flex flex-col items-center justify-center" x-cloak>
            <div class="relative">
                <div class="absolute inset-0 bg-brand-indigo/30 rounded-full blur-3xl animate-pulse"></div>
                <span class="relative text-5xl md:text-6xl mb-6 block drop-shadow-2xl">🙈</span>
            </div>
            <span class="text-[9px] md:text-[10px] font-black uppercase tracking-[0.6em] text-white/50 italic animate-pulse">Privacy Mode</span>
        </div>

        <!-- ОВЕРЛЕЙ "ПАРТНЕР СКРЫЛ КАМЕРУ" -->
        <div x-show="!partnerCamEnabled" class="absolute inset-0 bg-[#020202]/95 backdrop-blur-xl flex flex-col items-center justify-center z-30 transition-all duration-500" x-cloak>
            <span class="text-4xl mb-4 drop-shadow-2xl opacity-80">🚫</span>
            <span class="text-[9px] md:text-[10px] font-black uppercase text-gray-500 tracking-[0.4em] italic drop-shadow-md">User Hidden</span>
        </div>

        <!-- РАДАР (ЭКРАН ПОИСКА) -->
        <div x-show="state === 'searching' || state === 'idle'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505]/90 backdrop-blur-sm z-30">
            <div class="relative w-24 h-24 md:w-28 md:h-28 mb-8">
                <div class="absolute inset-0 border-[3px] border-brand-indigo/30 rounded-full animate-[ping_2s_cubic-bezier(0,0,0.2,1)_infinite]"></div>
                <div class="absolute inset-2 border-[2px] border-brand-cyan/20 rounded-full animate-[ping_3s_cubic-bezier(0,0,0.2,1)_infinite] animation-delay-500"></div>
                <div class="absolute inset-0 flex items-center justify-center text-4xl md:text-5xl bg-black/50 rounded-full backdrop-blur-md shadow-[0_0_30px_rgba(99,102,241,0.2)] border border-white/5">🛰️</div>
            </div>
            <p class="text-[9px] md:text-[10px] font-black uppercase tracking-[0.6em] text-brand-indigo animate-pulse italic drop-shadow-[0_0_10px_rgba(99,102,241,0.5)]" x-text="state === 'searching' ? '{{ __('chatroulette.Searching') }}...' : '{{ __('chatroulette.Ready_To_Start') }}'"></p>
        </div>
    </div>

    <!-- ВИДЖЕТЫ ПОВЕРХ ВИДЕО (Безопасный слой) -->
    <div class="absolute inset-0 z-40 pointer-events-none">
        
        <!-- АВАТАР И ФЛАГ СОБЕСЕДНИКА (Прижат в самый верхний левый угол: top-3) -->
        <div x-show="state === 'connected' && partnerData" class="absolute top-3 left-3 md:top-6 md:left-6 flex items-start pointer-events-none" x-cloak>
            <div class="relative w-12 h-12 md:w-16 md:h-16 shrink-0 pointer-events-auto block hover:scale-105 transition-transform duration-300">
                <div x-show="partnerData" class="absolute -inset-2 md:-inset-3 rounded-full blur-[15px] pointer-events-none z-0 gender-aura" :style="{ backgroundColor: partnerData?.ban_count > 0 ? '#ef4444' : (partnerData?.gender === 'female' ? '#ec4899' : '#3b82f6') }"></div>
                <div class="gender-ring-wrapper">
                    <div class="gender-ring" :style="partnerData?.ban_count > 0 ? { background: 'conic-gradient(from 0deg, transparent, #ef4444, transparent)' } : { background: partnerData?.gender === 'female' ? 'conic-gradient(from 0deg, transparent, rgba(236,72,153,0.8), transparent)' : 'conic-gradient(from 0deg, transparent, rgba(59,130,246,0.8), transparent)' }"></div>
                </div>
                <button @click.stop.prevent="uiShowPartnerCard = !uiShowPartnerCard" type="button" class="relative w-full h-full rounded-[1.2rem] md:rounded-[1.4rem] overflow-hidden border border-white/10 shadow-xl transition-all duration-300 active:scale-95 group bg-[#020202] focus:outline-none z-20">
                    <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors z-10"></div>
                    <img :src="partnerData?.country_flag" class="w-full h-full object-cover transition-all duration-700 opacity-90 group-hover:scale-110 group-hover:opacity-100" crossorigin="anonymous">
                </button>
            </div>
        </div>

        <!-- ИКОНКА "ПАРТНЕР ВЫКЛЮЧИЛ МИКРОФОН" -->
        <div x-show="!partnerMicEnabled && partnerCamEnabled" class="absolute top-3 md:top-6 left-1/2 -translate-x-1/2 bg-red-600/80 backdrop-blur-md px-2.5 py-1 md:px-3 md:py-1.5 rounded-full z-40 shadow-lg flex items-center gap-1.5" x-cloak>
            <span class="text-xs md:text-sm">🔇</span>
            <span class="text-[7px] md:text-[8px] font-black uppercase tracking-widest text-white">Muted</span>
        </div>

        <!-- ПРАВЫЙ БЛОК (Прижат в самый верхний правый угол: top-3) -->
        <div class="absolute top-3 right-3 md:top-6 md:right-6 flex flex-col items-end gap-2.5">
            
            <!-- ВЫБОР СТРАНЫ ДЛЯ ПОИСКА (Открывается строго ВНИЗ) -->
            <div x-show="state === 'idle' || state === 'searching'" class="relative pointer-events-auto" x-data="{ countryDropdown: false }">
                <button @click.stop="countryDropdown = !countryDropdown" class="px-3 py-2 md:px-4 md:py-2.5 bg-black/70 backdrop-blur-2xl rgb-led-border border-transparent rounded-[1rem] md:rounded-2xl flex items-center gap-2 text-[9px] md:text-[10px] font-black uppercase tracking-wider hover:border-brand-indigo/50 hover:bg-brand-indigo/10 transition-all shadow-2xl">
                    <span x-text="countryNames[targetCountry] || '🌍 {{__('chatroulette.Global_Match')}}'"></span>
                    <span class="text-[6px] md:text-[7px] text-gray-500 transition-transform duration-300" :class="countryDropdown ? 'rotate-180 text-brand-indigo' : ''">▼</span>
                </button>
                
                <div x-show="countryDropdown" @click.away="countryDropdown = false" 
                     x-transition:enter="transition ease-out duration-200 origin-top"
                     x-transition:enter-start="opacity-0 scale-y-75 -translate-y-2"
                     x-transition:enter-end="opacity-100 scale-y-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150 origin-top"
                     x-transition:leave-start="opacity-100 scale-y-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-y-75 -translate-y-2"
                     class="absolute top-full right-0 mt-2 bg-[#0a0a0a]/95 backdrop-blur-3xl rgb-led-border border-transparent rounded-[1.5rem] p-2 space-y-1 shadow-[0_30px_60px_rgba(0,0,0,0.8)] z-50 max-h-60 overflow-y-auto custom-scrollbar min-w-[150px] md:min-w-[160px]">
                    <button @click.stop="updateTargetCountry('global'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 md:py-2.5 rounded-xl hover:bg-brand-indigo/90 text-[8px] md:text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                        <img src="https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg" class="w-3.5 h-3 md:w-4 md:h-3.5 object-cover" crossorigin="anonymous" loading="lazy"> {{__('chatroulette.Global_Match')}}
                    </button>
                    <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent mx-2 my-1"></div>
                    @foreach(['az', 'ge', 'am', 'ru', 'kz', 'uz', 'ua', 'tr', 'de', 'gb', 'fr', 'it', 'es', 'pl', 'us', 'ca'] as $code)
                        <button @click.stop="updateTargetCountry('{{$code}}'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 md:py-2.5 rounded-xl hover:bg-white/10 text-[8px] md:text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/{{$code}}.svg" class="w-3.5 h-2.5 md:w-4 md:h-3 object-cover rounded-sm shadow-sm" crossorigin="anonymous" loading="lazy">
                            {{ strtoupper($code) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- ИНДИКАТОР ПИНГА И СЕТИ -->
            <div x-show="state === 'connected'" class="pointer-events-auto px-3 py-2 md:px-4 md:py-2.5 flex items-center gap-2 md:gap-3 rounded-[1rem] md:rounded-2xl border transition-all duration-500 backdrop-blur-2xl shadow-2xl" :class="{ 'hud-no-network': partnerState === 'problem', 'hud-away': partnerState === 'away', 'bg-black/60 border-white/[0.05]': partnerState === 'active' }" x-cloak>
                <div class="relative flex h-2 w-2 md:h-2.5 md:w-2.5">
                    <span class="absolute inline-flex h-full w-full rounded-full opacity-75" :class="{ 'animate-ping bg-red-500': partnerState === 'problem', 'animate-pulse bg-amber-500': partnerState === 'away', 'bg-green-500': partnerState === 'active' }"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 md:h-2.5 md:w-2.5 shadow-sm" :class="{ 'bg-red-600': partnerState === 'problem', 'bg-amber-500': partnerState === 'away', 'bg-green-500': partnerState === 'active' }"></span>
                </div>
                <div class="flex flex-col">
                    <template x-if="partnerState === 'problem'"><span class="text-[8px] md:text-[9px] font-black tracking-[0.2em] uppercase text-white animate-pulse">{{ __('chatroulette.No_Signal') }}</span></template>
                    <template x-if="partnerState === 'away'"><span class="text-[8px] md:text-[9px] font-black tracking-[0.2em] uppercase text-amber-500">{{ __('chatroulette.User_Away') }}</span></template>
                    <template x-if="partnerState === 'active'">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[9px] md:text-[10px] font-black tracking-[0.12em] uppercase font-mono" :class="ping < 150 ? 'text-green-400 drop-shadow-[0_0_5px_rgba(74,222,128,0.5)]' : 'text-red-400 drop-shadow-[0_0_5px_rgba(248,113,113,0.5)]'" x-text="ping + ' ms'"></span>
                            <span class="text-[6px] md:text-[7px] font-bold text-gray-500 uppercase tracking-widest">{{ __('chatroulette.Latency') }}</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ТАЙМЕР ЗВОНКА -->
            <div x-show="state === 'connected'" class="pointer-events-auto">
                <div @click.stop="timerExpanded = !timerExpanded" class="group flex items-center gap-2 md:gap-3 px-2.5 py-1.5 md:px-3 md:py-2.5 rounded-[1rem] md:rounded-2xl border border-white/[0.05] bg-black/60 backdrop-blur-2xl cursor-pointer transition-all duration-500 hover:bg-white/10 hover:border-white/20 shadow-xl" :class="timerExpanded ? 'max-w-[150px] pr-4 md:pr-5' : 'max-w-[40px] md:max-w-[48px] pr-2.5 md:pr-3.5'" x-cloak>
                    <div class="relative flex shrink-0">
                        <span class="text-xs md:text-sm transition-transform duration-500" :class="timerExpanded ? 'rotate-12 scale-110' : ''">⏱️</span>
                        <span class="absolute -top-1 -right-1 w-1.5 h-1.5 md:w-2 md:h-2 bg-brand-indigo rounded-full shadow-[0_0_8px_#6366f1] animate-pulse border border-[#020202]"></span>
                    </div>
                    <div x-show="timerExpanded" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" class="flex flex-col ml-1 md:ml-0">
                        <span class="text-[9px] md:text-[11px] font-black font-mono tracking-widest text-white/95 leading-none drop-shadow-md" x-text="formatCallTime()"></span>
                        <span class="text-[5px] md:text-[6px] font-black uppercase tracking-[0.2em] text-gray-500 mt-0.5 md:mt-1">Duration</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>