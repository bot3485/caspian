<nav class="sticky top-0 z-[300] bg-black/10 backdrop-blur-2xl border-b border-b-white/[0.05]">
    <div class="max-w-[1600px] mx-auto px-6">
        <div class="flex justify-between h-20 items-center">
            
            <!-- Logo Group -->
            <div class="flex items-center gap-10">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-80 transition-all">
                    <img src="{{ asset('roulette.jpg') }}" class="w-9 h-9 rounded-lg shadow-lg shadow-brand-indigo/20" alt="C">
                    <span class="text-lg font-black tracking-tighter uppercase italic hidden lg:block">Caspian</span>
                </a>

                <!-- Desktop Nav (Плавающие пилюли) -->
                <div class="hidden md:flex items-center bg-white/5 backdrop-blur-md rounded-2xl p-1 border border-white/10">
                    <a href="{{ route('chat') }}" 
                       class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ request()->routeIs('chat') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                       Roulette
                    </a>
                    <a href="{{ route('rooms.index') }}" 
                       class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ request()->routeIs('rooms.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                       Spaces
                    </a>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-3">
                
                <!-- Online Status (Элегантный прозрачный индикатор) -->
                <div class="flex items-center gap-2 px-4 py-2 bg-white/5 backdrop-blur-md border border-white/10 rounded-xl"
                    x-data="{ online: 0 }" 
                    x-init="window.Echo.join('online-status').here(u => online = u.length).joining(u => online++).leaving(u => online--)">
                    <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse shadow-[0_0_10px_#22c55e]"></div>
                    <span class="text-[10px] font-black text-white/90" x-text="online"></span>
                </div>

                <!-- NEW: Language Selector Component (Alpine.js) -->
                <div class="relative" x-data="{ langOpen: false }">
                    <button @click="langOpen = !langOpen" 
                            class="h-11 px-3 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center gap-2 hover:bg-white/10 transition-all text-[10px] font-black uppercase tracking-widest">
                        <span>
                            @if(App::getLocale() === 'en')
                                🇺🇸 EN
                            @elseif(App::getLocale() === 'ru')
                                🇷🇺 RU
                            @else
                                🇹🇷 TR
                            @endif
                        </span>
                        <span class="text-[7px] opacity-40">▼</span>
                    </button>
                    
                    <!-- Language Dropdown Menu -->
                    <div x-show="langOpen" 
                         @click.away="langOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-cloak
                         class="absolute right-0 mt-2 w-32 bg-[#0a0a0a]/90 backdrop-blur-2xl border border-white/10 rounded-2xl p-2 space-y-1 shadow-2xl">
                         
                        <a href="{{ route('lang.switch', 'en') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-indigo-600 text-[10px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <span>🇺🇸</span> EN
                        </a>
                        <a href="{{ route('lang.switch', 'ru') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-indigo-600 text-[10px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <span>🇷🇺</span> RU
                        </a>
                        <a href="{{ route('lang.switch', 'tr') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-indigo-600 text-[10px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-white">
                            <span>🇹🇷</span> TR
                        </a>
                    </div>
                </div>

                <!-- Chat Toggle -->
                <button @click="globalSidebarOpen = !globalSidebarOpen" 
                        class="w-11 h-11 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-brand-indigo/20 hover:border-brand-indigo/30 transition-all relative">
                    <span class="text-lg">💬</span>
                    <div x-show="isPartnerTyping" class="absolute -top-1 -right-1 w-3 h-3 bg-brand-indigo rounded-full animate-ping"></div>
                </button>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="64">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-3 p-1 pr-3 bg-white/5 border border-white/10 rounded-xl hover:border-white/20 transition-all">
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-xs font-black shadow-inner">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-tight hidden sm:block text-white/80">{{ Auth::user()->name }}</span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="bg-[#0a0a0a]/90 backdrop-blur-2xl border border-white/10 rounded-2xl p-2 space-y-1 shadow-2xl">
                            <!-- Мультиязычные кнопки навигации в профиле -->
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-[10px] font-black uppercase text-gray-400 hover:text-white hover:bg-indigo-600 rounded-xl transition-all">
                                {{ App::getLocale() === 'ru' ? 'Настройки' : (App::getLocale() === 'tr' ? 'Ayarlar' : 'Settings') }}
                            </a>
                            <div class="h-px bg-white/5 mx-2"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-[10px] font-black uppercase text-red-500 hover:bg-red-500/10 rounded-xl transition-all">
                                    {{ App::getLocale() === 'ru' ? 'Выйти из сессии' : (App::getLocale() === 'tr' ? 'Oturumu Kapat' : 'Terminate Session') }}
                                </button>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>