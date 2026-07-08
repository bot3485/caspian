<x-app-layout>
    <div class="py-12 bg-[#050505] min-h-screen text-white font-sans" 
         x-data="{ 
            occupancy: { 
                @foreach($rooms as $room) '{{ $room->uuid }}': {{ $room->current_occupancy }}, @endforeach 
            },
            init() {
                // Слушаем специальный канал ЛОББИ для обновлений
                window.Echo.channel('rooms-lobby')
                    .listen('.OccupancyUpdated', (e) => {
                        console.log('Update for room ' + e.roomUuid + ': ' + e.count);
                        // Обновляем количество ТОЛЬКО для той комнаты, чей UUID пришел
                        this.occupancy[e.roomUuid] = e.count;
                    });
            }
         }">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-black uppercase italic mb-12">Live Spaces</h1>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($rooms as $room)
                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-600/20 rounded-xl flex items-center justify-center text-xl">🏠</div>
                                <div>
                                    <span class="block text-[10px] font-black uppercase text-indigo-400">Room</span>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <!-- ПРОВЕРКА: Если в occupancy[uuid] есть данные, берем их, иначе из базы -->
                                        <div class="w-1.5 h-1.5 rounded-full" :class="(occupancy['{{ $room->uuid }}'] || 0) > 0 ? 'bg-green-500 animate-pulse' : 'bg-gray-600'"></div>
                                        <span class="text-[10px] font-bold text-white uppercase">
                                            <span x-text="(occupancy['{{ $room->uuid }}'] || 0)"></span> онлайн
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-black mb-6">{{ $room->title }}</h3>
                        <a href="{{ route('rooms.show', $room->uuid) }}" class="block w-full text-center bg-white text-black py-3 rounded-xl font-black text-[10px] uppercase hover:bg-indigo-600 hover:text-white transition-all">Войти ➔</a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>