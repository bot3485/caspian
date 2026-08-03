<div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-16 gap-6">
    <div>
        <h1 class="text-4xl sm:text-6xl font-black uppercase italic tracking-tighter leading-none bg-gradient-to-r from-white via-white to-white/40 bg-clip-text text-transparent">{{ __('rooms.Live_Rooms') }}</h1>
        <p class="text-brand-indigo font-black text-[9px] uppercase tracking-[0.5em] mt-4 ml-1">{{ __('rooms.Multiroom_Video_Hub') }}</p>
    </div>
    
    <button @click="userHasRoom ? window.dispatchEvent(new CustomEvent('toast', { detail: { msg: 'Space limit reached' } })) : showModal = true" 
            :class="userHasRoom ? 'opacity-30 grayscale cursor-not-allowed' : 'hover:scale-[1.02] active:scale-95'"
            class="w-full md:w-auto bg-brand-indigo px-8 py-4.5 rounded-2xl font-black text-[9px] uppercase tracking-[0.25em] transition-all duration-300 shadow-2xl border border-white/10">
        <span x-text="userHasRoom ? '{{ __('rooms.Room_Active') }}' : '+ {{ __('rooms.Create_New_Room') }}'"></span>
    </button>
</div>