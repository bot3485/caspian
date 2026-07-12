<x-app-layout>
    <div class="absolute inset-0 bg-black flex items-center justify-center overflow-hidden">
        
        <!-- ВИДЕО СОБЕСЕДНИКА (На весь экран) -->
        <video x-ref="remoteVideo" autoplay playsinline webkit-playsinline 
               class="absolute inset-0 w-full h-full object-cover transition-all duration-700" 
               :class="isRemoteBlurred ? 'blur-[100px] scale-110 opacity-50' : 'opacity-100'">
        </video>

        <!-- ДЕКОРАТИВНЫЕ СВЕЧЕНИЯ -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <!-- ИНФО-ПАНЕЛЬ ПАРТНЕРА -->
        <div x-show="state === 'connected' && partnerData" class="absolute top-6 left-6 z-50" x-transition>
            <div class="bg-black/40 backdrop-blur-2xl p-2 pr-6 rounded-2xl border border-white/10 flex items-center gap-3 shadow-2xl">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center font-black text-lg shadow-lg" x-text="partnerData?.name?.[0]"></div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black uppercase tracking-tighter" x-text="partnerData?.name"></span>
                        <span class="bg-indigo-600 text-[7px] font-black px-1.5 py-0.5 rounded-md" x-text="'LVL ' + (partnerData?.level || 1)"></span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5 opacity-80">
                        <div class="w-1.5 h-1.5 rounded-full" :class="partnerState === 'active' ? 'bg-green-500 shadow-[0_0_8px_#22c55e]' : (partnerState === 'away' ? 'bg-amber-500' : 'bg-red-500')"></div>
                        <span class="text-[8px] font-black uppercase tracking-widest" x-text="partnerState === 'active' ? partnerData?.rank_name : (partnerState === 'away' ? 'Away' : 'Connecting...')"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ИНДИКАТОР ПИНГА -->
        <div x-show="state === 'connected' && ping > 0" class="absolute top-6 right-6 z-50 bg-black/20 px-3 py-1.5 rounded-full border border-white/5">
            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Ping: <span x-text="ping + 'ms'" :class="ping < 150 ? 'text-green-500' : 'text-red-500'"></span></span>
        </div>

        <!-- ЦЕНТРАЛЬНЫЙ СТАТУС (ПОИСК) -->
        <div x-show="state === 'searching' || state === 'idle'" class="absolute inset-0 flex flex-col items-center justify-center bg-[#050505] z-40">
            <div class="relative w-20 h-20 mb-6">
                <div class="absolute inset-0 border-2 border-indigo-500/30 rounded-full" :class="state === 'searching' && 'animate-ping'"></div>
                <div class="absolute inset-0 flex items-center justify-center text-3xl" x-text="state === 'searching' ? '📡' : '👋'"></div>
            </div>
            <h3 class="text-white font-black uppercase text-[9px] tracking-[0.4em]" x-text="state === 'searching' ? 'Searching Partner...' : 'Ready to Chat?'"></h3>
        </div>

        <!-- СВОЁ ВИДЕО (PIP) -->
        <div x-show="showSelfVideo" class="absolute bottom-28 right-6 md:right-auto md:left-8 w-32 md:w-64 aspect-[3/4] md:aspect-video bg-[#111] rounded-3xl overflow-hidden shadow-2xl border border-white/10 z-50 transition-all">
            <video x-ref="localVideo" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]" :style="beautyFilter ? 'filter: saturate(1.2) contrast(1.1);' : ''"></video>
            <div x-show="!camEnabled" class="absolute inset-0 bg-gray-900 flex items-center justify-center">🚫</div>
        </div>

<!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
        <div class="absolute bottom-6 left-0 right-0 px-4 z-[100] flex flex-col items-center gap-3 pointer-events-none">
            <div class="pointer-events-auto flex flex-col items-center gap-3 w-full max-w-lg">
                
                <!-- ДОП КНОПКИ -->
                <div x-show="controlsOpen" x-transition class="flex items-center gap-1.5 p-1.5 bg-black/60 backdrop-blur-3xl border border-white/10 rounded-2xl">
                    <button @click="toggleMic()" :class="micEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all"><span x-text="micEnabled ? '🎤' : '🔇'"></span></button>
                    <button @click="toggleCam()" :class="camEnabled ? 'bg-white/5' : 'bg-red-600'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all"><span x-text="camEnabled ? '📷' : '🚫'"></span></button>
                    <button @click="isRemoteBlurred = !isRemoteBlurred" :class="isRemoteBlurred ? 'bg-indigo-600' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">🙈</button>
                    <button @click="toggleBeauty()" :class="beautyFilter ? 'bg-pink-600' : 'bg-white/5'" class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-all">✨</button>
                    <button @click="getDevices()" class="w-10 h-10 md:w-12 md:h-12 bg-white/5 rounded-xl flex items-center justify-center transition-all">⚙️</button>
                    
                    <template x-if="state === 'connected'">
                        <div class="flex items-center gap-1.5">
                            <div class="w-px h-6 bg-white/10 mx-1"></div>
                            <!-- КНОПКА ДРУЖБЫ: Скрываем если уже друзья -->
                            <button x-show="!isFriend" @click="toggleContact()" class="h-10 md:h-12 px-4 bg-white/5 rounded-xl font-black text-[9px] uppercase tracking-widest">+ Friend</button>
                            <button @click="reportPartner()" class="w-10 h-10 md:w-12 md:h-12 bg-red-600/10 text-red-500 rounded-xl flex items-center justify-center font-bold">🚩</button>
                        </div>
                    </template>
                </div>

                <!-- ГЛАВНЫЙ ОСТРОВОК -->
                <div class="w-full bg-[#121212]/90 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] p-2 flex items-center justify-between shadow-2xl">
                    <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center transition-transform" :class="controlsOpen && 'rotate-180'">
                        <span class="text-[8px]" x-text="controlsOpen ? '▼' : '⚡'"></span>
                    </button>
                    
                    <div class="flex-1 flex justify-center px-4">
                        <!-- РЕЖИМ: ОБЫЧНАЯ РУЛЕТКА -->
                        <template x-if="!isPersonalCall">
                            <div class="w-full flex gap-2">
                                <template x-if="state === 'idle'"><button @click="startSearch()" class="bg-indigo-600 w-full h-12 rounded-full font-black text-[10px] uppercase shadow-lg">Start Chat</button></template>
                                <template x-if="state === 'searching'"><button @click="stopSearch()" class="bg-red-600 w-full h-12 rounded-full font-black text-[10px] uppercase animate-pulse">Stop</button></template>
                                <template x-if="state === 'connected'">
                                    <div class="flex items-center gap-2 w-full">
                                        <button @click="stopSearch()" class="bg-red-600/20 text-red-500 px-6 h-12 rounded-full font-black text-[10px] uppercase">Stop</button>
                                        <button @click="startSearch()" class="bg-white text-black flex-1 h-12 rounded-full font-black text-[10px] uppercase shadow-xl">Next ➔</button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- РЕЖИМ: ПЕРСОНАЛЬНЫЙ ЗВОНОК (Нет кнопки Next) -->
                        <template x-if="isPersonalCall">
                            <button @click="stopSearch()" class="bg-red-600 text-white w-full h-12 rounded-full font-black text-[10px] uppercase shadow-lg">End Call</button>
                        </template>
                    </div>

                    <button @click="globalSidebarOpen = !globalSidebarOpen" class="w-12 h-12 rounded-full bg-indigo-600/10 text-indigo-400 flex items-center justify-center text-lg border border-indigo-500/20 relative">
                        💬
                        <div x-show="isPartnerTyping" class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-500 rounded-full animate-ping"></div>
                    </button>
                </div>
            </div>
        </div>
</x-app-layout>