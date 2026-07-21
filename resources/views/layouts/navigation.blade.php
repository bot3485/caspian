<nav class="sticky top-0 z-[300] bg-[#020202]/70 backdrop-blur-3xl border-b border-white/[0.04]"
     x-data="{ 
         init() {
            // Синхронизируем класс на body для всех страниц
            if (this.ledsActive) document.body.classList.add('leds-on');
            else document.body.classList.remove('leds-on');
         },
         ledsActive: localStorage.getItem('caspian_leds') !== 'false',
         toggleLeds() {
             this.ledsActive = !this.ledsActive;
             localStorage.setItem('caspian_leds', this.ledsActive);
             if (this.ledsActive) document.body.classList.add('leds-on');
             else document.body.classList.remove('leds-on');
         }
     }">
    
    <!-- ВЕРХНЯЯ LED-ЛЕНТА -->
    <div class="absolute top-0 inset-x-0 h-[2px] transition-all duration-1000 z-50"
         :class="ledsActive ? 'led-top-line' : 'bg-white/5 shadow-none'"></div>

    <div class="max-w-[1600px] mx-auto px-4 sm:px-6">
        <div class="flex justify-between h-[72px] items-center">
            
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl overflow-hidden border border-white/10"
                         :class="ledsActive ? 'shadow-[0_0_15px_#6366f1]' : ''">
                        <img src="{{ asset('roulette.jpg') }}" class="w-full h-full object-cover">
                    </div>
                    <span class="text-lg font-black tracking-tighter uppercase italic">Caspian</span>
                </a>
            </div>

            <!-- ПРАВАЯ ЧАСТЬ С РУБИЛЬНИКОМ -->
            <div class="flex items-center gap-4">
                
                <!-- КИБЕР-РУБИЛЬНИК -->
                <div class="flex items-center gap-3 px-4 py-2 bg-white/[0.03] border border-white/10 rounded-2xl">
                    <span class="text-[8px] font-black uppercase tracking-widest text-gray-500">Power</span>
                    <button @click="toggleLeds()" 
                            class="relative w-10 h-6 rounded-full transition-all duration-500 border shadow-inner"
                            :class="ledsActive ? 'bg-brand-indigo/30 border-brand-indigo/50' : 'bg-gray-800 border-white/10'">
                        <div class="absolute top-1 transition-all duration-500 w-4 h-4 rounded-full shadow-lg"
                             :class="ledsActive ? 'left-5 bg-white shadow-[0_0_10px_#fff]' : 'left-1 bg-gray-500'"></div>
                    </button>
                    <span class="text-[9px] font-black uppercase tracking-widest transition-colors duration-500"
                          :class="ledsActive ? 'text-brand-cyan animate-pulse' : 'text-gray-700'">LED</span>
                </div>

                <!-- Online Status -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 sm:px-3 rounded-full bg-white/[0.02] border border-white/[0.05]"
                    x-data="{ online: 0 }" 
                    x-init="window.Echo.join('online-status').here(u => online = u.length).joining(u => online++).leaving(u => online--)">
                    <div class="relative flex h-1.5 w-1.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-green-500 shadow-[0_0_8px_#22c55e]"></span>
                    </div>
                    <span class="text-[9px] font-black text-gray-300 tracking-widest font-mono">
                        <span x-text="online"></span><span class="hidden sm:inline"> ON</span>
                    </span>
                </div>

                <!-- Language Selector -->
                <div class="relative" x-data="{ langOpen: false }">
                    <button @click="langOpen = !langOpen" 
                            class="h-10 px-3 rounded-xl bg-transparent hover:bg-white/5 border border-transparent hover:border-white/10 flex items-center justify-center gap-1.5 transition-all duration-300 group">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 group-hover:text-white transition-colors">
                            @if(App::getLocale() === 'en') EN
                            @elseif(App::getLocale() === 'ru') RU
                            @else TR
                            @endif
                        </span>
                        <svg class="w-3 h-3 text-gray-500 group-hover:text-white transition-all duration-300" :class="langOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <div x-show="langOpen" 
                         @click.away="langOpen = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-3 scale-95 blur-sm"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100 blur-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-end="opacity-0 translate-y-2 scale-95 blur-sm"
                         x-cloak
                         class="absolute right-0 mt-3 w-36 bg-[#0a0a0a]/95 backdrop-blur-3xl border border-white/10 rounded-2xl p-1.5 shadow-[0_30px_60px_rgba(0,0,0,0.7)] z-50">
                        
                        <button @click="changeLanguage('en')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-white/10 text-[9px] font-black uppercase tracking-[0.2em] transition-all text-gray-400 hover:text-white">
                            <span>English</span> <span class="text-[12px] opacity-70">🇺🇸</span>
                        </button>
                        <button @click="changeLanguage('ru')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-white/10 text-[9px] font-black uppercase tracking-[0.2em] transition-all text-gray-400 hover:text-white">
                            <span>Русский</span> <span class="text-[12px] opacity-70">🇷🇺</span>
                        </button>
                        <button @click="changeLanguage('tr')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-white/10 text-[9px] font-black uppercase tracking-[0.2em] transition-all text-gray-400 hover:text-white">
                            <span>Türkçe</span> <span class="text-[12px] opacity-70">🇹🇷</span>
                        </button>
                    </div>
                </div>

                <!-- Chat Toggle -->
                <button @click="globalSidebarOpen = !globalSidebarOpen" 
                    class="relative w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-500 group border"
                    :class="{
                        'bg-brand-indigo/20 border-brand-indigo/40 shadow-[0_0_20px_rgba(99,102,241,0.3)]': hasUnreadFriends(),
                        'bg-amber-500/10 border-amber-500/30 shadow-[0_0_15px_rgba(245,158,11,0.2)]': !hasUnreadFriends() && hasUnreadHistory(),
                        'bg-white/[0.03] border-white/[0.08] hover:bg-white/10': !hasUnread()
                    }">
                
                    <svg class="w-4 h-4 transition-all duration-500" 
                        :class="hasUnread() ? 'text-white animate-pulse scale-110' : 'text-gray-400 group-hover:text-white'" 
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>

                    <template x-if="hasNewNotification">
                        <div class="absolute -top-1.5 -right-1.5 z-10 flex items-center justify-center">
                            <span class="animate-ping absolute inline-flex h-4 w-4 rounded-full bg-brand-indigo opacity-75"></span>
                            <div class="relative bg-brand-indigo text-white w-5 h-5 rounded-full flex items-center justify-center shadow-[0_0_15px_rgba(99,102,241,1)] border-2 border-[#020202]">
                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                        </div>
                    </template>

                    <template x-if="hasUnread()">
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                                :class="hasUnreadFriends() ? 'bg-brand-indigo' : 'bg-amber-500'"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 border-2 border-[#020202]"
                                :class="hasUnreadFriends() ? 'bg-brand-indigo' : 'bg-amber-500'"></span>
                        </span>
                    </template>
                </button>

                <!-- User Dropdown -->
                <div class="pl-2 sm:pl-4 ml-1 sm:ml-2 border-l border-white/[0.08]">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 p-1 sm:pr-3 rounded-full hover:bg-white/5 transition-all duration-300 group">
                                <div class="relative w-8 h-8 rounded-full overflow-hidden border border-white/10 group-hover:border-brand-indigo/50 transition-colors shadow-inner">
                                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-[11px] font-black">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold tracking-widest hidden sm:block text-gray-400 group-hover:text-white transition-colors uppercase">{{ Auth::user()->name }}</span>
                                <svg class="w-3 h-3 text-gray-600 group-hover:text-white hidden sm:block transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="bg-[#0a0a0a]/95 backdrop-blur-3xl border border-white/10 rounded-2xl p-1.5 shadow-[0_30px_60px_rgba(0,0,0,0.7)]">
                                <div class="px-3 py-2 mb-1 border-b border-white/[0.05]">
                                    <p class="text-[8px] font-black uppercase tracking-[0.2em] text-gray-500">{{ App::getLocale() === 'ru' ? 'Вы вошли как' : 'Logged in as' }}</p>
                                    <p class="text-[11px] font-bold text-white truncate mt-0.5">{{ Auth::user()->name }}</p>
                                </div>

                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2.5 text-[9px] font-black uppercase tracking-[0.2em] text-gray-400 hover:text-white hover:bg-white/10 rounded-xl transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ App::getLocale() === 'ru' ? 'Настройки' : (App::getLocale() === 'tr' ? 'Ayarlar' : 'Settings') }}
                                </a>
                                
                                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2.5 text-[9px] font-black uppercase tracking-[0.2em] text-red-500 hover:bg-red-500/10 rounded-xl transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        {{ App::getLocale() === 'ru' ? 'Выйти' : (App::getLocale() === 'tr' ? 'Oturumu Kapat' : 'Logout') }}
                                    </button>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </div>
</nav>