<template x-for="b in blockedList" :key="b.id">
    <div class="p-4 bg-red-500/[0.03] border border-red-500/10 rounded-[1.5rem] flex items-center justify-between">
        <div class="flex flex-col">
            <span class="text-xs font-bold text-gray-300" x-text="b.name"></span>
            <span class="text-[7px] font-black text-red-400/70 uppercase tracking-[0.2em] mt-1">{{ __('messenger.Protocol_Terminated') }}</span>
        </div>
        <button @click="unblock(b.id)" class="px-4 py-2 bg-white/5 border border-white/10 text-gray-300 rounded-xl text-[8px] font-black uppercase hover:bg-white/10 transition-all">
            {{ __('messenger.Unblock') }}
        </button>
    </div>
</template>