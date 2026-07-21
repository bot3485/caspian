<x-app-layout>
    <div class="relative min-h-[calc(100svh-80px)] p-4 md:p-8 lg:p-12 overflow-y-auto custom-scrollbar bg-[#020202] text-white">
        
        <!-- Ambient Glow -->
        <div class="absolute top-1/4 left-1/3 w-[500px] h-[500px] bg-brand-indigo/[0.03] rounded-full blur-[150px] pointer-events-none"></div>

        <div class="max-w-[1400px] mx-auto relative z-10">
            <!-- 1. Главные карточки (Roulette & Rooms) -->
            @include('dashboard.partials.hero-cards')

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mt-6">
                <!-- 2. Терминал пользователя -->
                @include('dashboard.partials.user-terminal')

                <!-- 3. Глобальная статистика -->
                @include('dashboard.partials.global-stats')

                <!-- 4. Быстрые ссылки (Leaderboard & Trust) -->
                @include('dashboard.partials.quick-links')
            </div>
        </div>
    </div>
</x-app-layout>