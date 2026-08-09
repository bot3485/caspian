<x-app-layout>
    <div class="py-12 bg-[#020202] min-h-[calc(100svh-80px)] text-white font-sans overflow-y-auto custom-scrollbar relative">
        
        <!-- Фоновое радиальное свечение для глубины -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-96 h-96 bg-brand-indigo/5 rounded-full blur-[150px] pointer-events-none"></div>

        <div class="max-w-4xl mx-auto px-4 relative z-10">
            
            <!-- HEADER (Полностью на английском) -->
            <div class="text-center mb-16">
                <h1 class="text-4xl sm:text-6xl font-black tracking-tighter uppercase italic bg-gradient-to-r from-white via-white to-white/40 bg-clip-text text-transparent">{{ __('leaderboard.Hall_Of_Fame') }}</h1>
                <p class="text-brand-indigo font-black uppercase text-[9px] tracking-[0.45em] mt-3">{{ __('leaderboard.Elite_Members') }}m</p>
            </div>
            
<!-- LEADERBOARD LIST -->
<div class="space-y-3">
    @foreach($topUsers as $u)
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 p-5 md:p-6 bg-[#050505]/40 backdrop-blur-sm border rounded-[1.75rem] transition-all duration-300 group
            {{ $loop->index === 0 ? 'border-amber-500/20 bg-gradient-to-r from-amber-500/[0.03] to-transparent shadow-[0_0_30px_rgba(245,158,11,0.02)]' : '' }}
            {{ $loop->index === 1 ? 'border-slate-400/20 bg-gradient-to-r from-slate-400/[0.03] to-transparent shadow-[0_0_30px_rgba(148,163,184,0.02)]' : '' }}
            {{ $loop->index === 2 ? 'border-amber-700/20 bg-gradient-to-r from-amber-700/[0.03] to-transparent shadow-[0_0_30px_rgba(180,83,9,0.02)]' : '' }}
            {{ $loop->index > 2 ? 'border-white/[0.03] hover:border-white/10 hover:bg-[#080808]/60' : '' }}">
            
            <div class="flex items-center gap-4 flex-1 w-full">
                <!-- Позиция/Место -->
                <div class="text-2xl font-black italic w-8 text-left shrink-0
                    {{ $loop->index === 0 ? 'text-amber-400' : '' }}
                    {{ $loop->index === 1 ? 'text-slate-400' : '' }}
                    {{ $loop->index === 2 ? 'text-amber-700' : '' }}
                    {{ $loop->index > 2 ? 'text-gray-600' : '' }}">
                    #{{ $loop->iteration }}
                </div>
                
                <!-- Аватар лидера с градиентной окантовкой для топ-3 -->
                <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-lg text-white group-hover:scale-105 transition-all duration-300 shrink-0 border
                    {{ $loop->index === 0 ? 'bg-gradient-to-br from-amber-500 to-amber-600 border-amber-400/30 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : '' }}
                    {{ $loop->index === 1 ? 'bg-gradient-to-br from-slate-500 to-slate-600 border-slate-400/30 shadow-[0_0_15px_rgba(148,163,184,0.2)]' : '' }}
                    {{ $loop->index === 2 ? 'bg-gradient-to-br from-amber-700 to-amber-800 border-amber-700/30 shadow-[0_0_15px_rgba(180,83,9,0.2)]' : '' }}
                    {{ $loop->index > 2 ? 'bg-white/[0.02] border-white/10' : '' }}">
                    {{ substr($u->name, 0, 1) }}
                </div>
                
                <!-- Метаданные пользователя -->
                <div class="min-w-0 flex-1">
                    <h3 class="font-black text-sm uppercase tracking-tight truncate group-hover:text-brand-indigo transition-colors duration-300">{{ $u->name }}</h3>
                    <div class="flex gap-3 mt-0.5">
                        <span class="text-[7.5px] font-bold text-gray-500 uppercase tracking-widest">Level {{ $u->level }}</span>
                        <span class="text-[7.5px] font-black text-brand-indigo uppercase tracking-widest">{{ $u->karma }} {{ __('leaderboard.Karma') }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Очки опыта и Время в эфире -->
            <div class="flex sm:flex-col justify-between items-center sm:items-end w-full sm:w-auto pt-3 sm:pt-0 border-t border-white/[0.03] sm:border-none shrink-0">
                <div class="text-right w-full sm:w-auto flex sm:flex-col justify-between items-center sm:items-end">
                    <div class="text-lg sm:text-xl font-black tracking-tight leading-none">
                        {{ number_format($u->xp) }} <span class="text-[8px] text-brand-indigo uppercase ml-1">{{ __('leaderboard.XP') }}</span>
                    </div>
                    <div class="text-[7.5px] font-bold text-gray-600 uppercase tracking-widest mt-1">
                        {{ $u->total_minutes }} {{ __('leaderboard.Minutes_On_Videochat') }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- EMPTY STATE -->
@if($topUsers->isEmpty())
    <div class="text-center py-20 bg-[#050505]/40 backdrop-blur-sm rounded-[2.5rem] border border-dashed border-white/5">
        <p class="text-gray-500 font-black uppercase text-[9px] tracking-[0.3em]">{{ __('leaderboard.Ranking_Is_Empty') }}</p>
    </div>
@endif
        </div>
    </div>
</x-app-layout>