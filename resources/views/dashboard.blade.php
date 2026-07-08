<x-app-layout>
    <div class="py-10 bg-[#050505] min-h-screen text-white" x-data="{ onlineList: [] }" x-init="window.Echo.join('online-status').here(u => onlineList = u)">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- ГЛАВНАЯ СЕТКА -->
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6">
                    


                    <!-- КАРТА: РУЛЕТКА -->
                    <a href="{{ route('chat') }}" class="group relative overflow-hidden rounded-[3rem] bg-indigo-600 p-10 transition-all hover:shadow-[0_0_50px_rgba(79,70,229,0.3)]">
                        <div class="relative z-20">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-2xl rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:rotate-12 transition-transform">🎲</div>
                            <h2 class="text-4xl font-black leading-none tracking-tighter mb-4 uppercase">Видео<br>Рулетка</h2>
                            <span class="inline-block bg-white text-indigo-600 px-6 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest">Найти пару ➔</span>
                        </div>
                        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-black/20 blur-3xl rounded-full"></div>
                    </a>

                    <!-- КАРТА: SPACES (ВЕРНУЛИ!) -->
                    <a href="{{ route('rooms.index') }}" class="group relative overflow-hidden rounded-[3rem] bg-[#111] border border-white/5 p-10 transition-all hover:border-indigo-500/50">
                        <div class="relative z-20">
                            <div class="w-14 h-14 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:scale-110 transition-transform">👥</div>
                            <h2 class="text-4xl font-black leading-none tracking-tighter mb-4 uppercase text-white">Live<br>Spaces</h2>
                            <span class="inline-block bg-white/5 text-gray-400 px-6 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest group-hover:bg-white group-hover:text-black transition-all">Общие комнаты ➔</span>
                        </div>
                        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-600/5 blur-3xl rounded-full"></div>
                    </a>

                </div>

                <!-- БОКОВАЯ ПАНЕЛЬ (ПРОФИЛЬ) -->
                <div class="bg-[#080808] border border-white/5 rounded-[2.5rem] p-8 flex flex-col items-center text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-[2rem] flex items-center justify-center text-3xl font-black mb-4 shadow-xl">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <h3 class="text-xl font-black tracking-tight">{{ Auth::user()->name }}</h3>
                    <p class="text-indigo-400 text-[9px] font-black uppercase tracking-[0.2em] mt-1">{{ Auth::user()->rank_name }}</p>
                    
                    <div class="w-full mt-8 pt-8 border-t border-white/5 space-y-4">
                        <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-gray-500">
                            <span>Level {{ Auth::user()->level }}</span>
                            <span>{{ Auth::user()->xp_progress }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full shadow-[0_0_10px_#6366f1]" style="width: {{ Auth::user()->xp_progress }}%"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>