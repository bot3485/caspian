<div class="flex flex-col gap-6">
    <!-- СЕКЦИЯ: ВХОДЯЩИЕ ЗАПРОСЫ -->
    <template x-if="friendsList.some(f => f.status === 'pending' && f.is_incoming)">
        <div class="space-y-3">
            <div class="flex items-center gap-3 px-4">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-indigo animate-pulse"></span>
                <h4 class="text-[8px] font-black uppercase tracking-[0.4em] text-brand-indigo">{{ __('messenger.Incoming_Protocol') }}</h4>
            </div>
            
            <template x-for="f in friendsList.filter(f => f.status === 'pending' && f.is_incoming)" :key="f.id">
                <div class="p-4 bg-brand-indigo/5 border border-brand-indigo/20 rounded-[2rem] flex items-center justify-between transition-all hover:bg-brand-indigo/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-brand-indigo/20 rounded-xl flex items-center justify-center font-black text-white" x-text="f.name[0]"></div>
                        <span class="text-[11px] font-black text-white uppercase italic tracking-tighter" x-text="f.name"></span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="handleFriendRequest(f.id, 'accept')" class="w-9 h-9 bg-brand-indigo text-white rounded-xl flex items-center justify-center shadow-lg hover:scale-110 transition-all">✓</button>
                        <button @click="handleFriendRequest(f.id, 'decline')" class="w-9 h-9 bg-white/5 text-gray-500 rounded-xl flex items-center justify-center hover:bg-red-500/20 transition-all">✕</button>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- СЕКЦИЯ: ВАШИ ДРУЗЬЯ -->
    <div class="space-y-2">
        <h4 class="px-4 text-[8px] font-black uppercase tracking-[0.4em] text-gray-600">{{ __('messenger.Contacts') }}</h4>
        <template x-for="f in friendsList.filter(f => f.status === 'accepted')" :key="f.id">
            <div @click="openFriendChat(f.id)" 
                 class="p-4 border rounded-[2rem] flex items-center justify-between transition-all duration-300 group cursor-pointer"
                 :class="f.unread_count > 0 ? 'bg-brand-indigo/5 border-brand-indigo/30' : 'bg-white/[0.01] border-white/[0.04] hover:bg-white/[0.04]'">
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-lg border border-white/5"
                             :class="f.is_online ? 'bg-brand-indigo/20 text-white' : 'bg-gray-800/20 text-gray-600'" 
                             x-text="f.name[0]"></div>
                        <template x-if="f.is_online">
                            <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-4 border-[#050505] rounded-full shadow-[0_0_10px_#22c55e]"></span>
                        </template>
                    </div>
                    <div>
                        <div class="text-sm font-black text-gray-100 uppercase italic tracking-tighter" x-text="f.name"></div>
                        <div class="text-[8px] font-black uppercase mt-1 tracking-widest text-gray-500" x-text="f.is_online ? 'BRIDGE: ACTIVE' : f.last_seen_human"></div>
                    </div>
                </div>

                <!-- Кол-во новых сообщений от конкретного друга -->
                <template x-if="f.unread_count > 0">
                    <div class="bg-brand-indigo text-white px-2 py-1 rounded-lg text-[9px] font-black min-w-[20px] text-center shadow-lg shadow-brand-indigo/20" x-text="f.unread_count"></div>
                </template>
            </div>
        </template>
    </div>
</div>