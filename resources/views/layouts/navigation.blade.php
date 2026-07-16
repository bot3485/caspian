<nav class="sticky top-0 z-[300] bg-[#020202]/70 backdrop-blur-3xl border-b border-white/[0.04] supports-[backdrop-filter]:bg-[#020202]/50">
    <!-- Тонкая градиентная линия снизу для эффекта премиального свечения -->
    <div class="absolute inset-x-0 -bottom-px h-px bg-gradient-to-r from-transparent via-brand-indigo/30 to-transparent"></div>
    
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6">
        <div class="flex justify-between h-[72px] items-center">
            
            <!-- Left: Logo & Navigation -->
            <div class="flex items-center gap-6 sm:gap-8">
                
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="relative flex items-center justify-center w-10 h-10 rounded-xl bg-white/[0.03] border border-white/10 overflow-hidden group-hover:border-brand-indigo/50 transition-all duration-500 shadow-[0_0_15px_rgba(0,0,0,0.5)]">
                        <img src="{{ asset('roulette.jpg') }}" class="w-full h-full object-cover opacity-90 group-hover:scale-110 group-hover:opacity-100 transition-transform duration-700" alt="Caspian">
                    </div>
                    <span class="text-lg font-black tracking-tighter uppercase text-white/90 group-hover:text-white transition-colors hidden md:block" style="font-style: oblique 10deg;">
                        Caspian
                    </span>
                </a>

                <!-- Desktop Nav (Elegant Pills with Glow) -->
                <div class="hidden md:flex items-center gap-1 p-1 bg-white/[0.02] border border-white/[0.05] rounded-2xl">
                    <a href="{{ route('chat') }}" 
                       class="relative px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] transition-all duration-300 {{ request()->routeIs('chat') ? 'text-white bg-white/10 shadow-inner' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                       {{ __('navigation.Roulette') }}
                       @if(request()->routeIs('chat'))
                           <!-- Светящаяся точка активного раздела -->
                           <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-[2px] bg-brand-indigo rounded-full shadow-[0_0_8px_rgba(99,102,241,0.9)]"></div>
                       @endif
                    </a>
                    <a href="{{ route('rooms.index') }}" 
                       class="relative px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] transition-all duration-300 {{ request()->routeIs('rooms.*') ? 'text-white bg-white/10 shadow-inner' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                       {{ __('navigation.Rooms') }}
                       @if(request()->routeIs('rooms.*'))
                           <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-[2px] bg-brand-indigo rounded-full shadow-[0_0_8px_rgba(99,102,241,0.9)]"></div>
                       @endif
                    </a>
                </div>
            </div>

            <!-- Right: Controls -->
            <div class="flex items-center gap-2 sm:gap-4">
                
                <!-- Online Status (Скрыт на мелких экранах для чистоты) -->
                <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/[0.02] border border-white/[0.05]"
                     x-data="{ online: 0 }" 
                     x-init="window.Echo.join('online-status').here(u => online = u.length).joining(u => online++).leaving(u => online--)">
                    <div class="relative flex h-1.5 w-1.5">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-green-500 shadow-[0_0_8px_#22c55e]"></span>
                    </div>
                    <span class="text-[9px] font-bold text-gray-400 tracking-widest font-mono" x-text="online + ' ON'"></span>
                </div>

                <!-- Language Selector (Refined Dropdown) -->
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

                <!-- Chat Toggle (Sleek SVG instead of emoji) -->
                <button @click="globalSidebarOpen = !globalSidebarOpen" 
                        class="relative w-10 h-10 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center justify-center hover:bg-brand-indigo/20 hover:border-brand-indigo/40 hover:shadow-[0_0_20px_rgba(99,102,241,0.2)] transition-all duration-300 group">
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <div x-show="isPartnerTyping" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-brand-indigo rounded-full border-2 border-[#020202] shadow-[0_0_8px_rgba(99,102,241,0.8)] animate-pulse"></div>
                </button>

                <!-- User Dropdown (Premium Avatar) -->
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
                                <!-- Profile Mini-Header -->
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