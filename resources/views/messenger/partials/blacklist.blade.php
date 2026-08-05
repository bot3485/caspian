<template x-if="blockedList.length === 0">
    <div class="flex-1 flex items-center justify-center opacity-40 text-center p-8">
        <div class="text-[9px] font-black uppercase tracking-[0.4em] text-gray-500">{{ __('messenger.Blacklist_Empty') }}</div>
    </div>
</template>

<template x-for="b in blockedList" :key="b.id">
    <div class="p-3.5 border border-white/[0.05] bg-white/[0.02] rounded-[1.5rem] flex items-center justify-between transition-all hover:bg-white/[0.04]">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 bg-red-500/10 border border-red-500/20 rounded-[1.2rem] flex items-center justify-center text-red-500 font-black" x-text="b.name[0]"></div>
            <div>
                <div class="text-xs font-bold text-gray-200" x-text="b.name"></div>
                <div class="text-[8px] font-black uppercase tracking-widest text-gray-500 mt-1">{{ __('messenger.Blocked') }}</div>
            </div>
        </div>
        
        <button @click="if(confirm('Разблокировать?')) { window.axios.post('/chat/unblock', {userId: b.id}).then(() => loadBlacklist()) }" 
                class="px-4 py-2 bg-white/[0.03] hover:bg-red-500 hover:text-white text-gray-400 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
            Разблокировать
        </button>
    </div>
</template>