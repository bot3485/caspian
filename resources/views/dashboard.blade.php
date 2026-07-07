<x-app-layout>
    <div class="py-10 bg-[#050505] min-h-screen text-white" 
         x-data="{ onlineList: [], friends: [] }" 
         x-init="
            window.Echo.join('online-status')
                .here(u => onlineList = u)
                .joining(u => onlineList.push(u))
                .leaving(u => onlineList = onlineList.filter(x => x.id !== u.id));
            fetch('/chat/contacts').then(r => r.json()).then(d => friends = d.contacts);
         ">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div>
                    <h1 class="text-5xl font-black tracking-tighter bg-gradient-to-r from-white via-white to-gray-500 bg-clip-text text-transparent italic">
                        CASPIAN <span class="text-indigo-500">2.1</span>
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('leaderboard') }}" class="bg-white/5 border border-white/10 px-5 py-3 rounded-2xl hover:bg-indigo-600 transition-all font-black text-[10px] uppercase tracking-widest">🏆 Топ</a>
                    <div class="bg-white/5 border border-white/10 px-5 py-3 rounded-2xl flex items-center gap-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest" x-text="onlineList.length + ' online'"></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- ГЛАВНАЯ СЕТКА -->
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('chat') }}" class="md:row-span-2 group relative overflow-hidden rounded-[3rem] bg-indigo-600 flex flex-col justify-end p-10 transition-all hover:shadow-[0_0_50px_rgba(79,70,229,0.4)]">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent z-10"></div>
                        <div class="relative z-20">
                            <div class="w-16 h-16 bg-white/20 backdrop-blur-2xl rounded-2xl flex items-center justify-center text-4xl mb-6 group-hover:rotate-12 transition-transform">🎲</div>
                            <h2 class="text-4xl font-black leading-none tracking-tighter mb-4 uppercase">Видео<br>Рулетка</h2>
                            <div class="inline-flex items-center gap-3 bg-white text-indigo-600 px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all">Вход ➔</div>
                        </div>
                    </a>

                    <div class="bg-[#111] border border-white/5 rounded-[2.5rem] p-8 relative overflow-hidden">
                        <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Ваш уровень</div>
                        <div class="text-4xl font-black mt-2">Level {{ Auth::user()->level }}</div>
                        <div class="mt-6 w-full bg-white/5 h-2 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full transition-all duration-1000" style="width: {{ (Auth::user()->xp % 1000) / 10 }}%"></div>
                        </div>
                    </div>

                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic"># Интересы</span>
                            <a href="{{ route('profile.edit') }}" class="text-indigo-500 text-[10px] font-black uppercase">Изм.</a>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @php $interests = is_array(Auth::user()->interests) ? Auth::user()->interests : []; @endphp
                            @forelse($interests as $tag)
                                <span class="px-3 py-1.5 bg-white/5 border border-white/10 rounded-lg text-[10px] font-bold">#{{ $tag }}</span>
                            @empty
                                <p class="text-gray-600 text-[10px] italic">Добавьте теги в профиле</p>
                            @endforelse
                        </div>
                    </div>

                    <a href="{{ route('rooms.index') }}" class="md:col-span-2 bg-white/5 border border-white/10 rounded-[2.5rem] p-8 flex items-center justify-between group hover:bg-white/10 transition-all">
                        <div class="flex items-center gap-6">
                            <div class="text-4xl group-hover:scale-110 transition-transform">👥</div>
                            <h3 class="text-xl font-black uppercase tracking-tighter italic">Live Spaces <span class="text-gray-500 text-xs block font-medium tracking-normal not-italic">Групповые видео-комнаты</span></h3>
                        </div>
                        <div class="text-indigo-400 text-2xl group-hover:translate-x-2 transition-transform">➔</div>
                    </a>
                </div>

                <!-- СПИСОК ДРУЗЕЙ (ВОЗВРАЩЕНО) -->
                <div class="bg-[#080808] border border-white/5 rounded-[2.5rem] flex flex-col overflow-hidden">
                    <div class="p-6 border-b border-white/5 bg-white/[0.02]">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400">Друзья (в сети)</h3>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        <template x-for="friend in friends" :key="friend.id">
                            <div class="flex items-center justify-between p-4 bg-white/[0.02] border border-white/5 rounded-2xl hover:bg-white/5 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center font-black text-xs" x-text="friend.name[0]"></div>
                                        <div class="absolute -bottom-1 -right-1 w-3 h-3 border-2 border-[#080808] rounded-full" 
                                             :class="onlineList.some(u => u.id === friend.id) ? 'bg-green-500' : 'bg-gray-700'"></div>
                                    </div>
                                    <span class="text-xs font-bold" x-text="friend.name"></span>
                                </div>
                                <a :href="'/chat?call=' + friend.id" class="w-8 h-8 bg-indigo-500/20 text-indigo-400 rounded-lg flex items-center justify-center hover:bg-indigo-500 hover:text-white transition-all">📞</a>
                            </div>
                        </template>
                        <template x-if="friends.length === 0">
                            <div class="text-center py-10 opacity-20">
                                <p class="text-[10px] font-black uppercase">У вас пока нет друзей</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>