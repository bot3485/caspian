<div x-show="activeFriend" class="flex-1 flex flex-col overflow-hidden relative bg-[#050505]">
    <!-- Header -->
    <div class="px-4 py-3 bg-[#020202]/80 backdrop-blur-xl border-b border-white/[0.05] flex items-center justify-between z-10">
        <div class="flex gap-2">
            <button @click="clearMessages(activeFriend.id)" class="w-9 h-9 flex items-center justify-center bg-white/[0.03] hover:bg-red-500/10 text-gray-600 hover:text-red-500 rounded-xl transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </button>
            <button @click="activeFriend = null" class="w-9 h-9 flex items-center justify-center bg-white/[0.03] hover:bg-white/10 rounded-xl transition-colors">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
        </div>
        <div class="text-center">
            <div class="text-[11px] font-black uppercase tracking-widest text-white" x-text="activeFriend?.name"></div>
            <div class="text-[7px] uppercase font-bold tracking-[0.2em] mt-0.5 text-gray-500" x-text="activeFriend?.is_online ? 'Secure Bridge: Linked' : 'Secure Bridge: Offline'"></div>
        </div>
        <button @click="callFriend(activeFriend)" :disabled="!activeFriend?.is_online" 
                class="w-9 h-9 flex items-center justify-center rounded-xl transition-all" 
                :class="activeFriend?.is_online ? 'bg-brand-indigo text-white shadow-[0_0_15px_rgba(99,102,241,0.4)]' : 'bg-white/5 text-gray-700 opacity-50'">
            <span class="text-xs">📞</span>
        </button>
    </div>

    <!-- Messages -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6 custom-scrollbar" x-ref="friendChatBox">
        <template x-for="msg in friendMessages" :key="msg.id || Math.random()">
            <div :class="Number(msg.sender_id) === Number({{ auth()->id() }}) ? 'items-end' : 'items-start'" class="flex flex-col w-full group">
                
                <!-- Handshake request -->
                <template x-if="msg.message === 'SYSTEM_FRIEND_REQUEST'">
                    <div class="w-full flex flex-col items-center py-4 bg-brand-indigo/5 rounded-[2rem] border border-brand-indigo/10 my-2">
                        <span class="text-[9px] font-black uppercase tracking-[0.3em] text-brand-indigo mb-4">{{ __('messenger.Incoming_Protocol') }}</span>
                        <div class="flex gap-2" x-show="Number(msg.sender_id) !== Number({{ auth()->id() }})">
                            <button @click="handleFriendRequest(msg.sender_id, 'accept')" class="px-6 py-2 bg-brand-indigo text-white rounded-lg text-[9px] font-black uppercase">{{ __('messenger.Accept') }}</button>
                            <button @click="handleFriendRequest(msg.sender_id, 'decline')" class="px-6 py-2 bg-white/5 text-gray-500 rounded-lg text-[9px] font-black uppercase">{{ __('messenger.Decline') }}</button>
                        </div>
                    </div>
                </template>

                <template x-if="!msg.message.startsWith('SYSTEM_')">
                    <div :class="Number(msg.sender_id) === Number({{ auth()->id() }}) 
                            ? 'bg-brand-indigo text-white rounded-[1.25rem] rounded-br-none shadow-lg' 
                            : 'bg-white/[0.04] border border-white/[0.05] text-gray-200 rounded-[1.25rem] rounded-bl-none'" 
                         class="px-5 py-3 text-[13px] max-w-[85%] break-words" x-text="msg.message"></div>
                </template>
                <span class="text-[7px] font-bold text-gray-600 mt-2 px-2 uppercase opacity-0 group-hover:opacity-100 transition-opacity" x-text="formatDateTime(msg)"></span>
            </div>
        </template>
    </div>

    <!-- Input -->
    <div class="p-4 bg-[#020202]/50 border-t border-white/5">
        <template x-if="activeFriend?.status === 'accepted'">
            <div class="flex items-center gap-2 bg-white/[0.03] p-1.5 rounded-2xl border border-white/10 focus-within:border-brand-indigo/50 transition-all">
                <input type="text" x-model="friendChatInput" @keyup.enter="sendFriendMsg()" placeholder="Message..." class="flex-1 bg-transparent border-none text-sm px-4 focus:ring-0 text-white">
                <button @click="sendFriendMsg()" class="bg-brand-indigo text-white w-10 h-10 rounded-xl flex items-center justify-center hover:scale-105 transition-all">➔</button>
            </div>
        </template>
    </div>
</div>