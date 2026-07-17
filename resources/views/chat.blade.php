<x-app-layout>

<div class="fixed top-[120px] bottom-0 left-0 right-0 w-full bg-[#020202] overflow-hidden px-3 pt-4 pb-40 md:p-4 overscroll-none" 
     style="height: calc(100dvh - 120px - env(safe-area-inset-bottom));">
        
        <!-- 1. VIDEO ECOSYSTEM (Dual-Engine Split Screen) -->
       <div class="flex flex-col md:flex-row w-full h-full gap-2 md:gap-4 transition-all duration-700 ease-in-out"
     :class="isBlitzActive ? 'blitz-grid-warp' : ''">
    
    <!-- PARTNER CONTAINER (REMOTE) -->
    <div @click="toggleFocus('remote')"
        :class="{
            'blitz-hell-logic': isBlitzActive,
            'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
            'h-[70%] md:h-full md:w-[75%] shadow-[0_0_50px_rgba(0,0,0,0.5)] z-10': layoutFocus === 'remote',
            'h-[30%] md:h-full md:w-[25%] opacity-40': layoutFocus === 'local'
        }"
        class="relative overflow-hidden rounded-[2.5rem] bg-[#050505] border border-white/5 transition-all duration-700 ease-in-out cursor-pointer group">
        
        <video x-ref="remoteVideo" 
            id="remoteVideo"
            autoplay playsinline webkit-playsinline 
            :class="[getFilterClass('remote'), { 
                'blitz-hell-video': isBlitzActive,
                'opacity-40': isRemoteBlurred && !isBlitzActive 
            }]"
            class="w-full h-full object-cover transition-all duration-1000 bg-black">
        </video>

        <!-- Кровавый слой (только для Блица) -->
        <div x-show="isBlitzActive" 
             class="absolute inset-0 bg-red-900/40 mix-blend-color-burn pointer-events-none z-20 animate-pulse" 
             x-cloak></div>


                <!-- Оверлей размытия (Privacy Mode) -->
                <div x-show="isRemoteBlurred" 
                    x-transition:enter="transition opacity duration-500"
                    x-transition:leave="transition opacity duration-500"
                    class="absolute inset-0 z-10 bg-black/40 backdrop-blur-[100px] flex flex-col items-center justify-center"
                    x-cloak>
                    <div class="relative">
                        <div class="absolute inset-0 bg-white/20 rounded-full blur-2xl animate-pulse"></div>
                        <span class="relative text-6xl mb-6 block">🙈</span>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-[0.5em] text-white/40 italic animate-pulse">Privacy Mode</span>
                </div>
                
                <!-- NEW: COUNTRY TARGET SELECTOR (Элегантный переключатель стран в рулетке) -->
                    <div x-show="state === 'idle' || state === 'searching'" 
                        class="absolute top-6 right-6 z-30 pointer-events-auto"
                        x-data="{ countryDropdown: false }">
                     
                    <button @click.stop="countryDropdown = !countryDropdown" 
                            class="px-4 py-2.5 bg-black/60 backdrop-blur-xl border border-white/10 rounded-2xl flex items-center gap-2 text-[10px] font-black uppercase tracking-wider hover:border-brand-indigo/40 transition-all shadow-2xl">
                    <span x-text="countryNames[targetCountry] || '🌍 {{__('chatroulette.Global_Match')}}'"></span>
                        <span class="text-[7px] opacity-40">▼</span>
                    </button>
                    
                    <!-- Выпадающий список с кастомной прокруткой -->
                    <div x-show="countryDropdown" 
                         @click.away="countryDropdown = false"
                         x-transition
                         class="absolute right-0 mt-2  bg-[#0a0a0a]/95 backdrop-blur-3xl border border-white/10 rounded-2xl p-2 space-y-1 shadow-2xl z-50 max-h-64 overflow-y-auto custom-scrollbar">
                        
                        <button @click.stop="updateTargetCountry('global'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg" class="w-4 h-3.5 object-cover" crossorigin="anonymous" loading="lazy" alt="global"> {{__('chatroulette.Global_Match')}}
                        </button>
                        <div class="h-px bg-white/5 mx-2"></div>
                        
                        <!-- Региональные приоритеты -->
                        @foreach(['az', 'ge', 'am', 'ru', 'kz', 'uz', 'ua', 'tr', 'de', 'gb', 'fr', 'it', 'es', 'pl', 'us', 'ca'] as $code)
                            <button @click.stop="updateTargetCountry('{{$code}}'); countryDropdown = false;" 
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                                <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/{{$code}}.svg" 
                                    class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy">
                                {{ strtoupper($code) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- SEARCH OVERLAY (Inside remote window) -->
                <div x-show="state === 'searching' || state === 'idle'" 
                     class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-20">
                    <div class="relative w-24 h-24 mb-6">
                        <div class="absolute inset-0 border-4 border-brand-indigo/20 rounded-[2rem] animate-ping"></div>
                        <div class="absolute inset-0 flex items-center justify-center text-5xl">🛰️</div>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-[0.5em] text-brand-indigo animate-pulse italic" 
                       x-text="state === 'searching' ? '{{ __('chatroulette.Searching') }}...' : '{{ __('chatroulette.Ready_To_Start') }}'"></p>
                </div>

                <!-- PARTNER INFO WIDGET (Engine v3.8 - Mobile Shield Edition) -->
                <div x-show="state === 'connected' && partnerData" 
                     class="absolute top-6 left-6 z-[1000] flex items-start pointer-events-none"
                     x-cloak>
                    
                    <!-- TRIGGER BUTTON (Оптимизировано для iOS) -->
                    <div class="relative w-14 h-14 md:w-16 md:h-16 shrink-0 pointer-events-auto block z-10" style="transform: translateZ(0);">
                        
                        <!-- ИНДИКАТОР VPN НАД ФЛАГОМ -->
                        <div x-show="partnerData?.vpn" 
                             x-cloak
                             class="absolute -top-5 left-1/2 -translate-x-1/2 z-[60] whitespace-nowrap transform-gpu">
                            <div class="flex items-center gap-1 px-2 py-0.5 bg-amber-500/95 rounded-md border border-white/20 shadow-[0_0_15px_rgba(245,158,11,0.5)] animate-bounce">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 text-black" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zM10 5a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-[7px] font-black uppercase tracking-tighter text-black">Masked IP</span>
                            </div>
                        </div>

                        <!-- LED ГРАДИЕНТ -->
                        <div class="absolute -inset-1.5 rounded-[1.8rem] opacity-70 transform-gpu"
                             :class="partnerData?.ban_count > 0 ? 'led-toxic' : 'animate-[led-rotate_4s_linear_infinite]'"
                             :style="partnerData?.ban_count > 0 ? '' : { 
                                background: partnerData?.gender === 'female' 
                                    ? 'conic-gradient(from 0deg, transparent, #db2777, transparent, #db2777, transparent)' 
                                    : 'conic-gradient(from 0deg, transparent, #2563eb, transparent, #6366f1, transparent)' 
                             }">
                        </div>
                        
                        <!-- Halo Свечение -->
                        <div class="absolute -inset-1.5 rounded-[1.8rem] blur-md transform-gpu"
                             :class="partnerData?.ban_count > 0 ? 'bg-red-600/60 animate-pulse' : 'animate-[led-pulse_2s_ease-in-out_infinite]'"
                             :style="partnerData?.ban_count > 0 ? '' : { backgroundColor: partnerData?.gender === 'female' ? '#db277740' : '#2563eb40' }">
                        </div>

                        <!-- Кнопка -->
                        <button @click.stop.prevent="uiShowPartnerCard = !uiShowPartnerCard"
                                type="button"
                                class="relative w-full h-full rounded-[1.4rem] overflow-hidden border shadow-2xl transition-all duration-300 active:scale-95 group bg-black focus:outline-none z-20"
                                :class="partnerData?.ban_count > 0 ? 'border-red-500/50' : 'border-white/20'"
                                style="-webkit-tap-highlight-color: transparent; touch-action: manipulation;">
                            
                            <!-- ФЛАГ -->
                            <img :src="partnerData?.country_flag" 
                                 class="w-full h-full object-cover transition-transform duration-700"
                                 :class="partnerData?.ban_count > 0 ? 'grayscale brightness-50 group-hover:grayscale-0' : 'opacity-90 group-hover:scale-105'"
                                 style="backface-visibility: hidden; transform: translateZ(0);"
                                 crossorigin="anonymous">
                            
                            <!-- Череп (Замена x-if на x-show) -->
                            <div x-show="partnerData?.ban_count > 0" 
                                 x-cloak
                                 class="absolute inset-0 flex items-center justify-center bg-red-950/40">
                                <span class="text-xl filter drop-shadow-md">💀</span>
                            </div>
                        </button>

                        <!-- 1. ИНДИКАТОР AFK -->
                        <div x-show="partnerState === 'away'" 
                             x-cloak
                             class="absolute -top-1 -right-1 w-6 h-6 bg-amber-500 rounded-full border-2 border-[#020202] flex items-center justify-center shadow-lg z-30 animate-pulse transform-gpu">
                            <span class="text-[9px]">🌙</span>
                        </div>

                        <!-- 2. ИНДИКАТОР ПРОБЛЕМЫ -->
                        <div x-show="partnerState === 'problem'" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="scale-0"
                             x-transition:enter-end="scale-100"
                             class="absolute -top-1 -right-1 w-7 h-7 bg-red-600 rounded-full border-2 border-[#020202] flex items-center justify-center shadow-[0_0_20px_#ff0000] z-40 animate-bounce transform-gpu">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12" class="text-red-200 opacity-80" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- DYNAMIC STATUS HUD (v3.9 - Context Aware) -->
                <div x-show="state === 'connected'" 
                    class="absolute top-6 right-6 px-4 py-2 flex items-center gap-3 rounded-2xl border transition-all duration-500 backdrop-blur-xl shadow-2xl z-[110]"
                    :class="{
                        'hud-no-network': partnerState === 'problem',
                        'hud-away': partnerState === 'away',
                        'bg-black/40 border-white/[0.04]': partnerState === 'active'
                    }">
                    
                    <!-- Индикатор (Точка) -->
                    <div class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full rounded-full opacity-75"
                            :class="{
                                'animate-ping bg-red-500': partnerState === 'problem',
                                'animate-pulse bg-amber-500': partnerState === 'away',
                                'bg-green-500': partnerState === 'active'
                            }"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                            :class="{
                                'bg-red-600': partnerState === 'problem',
                                'bg-amber-500': partnerState === 'away',
                                'bg-green-500': partnerState === 'active'
                            }"></span>
                    </div>

                    <!-- Текстовый Контент -->
                    <div class="flex flex-col">
                        <!-- Режим: NO NETWORK -->
                        <template x-if="partnerState === 'problem'">
                            <span class="text-[9px] font-black tracking-[0.2em] uppercase text-white animate-pulse">
                                {{ __('chatroulette.No_Signal') }}
                            </span>
                        </template>

                        <!-- Режим: AWAY -->
                        <template x-if="partnerState === 'away'">
                            <span class="text-[9px] font-black tracking-[0.2em] uppercase text-amber-500">
                                {{ __('chatroulette.User_Away') }}
                            </span>
                        </template>

                        <!-- Режим: ACTIVE (Пинг) -->
                        <template x-if="partnerState === 'active'">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[9px] font-black tracking-[0.12em] uppercase"
                                    :class="ping < 150 ? 'text-green-400' : 'text-red-400'"
                                    x-text="ping + ' ms'"></span>
                                <span class="text-[7px] font-bold text-gray-500 uppercase tracking-tighter">{{ __('chatroulette.Latency') }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- MY CONTAINER (LOCAL) -->
    <div @click="toggleFocus('local')"
        :class="{
            'blitz-hell-logic': isBlitzActive,
            'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
            'h-[70%] md:h-full md:w-[75%]': layoutFocus === 'local',
            'h-[30%] md:h-full md:w-[25%]': layoutFocus === 'remote'
        }"
        class="relative overflow-hidden rounded-[2.5rem] bg-[#050505] border border-white/5 transition-all duration-700 ease-in-out cursor-pointer group">
        
        <video x-ref="localVideo" 
            id="localVideo" 
            autoplay muted playsinline webkit-playsinline
            :class="[getFilterClass('local'), { 
                'blitz-hell-video': isBlitzActive,
                'scale-x-[-1]': true 
            }]"
            class="w-full h-full object-cover transition-all duration-1000 bg-black">
        </video>

        <!-- Кровавый слой (только для Блица) -->
        <div x-show="isBlitzActive" 
             class="absolute inset-0 bg-red-900/40 mix-blend-color-burn pointer-events-none z-20 animate-pulse" 
             x-cloak></div>

                <!-- MY INFO & ACTIVE FILTERS TAG -->
                <div class="absolute top-6 left-6 z-40" x-data="{ expanded: false }" @click.away="expanded = false">
                    <!-- Свернутая кнопка -->
                    <button @click.stop="expanded = !expanded" 
                            class="caspian-glass px-4 py-2 rounded-xl flex items-center gap-2 border border-white/10 hover:border-brand-indigo/40 hover:bg-white/5 transition-all shadow-lg group pointer-events-auto">
                        <div class="w-1.5 h-1.5 rounded-full bg-brand-indigo animate-pulse"></div>
                        <span class="text-[9px] font-black uppercase tracking-widest italic text-white/70">{{ __('chatroulette.Target') }}</span>
                        <span class="text-[7px] text-gray-500 transition-transform duration-300 ml-1" 
                              :class="expanded ? 'rotate-180 text-brand-indigo' : ''">▼</span>
                    </button>

                    <!-- Раскрывающаяся панель параметров -->
    <div x-show="expanded" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
     class="absolute top-full left-0 mt-2 p-4 bg-[#0a0a0a]/95 backdrop-blur-3xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.8)] min-w-[200px] pointer-events-auto flex flex-col gap-3"
     @click.stop=""
     x-cloak>
    
    <div class="text-[8px] font-black uppercase tracking-[0.4em] text-gray-500 mb-2 border-b border-white/5 pb-2">
        {{ __('chatroulette.Target') }}
    </div>
    
    <!-- География -->
    <div class="flex justify-between items-center text-[9px] font-black uppercase tracking-widest">
        <span class="text-brand-indigo drop-shadow-[0_0_8px_rgba(99,102,241,0.6)]">🌎</span>
        <span class="text-gray-300" x-text="countryNames[targetCountry] || '{{__('chatroulette.Global_Match')}}'"></span>
    </div>
    
    <!-- Пол -->
    <div class="flex justify-between items-center text-[9px] font-black uppercase tracking-widest">
        <span class="text-pink-500 drop-shadow-[0_0_8px_rgba(236,72,153,0.6)]">👤</span>
        <span class="text-gray-300" x-text="t(targetGender)"></span>
    </div>
    
    <!-- Возраст -->
    <div class="flex justify-between items-center text-[9px] font-black uppercase tracking-widest">
        <span class="text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.6)]">⚡</span>
        <span class="text-brand-indigo"><span x-text="targetAgeMin"></span> — <span x-text="targetAgeMax"></span></span>
    </div>

    <!-- Кнопка перехода -->
    <button @click.stop="filterModalOpen = true; expanded = false" 
            class="mt-2 w-full py-2 rounded-xl bg-white/[0.03] hover:bg-brand-indigo hover:text-white border border-white/5 text-gray-400 text-[8px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2">
        <span class="text-brand-indigo">⚙️</span> {{ __('chatroulette.Change') }}
    </button>
</div>
                </div>
                <!-- CAMERA OFF OVERLAY -->
                <div x-show="!camEnabled" class="absolute inset-0 bg-[#020202]/95 flex flex-col items-center justify-center z-10">
                    <span class="text-2xl mb-2">🚫</span>
                    <span class="text-[9px] font-black uppercase text-red-500 tracking-[0.3em] italic">{{ __('chatroulette.Stream_Paused') }}</span>
                </div>
            </div>
        </div>

        <!-- 2. OVERLAYS (Typing) -->
        <div x-show="isPartnerTyping && state === 'connected'" 
             class="fixed bottom-40 left-1/2 -translate-x-1/2 z-[400] bg-brand-indigo/90 backdrop-blur-2xl px-6 py-2.5 rounded-2xl border border-white/20 shadow-2xl" 
             x-cloak x-transition:enter="transition ease-out duration-300 translate-y-10 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">
            <div class="flex items-center gap-3">
                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-white" x-text="typingPartnerName + ' is typing' "></span>
                <div class="flex gap-1">
                    <span class="w-1 h-1 bg-white rounded-full animate-bounce"></span>
                    <span class="w-1 h-1 bg-white rounded-full animate-bounce [animation-delay:0.2s]"></span>
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
                    <!--Start conversation about this-->
                    {{ __('chatroulette.Start_Conversation') }}
                </div>
            </div>
        </div>

        <!-- 3. FLOATING CONTROL ISLAND -->
        <div class="fixed bottom-24 md:bottom-4 left-0 right-0 px-6 z-[500] flex flex-col items-center gap-4 pointer-events-none"
     :class="globalSidebarOpen ? 'max-md:opacity-0 max-md:translate-y-10 max-md:pointer-events-none' : 'opacity-100'">
            
            <!-- TOOL GRID -->
            <div x-show="controlsOpen" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-10 scale-90"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-end="opacity-0 translate-y-10 scale-90"
                 class="pointer-events-auto w-full max-w-[360px] caspian-glass rounded-[2.5rem] p-3 shadow-[0_20px_50px_rgba(0,0,0,0.6)] border-white/10">
                
                <div class="grid grid-cols-3 gap-2">
                    <!-- Main Hardware -->
                    <button @click="openDeviceSettings()" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl bg-white/5 hover:bg-white/10 transition-all">
                        <span class="text-lg">⚙️</span>
                        <span class="text-[7px] font-black uppercase tracking-widest text-gray-500">{{ __('chatroulette.Hardware') }}</span>
                    </button>
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600/80'" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl transition-all">
                        <span class="text-lg" x-text="micEnabled ? '🎤' : '🔇'"></span>
                        <span class="text-[7px] font-black uppercase tracking-widest opacity-60">{{ __('chatroulette.Mute') }}</span>
                    </button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600/80'" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl transition-all">
                        <span class="text-lg" x-text="camEnabled ? '📷' : '🚫'"></span>
                        <span class="text-[7px] font-black uppercase tracking-widest opacity-60">{{ __('chatroulette.Hide_Yourself') }}</span>
                    </button>
                    <!-- Search Filters Toggle -->
                    <button @click="filterModalOpen = true" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl bg-white/5 hover:bg-brand-indigo/20 transition-all group">
                        <span class="text-lg group-hover:scale-110 transition-transform">🎯</span>
                        <span class="text-[7px] font-black uppercase tracking-widest text-gray-500 group-hover:text-brand-indigo">{{ __('chatroulette.Target') }}</span>
                    </button>
                    <!-- FX Row -->
                    <button @click="toggleBeauty()" :class="beautyFilter ? 'bg-pink-600 shadow-[0_0_15px_rgba(219,39,119,0.3)]' : 'bg-white/5'" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl transition-all">
                        <span class="text-lg">✨</span>
                        <span class="text-[7px] font-black uppercase tracking-widest opacity-60">{{ __('chatroulette.Contrast') }}</span>
                    </button>
                    <button @click="toggleCinema()" :class="cinemaFilter ? 'bg-amber-600 shadow-[0_0_15px_rgba(217,119,6,0.3)]' : 'bg-white/5'" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl transition-all">
                        <span class="text-lg">🎬</span>
                        <span class="text-[7px] font-black uppercase tracking-widest opacity-60">{{ __('chatroulette.Monochrome') }}</span>
                    </button>
                    <button @click="isRemoteBlurred = !isRemoteBlurred" :class="isRemoteBlurred ? 'bg-brand-indigo shadow-[0_0_15px_rgba(99,102,241,0.3)]' : 'bg-white/5'" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl transition-all">
                        <span class="text-lg">🙈</span>
                        <span class="text-[7px] font-black uppercase tracking-widest opacity-60">{{ __('chatroulette.Hide_Interlocutor') }}</span>
                    </button>
                    <!-- Кнопка Icebreaker (🎲 Кубик) -->
                    <button @click="sendIcebreaker()" 
                            :disabled="icebreakerCooldown > 0 || state !== 'connected'"
                            class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl transition-all group"
                            :class="icebreakerCooldown > 0 
                                ? 'bg-white/5 opacity-40 cursor-not-allowed' 
                                : 'bg-white/5 hover:bg-brand-indigo/20'">
                        
                        <span class="text-lg" 
                            :class="icebreakerCooldown > 0 ? '' : 'group-hover:animate-spin'"
                            x-text="icebreakerCooldown > 0 ? '⏳' : '🎲'"></span>
                        
                        <span class="text-[7px] font-black uppercase tracking-widest"
                            :class="icebreakerCooldown > 0 ? 'text-gray-600' : 'text-gray-500 group-hover:text-brand-indigo'"
                            x-text="icebreakerCooldown > 0 ? icebreakerCooldown + 's' : '{{ __('chatroulette.Cube') }}'">
                        </span>
                    </button>

                    <!-- Кнопка Blitz Mode (⚡ Напряжение) -->
                    <button @click="triggerBlitz()" 
                        :disabled="blitzCooldown > 0 || isBlitzActive || state !== 'connected'" 
                        class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl transition-all group"
                        :class="isBlitzActive ? 'bg-red-600 animate-pulse' : 'bg-white/5 hover:bg-yellow-500/20'">
                    
                        <span class="text-lg" 
                            :class="blitzCooldown > 0 ? 'opacity-40' : 'group-hover:scale-150 transition-transform'"
                            x-text="blitzCooldown > 0 ? '⌛' : '⚡'"></span>
                        
                        <span class="text-[7px] font-black uppercase tracking-widest"
                            :class="blitzCooldown > 0 ? 'text-gray-600' : 'text-gray-500 group-hover:text-yellow-500'"
                            x-text="blitzCooldown > 0 ? blitzCooldown + 's' : '{{ __('chatroulette.Tension') }}'">
                        </span>
                    </button>
                    <!-- Social Row -->
                    <template x-if="callContext !== 'personal'">
                        <div class="col-span-3 grid grid-cols-4 gap-2 mt-1 pt-2 border-t border-white/5">
                            <button @click="toggleContact()" 
                                    :class="isFriend 
                                        ? 'bg-red-600/10 text-red-500 border border-red-500/20 hover:bg-red-600 hover:text-white' 
                                        : 'bg-brand-indigo/20 text-brand-indigo hover:bg-brand-indigo hover:text-white'" 
                                    class="col-span-3 h-12 rounded-xl font-black text-[9px] uppercase tracking-[0.2em] transition-all">
                                <span x-text="isFriend ? '✕  {{__('chatroulette.Remove_Friend')}} ' : '+  {{__('chatroulette.Add_Friend')}} '"></span>
                            </button>
                            <button @click="reportPartner()" class="col-span-1 h-12 bg-red-600/10 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all">
                                🚩
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- MAIN ACTION BAR (v3.2 Immersive Orb Edition) -->
            <div class="pointer-events-auto flex items-center transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]"
                 :class="actionsOpen 
                    ? 'w-full max-w-[400px] bg-[#121212] border border-white/10 rounded-[3rem] p-1.5 shadow-2xl' 
                    : 'w-14 h-14 bg-brand-indigo/20 backdrop-blur-xl border border-brand-indigo/40 rounded-full shadow-[0_0_20px_rgba(99,102,241,0.3)] hover:scale-110 hover:bg-brand-indigo/40'">
                
                <!-- Левая кнопка инструментов (⚡) -->
                <div x-show="actionsOpen" x-transition:enter="delay-300 opacity-0" class="shrink-0">
                    <button @click="controlsOpen = !controlsOpen" 
                            class="w-12 h-12 rounded-full flex items-center justify-center transition-all"
                            :class="controlsOpen ? 'bg-brand-indigo text-white rotate-180 shadow-lg' : 'bg-white/5 text-gray-400'">
                        <span class="text-[8px]" x-text="controlsOpen ? '▼' : '⚡'"></span>
                    </button>
                </div>
                
                <!-- Основной контент (Кнопки Skip/Next) -->
                <div class="flex-1 flex justify-center px-2 overflow-hidden transition-all duration-500"
                     x-show="actionsOpen"
                     x-transition:enter="transition delay-300 duration-500 opacity-0 scale-95"
                     x-transition:leave="transition duration-200 opacity-0 scale-90">
                    
                    <template x-if="callContext !== 'personal'">
                        <div class="w-full flex gap-2">
                            <button x-show="state === 'idle'" @click="startSearch()" class="btn-primary w-full !py-3.5 !rounded-full">{{ __('chatroulette.Start_Connect') }}</button>
                            <button x-show="state === 'searching'" @click="stopCall()" class="w-full py-3.5 bg-red-600/20 text-red-500 rounded-full font-black text-[9px] uppercase border border-red-500/20">{{ __('chatroulette.Abort') }}</button>
                            
                            <div x-show="state === 'connected'" class="flex items-center gap-2 w-full">
                                <button @click="stopCall()" class="bg-white/5 text-gray-400 px-5 py-3.5 rounded-full font-black text-[9px] uppercase hover:bg-red-600/20 hover:text-red-500 transition-all">{{ __('chatroulette.Stop') }}</button>
                                <button @click="startSearch()" class="btn-primary flex-1 !py-3.5 !rounded-full italic shadow-lg shadow-brand-indigo/30">{{ __('chatroulette.Next') }} ➔</button>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="callContext === 'personal'">
                        <button @click="stopCall()" class="bg-red-600 text-white w-full py-3.5 rounded-full font-black text-[9px] uppercase tracking-[0.2em] shadow-lg">{{ __('chatroulette.End_Call') }}</button>
                    </template>
                </div>

                <!-- КНОПКА-СФЕРА (Главный переключатель) -->
                <button @click="actionsOpen = !actionsOpen; if(!actionsOpen) controlsOpen = false" 
                        class="transition-all duration-500 shrink-0 flex items-center justify-center"
                        :class="actionsOpen 
                            ? 'w-12 h-12 rounded-full bg-white/5 text-gray-500 hover:text-white' 
                            : 'w-full h-full rounded-full text-brand-indigo shadow-inner'">
                    
                    <template x-if="actionsOpen">
                        <span class="text-[10px] font-bold">⊙</span>
                    </template>

                    <template x-if="!actionsOpen">
                        <div class="relative flex items-center justify-center">
                            <div class="absolute inset-0 bg-brand-indigo rounded-full animate-ping opacity-20"></div>
                            <span class="text-lg drop-shadow-[0_0_8px_rgba(99,102,241,0.8)]">💠</span>
                        </div>
                    </template>
                </button>
            </div>
        </div>

        <!-- DEVICE MODAL (v3.2 Hardware Engine) -->
        <div x-show="deviceModalOpen" 
             class="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-3xl" 
             x-cloak 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-end="opacity-0 scale-95">
            
            <div class="bg-[#080808] border border-white/10 w-full max-w-sm rounded-[3rem] p-10 shadow-[0_0_100px_rgba(0,0,0,1)]" 
                 @click.away="deviceModalOpen = false">
                
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 bg-brand-indigo/10 rounded-2xl flex items-center justify-center text-xl">⚙️</div>
                    <h3 class="text-xl font-black uppercase italic tracking-tighter text-white">{{ __('chatroulette.Hardware') }}</h3>
                </div>

                <div class="space-y-8">
                    <!-- Video Select -->
                    <div class="space-y-3">
                        <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">{{ __('chatroulette.Video_Interface') }}</label>
                        <select x-model="selectedVideoId" 
                                class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-6 text-xs font-bold text-white focus:ring-2 focus:ring-brand-indigo outline-none transition-all appearance-none cursor-pointer">
                            <template x-for="dev in videoDevices" :key="dev.deviceId">
                                <option :value="dev.deviceId" x-text="dev.label || 'Camera ' + (videoDevices.indexOf(dev)+1)"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Audio Select -->
                    <div class="space-y-3">
                        <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">{{ __('chatroulette.Audio_Interface') }}</label>
                        <select x-model="selectedAudioId" 
                                class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-6 text-xs font-bold text-white focus:ring-2 focus:ring-brand-indigo outline-none transition-all appearance-none cursor-pointer">
                            <template x-for="dev in audioDevices" :key="dev.deviceId">
                                <option :value="dev.deviceId" x-text="dev.label || 'Microphone ' + (audioDevices.indexOf(dev)+1)"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-12">
                    <button @click="deviceModalOpen = false" 
                            class="py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:text-white transition-all">
                        {{ __('chatroulette.Cancel') }}
                    </button>
                    <button @click="changeVideoDevice()" 
                            class="bg-brand-indigo py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-white shadow-xl shadow-brand-indigo/30 hover:scale-105 active:scale-95 transition-all">
                        {{ __('chatroulette.Apply_Changes') }}
                    </button>
                </div>
            </div>
        </div>
        
        <!-- FILTER MODAL -->
        <div x-show="filterModalOpen" 
             class="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-3xl" 
             x-cloak 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-end="opacity-0 scale-95">
            
            <div class="bg-[#080808] border border-white/10 w-full max-w-sm rounded-[3rem] p-10 shadow-[0_0_100px_rgba(99,102,241,0.1)]" 
                 @click.away="filterModalOpen = false">
                
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 bg-brand-indigo/10 rounded-2xl flex items-center justify-center text-xl border border-brand-indigo/20">🎯</div>
                    <h3 class="text-xl font-black uppercase italic tracking-tighter text-white">{{ __('chatroulette.Matching') }}</h3>
                </div>

                <div class="space-y-10">
                    <!-- Gender Selector -->
                    <div class="space-y-4">
                        <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">
                            {{ __('chatroulette.Looking_For') }}
                        </label>
                        
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="g in ['male', 'female', 'all']">
                                <button @click="targetGender = g" 
                                        :class="targetGender === g 
                                            ? 'bg-brand-indigo text-white border-brand-indigo shadow-[0_0_15px_rgba(99,102,241,0.4)]' 
                                            : 'bg-white/5 text-gray-500 border-white/5'"
                                        class="py-3 rounded-xl border font-black text-[9px] uppercase tracking-widest transition-all"
                                        x-text="t(g)">
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Age Range -->
                    <div class="space-y-6">
                        <div class="flex justify-between items-center ml-2">
                            <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em]">{{ __('chatroulette.Partner_Age') }}</label>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black text-brand-indigo" x-text="targetAgeMin"></span>
                                <span class="text-gray-700">—</span>
                                <span class="text-[10px] font-black text-brand-indigo" x-text="targetAgeMax"></span>
                            </div>
                        </div>
                        
                        <div class="px-2 space-y-8">
                            <!-- Min Age Slider -->
                            <div class="relative">
                                <input type="range" min="18" max="99" x-model="targetAgeMin" 
                                       class="w-full h-1 bg-white/10 rounded-lg appearance-none cursor-pointer accent-brand-indigo">
                                <p class="text-[7px] font-black uppercase mt-3 text-gray-600 tracking-widest">{{ __('chatroulette.Minimum_Age') }}</p>
                            </div>
                            <!-- Max Age Slider -->
                            <div class="relative">
                                <input type="range" min="18" max="99" x-model="targetAgeMax" 
                                       class="w-full h-1 bg-white/10 rounded-lg appearance-none cursor-pointer accent-brand-indigo">
                                <p class="text-[7px] font-black uppercase mt-3 text-gray-600 tracking-widest">{{ __('chatroulette.Maximum_Age') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-4 mt-12">
                    <button @click="filterModalOpen = false" class="py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:text-white transition-all">
                        {{ __('chatroulette.Cancel') }}
                    </button>
                    <button @click="applyFilters()" class="bg-brand-indigo py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-white shadow-xl shadow-brand-indigo/20 hover:scale-105 active:scale-95 transition-all">
                        {{ __('chatroulette.Apply') }} 🎯
                    </button>
                </div>
            </div>
        </div>
        
    </div>
    <!-- КОНЕЦ VIDEO ECOSYSTEM -->
    
    <!-- ICEBREAKER PREMIUM OVERLAY (v4.0 - Ethereal Prompt) -->
    <div x-show="showIcebreakerOverlay" 
         x-transition:enter="transition cubic-bezier(0.34, 1.56, 0.64, 1) duration-600"
         x-transition:enter-start="opacity-0 scale-75 translate-y-20 blur-xl"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0 blur-0"
         x-transition:leave="transition ease-in duration-400"
         x-transition:leave-end="opacity-0 scale-90 blur-lg"
         class="fixed bottom-44 left-1/2 -translate-x-1/2 z-[1500] w-full max-w-xl px-6 pointer-events-none"
         x-cloak>
        
        <div class="pointer-events-auto relative group">
            <!-- Анимированное свечение сзади -->
            <div class="absolute -inset-1 bg-gradient-to-r from-brand-indigo/50 via-purple-500/50 to-brand-cyan/50 rounded-[2.5rem] blur opacity-30 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
            
            <div class="relative caspian-glass rounded-[2rem] p-1 border-white/10 shadow-[0_32px_64px_rgba(0,0,0,0.8)] overflow-hidden">
                <div class="bg-[#050505]/90 rounded-[1.8rem] p-6 md:p-8 flex flex-col items-center text-center gap-4">
                    
                    <!-- Верхняя пометка -->
                    <div class="flex items-center gap-3">
                        <div class="h-px w-8 bg-brand-indigo/30"></div>
                        <span class="text-[9px] font-black uppercase tracking-[0.4em] text-brand-indigo animate-pulse">🎲 System Icebreaker</span>
                        <div class="h-px w-8 bg-brand-indigo/30"></div>
                    </div>

                    <!-- Текст вопроса (Элегантный и крупный) -->
                    <h2 class="text-lg md:text-2xl font-black italic tracking-tight text-white/95 leading-tight" 
                        x-text="icebreakerQuestion">
                    </h2>

                    <!-- Таймер-полоска снизу (визуально показывает сколько осталось) -->
                    <div class="w-full h-0.5 bg-white/5 rounded-full mt-2 overflow-hidden">
                        <div class="h-full bg-brand-indigo shadow-[0_0_10px_#6366f1]"
                             x-show="showIcebreakerOverlay"
                             x-transition:enter="transition-all linear duration-[12000ms]"
                             x-transition:enter-start="w-full"
                             x-transition:enter-end="w-0">
                        </div>
                    </div>

                    <!-- Кнопка быстрого закрытия -->
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
    
    <!-- 🌟 СЮДА ВЫНЕСЕНА КАРТОЧКА ПРОФИЛЯ: ПОВЕРХ ВСЕГО И ВНЕ СТРОГОГО КОНТЕЙНЕРА ИЗ-ЗА iOS 🌟 -->
    <div x-show="uiShowPartnerCard"
         x-cloak 
         class="fixed inset-0 z-[3000] pointer-events-none flex items-start justify-center md:items-start md:justify-start"
         style="transform: translateZ(0);">
         
         <!-- Невидимый слой для закрытия по клику вне карточки (Только для мобилок) -->
         <div class="absolute inset-0 bg-black/40 backdrop-blur-sm pointer-events-auto md:hidden" 
              x-show="uiShowPartnerCard" 
              x-transition.opacity 
              @click="uiShowPartnerCard = false"></div>

         <!-- Сама карточка -->
         <div @click.stop=""
              x-transition:enter="transition cubic-bezier(0.34, 1.56, 0.64, 1) duration-500"
              x-transition:enter-start="opacity-0 translate-y-8 md:translate-y-0 md:-translate-x-8 blur-xl scale-95"
              x-transition:enter-end="opacity-100 translate-y-0 md:translate-x-0 blur-0 scale-100"
              x-transition:leave="transition ease-in duration-300"
              x-transition:leave-start="opacity-100 translate-y-0 scale-100"
              x-transition:leave-end="opacity-0 translate-y-8 scale-95"
              class="pointer-events-auto relative md:absolute mt-36 md:mt-0 md:top-24 left-4 right-4 md:left-24 md:right-auto 
                     md:w-[320px] bg-[#0a0a0a]/95 backdrop-blur-3xl p-5 md:p-6 rounded-[2rem] md:rounded-[2.5rem] 
                     border border-white/10 shadow-[0_30px_100px_rgba(0,0,0,0.9)] overflow-hidden">
            
            <!-- Фоновое свечение -->
            <div class="absolute -top-24 -right-24 w-48 h-48 rounded-full blur-[80px] opacity-20 pointer-events-none"
                 :style="{ backgroundColor: partnerData?.gender === 'female' ? '#db2777' : '#2563eb' }"></div>

            <div class="relative z-10 flex flex-col gap-5">
                
                <!-- HEADER -->
                <div class="flex justify-between items-start">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center flex-wrap gap-2">
                            <h3 class="text-xl md:text-2xl font-black uppercase italic tracking-tighter text-white" x-text="partnerData?.name"></h3>
                            
                            <!-- КРАСНЫЙ ТЭГ ДЛЯ НАРУШИТЕЛЕЙ -->
                            <template x-if="partnerData?.ban_count > 0">
                                <span class="px-2 py-0.5 rounded bg-red-600 text-white text-[7px] font-black uppercase tracking-tighter animate-bounce">
                                    {{ __('chatroulette.Recidivist') }}
                                </span>
                            </template>
                            
                            <template x-if="partnerData?.vpn">
                                <span class="px-2 py-0.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-500 text-[8px] font-black uppercase tracking-widest flex items-center gap-1 shadow-[0_0_10px_rgba(245,158,11,0.2)]">
                                    <span class="animate-pulse">🛡️</span> Masked IP
                                </span>
                            </template>
                            
                            <span class="px-2 py-0.5 rounded-lg text-[8px] md:text-[9px] font-black uppercase tracking-widest border"
                                  :class="partnerData?.gender === 'female' ? 'bg-pink-500/10 text-pink-500 border-pink-500/20' : 'bg-blue-500/10 text-blue-500 border-blue-500/20'"
                                  x-text="partnerData?.gender === 'female' ? '{{ __('chatroulette.Female') }}' : '{{ __('chatroulette.Male') }}'"></span>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <span class="text-[8px] md:text-[9px] font-black uppercase tracking-[0.3em] text-gray-500" x-text="partnerData?.rank_name"></span>
                            <div class="h-1 w-1 rounded-full bg-white/20"></div>
                            <span class="text-[9px] md:text-[10px] font-bold text-gray-300" x-text="partnerData?.age + ' {{ __('chatroulette.Years_Old') }}'"></span>
                        </div>
                        
                        <!-- ЭЛЕГАНТНАЯ СТРОКА ЛОКАЦИИ -->
                        <div class="flex items-center gap-1.5 mt-0.5 opacity-60">
                            <span class="text-[7px] text-brand-indigo">📍</span>
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-400 italic" 
                                  x-text="countryNames[partnerData?.country_code]?.replace(/.[^\s]*\s/, '') || '{{ __('chatroulette.Unknown_Location') }}'">
                            </span>
                        </div>
                    </div>

                    <button @click="uiShowPartnerCard = false" class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- TRUST & STATS -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-white/[0.03] border border-white/[0.06] py-3 px-1 rounded-2xl flex flex-col items-center gap-1">
                        <span class="text-[6px] md:text-[7px] font-black uppercase text-gray-500">{{ __('chatroulette.Karma') }}</span>
                        <span class="text-xs font-black text-amber-500" x-text="partnerData?.karma"></span>
                    </div>

                    <div class="bg-white/[0.03] border border-white/[0.06] py-3 px-1 rounded-2xl flex flex-col items-center gap-1">
                        <span class="text-[6px] md:text-[7px] font-black uppercase text-gray-500">{{ __('chatroulette.Level') }}</span>
                        <span class="text-xs font-black text-white" x-text="partnerData?.level"></span>
                    </div>

                    <div class="bg-white/[0.03] border border-white/[0.06] py-3 px-1 rounded-2xl flex flex-col items-center gap-1">
                        <span class="text-[6px] md:text-[7px] font-black uppercase text-gray-500">{{ __('chatroulette.Reports') }}</span>
                        <div class="flex items-center gap-1">
                            <span class="text-[10px]">🚩</span>
                            <span class="text-xs font-black text-red-500" x-text="partnerData?.blocked_count || 0"></span>
                        </div>
                    </div>
                </div>

                <template x-if="partnerData?.ban_count > 0">
                    <div class="mt-4 px-4 py-2 bg-red-950/30 border border-red-500/20 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xs">⚠️</span>
                            <span class="text-[8px] font-black uppercase tracking-widest text-red-400">{{ __('chatroulette.Past_Violations') }}</span>
                        </div>
                        <span class="text-[10px] font-black text-red-500" x-text="partnerData.ban_count + ' BANS'"></span>
                    </div>
                </template>

                <!-- PRESTIGE FOOTER -->
                <template x-if="partnerData?.badge">
                    <div class="pt-4 border-t border-white/5 flex items-center justify-between">
                        <span class="text-[7px] md:text-[8px] font-black uppercase tracking-[0.25em] text-gray-600">{{ __('chatroulette.Prestige_Status') }}</span>
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/[0.02] border border-white/10 shadow-inner"
                             :style="{ borderColor: partnerData.badge.color + '30' }">
                            <span class="text-sm" x-text="partnerData.badge.icon"></span>
                            <span class="text-[9px] font-black uppercase tracking-widest" 
                                  :style="{ color: partnerData.badge.color, 'text-shadow': '0 0 10px ' + partnerData.badge.color + '40' }" 
                                  x-text="partnerData.badge.name"></span>
                        </div>
                    </div>
                </template>

            </div>
         </div>
    </div>

</x-app-layout>

<style>
    /* 1. Глобальный глитч-шум (накладывается поверх видео) */
    /* Дьявольская 3D деформация контейнера */
    @keyframes blitz-hell-warp {
        0%, 100% { transform: perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1); }
        20% { transform: perspective(1000px) rotateX(15deg) rotateY(-15deg) scale(1.05) skewX(5deg); }
        40% { transform: perspective(1000px) rotateX(-20deg) rotateY(20deg) scale(0.95) skewY(-5deg); }
        60% { transform: perspective(1000px) rotateX(10deg) rotateY(10deg) scale(1.1); }
        80% { transform: perspective(1000px) rotateX(-5deg) rotateY(-10deg) scale(0.9); }
    }

    /* Агрессивное свечение бездны */
    @keyframes blitz-hell-border {
        0%, 100% { border-color: #ff0000; box-shadow: 0 0 80px #ff0000, inset 0 0 30px #000; }
        50% { border-color: #ffffff; box-shadow: 0 0 120px #ff0000, inset 0 0 60px #600000; }
    }

    /* Глитч внутри видео (инверсия и цветовой сдвиг) */
    .blitz-hell-video {
        animation: blitz-glitch-visual 0.15s steps(2) infinite !important;
        filter: invert(1) hue-rotate(180deg) contrast(4) brightness(1.2) saturate(4) !important;
        mix-blend-mode: exclusion;
        will-change: filter, transform;
    }

    /* Класс для контейнеров */
    .blitz-hell-logic {
        animation: 
            blitz-shake-intense 0.08s linear infinite,
            blitz-hell-warp 1.2s ease-in-out infinite,
            blitz-hell-border 0.2s infinite !important;
        z-index: 500 !important;
        border-width: 8px !important;
        background: #000 !important;
        /* Ломаем края через рваный полигон */
        clip-path: polygon(0 0, 100% 0, 100% 90%, 80% 100%, 20% 95%, 0 100%) !important;
    }

    /* Глобальный эффект "плавления" сетки */
    .blitz-grid-warp {
        perspective: 1500px;
        overflow: visible !important;
    }

    /* Анимация вращения градиента для LED эффекта */
@keyframes led-rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Пульсация свечения */
@keyframes led-pulse {
    0%, 100% { opacity: 0.6; filter: blur(6px); }
    50% { opacity: 1; filter: blur(10px); }
}

.led-border {
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    mask-composite: exclude;
    pointer-events: none;
}

@keyframes toxic-flicker {
    0%, 100% { opacity: 1; filter: drop-shadow(0 0 15px #ff0000); }
    33% { opacity: 0.4; filter: drop-shadow(0 0 5px #7f1d1d); }
    66% { opacity: 0.8; filter: drop-shadow(0 0 20px #b91c1c); }
}

.led-toxic {
    background: conic-gradient(from 0deg, #ff0000, #000, #ff0000, #450a0a, #ff0000) !important;
    animation: led-rotate 2s linear infinite, toxic-flicker 0.5s ease-in-out infinite !important;
}

@keyframes devilish-pulse {
    0% { box-shadow: 0 0 5px #ff0000, inset 0 0 5px #ff0000; opacity: 0.8; }
    50% { box-shadow: 0 0 20px #ff0000, inset 0 0 10px #ff0000; opacity: 1; }
    100% { box-shadow: 0 0 5px #ff0000, inset 0 0 5px #ff0000; opacity: 0.8; }
}

.hud-no-network {
    background: rgba(127, 29, 29, 0.6) !important;
    border-color: rgba(220, 38, 38, 0.5) !important;
    animation: devilish-pulse 1s infinite;
}

.hud-away {
    background: rgba(245, 158, 11, 0.1) !important;
    border-color: rgba(245, 158, 11, 0.3) !important;
}
</style>