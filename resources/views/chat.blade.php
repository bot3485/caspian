<x-app-layout>

<div class="fixed top-[120px] bottom-0 left-0 right-0 w-full bg-[#020202] overflow-hidden px-3 pt-4 pb-28 md:p-4 overscroll-none" 
     style="height: calc(100vh - 120px - env(safe-area-inset-bottom));">
        
        <!-- 1. VIDEO ECOSYSTEM (Dual-Engine Split Screen) -->
        <div class="flex flex-col md:flex-row w-full h-full gap-2 md:gap-4 transition-all duration-700 ease-in-out">
            
            <!-- PARTNER CONTAINER (REMOTE) -->
            <div @click="toggleFocus('remote')"
                :class="{
                    'blitz-shaking-logic': isBlitzActive, /* Тряска всего окна */
                    'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
                    'h-[70%] md:h-full md:w-[75%] shadow-[0_0_50px_rgba(0,0,0,0.5)] z-10': layoutFocus === 'remote',
                    'h-[30%] md:h-full md:w-[25%] opacity-40': layoutFocus === 'local'
                }"
                class="relative overflow-hidden rounded-[2.5rem] bg-[#050505] border border-white/5 transition-all duration-700 ease-in-out cursor-pointer group">
                
                <!-- Само видео внутри -->
                <video x-ref="remoteVideo" 
                    id="remoteVideo"
                    autoplay 
                    playsinline 
                    webkit-playsinline 
                    @loadedmetadata="$el.play().catch(e => console.log('Remote play blocked', e))"
                    :class="[getFilterClass('remote'), { 
                        'blitz-visual-logic': isBlitzActive,
                        'opacity-40': isRemoteBlurred && !isBlitzActive 
                    }]"
                    class="w-full h-full object-cover transition-all duration-1000 bg-black">
                </video>

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
                        <!-- Используем сетку или просто список для всех стран из Enum -->
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

<!-- PARTNER INFO WIDGET (Engine v3.5 - Devilish Update) -->
<div x-show="state === 'connected' && partnerData" 
     class="absolute top-6 left-6 z-[1000] flex items-center gap-3 pointer-events-none"
     x-cloak>
    
    <!-- TRIGGER BUTTON -->
    <button @click.stop.prevent="uiShowPartnerCard = !uiShowPartnerCard"
            type="button"
            class="relative w-16 h-16 shrink-0 transition-all duration-500 active:scale-90 group pointer-events-auto">
        
        <div class="relative w-full h-full rounded-2xl flex flex-col border border-white/10 shadow-2xl overflow-hidden transition-all duration-500"
             :style="{ 
                'background-color': partnerState === 'problem' ? '#7f1d1d' : // Дьявольский красный
                                    (partnerState === 'away' ? '#f59e0b' : 
                                    (partnerData?.gender === 'female' ? '#db2777' : '#2563eb')) 
             }"
             :class="{ 'animate-pulse shadow-[0_0_25px_rgba(127,29,29,0.8)] border-red-500/50': partnerState === 'problem' }">
            
            <!-- Пол и Возраст -->
            <div class="flex-[0.8] flex items-center justify-center pt-1">
                <span class="text-white text-[10px] font-black tracking-tighter uppercase" 
                      x-text="(partnerData?.gender === 'male' ? 'M' : 'W') + ' ' + (partnerData?.age || '??')">
                </span>
            </div>
            
            <!-- Флаг -->
            <div class="flex-1 bg-black/20 flex items-center justify-center border-t border-white/5 overflow-hidden">
                 <img :src="partnerData?.country_flag" 
                      class="w-full h-full object-cover opacity-90 transition-transform group-hover:scale-110"
                      :class="{ 'grayscale sepia contrast-150': partnerState === 'problem' }" 
                      crossorigin="anonymous">
            </div>
        </div>

        <!-- AFK Dot (Желтый полумесяц) -->
        <div x-show="partnerState === 'away'" 
             class="absolute -top-1 -right-1 w-6 h-6 bg-amber-500 rounded-full border-2 border-[#020202] flex items-center justify-center shadow-lg z-30">
            <span class="text-[9px]">🌙</span>
        </div>

        <!-- PROBLEM Dot (Дьявольская иконка) -->
        <div x-show="partnerState === 'problem'" 
             x-transition:enter="transition cubic-bezier(0.68, -0.55, 0.265, 1.55) duration-500"
             x-transition:enter-start="scale-0 rotate-180"
             class="absolute -top-1 -right-1 w-7 h-7 bg-red-600 rounded-full border-2 border-[#020202] flex items-center justify-center shadow-[0_0_15px_#ff0000] z-30 animate-bounce">
            <span class="text-[11px]">⚠️</span> <!-- Можно заменить на 💀 или ⚡ -->
        </div>
    </button>

<!-- EXPANDABLE PROFILE CARD -->
        <div x-show="uiShowPartnerCard"
             x-cloak 
             @click.stop=""
             @click.away="uiShowPartnerCard = false"
             x-transition:enter="transition ease-out duration-400"
             x-transition:enter-start="opacity-0 -translate-x-4 blur-md"
             x-transition:enter-end="opacity-100 translate-x-0 blur-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-end="opacity-0 -translate-x-4 blur-md"
             class="pointer-events-auto bg-black/60 backdrop-blur-2xl px-6 py-4 rounded-[2rem] border border-white/10 shadow-2xl flex items-center gap-6 ml-2"
             :style="{ 'border-color': partnerData?.gender === 'female' ? '#db277740' : '#2563eb40' }">
            
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-3">
                    <span class="text-[13px] font-black uppercase tracking-tight text-white italic" x-text="partnerData?.name"></span>
                    <span class="text-[7px] font-black uppercase px-2 py-0.5 rounded bg-white/5 text-gray-400 border border-white/5 tracking-[0.2em]" 
                          x-text="partnerData?.rank_name"></span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <div class="w-1 h-1 rounded-full bg-green-500 shadow-[0_0_8px_#22c55e]"></div>
                        <span class="text-[8px] font-black text-brand-indigo uppercase tracking-widest" x-text="'LVL ' + (partnerData?.level || 1)"></span>
                    </div>
                    <template x-if="partnerData?.badge">
                        <div class="flex items-center gap-1 px-2 py-0.5 rounded border border-white/5 bg-white/[0.03]">
                            <span class="text-[10px]" x-text="partnerData.badge.icon"></span>
                            <span class="text-[7px] font-black uppercase tracking-wider" :style="{ color: partnerData.badge.color }" x-text="partnerData.badge.name"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- КРЕСТИК ЗАКРЫТИЯ -->
            <button @click.stop.prevent="uiShowPartnerCard = false" 
                    type="button"
                    class="p-2 hover:bg-white/10 rounded-full transition-colors group">
                <svg class="w-4 h-4 text-gray-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
</div>

                <!-- PING TAG -->
                <div x-show="state === 'connected' && ping > 0" 
                     class="absolute top-6 right-6 px-3 py-1.5 flex items-center gap-2 rounded-full border border-white/[0.04] bg-black/40 backdrop-blur-md shadow-2xl">
                    <span class="relative flex h-1.5 w-1.5">
                        <span :class="ping < 150 ? 'bg-green-500' : 'bg-red-500'" class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                        <span :class="ping < 150 ? 'bg-green-500' : 'bg-red-500'" class="relative inline-flex rounded-full h-1.5 w-1.5 transition-colors duration-300"></span>
                    </span>
                    <span :class="ping < 150 ? 'text-green-400' : 'text-red-400'" 
                          class="text-[8px] font-black tracking-[0.12em] uppercase transition-colors duration-300"
                          x-text="ping + ' ms'">
                    </span>
                </div>
            </div>

            <!-- MY CONTAINER (LOCAL) -->
            <div @click="toggleFocus('local')"
                :class="{
                    'blitz-shaking-logic': isBlitzActive, /* Тряска и у тебя тоже */
                    'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
                    'h-[70%] md:h-full md:w-[75%] shadow-[0_0_50px_rgba(0,0,0,0.5)] z-10': layoutFocus === 'local',
                    'h-[30%] md:h-full md:w-[25%] opacity-40': layoutFocus === 'remote'
                }"
                class="relative overflow-hidden rounded-[2.5rem] bg-[#050505] border border-white/5 transition-all duration-700 ease-in-out cursor-pointer group">
                
            <video x-ref="localVideo" 
                id="localVideo" 
                autoplay 
                muted 
                playsinline 
                webkit-playsinline
                @loadedmetadata="$el.play()"
                :class="[getFilterClass('local'), { 
                    'blitz-visual-logic': isBlitzActive,
                    'scale-x-[-1]': true 
                }]"
                class="w-full h-full object-cover transition-all duration-1000 bg-black">
            </video>


                <!-- MY INFO TAG -->
                <div class="absolute top-6 left-6 caspian-glass px-4 py-2 rounded-xl flex items-center gap-2 border-white/10 group-hover:scale-105 transition-transform">
                    <div class="w-1.5 h-1.5 rounded-full bg-brand-indigo animate-pulse"></div>
                    <span class="text-[9px] font-black uppercase tracking-widest italic text-white/70">{{ __('chatroulette.You') }}</span>
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
        <div class="fixed bottom-14 md:bottom-4 left-0 right-0 px-6 z-[500] flex flex-col items-center gap-4 pointer-events-none"
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
                        <span class="text-[7px] font-black uppercase tracking-widest text-gray-500 group-hover:text-brand-indigo">Target</span>
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
                    <!-- 1. Icebreaker -->
                    <button @click="sendIcebreaker()" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl bg-white/5 hover:bg-brand-indigo/20 transition-all group">
                        <span class="text-lg group-hover:animate-spin">🎲</span>
                        <span class="text-[7px] font-black uppercase tracking-widest text-gray-500 group-hover:text-brand-indigo">Icebreaker</span>
                    </button>

                    <!-- 2. Blitz Mode -->
                    <button @click="triggerBlitz()" :disabled="isBlitzActive" class="flex flex-col items-center justify-center gap-1.5 h-16 rounded-2xl bg-white/5 hover:bg-yellow-500/20 transition-all group">
                        <span class="text-lg group-hover:scale-150 transition-transform">⚡</span>
                        <span class="text-[7px] font-black uppercase tracking-widest text-gray-500 group-hover:text-yellow-500">Blitz FX</span>
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
            <h3 class="text-xl font-black uppercase italic tracking-tighter text-white">Match Filter</h3>
        </div>

        <div class="space-y-10">
            <!-- Gender Selector -->
            <div class="space-y-4">
                <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">I am looking for</label>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="g in ['male', 'female', 'all']">
                        <button @click="targetGender = g" 
                                :class="targetGender === g ? 'bg-brand-indigo text-white border-brand-indigo shadow-[0_0_15px_rgba(99,102,241,0.4)]' : 'bg-white/5 text-gray-500 border-white/5'"
                                class="py-3 rounded-xl border font-black text-[9px] uppercase tracking-widest transition-all"
                                x-text="g"></button>
                    </template>
                </div>
            </div>

            <!-- Age Range -->
            <div class="space-y-6">
                <div class="flex justify-between items-center ml-2">
                    <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em]">Partner Age</label>
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
                        <p class="text-[7px] font-black uppercase mt-3 text-gray-600 tracking-widest">Minimum Age</p>
                    </div>
                    <!-- Max Age Slider -->
                    <div class="relative">
                        <input type="range" min="18" max="99" x-model="targetAgeMax" 
                               class="w-full h-1 bg-white/10 rounded-lg appearance-none cursor-pointer accent-brand-indigo">
                        <p class="text-[7px] font-black uppercase mt-3 text-gray-600 tracking-widest">Maximum Age</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-4 mt-12">
            <button @click="filterModalOpen = false" class="py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:text-white transition-all">
                Cancel
            </button>
            <button @click="applyFilters()" class="bg-brand-indigo py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-white shadow-xl shadow-brand-indigo/20 hover:scale-105 active:scale-95 transition-all">
                Apply 🎯
            </button>
        </div>
    </div>
</div>
    </div>
</x-app-layout>
<style>
    @keyframes blitz-shake-intense {
        0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
        10% { transform: translate(-5px, -8px) scale(1.05) rotate(2deg); }
        20% { transform: translate(7px, 4px) scale(0.95) rotate(-1deg); }
        30% { transform: translate(-4px, 7px) scale(1.1) rotate(3deg); }
        40% { transform: translate(8px, -5px) scale(0.9) rotate(-2deg); }
        50% { transform: translate(-9px, -2px) scale(1.15) rotate(4deg); }
        60% { transform: translate(4px, 8px) scale(0.85) rotate(-1.5deg); }
        70% { transform: translate(-7px, -7px) scale(1.05) rotate(2.5deg); }
        80% { transform: translate(5px, 5px) scale(0.8) rotate(-3deg); }
        90% { transform: translate(-2px, -4px) scale(1.2) rotate(1deg); }
    }

    /* Жесткий глитч (инверсия, цвета, искажения) */
    @keyframes blitz-glitch-visual {
        0% { filter: hue-rotate(0deg) contrast(1) brightness(1); transform: scale(1); }
        5% { filter: hue-rotate(180deg) invert(1) contrast(5); transform: scale(1.1) skewX(20deg); }
        10% { filter: hue-rotate(90deg) invert(0) contrast(2) brightness(2); transform: scale(0.9) skewX(-15deg); }
        15% { filter: hue-rotate(270deg) invert(1) contrast(8); transform: scale(1.3) rotate(5deg); }
        20% { filter: none; transform: scale(1); }
        25% { filter: brightness(5) contrast(10); transform: scale(1.05) skewY(10deg); }
        100% { filter: none; }
    }

    /* Огненная пульсирующая рамка */
    @keyframes blitz-border-fire {
        0%, 100% { border-color: #ff0000; box-shadow: 0 0 20px #ff0000, inset 0 0 20px #ff0000; }
        20% { border-color: #ffae00; box-shadow: 0 0 40px #ffae00, inset 0 0 30px #ffae00; }
        40% { border-color: #ff4800; box-shadow: 0 0 60px #ff4800, inset 0 0 40px #ff4800; }
        60% { border-color: #ffffff; box-shadow: 0 0 80px #ffffff, inset 0 0 50px #ffffff; }
        80% { border-color: #ff0000; box-shadow: 0 0 50px #ff0000, inset 0 0 30px #ff0000; }
    }

    /* Мигание экрана (вспышки) */
    @keyframes intense-flash {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; background: rgba(255, 0, 0, 0.2); }
    }

    .blitz-visual-logic {
        /* Применяем глитч и вспышки */
        animation: blitz-glitch-visual 0.15s step-end infinite, intense-flash 0.05s infinite !important;
        mix-blend-mode: exclusion;
    }

    .blitz-shaking-logic {
        /* Применяем жесткую тряску и огонь на рамку */
        animation: blitz-shake-intense 0.07s linear infinite !important;
        border: 8px solid !important;
        animation: blitz-border-fire 0.1s infinite !important;
        z-index: 9999 !important;
        background-color: black !important;
    }

    /* Делаем так, чтобы всё внутри видео-окна тоже дрожало и искажалось */
    .blitz-shaking-logic video {
        filter: contrast(2) saturate(5) !important;
    }
</style>
