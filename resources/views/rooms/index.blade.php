<x-app-layout>
    <div class="py-12 bg-[#020202] min-h-[calc(100svh-80px)] text-white font-sans overflow-y-auto custom-scrollbar" 
         x-data="{ 
            showModal: false,
            userHasRoom: @js($userHasRoom),
            newRoom: { title: '', password: '', is_public: true },
            occupancy: { 
                @foreach($rooms as $room) '{{ $room->uuid }}': {{ $room->current_occupancy }}, @endforeach 
            },
            async createRoom() {
                if (this.userHasRoom) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Space limit reached. Delete existing one.' } }));
                    return;
                }
                try {
                    const res = await window.axios.post('{{ route('rooms.store') }}', this.newRoom);
                    window.location.href = res.data.redirect;
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: e.response?.data?.message || 'Initialization failed' } }));
                }
            },
            async deleteRoom(uuid) {
                if (!confirm('Are you sure? This will disconnect all active participants.')) return;
                try {
                    await window.axios.delete(`/rooms/${uuid}`);
                    window.location.reload();
                } catch (e) { 
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Termination failed' } }));
                }
            },
            init() {
                window.Echo.channel('rooms-lobby')
                    .listen('.OccupancyUpdated', (e) => {
                        this.occupancy[e.roomUuid] = e.count;
                    });
            }
         }">
        
        <div class="max-w-7xl mx-auto px-6">
            <!-- HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-16 gap-6">
                <div>
                    <h1 class="text-6xl font-black uppercase italic tracking-tighter">Live Spaces</h1>
                    <p class="text-brand-indigo font-black text-[10px] uppercase tracking-[0.5em] mt-3 ml-1">Multiverse Video Hubs</p>
                </div>
                
                <button @click="userHasRoom ? window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Delete your current space first' } })) : showModal = true" 
                        :class="userHasRoom ? 'opacity-40 grayscale cursor-not-allowed' : 'hover:scale-105 shadow-brand-indigo/20'"
                        class="bg-brand-indigo px-10 py-5 rounded-[1.5rem] font-black text-[10px] uppercase tracking-[0.2em] transition-all shadow-2xl flex items-center gap-3">
                    <span x-text="userHasRoom ? 'Space Active' : '+ Create New Space'"></span>
                </button>
            </div>

            <!-- ROOMS GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($rooms as $room)
                    @php $isMine = $room->creator_id === Auth::id(); @endphp
                    
                    <div class="relative overflow-hidden rounded-[3.5rem] p-10 flex flex-col justify-between min-h-[380px] transition-all border group
                                {{ $isMine ? 'bg-brand-indigo/10 border-brand-indigo/30 shadow-[0_0_50px_rgba(99,102,241,0.1)]' : 'bg-white/[0.02] border-white/5 hover:border-white/20' }}">
                        
                        @if($isMine)
                            <div class="absolute top-8 right-10 bg-brand-indigo text-[8px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest shadow-lg animate-pulse">Your Space</div>
                        @endif

                        <div>
                            <div class="flex justify-between items-start mb-10">
                                <div class="w-14 h-14 {{ $isMine ? 'bg-brand-indigo text-white' : 'bg-white/5 text-brand-indigo' }} rounded-[1.5rem] flex items-center justify-center text-2xl shadow-inner border border-white/10">
                                    {{ $room->password ? '🔐' : ($isMine ? '👑' : '🌍') }}
                                </div>
                                <div class="flex items-center gap-2 px-5 py-2.5 bg-black/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-xl">
                                    <div class="w-2 h-2 rounded-full shadow-[0_0_12px_#22c55e]" 
                                        :class="(occupancy['{{ $room->uuid }}'] || 0) > 0 ? 'bg-green-500 animate-pulse' : 'bg-gray-700'"></div>
                                    <span class="text-[11px] font-black uppercase tracking-widest">
                                        <span x-text="(occupancy['{{ $room->uuid }}'] || 0) + '/6'"></span> 
                                        <span class="text-gray-500 ml-1">Live</span>
                                    </span>
                                </div>
                            </div>

                            <h3 class="text-3xl font-black tracking-tighter italic {{ $isMine ? 'text-white' : 'text-white/90 group-hover:text-brand-indigo' }} transition-colors uppercase">
                                {{ $room->title }}
                            </h3>
                            <p class="text-[9px] text-gray-500 font-black uppercase tracking-[0.2em] mt-3 italic">
                                {{ $isMine ? 'Authorized Host: You' : 'Initiated by: ' . $room->creator->name }}
                            </p>
                            
                            @if(!$room->is_public)
                                <span class="inline-block mt-4 text-[7px] font-black uppercase tracking-widest text-amber-500 bg-amber-500/10 px-3 py-1 rounded-lg border border-amber-500/20">Private Enclave</span>
                            @endif
                        </div>

                        <div class="flex gap-3 mt-10">
                            <a href="{{ route('rooms.show', $room->uuid) }}" 
                               class="flex-1 {{ $isMine ? 'bg-white text-black' : 'bg-white/5 text-white hover:bg-white hover:text-black' }} py-5 rounded-[1.5rem] font-black text-[10px] uppercase tracking-[0.2em] text-center transition-all shadow-xl active:scale-95">
                               Enter Core ➔
                            </a>
                            
                            @if($isMine)
                                <button @click="deleteRoom('{{ $room->uuid }}')" 
                                        class="w-16 bg-red-600/10 text-red-500 rounded-[1.5rem] flex items-center justify-center hover:bg-red-600 hover:text-white transition-all border border-red-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- EMPTY STATE -->
            @if($rooms->isEmpty())
                <div class="flex flex-col items-center justify-center py-32 bg-white/[0.01] rounded-[4rem] border-2 border-dashed border-white/5">
                    <span class="text-6xl mb-6 grayscale opacity-20">🪐</span>
                    <p class="text-[10px] font-black uppercase tracking-[0.5em] text-gray-600">No Active Spaces Found</p>
                </div>
            @endif
        </div>

        <!-- CREATE MODAL (v3.0 Ethereal Edition) -->
        <div x-show="showModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-3xl" x-cloak x-transition>
            <div class="bg-[#080808] border border-white/10 w-full max-w-lg rounded-[4rem] p-12 shadow-[0_0_100px_rgba(0,0,0,1)]" @click.away="showModal = false">
                <div class="flex justify-between items-center mb-12">
                    <h2 class="text-4xl font-black uppercase tracking-tighter italic">Init Space</h2>
                    <div class="w-12 h-12 bg-brand-indigo/10 rounded-2xl flex items-center justify-center text-xl">🛸</div>
                </div>
                
                <div class="space-y-10">
                    <div class="space-y-3">
                        <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">Deployment Title</label>
                        <input type="text" x-model="newRoom.title" placeholder="Sector 7..." 
                               class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-8 text-white focus:ring-2 focus:ring-brand-indigo outline-none font-bold text-sm transition-all">
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">Access Key (Optional)</label>
                        <input type="password" x-model="newRoom.password" placeholder="••••" 
                               class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-8 text-white focus:ring-2 focus:ring-brand-indigo outline-none text-sm transition-all">
                    </div>

                    <label class="flex items-center gap-5 cursor-pointer group p-4 bg-white/5 rounded-3xl border border-white/5 hover:border-brand-indigo/30 transition-all">
                        <input type="checkbox" x-model="newRoom.is_public" class="w-7 h-7 rounded-xl bg-black border-white/10 text-brand-indigo focus:ring-0">
                        <div>
                            <span class="text-xs font-black uppercase tracking-widest block">Public Visibility</span>
                            <span class="text-[9px] text-gray-500 font-bold uppercase mt-1 block">Visible in global directory</span>
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-16">
                    <button @click="showModal = false" class="py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:bg-white/5 transition-all">Cancel</button>
                    <button @click="createRoom()" class="bg-brand-indigo py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-105 transition-all shadow-xl shadow-brand-indigo/30">Launch Space</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>