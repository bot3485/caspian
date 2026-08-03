<template x-for="f in friendsList" :key="f.id">
    <div class="p-3.5 border rounded-[1.5rem] flex items-center justify-between transition-all duration-300 group bg-white/[0.01] border-white/[0.03] hover:bg-white/[0.03]"
         :class="f.has_new_message ? 'border-brand-indigo/30 bg-white/[0.05]' : ''">
        
        <div @click="openFriendChat(f.id)" class="flex items-center gap-4 cursor-pointer flex-1">
            <div class="relative">
                <div class="w-11 h-11 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 border border-white/5 rounded-[1.2rem] flex items-center justify-center text-white font-black" x-text="f.name[0]"></div>
                <template x-if="f.is_online && f.status === 'accepted'">
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-[#050505] rounded-full"></span>
                </template>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-200" x-text="f.name"></span>
                    <template x-if="f.status === 'pending'">
                        <span class="text-[7px] font-black uppercase px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-500 border border-amber-500/20 tracking-widest">{{ __('messenger.Pending') }}</span>
                    </template>
                </div>
                <div class="text-[8px] font-black uppercase mt-1 tracking-widest text-gray-500" x-text="f.is_online ? 'online' : f.last_seen_human"></div>
            </div>
        </div>

        <button @click.stop="removeContact(f.id)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white/5 text-gray-500 hover:text-red-500 transition-all">
            ✕
        </button>
    </div>
</template>