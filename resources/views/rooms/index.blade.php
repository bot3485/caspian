<x-app-layout>
    <div class="py-12 bg-[#020202] min-h-[calc(100svh-80px)] text-white relative" 
         x-data="{ 
            showModal: false,
            userHasRoom: @js($userHasRoom),
            newRoom: { title: '', password: '', is_public: true },
            occupancy: { @foreach($rooms as $room) '{{ $room->uuid }}': {{ $room->current_occupancy }}, @endforeach },
            
            async createRoom() {
                if (!this.newRoom.title) return;
                try {
                    const res = await window.axios.post('{{ route('rooms.store') }}', this.newRoom);
                    window.location.href = res.data.redirect;
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: e.response?.data?.message || 'Error' } }));
                }
            },
            
            async deleteRoom(uuid) {
                if (!confirm('Are you sure?')) return;
                try {
                    await window.axios.delete(`/rooms/${uuid}`);
                    window.location.reload();
                } catch (e) { 
                    window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Termination failed' } }));
                }
            },

            init() {
                window.Echo.channel('rooms-lobby').listen('.OccupancyUpdated', (e) => { 
                    this.occupancy[e.roomUuid] = e.count; 
                });
            }
         }">
         
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-brand-indigo/5 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10">
            @include('rooms.partials.index.header')
            @include('rooms.partials.index.grid')
        </div>

        @include('rooms.partials.index.create-modal')
    </div>
</x-app-layout>