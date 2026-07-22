<template x-for="h in historyList" :key="h.id">
    <div class="p-5 bg-white/[0.02] border border-white/[0.05] rounded-[1.5rem] space-y-4 hover:bg-white/[0.03] transition-colors relative">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-gray-800 to-[#111] rounded-[1.2rem] flex items-center justify-center text-white font-black" x-text="h.name[0]"></div>
                <div>
                    <div class="text-xs font-bold text-gray-200" x-text="h.name"></div>
                    <div class="text-[8px] font-black text-gray-600 uppercase tracking-widest mt-1" x-text="h.last_met_diff"></div>
                </div>
            </div>
            <button @click="if(confirm('{{ __('messenger.Block') }}')) { window.axios.post('/chat/block', {userId: h.id}).then(() => loadHistory()) }" 
                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all">
                🚩
            </button>
        </div>
        <button @click="openFriendChat(h.id)" class="w-full bg-white/[0.03] border border-white/5 text-gray-300 py-3 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-brand-indigo hover:text-white transition-all">
            {{ __('messenger.Message') }}
        </button>
    </div>
</template>