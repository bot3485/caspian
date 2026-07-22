<div x-data="{ 
        toasts: [], 
        addToast(msg) {
            if (this.toasts.some(t => t.msg === msg)) return;
            const id = Date.now();
            this.toasts.push({id, msg});
            setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 3500);
        }
    }"
     @toast.window="addToast($event.detail.msg)" 
     class="fixed top-24 left-1/2 -translate-x-1/2 z-[1000] w-full max-w-xs space-y-2 pointer-events-none">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-[-20px] scale-90"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90"
             class="pointer-events-auto bg-brand-indigo/90 backdrop-blur-2xl border border-white/20 px-6 py-3 rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.5)] text-center">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-white" x-text="toast.msg"></span>
        </div>
    </template>
</div>