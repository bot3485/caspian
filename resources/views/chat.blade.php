<x-app-layout>

<div class="fixed top-[120px] bottom-0 left-0 right-0 w-full bg-[#020202] overflow-hidden px-3 pt-4 pb-28 md:p-4 overscroll-none" 
     style="height: calc(100vh - 120px - env(safe-area-inset-bottom));
         x-init="
            // Инициализируем выбранную страну пользователя из БД
            targetCountry = '{{ Auth::user()->target_country }}';
         "
         x-data="{
            targetCountry: 'global',
            async updateTargetCountry(country) {
                this.targetCountry = country;
                try {
                    await window.axios.post('/profile', { 
                        _method: 'PATCH',
                        target_country: country 
                    });
                window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { msg: '{{ __('chatroulette.Target_Country_Updated') }}' } 
                    }));
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { msg: '{{ __('chatroulette.Update_Failed') }}' } 
                    }));
                }
            }
         }">
        
        <!-- 1. VIDEO ECOSYSTEM (Dual-Engine Split Screen) -->
        <div class="flex flex-col md:flex-row w-full h-full gap-2 md:gap-4 transition-all duration-700 ease-in-out">
            
            <!-- PARTNER CONTAINER (REMOTE) -->
            <div @click="toggleFocus('remote')"
                 class="relative overflow-hidden rounded-[2.5rem] bg-[#050505] border border-white/5 transition-all duration-700 ease-in-out cursor-pointer group"
                 :class="{
                    'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
                    'h-[70%] md:h-full md:w-[75%] shadow-[0_0_50px_rgba(0,0,0,0.5)] z-10': layoutFocus === 'remote',
                    'h-[30%] md:h-full md:w-[25%] opacity-40 hover:opacity-100 scale-[0.98]': layoutFocus === 'local'
                 }">
                
                <video x-ref="remoteVideo" 
                    id="remoteVideo"
                    autoplay 
                    playsinline 
                    webkit-playsinline 
                    @loadedmetadata="$el.play().catch(e => console.log('Remote play blocked', e))"
                    class="w-full h-full object-cover transition-all duration-1000 bg-black" 
                    :class="[isRemoteBlurred ? 'blur-[100px] opacity-40' : 'opacity-100', getFilterClass('remote')]">
                </video>

                <!-- NEW: COUNTRY TARGET SELECTOR (Элегантный переключатель стран в рулетке) -->
                <div x-show="state === 'idle' || state === 'searching'" 
                     class="absolute top-6 right-6 z-30 pointer-events-auto"
                     x-data="{ countryDropdown: false }">
                    <button @click.stop="countryDropdown = !countryDropdown" 
                            class="px-4 py-2.5 bg-black/60 backdrop-blur-xl border border-white/10 rounded-2xl flex items-center gap-2 text-[10px] font-black uppercase tracking-wider hover:border-brand-indigo/40 transition-all shadow-2xl">
                        <span x-text="
                            targetCountry === 'global' ? '🌍 {{__('chatroulette.Global_Match')}}' : 
                            (targetCountry === 'az' ? '🇦🇿 Azerbaijan' : 
                            (targetCountry === 'ge' ? '🇬🇪 Georgia' : 
                            (targetCountry === 'ru' ? '🇷🇺 Russia' : 
                            (targetCountry === 'tr' ? '🇹🇷 Turkey' : 
                            (targetCountry === 'kz' ? '🇰🇿 Kazakhstan' : 
                            (targetCountry === 'uz' ? '🇺🇿 Uzbekistan' : 
                            (targetCountry === 'ua' ? '🇺🇦 Ukraine' : 
                            (targetCountry === 'de' ? '🇩🇪 Germany' : 
                            (targetCountry === 'us' ? '🇺🇸 USA' : '🌍 {{__('chatroulette.Global_Match')}} ')))))))))
                        "></span>
                        <span class="text-[7px] opacity-40">▼</span>
                    </button>
                    
                    <!-- Выпадающий список с кастомной прокруткой -->
                    <div x-show="countryDropdown" 
                         @click.away="countryDropdown = false"
                         x-transition
                         class="absolute right-0 mt-2 w-52 bg-[#0a0a0a]/95 backdrop-blur-3xl border border-white/10 rounded-2xl p-2 space-y-1 shadow-2xl z-50 max-h-64 overflow-y-auto custom-scrollbar">
                        
                        <button @click.stop="updateTargetCountry('global'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg" class="w-4 h-3.5 object-cover" crossorigin="anonymous" loading="lazy" alt="global"> {{__('chatroulette.Global_Match')}}
                        </button>
                        <div class="h-px bg-white/5 mx-2"></div>
                        
                        <!-- Региональные приоритеты -->
                        <button @click.stop="updateTargetCountry('az'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/az.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="az"> Azerbaijan
                        </button>
                        <button @click.stop="updateTargetCountry('ge'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/ge.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="ge"> Georgia
                        </button>
                        <button @click.stop="updateTargetCountry('tr'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/tr.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="tr"> Turkey
                        </button>
                        <button @click.stop="updateTargetCountry('ru'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/ru.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="ru"> Russia
                        </button>
                        <button @click.stop="updateTargetCountry('kz'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/kz.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="kz"> Kazakhstan
                        </button>
                        <button @click.stop="updateTargetCountry('uz'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/uz.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="uz"> Uzbekistan
                        </button>
                        <button @click.stop="updateTargetCountry('ua'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/ua.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="ua"> Ukraine
                        </button>
                        <button @click.stop="updateTargetCountry('de'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/de.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="de"> Germany
                        </button>
                        <button @click.stop="updateTargetCountry('us'); countryDropdown = false;" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-brand-indigo text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <img src="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/us.svg" class="w-4 h-3 object-cover rounded-sm" crossorigin="anonymous" loading="lazy" alt="us"> USA
                        </button>
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

                <!-- PARTNER INFO TAG (Теперь выводит Флаг Собеседника) -->
                <div x-show="state === 'connected' && partnerData" 
                     class="absolute top-6 left-6 bg-[#0a0a0a] p-2.5 pr-6 rounded-3xl flex items-center gap-3 border border-white/10 shadow-2xl transition-all"
                     x-transition:enter="translate-x-[-20px] opacity-0" x-transition:enter-end="translate-x-0 opacity-100">
                    
                    <div class="relative">
                        <div class="w-10 h-10 bg-brand-indigo rounded-2xl flex items-center justify-center font-black text-sm shadow-lg border border-white/20" x-text="partnerData?.name?.[0]"></div>
                        <!-- Микро-флаг поверх аватара партнера -->
                        <div class="absolute -bottom-1 -right-1 bg-black/80 rounded-full w-5 h-5 flex items-center justify-center shadow-md border border-white/10 overflow-hidden">
                            <img :src="partnerData?.country_flag || 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg'" 
                                class="w-full h-full object-cover rounded-full" 
                                crossorigin="anonymous"
                                alt="flag">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase italic tracking-tighter" x-text="partnerData?.name"></span>
                            <span class="bg-brand-indigo/20 text-brand-indigo text-[7px] font-black px-1.5 py-0.5 rounded" x-text="'LVL ' + (partnerData?.level || 1)"></span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <div class="w-1.5 h-1.5 rounded-full" :class="partnerState === 'active' ? 'bg-green-500 shadow-[0_0_8px_#22c55e]' : 'bg-amber-500 animate-pulse'"></div>
                            <span class="text-[8px] font-black uppercase tracking-widest text-gray-400" 
                                  x-text="partnerState === 'active' ? (partnerData?.rank_name || 'Regular') : 'Away'"></span>
                        </div>
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
                 class="relative overflow-hidden rounded-[2.5rem] bg-[#050505] border border-white/5 transition-all duration-700 ease-in-out cursor-pointer group"
                 :class="{
                    'h-1/2 md:h-full md:w-1/2': layoutFocus === 'split',
                    'h-[70%] md:h-full md:w-[75%] shadow-[0_0_50px_rgba(0,0,0,0.5)] z-10': layoutFocus === 'local',
                    'h-[30%] md:h-full md:w-[25%] opacity-40 hover:opacity-100 scale-[0.98]': layoutFocus === 'remote'
                 }">
                
                <video x-ref="localVideo" 
                    id="localVideo" 
                    autoplay 
                    muted 
                    playsinline 
                    webkit-playsinline
                    @loadedmetadata="$el.play()"
                    class="w-full h-full object-cover scale-x-[-1] transition-all duration-1000 bg-black"
                    :class="getFilterClass('local')">
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
    </div>
</x-app-layout>
<style>
    /* Делаем шапку полностью прозрачной с эффектом матового стекла */
    header, nav, .bg-white, .bg-gray-800 {
        background-color: rgba(2, 2, 2, 0.1) !important; /* почти 100% прозрачность */
        backdrop-filter: blur(12px) !important; /* размытие заднего фона (видео) */
        -webkit-backdrop-filter: blur(12px) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important; /* тонкая грань */
    }

    /* Выделяем кнопки и ссылки в хедере, чтобы они были отличимы */
    header a, header button, nav a, nav button {
        background-color: rgba(255, 255, 255, 0.03) !important; /* легкая подложка */
        border: 1px solid rgba(255, 255, 255, 0.08) !important; /* тонкий контур */
        border-radius: 14px !important; /* скругление в стиле Caspian */
        padding: 8px 16px !important;
        transition: all 0.3s ease !important;
    }

    /* Эффект при наведении на кнопки */
    header a:hover, header button:hover, nav a:hover, nav button:hover {
        background-color: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(99, 102, 241, 0.4) !important; /* твоя подсветка brand-indigo */
        color: #ffffff !important;
    }
</style>
