<x-app-layout>
    <div class="py-12 bg-[#020202] min-h-[calc(100svh-80px)] text-white font-sans overflow-y-auto custom-scrollbar relative" 
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
         
        <!-- Фоновое неоновое свечение (Абсолютный люкс) -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-brand-indigo/5 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-10 right-10 w-80 h-80 bg-red-500/5 rounded-full blur-[150px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10">
            <!-- HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-16 gap-6">
                <div>
                    <h1 class="text-4xl sm:text-6xl font-black uppercase italic tracking-tighter leading-none bg-gradient-to-r from-white via-white to-white/40 bg-clip-text text-transparent">Live Spaces</h1>
                    <p class="text-brand-indigo font-black text-[9px] uppercase tracking-[0.5em] mt-4 ml-1">Multiverse Video Hubs</p>
                </div>
                
                <button @click="userHasRoom ? window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Delete your current space first' } })) : showModal = true" 
                        :class="userHasRoom ? 'opacity-30 grayscale cursor-not-allowed' : 'hover:scale-[1.02] active:scale-95 shadow-brand-indigo/10 hover:shadow-brand-indigo/20'"
                        class="w-full md:w-auto bg-brand-indigo px-8 py-4.5 rounded-2xl font-black text-[9px] uppercase tracking-[0.25em] transition-all duration-300 shadow-2xl flex items-center justify-center gap-3 border border-white/10">
                    <span x-text="userHasRoom ? 'Space Active' : '+ Create New Space'"></span>
                </button>
            </div>

            <!-- ROOMS GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                @foreach($rooms as $room)
                    @php $isMine = $room->creator_id === Auth::id(); @endphp
                    
                    <div class="relative overflow-hidden rounded-[2.5rem] p-6 md:p-8 flex flex-col justify-between min-h-[360px] transition-all duration-500 border group
                        {{ $isMine 
                            ? 'bg-gradient-to-br from-brand-indigo/[0.08] to-brand-indigo/[0.01] border-brand-indigo/30 shadow-[0_15px_50px_rgba(99,102,241,0.08)]' 
                            : 'bg-[#050505]/40 backdrop-blur-sm border-white/[0.04] hover:border-white/15 hover:bg-[#080808]/80 hover:shadow-[0_20px_40px_rgba(0,0,0,0.8)]' }}">
                        
                        <!-- Плавное свечение при наведении на обычную карточку -->
                        <div class="absolute -inset-px bg-gradient-to-br from-white/[0.02] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-[2.5rem]"></div>

                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-8">
                                <!-- Левая часть: Иконка с глубокими тенями -->
                                <div class="w-12 h-12 {{ $isMine ? 'bg-brand-indigo text-white' : 'bg-white/[0.03] text-brand-indigo border-white/5' }} rounded-[1.25rem] flex items-center justify-center text-xl shadow-inner border">
                                    {{ $room->password ? '🔐' : ($isMine ? '👑' : '🌍') }}
                                </div>

                                <!-- Правая часть: Стек статусов -->
                                <div class="flex flex-col items-end gap-1.5">
                                    @if($isMine)
                                        <div class="bg-brand-indigo/20 text-brand-indigo text-[7px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest border border-brand-indigo/20 animate-pulse">
                                            Your Space
                                        </div>
                                    @endif

                                    <div class="flex items-center gap-2 px-3 py-1.5 bg-black/60 backdrop-blur-xl rounded-xl border border-white/[0.05] shadow-lg">
                                        <div class="w-1.5 h-1.5 rounded-full transition-all duration-300" 
                                             :class="(occupancy['{{ $room->uuid }}'] || 0) > 0 ? 'bg-green-500 shadow-[0_0_8px_#22c55e]' : 'bg-gray-700'"></div>
                                        <span class="text-[8px] font-black uppercase tracking-widest flex items-center leading-none mt-[1px]">
                                            <span x-text="(occupancy['{{ $room->uuid }}'] || 0) + '/6'"></span> 
                                            <span class="text-gray-500 ml-1">Live</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-2xl sm:text-3xl font-black tracking-tight italic {{ $isMine ? 'text-white' : 'text-white/90 group-hover:text-brand-indigo' }} transition-colors duration-300 uppercase">
                                {{ $room->title }}
                            </h3>
                            
                            <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[0.25em] mt-3 italic">
                                {{ $isMine ? 'Authorized Host: You' : 'Initiated by: ' . $room->creator->name }}
                            </p>
                            
                            @if(!$room->is_public)
                                <span class="inline-block mt-4 text-[7px] font-black uppercase tracking-widest text-amber-500 bg-amber-500/5 px-2.5 py-1 rounded-lg border border-amber-500/10">Private Enclave</span>
                            @endif
                        </div>

                        <div class="flex gap-3 mt-8 relative z-10">
                            <a href="{{ route('rooms.show', $room->uuid) }}" 
                               class="flex-1 {{ $isMine ? 'bg-white text-black hover:bg-white/90' : 'bg-white/[0.03] border border-white/[0.05] text-white hover:bg-white hover:text-black hover:border-white' }} py-4 rounded-[1.25rem] font-black text-[9px] uppercase tracking-[0.25em] text-center transition-all duration-300 active:scale-95 shadow-lg">
                               Enter Core ➔
                            </a>
                            
                            @if($isMine)
                                <button @click="deleteRoom('{{ $room->uuid }}')" 
                                        class="w-12 h-12 bg-red-600/5 text-red-500 rounded-[1.25rem] flex items-center justify-center hover:bg-red-600 hover:text-white transition-all duration-300 border border-red-500/10">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- EMPTY STATE -->
            @if($rooms->isEmpty())
                <div class="flex flex-col items-center justify-center py-24 bg-[#050505]/40 backdrop-blur-sm rounded-[3rem] border border-dashed border-white/5 max-w-xl mx-auto mt-12">
                    <span class="text-5xl mb-4 grayscale opacity-20 animate-bounce">🪐</span>
                    <p class="text-[9px] font-black uppercase tracking-[0.4em] text-gray-500">No Active Spaces Found</p>
                </div>
            @endif
        </div>

        <!-- CREATE MODAL (Ethereal Edition 3.1) -->
        <div x-show="showModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/95 backdrop-blur-2xl" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="bg-[#050505] border border-white/10 w-full max-w-md rounded-[2.5rem] p-8 md:p-10 shadow-[0_0_80px_rgba(0,0,0,0.8)]" @click.away="showModal = false">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl font-black uppercase tracking-tighter italic">Init Space</h2>
                    <div class="w-10 h-10 bg-brand-indigo/10 rounded-xl flex items-center justify-center text-lg">🛸</div>
                </div>
                
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[8px] font-black uppercase text-gray-500 tracking-[0.25em] ml-1">Deployment Title</label>
                        <input type="text" x-model="newRoom.title" placeholder="Sector 7..." 
                               class="w-full bg-white/[0.03] border border-white/10 rounded-xl py-4 px-6 text-white focus:ring-1 focus:ring-brand-indigo focus:border-brand-indigo outline-none font-bold text-sm transition-all">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[8px] font-black uppercase text-gray-500 tracking-[0.25em] ml-1">Access Key (Optional)</label>
                        <input type="password" x-model="newRoom.password" placeholder="••••" 
                               class="w-full bg-white/[0.03] border border-white/10 rounded-xl py-4 px-6 text-white focus:ring-1 focus:ring-brand-indigo focus:border-brand-indigo outline-none text-sm transition-all">
                    </div>

                    <label class="flex items-center gap-4 cursor-pointer group p-4 bg-white/[0.02] rounded-2xl border border-white/[0.04] hover:border-brand-indigo/30 transition-all duration-300">
                        <input type="checkbox" x-model="newRoom.is_public" class="w-5 h-5 rounded-lg bg-black border-white/10 text-brand-indigo focus:ring-0">
                        <div>
                            <span class="text-xs font-black uppercase tracking-wider block">Public Visibility</span>
                            <span class="text-[8px] text-gray-500 font-bold uppercase mt-0.5 block">Visible in global directory</span>
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3 mt-10">
                    <button @click="showModal = false" class="py-4 rounded-xl font-black text-[9px] uppercase tracking-widest text-gray-400 hover:bg-white/5 transition-all">Cancel</button>
                    <button @click="createRoom()" class="bg-brand-indigo py-4 rounded-xl font-black text-[9px] uppercase tracking-widest hover:scale-[1.02] transition-all shadow-lg shadow-brand-indigo/10">Launch Space</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>