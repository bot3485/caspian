<x-app-layout>
    <div class="relative w-full h-[calc(100svh-80px)] bg-black flex items-center justify-center overflow-hidden">
        
        <!-- REMOTE VIDEO -->
        <video x-ref="remoteVideo" autoplay playsinline webkit-playsinline 
               class="absolute inset-0 w-full h-full object-cover transition-all duration-700 bg-black" 
               :class="[isRemoteBlurred ? 'blur-[100px] scale-110 opacity-50' : 'opacity-100', getFilterClass('remote')]">
        </video>

        <!-- PARTNER INFO -->
        <div x-show="state === 'connected' && partnerData" class="absolute top-6 left-6 z-50" x-cloak x-transition>
            <div class="bg-black/40 backdrop-blur-2xl p-2 pr-6 rounded-2xl border border-white/10 flex items-center gap-3 shadow-2xl">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center font-black text-lg shadow-lg" x-text="partnerData?.name?.[0]"></div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black uppercase tracking-tighter" x-text="partnerData?.name"></span>
                        <span class="bg-indigo-600 text-[7px] font-black px-1.5 py-0.5 rounded-md" x-text="'LVL ' + (partnerData?.level || 1)"></span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5 opacity-80">
                        <div class="w-1.5 h-1.5 rounded-full shadow-[0_0_8px]" 
                             :class="{
                                'bg-green-500 shadow-green-500': partnerState === 'active',
                                'bg-amber-500 shadow-amber-500': partnerState === 'away',
                                'bg-red-500 shadow-red-500 animate-pulse': partnerState === 'problem'
                             }"></div>
                        <span class="text-[8px] font-black uppercase tracking-widest" 
                              x-text="partnerState === 'active' ? (partnerData?.rank_name || 'Regular') : (partnerState === 'away' ? 'Away' : 'Network Problem...')"></span>
                    </div>
                </div>
            </div>
        </div>

       <!-- 3. ОБНОВЛЕННЫЙ PING INDICATOR (Вверху справа) -->
        <div x-show="state === 'connected' && ping > 0" class="absolute top-6 right-6 z-50 bg-black/40 px-3 py-1.5 rounded-full border border-white/5" x-cloak>
            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">
                Ping: 
                <span x-text="ping + 'ms'" 
                      :class="{
                          'text-green-500': ping < 100,
                          'text-yellow-500': ping >= 100 && ping < 250,
                          'text-red-500': ping >= 250
                      }">
                </span>
            </span>
        </div>

        <!-- 4. НОВЫЙ TYPING INDICATOR (По центру над кнопками) -->
        <div x-show="isPartnerTyping && state === 'connected'" 
             class="absolute bottom-32 left-1/2 -translate-x-1/2 z-50 bg-indigo-600/80 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/10 shadow-2xl" 
             x-cloak x-transition>
            <div class="flex items-center gap-3">
                <span class="text-[9px] font-black uppercase tracking-widest" x-text="typingPartnerName + ' is typing'"></span>
                <div class="flex gap-1">
                    <span class="w-1 h-1 bg-white rounded-full animate-bounce [animation-delay:-0.3s]"></span>
                    <span class="w-1 h-1 bg-white rounded-full animate-bounce [animation-delay:-0.15s]"></span>
                    <span class="w-1 h-1 bg-white rounded-full animate-bounce"></span>
                </div>
            </div>
        </div>

        <!-- SEARCH STATUS -->
        <div x-show="state === 'searching' || state === 'idle'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-40">
            <div class="relative w-20 h-20 mb-6">
                <div class="absolute inset-0 border-2 border-indigo-500/30 rounded-full" :class="state === 'searching' && 'animate-ping'"></div>
                <div class="absolute inset-0 flex items-center justify-center text-3xl" x-text="state === 'searching' ? '📡' : '👋'"></div>
            </div>
            <h3 class="text-white font-black uppercase text-[9px] tracking-[0.4em]" x-text="state === 'searching' ? 'Searching Partner...' : 'Ready to Chat?'"></h3>
        </div>

        <!-- PIP (SELF VIDEO) -->
        <div x-show="showSelfVideo" class="absolute bottom-28 right-6 md:right-auto md:left-8 w-32 md:w-64 aspect-[3/4] md:aspect-video bg-[#111] rounded-[2rem] overflow-hidden shadow-2xl border border-white/10 z-50 transition-all">
            <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]" :class="getFilterClass('local')"></video>
            <div x-show="!camEnabled" class="absolute inset-0 bg-gray-900 flex items-center justify-center">🚫</div>
        </div>

        <!-- CONTROLS -->
        <div class="absolute bottom-6 left-0 right-0 px-4 z-[100] flex flex-col items-center gap-3 pointer-events-none">
            <div class="pointer-events-auto flex flex-col items-center gap-3 w-full max-w-lg">
                
                <div x-show="controlsOpen" x-transition class="flex items-center gap-1.5 p-1.5 bg-black/60 backdrop-blur-3xl border border-white/10 rounded-2xl overflow-x-auto max-w-full no-scrollbar">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center shrink-0">🎤</button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center shrink-0">📷</button>
                    <button @click="toggleBeauty()" :class="beautyFilter ? 'bg-pink-600' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center shrink-0">✨</button>
                    <button @click="toggleCinema()" :class="cinemaFilter ? 'bg-amber-600' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center shrink-0">🎬</button>
                    <button @click="isRemoteBlurred = !isRemoteBlurred" :class="isRemoteBlurred ? 'bg-indigo-600' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center shrink-0">🙈</button>
                    
                    <template x-if="callContext === 'roulette' && state === 'connected'">
                        <div class="flex items-center gap-1.5 shrink-0">
                            <div class="w-px h-6 bg-white/10 mx-1"></div>
                            <button @click="toggleContact()" :class="isFriend ? 'text-green-500 bg-green-500/10 border-green-500/20' : 'text-white bg-white/5 border-white/10'" class="h-10 md:h-12 px-4 rounded-xl font-black text-[9px] uppercase tracking-widest border" x-text="isFriend ? '- Unfriend' : '+ Friend'"></button>
                            <button @click="reportPartner()" class="w-10 h-10 md:w-12 md:h-12 bg-red-600/10 text-red-500 rounded-xl flex items-center justify-center">🚩</button>
                        </div>
                    </template>
                </div>

                <div class="w-full bg-[#121212]/90 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] p-2 flex items-center justify-between shadow-2xl">
                    <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center transition-transform" :class="controlsOpen && 'rotate-180'">
                        <span class="text-[8px]" x-text="controlsOpen ? '▼' : '⚡'"></span>
                    </button>
                    <div class="flex-1 flex justify-center px-4">
                        <template x-if="callContext !== 'personal'">
                            <div class="w-full flex gap-2">
                                <button x-show="state === 'idle'" @click="startSearch()" class="bg-indigo-600 w-full h-12 rounded-full font-black text-[10px] uppercase">Start Roulette</button>
                                <button x-show="state === 'searching'" @click="stopCall()" class="bg-red-600 w-full h-12 rounded-full font-black text-[10px] uppercase">Stop Search</button>
                                <div x-show="state === 'connected'" class="flex items-center gap-2 w-full">
                                    <button @click="stopCall()" class="bg-red-600/20 text-red-500 px-6 h-12 rounded-full font-black text-[10px] uppercase">Stop</button>
                                    <button @click="startSearch()" class="bg-white text-black flex-1 h-12 rounded-full font-black text-[10px] uppercase">Next ➔</button>
                                </div>
                            </div>
                        </template>
                        <template x-if="callContext === 'personal'">
                            <button @click="stopCall()" class="bg-red-600 text-white w-full h-12 rounded-full font-black text-[10px] uppercase tracking-widest">End Personal Call</button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>