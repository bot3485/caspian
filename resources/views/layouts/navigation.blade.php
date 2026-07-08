<nav x-data="{ open: false }" class="sticky top-0 z-[100] bg-black/40 backdrop-blur-2xl border-b border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20 items-center">
            
            <!-- ЛЕВАЯ ЧАСТЬ: Лого и Ссылки -->
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-[0_0_15px_rgba(79,70,229,0.4)]">
                        <x-application-logo class="w-6 h-6 fill-white" />
                    </div>
                    <span class="text-lg font-black tracking-tighter uppercase hidden sm:block">Caspian</span>
                </a>

                <div class="hidden space-x-1 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest !border-none hover:bg-white/5 transition-colors">Hub</x-nav-link>
                    <x-nav-link :href="route('chat')" :active="request()->routeIs('chat')" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest !border-none hover:bg-white/5 transition-colors">Roulette</x-nav-link>
                    <x-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms.*')" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest !border-none hover:bg-white/5 transition-colors">Spaces</x-nav-link>
                </div>
            </div>

            <!-- ПРАВАЯ ЧАСТЬ: Онлайн и Профиль -->
            <div class="hidden sm:flex sm:items-center gap-6">
                
                <!-- КРАСИВЫЙ ОБЩИЙ ОНЛАЙН -->
                <!-- ВСЕГО НА САЙТЕ (Глобальный) -->
                <div class="flex items-center gap-3 px-4 py-2 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl"
                    x-data="{ globalOnline: 0 }" 
                    x-init="window.Echo.join('online-status').here(u => globalOnline = u.length).joining(u => globalOnline++).leaving(u => globalOnline--)">
                    <div class="text-right">
                        <p class="text-[7px] font-black text-indigo-400/60 uppercase tracking-[0.2em] leading-none mb-1">Всего в сети</p>
                        <p class="text-xs font-black text-white leading-none" x-text="globalOnline + ' USERS'"></p>
                    </div>
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse shadow-[0_0_10px_#22c55e]"></div>
                </div>

                <!-- Меню профиля -->
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

            <!-- Mobile menu button -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="p-3 bg-white/5 rounded-xl text-gray-400">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-black border-b border-white/5">
        <div class="pt-4 pb-1 border-t border-white/5 px-4">
            <div class="font-black text-sm uppercase">{{ Auth::user()->name }}</div>
            <div class="font-medium text-xs text-gray-500">{{ Auth::user()->email }}</div>
        </div>
        <div class="p-4 space-y-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="rounded-xl !border-none !bg-white/5 !text-white font-bold">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('chat')" :active="request()->routeIs('chat')" class="rounded-xl !border-none !bg-white/5 !text-white font-bold">Roulette</x-responsive-nav-link>
        </div>
    </div>
</nav>