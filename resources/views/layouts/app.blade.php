<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
<body class="antialiased custom-scrollbar"
      x-data="caspianApp(window.caspianInitData)"
      @visibilitychange.window="handleVisibilityChange()"
      @focus.window="handleVisibilityChange()">
      
    <!-- Системные уведомления -->
    @include('layouts.partials.notifications')

    <!-- Глобальные оверлеи (Звонки, Level Up) -->
    @include('layouts.partials.overlays')

    <div class="flex flex-col min-h-screen relative">
        <!-- Навигация -->
        @include('layouts.navigation')

        <!-- Основной контент страницы -->
        <main class="flex-1 relative">
            {{ $slot }}
        </main>

        <!-- Правый Сайдбар (Мессенджер) -->
        <aside x-show="globalSidebarOpen" 
               @click.outside="globalSidebarOpen = false"
               class="fixed right-0 top-0 bottom-0 z-[450] w-full md:w-[420px] bg-[#050505] border-l border-white/[0.05] shadow-2xl flex flex-col"
               x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
               x-transition:leave="transition ease-in duration-200" x-transition:leave-end="translate-x-full" x-cloak>
            
            <div class="p-8 border-b border-white/5 flex justify-between items-center bg-[#080808]">
                <h2 class="text-[10px] font-black uppercase tracking-[0.5em] text-gray-500">{{ __('app.Personal_Messenger') }}</h2>
                <button @click="globalSidebarOpen = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/5 transition-colors">✕</button>
            </div>
            
            @include('messenger.index')
        </aside>
    </div>

    <!-- Загрузка данных и внешние скрипты -->
    @include('layouts.partials.scripts')
</body>
</html>