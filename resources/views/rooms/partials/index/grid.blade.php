<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
    @foreach($rooms as $room)
        @php $isMine = $room->creator_id === Auth::id(); @endphp
        <div class="relative overflow-hidden rounded-[2.5rem] p-6 md:p-8 flex flex-col justify-between min-h-[360px] transition-all duration-500 border group
            {{ $isMine 
                ? 'bg-gradient-to-br from-brand-indigo/[0.08] to-brand-indigo/[0.01] border-brand-indigo/30 shadow-[0_15px_50px_rgba(99,102,241,0.08)]' 
                : 'bg-[#050505]/40 backdrop-blur-sm border-white/[0.04] hover:border-white/15 hover:bg-[#080808]/80' }}">
            
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-8">
                    <div class="w-12 h-12 {{ $isMine ? 'bg-brand-indigo text-white' : 'bg-white/[0.03] text-brand-indigo border-white/5' }} rounded-[1.25rem] flex items-center justify-center text-xl border">
                        {{ $room->password ? '🔐' : ($isMine ? '👑' : '🌍') }}
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-black/60 backdrop-blur-xl rounded-xl border border-white/[0.05]">
                            <div class="w-1.5 h-1.5 rounded-full" :class="(occupancy['{{ $room->uuid }}'] || 0) > 0 ? 'bg-green-500 animate-pulse' : 'bg-gray-700'"></div>
                            <span class="text-[8px] font-black uppercase tracking-widest"><span x-text="(occupancy['{{ $room->uuid }}'] || 0) + '/6'"></span> {{ __('rooms.Live') }}</span>
                        </div>
                    </div>
                </div>

                <h3 class="text-2xl sm:text-3xl font-black tracking-tight italic uppercase">{{ $room->title }}</h3>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[0.25em] mt-3">{{ __('rooms.Created_By') }} {{$room->creator->name }}</p>
            </div>

            <div class="flex gap-3 mt-8 relative z-10">
                <a href="{{ route('rooms.show', $room->uuid) }}" 
                   class="flex-1 {{ $isMine ? 'bg-white text-black' : 'bg-white/[0.03] border border-white/[0.05] text-white hover:bg-white hover:text-black' }} py-4 rounded-[1.25rem] font-black text-[9px] uppercase tracking-[0.25em] text-center transition-all">
                   {{ __('rooms.Enter_Room') }} ➔
                </a>
                @if($isMine)
                    <button @click="deleteRoom('{{ $room->uuid }}')" class="w-12 h-12 bg-red-600/5 text-red-500 rounded-[1.25rem] flex items-center justify-center border border-red-500/10 hover:bg-red-600 hover:text-white transition-all">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>