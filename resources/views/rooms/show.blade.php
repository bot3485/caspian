<x-app-layout>
    <div class="h-[calc(100svh-80px)] w-full bg-[#020203] flex flex-col overflow-hidden text-white relative" 
x-data="groupRoomComponent('{{ $room->uuid }}', '{{ auth()->user()->hashid }}', '{{ auth()->user()->name }}', {{ auth()->id() }})"
     x-init="init()"
     @resize.window="windowWidth = window.innerWidth">
        
        <!-- Background -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] bg-brand-indigo/10 rounded-full blur-[140px]" :class="focusedId ? 'opacity-100' : 'opacity-50'"></div>
        </div>

        @include('rooms.partials.show.header')

        <!-- Исправленный контейнер сетки -->
        <div class="absolute inset-0 top-[70px] bottom-[100px] px-4 md:px-8 flex flex-wrap items-center justify-center gap-2 md:gap-4 overflow-hidden z-10">
            @include('rooms.partials.show.video-grid')
        </div>

        @include('rooms.partials.show.hud')
        @include('rooms.partials.show.settings-modal')
    </div>

    @include('rooms.partials.show.script')
</x-app-layout>