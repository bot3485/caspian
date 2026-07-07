<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Caspian 2.0 — Экосистема общения</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-[#050505] text-white selection:bg-indigo-500/30 overflow-x-hidden" 
          x-data="{ 
            toasts: [], 
            addToast(msg, type = 'info') { 
                const id = Date.now();
                this.toasts.push({ id, msg, type });
                setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 5000);
            } 
          }"
          @toast.window="addToast($event.detail.msg, $event.detail.type)">
        
        <div class="min-h-screen flex flex-col relative">
            @include('layouts.navigation')

            <main class="flex-1">
                {{ $slot }}
            </main>

            <!-- TOASTS -->
            <div class="fixed bottom-24 left-1/2 -translate-x-1/2 z-[200] flex flex-col gap-3 w-full max-w-sm px-4 pointer-events-none">
                <template x-for="t in toasts" :key="t.id">
                    <div x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-8 scale-90"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-90"
                         class="pointer-events-auto bg-white/10 backdrop-blur-3xl border border-white/10 p-5 rounded-[1.5rem] shadow-2xl flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg"
                             :class="{
                                'bg-indigo-600/20 text-indigo-400': t.type === 'info',
                                'bg-green-600/20 text-green-400': t.type === 'success',
                                'bg-red-600/20 text-red-400': t.type === 'error'
                             }">
                            <span x-text="t.type === 'success' ? '✅' : (t.type === 'error' ? '🚫' : '✨')"></span>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-black uppercase tracking-widest text-white/40 mb-0.5" x-text="t.type"></p>
                            <p class="text-[13px] font-bold text-white/90" x-text="t.msg"></p>
                        </div>
                        <button @click="toasts = toasts.filter(toast => toast.id !== t.id)" class="text-white/20 hover:text-white">✕</button>
                    </div>
                </template>
            </div>
        </div>

        <script>
            // Heartbeat: Пингуем сервер каждые 45 секунд для обновления last_seen
            @auth
                setInterval(() => {
                    fetch('{{ route('ping') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });
                }, 45000);
            @endauth
        </script>

        <style>
            [x-cloak] { display: none !important; }
            ::-webkit-scrollbar { width: 5px; }
            ::-webkit-scrollbar-track { background: #050505; }
            ::-webkit-scrollbar-thumb { background: #1a1a1a; border-radius: 10px; }
            body { -webkit-font-smoothing: antialiased; }
        </style>
    </body>
</html>