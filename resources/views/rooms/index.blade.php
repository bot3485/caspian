<x-app-layout>
    <div class="py-12 bg-[#050505] min-h-screen text-white font-sans" 
         x-data="{ 
            showModal: false,
            userHasRoom: @js($userHasRoom),
            newRoom: { title: '', password: '', is_public: true },
            occupancy: { 
                @foreach($rooms as $room) '{{ $room->uuid }}': {{ $room->current_occupancy }}, @endforeach 
            },
            async createRoom() {
                if (this.userHasRoom) {
                    alert('У вас уже есть активная комната! Сначала удалите её.');
                    return;
                }
                try {
                    const res = await window.axios.post('{{ route('rooms.store') }}', this.newRoom);
                    window.location.href = res.data.redirect;
                } catch (e) {
                    alert(e.response?.data?.message || 'Ошибка');
                }
            },
            async deleteRoom(uuid) {
                if (!confirm('Вы уверены, что хотите удалить свою комнату? Все участники будут отключены.')) return;
                try {
                    await window.axios.delete(`/rooms/${uuid}`);
                    window.location.reload();
                } catch (e) { alert('Ошибка при удалении'); }
            },
            init() {
                window.Echo.channel('rooms-lobby')
                    .listen('.OccupancyUpdated', (e) => {
                        console.log('Lobby Update:', e); // Для отладки
                        this.occupancy[e.roomUuid] = e.count;
                    });
            }
         }">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h1 class="text-5xl font-black uppercase italic tracking-tighter">Live Spaces</h1>
                    <p class="text-indigo-500 font-bold text-[10px] uppercase tracking-[0.4em] mt-2 ml-1">Групповые видео-комнаты</p>
                </div>
                <!-- Кнопка создания -->
                <button @click="userHasRoom ? alert('Сначала удалите текущую комнату') : showModal = true" 
                        :class="userHasRoom ? 'opacity-50 grayscale cursor-not-allowed' : 'hover:scale-105'"
                        class="bg-indigo-600 px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all shadow-xl shadow-indigo-600/20">
                    <span x-text="userHasRoom ? 'Комната уже создана' : '+ Создать комнату'"></span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($rooms as $room)
                    @php $isMine = $room->creator_id === Auth::id(); @endphp
                    
                    <div class="relative overflow-hidden rounded-[3rem] p-10 flex flex-col justify-between min-h-[340px] transition-all border group
                                {{ $isMine ? 'bg-gradient-to-br from-indigo-900/40 to-purple-900/40 border-indigo-500/50 shadow-[0_0_40px_rgba(99,102,241,0.1)]' : 'bg-[#0a0a0a] border-white/5 hover:border-white/20' }}">
                        
                        <!-- БЭЙДЖ "ВАША КОМНАТА" -->
                        @if($isMine)
                            <div class="absolute top-6 right-10 bg-indigo-500 text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg">Ваш Space</div>
                        @endif

                        <div>
                            <div class="flex justify-between items-start mb-8">
                                <div class="w-12 h-12 {{ $isMine ? 'bg-indigo-500' : 'bg-white/5' }} rounded-2xl flex items-center justify-center text-xl shadow-inner">
                                    {{ $room->password ? '🔐' : ($isMine ? '👑' : '🌍') }}
                                </div>
                                    <div class="flex items-center gap-2 px-4 py-2 bg-black/40 backdrop-blur-md rounded-xl border border-white/5">
                                        <div class="w-2 h-2 rounded-full shadow-[0_0_10px_#22c55e]" 
                                            :class="(occupancy['{{ $room->uuid }}'] || 0) > 0 ? 'bg-green-500 animate-pulse' : 'bg-gray-600'"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">
                                            <!-- Показываем X/6 -->
                                            <span x-text="(occupancy['{{ $room->uuid }}'] || 0) + '/6'"></span> 
                                            <span class="text-gray-500 ml-1">Live</span>
                                        </span>
                                    </div>
                            </div>

                            <h3 class="text-2xl font-black tracking-tight {{ $isMine ? 'text-white' : 'group-hover:text-indigo-400' }} transition-colors">
                                {{ $room->title }}
                            </h3>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-2 italic">
                                {{ $isMine ? 'Вы создатель' : 'Автор: ' . $room->creator->name }}
                            </p>
                            
                            @if(!$room->is_public)
                                <span class="inline-block mt-3 text-[8px] font-black uppercase tracking-tighter text-amber-500 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">Приватная</span>
                            @endif
                        </div>

                        <div class="flex gap-3 mt-8">
                            <a href="{{ route('rooms.show', $room->uuid) }}" 
                               class="flex-1 {{ $isMine ? 'bg-white text-black' : 'bg-white/5 text-white hover:bg-white hover:text-black' }} py-5 rounded-2xl font-black text-xs uppercase tracking-widest text-center transition-all shadow-xl">
                               Войти ➔
                            </a>
                            
                            @if($isMine)
                                <button @click="deleteRoom('{{ $room->uuid }}')" 
                                        class="w-16 bg-red-600/20 text-red-500 rounded-2xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all border border-red-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- МОДАЛКА СОЗДАНИЯ -->
        <div x-show="showModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl" x-cloak x-transition>
            <div class="bg-[#080808] border border-white/10 w-full max-w-lg rounded-[3.5rem] p-12 shadow-2xl" @click.away="showModal = false">
                <h2 class="text-4xl font-black uppercase tracking-tighter italic mb-10">New Space</h2>
                
                <div class="space-y-8">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-[0.2em] mb-3 ml-2 block">Название</label>
                        <input type="text" x-model="newRoom.title" placeholder="Chill Zone..." 
                               class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-8 text-white focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none font-bold">
                    </div>
                    
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-[0.2em] mb-3 ml-2 block">Пароль (опционально)</label>
                        <input type="password" x-model="newRoom.password" placeholder="••••" 
                               class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-8 text-white focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none">
                    </div>

                    <label class="flex items-center gap-4 cursor-pointer group">
                        <input type="checkbox" x-model="newRoom.is_public" class="w-6 h-6 rounded-lg bg-white/5 border-white/10 text-indigo-600 focus:ring-0">
                        <span class="text-xs font-black uppercase tracking-widest text-gray-400 group-hover:text-white transition-colors">Сделать публичной</span>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-12">
                    <button @click="showModal = false" class="py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:bg-white/5">Закрыть</button>
                    <button @click="createRoom()" class="bg-indigo-600 py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-500 transition-all shadow-xl shadow-indigo-600/20">Создать Space</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>