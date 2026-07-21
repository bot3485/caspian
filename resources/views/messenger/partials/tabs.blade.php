<div class="flex border-b border-white/[0.05] bg-[#020202]/80 backdrop-blur-2xl px-2 shrink-0 z-20 sticky top-0">
    <!-- Roulette Chat -->
    <button @click="if(!isFriend) { tab = 'chat'; activeFriend = null; }" 
            :disabled="isFriend && state === 'connected'"
            class="flex-1 py-4 text-[9px] font-black uppercase tracking-[0.15em] relative transition-all duration-300 group"
            :class="tab === 'chat' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
        <span>{{ __('messenger.Roulette_Chat') }}</span>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] bg-brand-indigo rounded-t-full transition-all duration-500"
             :class="tab === 'chat' ? 'w-1/2 opacity-100 shadow-[0_-2px_10px_rgba(99,102,241,0.5)]' : 'w-0 opacity-0'"></div>
    </button>

    <!-- Contacts -->
    <button @click="tab = 'friends'; activeFriend = null;" 
            class="flex-1 py-4 text-[9px] font-black uppercase tracking-[0.15em] flex items-center justify-center gap-2 relative transition-all"
            :class="tab === 'friends' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
        <span>{{ __('messenger.Contacts') }}</span>
        <template x-if="hasUnreadFriends()">
            <span class="flex h-1.5 w-1.5 rounded-full bg-brand-indigo shadow-[0_0_8px_#6366f1] animate-pulse"></span>
        </template>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] bg-brand-indigo transition-all duration-500"
            :class="tab === 'friends' ? 'w-1/2 opacity-100' : 'w-0 opacity-0'"></div>
    </button>

    <!-- Logs -->
    <button @click="tab = 'history'; activeFriend = null;" 
            class="flex-1 py-4 text-[9px] font-black uppercase tracking-[0.15em] flex items-center justify-center gap-2 relative transition-all"
            :class="tab === 'history' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
        <span>{{ __('messenger.Logs') }}</span>
        <template x-if="hasUnreadHistory()">
            <span class="flex h-1.5 w-1.5 rounded-full bg-amber-500 shadow-[0_0_8px_#f59e0b] animate-pulse"></span>
        </template>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] bg-brand-indigo transition-all duration-500"
            :class="tab === 'history' ? 'w-1/2 opacity-100' : 'w-0 opacity-0'"></div>
    </button>

    <!-- НОВОЕ: Кнопка Blacklist (Blocked) -->
    <button @click="tab = 'blacklist'; activeFriend = null;" 
            class="flex-1 py-4 text-[9px] font-black uppercase tracking-[0.15em] relative transition-all duration-300"
            :class="tab === 'blacklist' ? 'text-red-500' : 'text-gray-500 hover:text-red-400'">
        <span>{{ __('messenger.Blocked') }}</span>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] bg-red-600 rounded-t-full transition-all duration-500"
             :class="tab === 'blacklist' ? 'w-1/2 opacity-100 shadow-[0_-2px_10px_rgba(220,38,38,0.5)]' : 'w-0 opacity-0'"></div>
    </button>
</div>