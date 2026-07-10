<x-app-layout>
    <div class="py-12 bg-[#050505] min-h-screen text-white font-sans">
        <div class="max-w-4xl mx-auto px-4">
            
            <div class="text-center mb-16">
                <h1 class="text-6xl font-black tracking-tighter uppercase italic">Hall of Fame</h1>
                <p class="text-indigo-500 font-black uppercase text-xs tracking-[0.4em] mt-2">Лучшие пользователи ChatRoulette</p>
            </div>
            
            <div class="space-y-3">
                @php 
                    $topUsers = \App\Models\User::orderBy('xp', 'desc')->take(20)->get();
                @endphp
                
                @foreach($topUsers as $index => $u)
                    <div class="flex items-center gap-6 p-6 bg-white/[0.02] border border-white/5 rounded-[2rem] hover:bg-white/[0.05] hover:border-indigo-500/30 transition-all group">
                        <!-- Место -->
                        <div class="text-3xl font-black {{ $index < 3 ? 'text-indigo-500' : 'text-gray-700' }} w-12 italic">
                            #{{ $index + 1 }}
                        </div>
                        
                        <!-- Аватар -->
                        <div class="w-14 h-14 bg-gradient-to-br from-gray-800 to-black border border-white/10 rounded-2xl flex items-center justify-center font-black text-xl text-white group-hover:scale-110 transition-transform">
                            {{ substr($u->name, 0, 1) }}
                        </div>
                        
                        <!-- Инфо -->
                        <div class="flex-1">
                            <h3 class="font-black text-lg group-hover:text-indigo-400 transition-colors">{{ $u->name }}</h3>
                            <div class="flex gap-4 mt-1">
                                <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Level {{ $u->level }}</span>
                                <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">{{ $u->karma }} Karma</span>
                            </div>
                        </div>
                        
                        <!-- Очки -->
                        <div class="text-right">
                            <div class="text-2xl font-black tracking-tighter">{{ number_format($u->xp) }} <span class="text-[10px] text-indigo-500 uppercase ml-1">XP</span></div>
                            <div class="text-[9px] font-bold text-gray-600 uppercase tracking-widest mt-1">{{ $u->total_minutes }} минут в эфире</div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($topUsers->isEmpty())
                <div class="text-center py-20 bg-white/5 rounded-[3rem] border-2 border-dashed border-white/5">
                    <p class="text-gray-500 font-black uppercase text-xs tracking-widest">Пока здесь пусто. Станьте первым!</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>