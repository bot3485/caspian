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
                    
                    <!-- КАРТА ВХОДА В РУЛЕТКУ -->
                    <a href="{{ route('chat') }}" class="md:row-span-2 group relative overflow-hidden rounded-[3rem] bg-indigo-600 flex flex-col justify-end p-10 transition-all hover:shadow-[0_0_50px_rgba(79,70,229,0.4)]">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent z-10"></div>
                        <div class="relative z-20">
                            <div class="w-16 h-16 bg-white/20 backdrop-blur-2xl rounded-2xl flex items-center justify-center text-4xl mb-6 group-hover:rotate-12 transition-transform">🎲</div>
                            <h2 class="text-4xl font-black leading-none tracking-tighter mb-4 uppercase">Видео<br>Рулетка</h2>
                            <div class="inline-flex items-center gap-3 bg-white text-indigo-600 px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all">Вход ➔</div>
                        </div>
                    </a>

                    <!-- РАНГ И XP -->
                    <div class="bg-[#111] border border-white/5 rounded-[2.5rem] p-8 relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Ранг</div>
                                <div class="text-4xl font-black mt-2 text-white italic">LVL {{ Auth::user()->level }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-black text-indigo-500 uppercase tracking-widest">Опыт</div>
                                <div class="text-xl font-bold mt-1 text-white/80">{{ number_format(Auth::user()->xp) }}</div>
                            </div>
                        </div>
                        <div class="mt-6 w-full bg-white/5 h-2 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full shadow-[0_0_15px_#6366f1] transition-all duration-1000" style="width: {{ (Auth::user()->xp % 1000) / 10 }}%"></div>
                        </div>
                    </div>

                    <!-- КАРМА -->
                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 flex flex-col justify-center">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-green-500/10 flex items-center justify-center text-2xl">⚖️</div>
                            <div>
                                <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Карма (Доверие)</div>
                                <div class="text-2xl font-black {{ Auth::user()->karma >= 100 ? 'text-green-500' : 'text-red-500' }}">
                                    {{ Auth::user()->karma }}
                                </div>
                            </div>
                        </div>
                        <p class="text-[9px] text-gray-600 mt-4 leading-tight uppercase font-black tracking-tighter">
                            Высокая карма дает приоритет в поиске и статус надежного партнера.
                        </p>
                    </div>

                    <!-- ТАЙМЕРЫ (НОВОЕ) -->
                    <div class="md:col-span-2 bg-white/[0.02] border border-white/5 rounded-[2.5rem] p-8 grid grid-cols-2 gap-8">
                        <div>
                            <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1 italic"># Время на сайте</div>
                            <div class="text-3xl font-black italic text-white">{{ Auth::user()->site_minutes ?? 0 }} <span class="text-xs text-gray-600 not-italic uppercase">мин</span></div>
                        </div>
                        <div class="border-l border-white/5 pl-8">
                            <div class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1 italic"># Время общения</div>
                            <div class="text-3xl font-black italic text-white">{{ Auth::user()->total_minutes ?? 0 }} <span class="text-xs text-gray-600 not-italic uppercase">мин</span></div>
                        </div>
                    </div>

                    <!-- ИНТЕРЕСЫ -->
                    <div class="md:col-span-2 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic"># Теги интересов</span>
                            <a href="{{ route('profile.edit') }}" class="text-indigo-500 text-[10px] font-black uppercase">Изменить</a>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @php $interests = is_array(Auth::user()->interests) ? Auth::user()->interests : []; @endphp
                            @forelse($interests as $tag)
                                <span class="px-3 py-1.5 bg-white/5 border border-white/10 rounded-lg text-[10px] font-bold">#{{ $tag }}</span>
                            @empty
                                <p class="text-gray-600 text-[10px] italic font-bold">Добавьте теги в профиле для умного подбора</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- СПИСОК ДРУЗЕЙ -->
                <div class="bg-[#080808] border border-white/5 rounded-[2.5rem] flex flex-col overflow-hidden">
                    <div class="p-6 border-b border-white/5 bg-white/[0.02]">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400">Контакты</h3>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        <template x-for="friend in friends" :key="friend.id">
                            <div class="flex items-center justify-between p-4 bg-white/[0.02] border border-white/5 rounded-2xl hover:bg-white/5 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center font-black text-xs" x-text="friend.name[0]"></div>
                                        
                                        <!-- ЖИВОЙ ИНДИКАТОР: Проверяем наличие ID в onlineList (WebSocket) -->
                                        <div class="absolute -bottom-1 -right-1 w-3 h-3 border-2 border-[#080808] rounded-full" 
                                            :class="onlineList.some(u => u.id === friend.id) ? 'bg-green-500' : 'bg-gray-700'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold" x-text="friend.name"></div>
                                        
                                        <!-- ДИНАМИЧЕСКИЙ ТЕКСТ: Если в WebSocket списке есть — пишем "В сети", если нет — время из базы -->
                                        <div class="text-[8px] font-black uppercase tracking-widest" 
                                            :class="onlineList.some(u => u.id === friend.id) ? 'text-green-500' : 'text-gray-500'"
                                            x-text="onlineList.some(u => u.id === friend.id) ? 'В сети' : friend.last_seen_human">
                                        </div>
                                    </div>
                                </div>
                                <a :href="'/chat?call=' + friend.id" class="w-8 h-8 bg-indigo-500/20 text-indigo-400 rounded-lg flex items-center justify-center hover:bg-indigo-500 hover:text-white transition-all">📞</a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>