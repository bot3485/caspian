<div @click="toggleFocus('local')"
     :class="{
         'blitz-hell-logic': isBlitzActive,
         'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
         'h-[70%] md:h-full md:w-[75%] z-10': layoutFocus === 'local',
         'h-[30%] md:h-full md:w-[25%] opacity-50 grayscale-[30%] hover:opacity-80': layoutFocus === 'remote'
     }"
     class="elegant-glass led-container-fx relative rounded-[2.5rem] transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] cursor-pointer group">
    
    <!-- ВНУТРЕННИЙ КОНТЕЙНЕР (Обрезает только видео, но не меню) -->
    <div class="absolute inset-0 rounded-[2.4rem] overflow-hidden bg-[#050505] shadow-[inset_0_0_30px_rgba(0,0,0,0.8)] z-10">
        <div class="inner-video-ring"></div>
        
        <video x-ref="localVideo" id="localVideo" autoplay muted playsinline webkit-playsinline
            :class="[getFilterClass('local'), { 'blitz-hell-video': isBlitzActive, 'scale-x-[-1]': true }]"
            class="absolute inset-0 w-full h-full object-cover transition-all duration-1000 bg-[#050505]">
        </video>

        <div x-show="isBlitzActive" class="absolute inset-0 bg-red-900/40 mix-blend-color-burn pointer-events-none z-20 animate-pulse" x-cloak></div>

        <!-- КАМЕРА ОТКЛЮЧЕНА -->
        <div x-show="!camEnabled" class="absolute inset-0 bg-[#020202]/95 backdrop-blur-xl flex flex-col items-center justify-center z-30 transition-all duration-500" x-cloak>
            <span class="text-4xl mb-4 drop-shadow-2xl opacity-80">🚫</span>
            <span class="text-[10px] font-black uppercase text-red-500 tracking-[0.4em] italic drop-shadow-[0_0_10px_rgba(239,68,68,0.5)]">{{ __('chatroulette.Stream_Paused') }}</span>
        </div>
    </div>

    <!-- ВИДЖЕТЫ ПОВЕРХ ВИДЕО (Безопасный слой) -->
    <div class="absolute inset-0 z-40 pointer-events-none">
        <!-- МОЙ ТАРГЕТ (Прижат в левый верхний угол: top-3) -->
        <div class="absolute top-3 left-3 md:top-6 md:left-6 flex flex-col items-start gap-2">
            <div x-data="{ expanded: false }" @click.away="expanded = false" class="relative pointer-events-auto">
                <button @click.stop="expanded = !expanded" class="px-3 py-2 md:px-4 md:py-2.5 bg-black/60 backdrop-blur-xl rounded-[1rem] flex items-center gap-2.5 rgb-led-border border-transparent hover:border-brand-indigo/40 hover:bg-white/10 transition-all shadow-xl group">
                    <div class="w-1.5 h-1.5 md:w-2 md:h-2 rounded-full bg-brand-indigo animate-pulse shadow-[0_0_8px_#6366f1]"></div>
                    <span class="text-[8px] md:text-[10px] font-black uppercase tracking-[0.2em] italic text-white/80 group-hover:text-white">{{ __('chatroulette.Target') }}</span>
                    <span class="text-[7px] text-gray-500 transition-transform duration-300" :class="expanded ? 'rotate-180 text-brand-indigo' : ''">▼</span>
                </button>

                <!-- Выпадающее окно (Открывается строго ВНИЗ) -->
                <div x-show="expanded" 
                     x-transition:enter="transition ease-out duration-200 origin-top"
                     x-transition:enter-start="opacity-0 scale-y-75 -translate-y-2"
                     x-transition:enter-end="opacity-100 scale-y-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150 origin-top"
                     x-transition:leave-start="opacity-100 scale-y-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-y-75 -translate-y-2"
                     class="absolute top-full left-0 mt-2 p-4 md:p-5 bg-[#0a0a0a]/95 backdrop-blur-3xl rgb-led-border border-transparent rounded-[1.5rem] shadow-[0_30px_60px_rgba(0,0,0,0.8)] min-w-[200px] md:min-w-[220px] flex flex-col gap-3 md:gap-4" @click.stop="" x-cloak>
                    
                    <div class="text-[7px] md:text-[8px] font-black uppercase tracking-[0.4em] text-gray-500 border-b border-white/5 pb-2">{{ __('chatroulette.Target') }}</div>
                    <div class="flex justify-between items-center text-[9px] md:text-[10px] font-black uppercase tracking-widest">
                        <span class="text-brand-indigo drop-shadow-[0_0_8px_rgba(99,102,241,0.6)] text-base md:text-lg">🌎</span>
                        <span class="text-white/90" x-text="countryNames[targetCountry] || '{{__('chatroulette.Global_Match')}}'"></span>
                    </div>
                    <div class="flex justify-between items-center text-[9px] md:text-[10px] font-black uppercase tracking-widest">
                        <span class="text-pink-500 drop-shadow-[0_0_8px_rgba(236,72,153,0.6)] text-base md:text-lg">👤</span>
                        <span class="text-white/90" x-text="t(targetGender)"></span>
                    </div>
                    <div class="flex justify-between items-center text-[9px] md:text-[10px] font-black uppercase tracking-widest">
                        <span class="text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.6)] text-base md:text-lg">⚡</span>
                        <span class="text-brand-indigo bg-brand-indigo/10 px-2 py-1 rounded-lg border border-brand-indigo/20"><span x-text="targetAgeMin"></span> — <span x-text="targetAgeMax"></span></span>
                    </div>
                    <button @click.stop="filterModalOpen = true; expanded = false" class="mt-1 md:mt-2 w-full py-2.5 md:py-3 rounded-xl bg-white/[0.03] hover:bg-brand-indigo hover:text-white rgb-led-border border-transparent text-gray-300 text-[8px] md:text-[9px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2 shadow-lg hover:shadow-brand-indigo/30">
                        <span class="text-brand-indigo text-xs md:text-sm">⚙️</span> {{ __('chatroulette.Change') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>