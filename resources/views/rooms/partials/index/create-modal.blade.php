<div x-show="showModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/95 backdrop-blur-2xl" x-cloak x-transition>
    <div class="bg-[#050505] border border-white/10 w-full max-w-md rounded-[2.5rem] p-8 md:p-10 shadow-2xl" @click.away="showModal = false">
        <h2 class="text-3xl font-black uppercase tracking-tighter italic mb-8">{{ __('rooms.Init_Room') }}</h2>
        <div class="space-y-6">
            <div class="space-y-2">
                <label class="text-[8px] font-black uppercase text-gray-500 tracking-widest ml-1">{{ __('rooms.Room_Name') }}</label>
                <input type="text" x-model="newRoom.title" class="w-full bg-white/[0.03] border border-white/10 rounded-xl py-4 px-6 text-white outline-none font-bold">
            </div>
            <div class="space-y-2">
                <label class="text-[8px] font-black uppercase text-gray-500 tracking-widest ml-1">{{ __('rooms.Access_Key') }}</label>
                <input type="password" x-model="newRoom.password" class="w-full bg-white/[0.03] border border-white/10 rounded-xl py-4 px-6 text-white outline-none">
            </div>
            <label class="flex items-center gap-4 cursor-pointer p-4 bg-white/[0.02] rounded-2xl border border-white/[0.04]">
                <input type="checkbox" x-model="newRoom.is_public" class="w-5 h-5 rounded-lg bg-black border-white/10 text-brand-indigo focus:ring-0">
                <span class="text-xs font-black uppercase">{{ __('rooms.Public_Visibility') }}</span>
            </label>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-10">
            <button @click="showModal = false" class="py-4 text-[9px] font-black uppercase text-gray-400">{{ __('rooms.Cancel') }}</button>
            <button @click="createRoom()" class="bg-brand-indigo py-4 rounded-xl font-black text-[9px] uppercase">{{ __('rooms.Create_Room') }}</button>
        </div>
    </div>
</div>