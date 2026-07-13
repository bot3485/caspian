<nav class="sticky top-0 z-[300] bg-black/60 backdrop-blur-3xl border-b border-white/[0.05]">
    <div class="max-w-[1600px] mx-auto px-6">
        <div class="flex justify-between h-20 items-center">
            
            <!-- Logo Group -->
            <div class="flex items-center gap-10">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <img src="{{ asset('roulette.jpg') }}" class="w-9 h-9 rounded-lg" alt="C">
                    <span class="text-lg font-black tracking-tighter uppercase italic hidden lg:block">Caspian</span>
                </a>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center bg-white/5 rounded-2xl p-1 gap-1 border border-white/5">
                    <a href="{{ route('chat') }}" 
                       class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ request()->routeIs('chat') ? 'bg-indigo-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' }}">
                       Roulette
                    </a>
                    <a href="{{ route('rooms.index') }}" 
                       class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ request()->routeIs('rooms.*') ? 'bg-indigo-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' }}">
                       Spaces
                    </a>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-3">
                <!-- Status -->
                <div class="flex items-center gap-2 px-4 py-2 bg-green-500/5 border border-green-500/10 rounded-xl"
                    x-data="{ online: 0 }" 
                    x-init="window.Echo.join('online-status').here(u => online = u.length).joining(u => online++).leaving(u => online--)">
                    <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-[10px] font-black text-white" x-text="online"></span>
                </a>

                <!-- Chat Toggle -->
                <button @click="globalSidebarOpen = !globalSidebarOpen" 
                        class="w-11 h-11 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-indigo-600/20 transition-all relative">
                    <span class="text-lg">💬</span>
                    <div x-show="isPartnerTyping" class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-500 rounded-full animate-ping"></div>
                </button>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="64">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-3 p-1 pr-3 bg-white/5 border border-white/10 rounded-xl hover:border-white/20 transition-all">
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-xs font-black">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-tight hidden sm:block">{{ Auth::user()->name }}</span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="bg-[#0a0a0a] border border-white/10 rounded-xl p-2 space-y-1">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-[10px] font-black uppercase text-gray-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Settings</a>
                            <div class="h-px bg-white/5 mx-2"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-[10px] font-black uppercase text-red-500 hover:bg-red-500/10 rounded-lg transition-all">Terminate Session</button>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
