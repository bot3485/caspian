<nav class="sticky top-0 z-[300] bg-black/40 backdrop-blur-2xl border-b border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20 items-center">
            
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-[0_0_15px_rgba(79,70,229,0.4)]">
                        <x-application-logo class="w-6 h-6 fill-white" />
                    </div>
                    <span class="text-lg font-black tracking-tighter uppercase hidden sm:block">Caspian</span>
                </a>

                <div class="hidden space-x-1 sm:flex">
                    <x-nav-link :href="route('chat')" :active="request()->routeIs('chat')" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest !border-none hover:bg-white/5 transition-colors">Roulette</x-nav-link>
                    <x-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms.*')" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest !border-none hover:bg-white/5 transition-colors">Spaces</x-nav-link>
                </div>
            </div>

           <div class="flex items-center gap-2 sm:gap-4">
                <!-- Глобальный онлайн -->
                <div class="flex items-center gap-2 px-3 py-1.5 bg-green-500/5 border border-green-500/10 rounded-xl"
                    x-data="{ globalOnline: 0 }" 
                    x-init="window.Echo.join('online-status').here(u => globalOnline = u.length).joining(u => globalOnline++).leaving(u => globalOnline--)">
                    <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse shadow-[0_0_8px_#22c55e]"></div>
                    <span class="text-[9px] font-black text-white leading-none uppercase" x-text="globalOnline"></span>
                    <span class="text-[9px] font-black text-white leading-none uppercase hidden xs:inline">online</span>
                </div>

                <!-- Кнопка Глобального Мессенджера (Теперь видна везде: и на мобилках, и на десктопе) -->
                <button @click="globalSidebarOpen = !globalSidebarOpen" 
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-indigo-600/20 hover:border-indigo-500/50 transition-all group relative">
                    <span class="text-lg sm:text-xl group-hover:scale-110 transition-transform">💬</span>
                    <!-- Индикатор печати -->
                    <div x-show="isPartnerTyping" class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-500 rounded-full animate-ping"></div>
                </button>

                <!-- Десктопное меню профиля (Скрыто на телефонах, так как там есть нижнее меню) -->
                <div class="hidden sm:block">
                    <x-dropdown align="right" width="64">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 p-1 pr-4 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 transition-all group">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-sm font-black shadow-lg">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                                <div class="text-left">
                                    <p class="text-[10px] font-black text-white leading-none uppercase tracking-tighter">{{ Auth::user()->name }}</p>
                                    <p class="text-[9px] font-bold mt-1 uppercase tracking-widest text-gray-500">{{ Auth::user()->rank_name }}</p>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="bg-[#0a0a0a] border border-white/10 rounded-2xl overflow-hidden shadow-2xl p-2 space-y-1">
                                <x-dropdown-link :href="route('profile.edit')" class="!bg-transparent !text-gray-400 hover:!text-white hover:!bg-white/5 !rounded-xl !p-3 font-bold text-xs">
                                    👤 Настройки профиля
                                </x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="!bg-transparent !text-red-500 hover:!bg-red-500/10 !rounded-xl !p-3 font-bold text-xs">
                                        🚪 Выйти из системы
                                    </x-dropdown-link>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </div>
</nav>