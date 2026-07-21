<x-app-layout>

    <div class="fixed top-[120px] bottom-0 left-0 right-0 w-full bg-[#020202] overflow-hidden px-3 pt-4 pb-40 md:p-6 overscroll-none" 
         style="height: calc(100dvh - 120px - env(safe-area-inset-bottom));">
            
        <!-- 1. VIDEO ECOSYSTEM -->
        @include('roulette.partials.video.ecosystem')
        
        <!-- 2. FLOATING CONTROL ISLAND -->
        @include('roulette.partials.ui.controls')

        <!-- 3. MODALS -->
        @include('roulette.partials.modals.hardware')
        @include('roulette.partials.modals.filters')
        
        <!-- 4. OVERLAYS (Typing, Icebreaker, Interest Match) -->
        @include('roulette.partials.ui.overlays')
        
    </div>
    
    <!-- КАРТОЧКА ПРОФИЛЯ (Вынесена за пределы fixed контейнера для iOS) -->
    @include('roulette.partials.ui.partner-card')

    <!-- СТИЛИ -->
    @include('roulette.partials.styles')

</x-app-layout>