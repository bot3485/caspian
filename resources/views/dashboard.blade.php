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
                    
                    <!-- ПУЛЬС ПЛАТФОРМЫ (Глобальная статистика) -->
                    <div class="md:col-span-2 bg-gradient-to-br from-indigo-600/20 to-transparent border border-white/5 rounded-[2.5rem] p-8 grid grid-cols-3 gap-6 relative overflow-hidden group">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/10 blur-3xl rounded-full group-hover:bg-indigo-500/20 transition-all"></div>
                        
                        <div class="relative z-10">
                            <div class="text-[9px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-1 italic">Сообщество</div>
                            <div class="text-3xl font-black tracking-tighter">{{ number_format($stats['total_users']) }}</div>
                            <div class="text-[8px] text-gray-500 font-bold uppercase mt-1">участников</div>
                        </div>
                        
                        <div class="relative z-10 border-x border-white/5 px-6">
                            <div class="text-[9px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-1 italic">В эфире</div>
                            <div class="text-3xl font-black tracking-tighter">{{ number_format($stats['total_minutes']) }}</div>
                            <div class="text-[8px] text-gray-500 font-bold uppercase mt-1">всего минут</div>
                        </div>

                        <div class="relative z-10">
                            <div class="text-[9px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-1 italic">Spaces</div>
                            <div class="text-3xl font-black tracking-tighter text-white">{{ $stats['active_rooms'] }}</div>
                            <div class="text-[8px] text-gray-500 font-bold uppercase mt-1">активных комнат</div>
                        </div>
                    </div>

                    <!-- КАРТА ВХОДА В РУЛЕТКУ -->
                    <a href="{{ route('chat') }}" class="group relative overflow-hidden rounded-[3rem] bg-indigo-600 flex flex-col justify-end p-10 transition-all hover:shadow-[0_0_50px_rgba(79,70,229,0.4)]">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent z-10"></div>
                        <div class="relative z-20">
                            <div class="w-16 h-16 bg-white/20 backdrop-blur-2xl rounded-2xl flex items-center justify-center text-4xl mb-6 group-hover:rotate-12 transition-transform">🎲</div>
                            <h2 class="text-4xl font-black leading-none tracking-tighter mb-4 uppercase">Видео<br>Рулетка</h2>
                            <div class="inline-flex items-center gap-3 bg-white text-indigo-600 px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all">Вход ➔</div>
                        </div>
                    </a>

                    <!-- ПЕРСОНАЛЬНЫЙ РАНГ -->
                    <div class="bg-[#111] border border-white/5 rounded-[2.5rem] p-8 relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Ваш статус</div>
                                <div class="text-3xl font-black mt-2 text-white italic tracking-tighter">{{ Auth::user()->rank_name }}</div>
                            </div>
                            <div class="bg-indigo-500/10 px-3 py-1 rounded-full text-[10px] font-black text-indigo-400 uppercase">LVL {{ Auth::user()->level }}</div>
                        </div>
                        <div class="mt-8">
                            <div class="flex justify-between text-[9px] font-black uppercase text-gray-500 mb-2">
                                <span>Прогресс до {{ Auth::user()->level + 1 }} LVL</span>
                                <span>{{ Auth::user()->xp % 1000 }} / 1000 XP</span>
                            </div>
                            <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-full shadow-[0_0_15px_#6366f1] transition-all duration-1000" style="width: {{ Auth::user()->xp_progress }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ТАЙМЕРЫ -->
                    <div class="md:col-span-2 bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 grid grid-cols-2 gap-8">
                        <div>
                            <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1 italic"># Время на сайте</div>
                            <div class="text-3xl font-black italic text-white">{{ Auth::user()->site_minutes ?? 0 }} <span class="text-xs text-gray-600 not-italic uppercase">мин</span></div>
                        </div>
                        <div class="border-l border-white/5 pl-8">
                            <div class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1 italic"># Время общения</div>
                            <div class="text-3xl font-black italic text-white">{{ Auth::user()->total_minutes ?? 0 }} <span class="text-xs text-gray-600 not-italic uppercase">мин</span></div>
                        </div>
                    </div>
                </div>

                <!-- СПИСОК КОНТАКТОВ -->
                <div class="bg-[#080808] border border-white/5 rounded-[2.5rem] flex flex-col overflow-hidden">
                    <div class="p-6 border-b border-white/5 bg-white/[0.02]">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400">Контакты</h3>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-hide">
                        <template x-for="friend in friends" :key="friend.id">
                            <div class="flex items-center justify-between p-4 bg-white/[0.02] border border-white/5 rounded-2xl hover:bg-white/5 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center font-black text-xs" x-text="friend.name[0]"></div>
                                        <div class="absolute -bottom-1 -right-1 w-3 h-3 border-2 border-[#080808] rounded-full" 
                                            :class="onlineList.some(u => u.id === friend.id) ? 'bg-green-500' : 'bg-gray-700'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold" x-text="friend.name"></div>
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