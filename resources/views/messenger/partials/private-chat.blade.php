<div x-show="activeFriend" class="flex-1 flex flex-col overflow-hidden relative bg-[#050505]" x-cloak>
    <!-- Header -->
    <div class="px-4 py-3 bg-[#020202]/90 backdrop-blur-2xl border-b border-white/[0.05] flex items-center justify-between z-20">
        <div class="flex items-center gap-3">
            <button @click="activeFriend = null" class="w-10 h-10 flex items-center justify-center bg-white/[0.03] hover:bg-white/10 rounded-xl transition-all">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-[12px] font-black uppercase tracking-wider text-white" x-text="activeFriend?.name"></span>
                    <div x-show="activeFriend?.is_online" class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_8px_#22c55e]"></div>
                </div>
                <div class="h-3">
                    <span x-show="isPartnerTyping" x-transition class="text-[8px] font-black text-brand-indigo uppercase tracking-widest animate-pulse">Printing...</span>
                    <span x-show="!isPartnerTyping" class="text-[8px] font-bold text-gray-600 uppercase tracking-widest" x-text="activeFriend?.is_online ? 'Secure Link Established' : 'Last seen: ' + activeFriend?.last_seen_human"></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button @click="clearMessages(activeFriend.id)" title="Clear History" class="w-10 h-10 flex items-center justify-center bg-white/[0.03] hover:bg-red-500/10 text-gray-600 hover:text-red-500 rounded-xl transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </button>

            <!-- ЕСЛИ ДРУГ -->
            <template x-if="activeFriend?.status === 'accepted'">
                <button @click="callFriend(activeFriend)" :disabled="!activeFriend?.is_online" 
                        title="Secure Call"
                        class="w-10 h-10 flex items-center justify-center rounded-xl transition-all" 
                        :class="activeFriend?.is_online ? 'bg-brand-indigo text-white shadow-[0_0_15px_rgba(99,102,241,0.4)]' : 'bg-white/5 text-gray-700 opacity-50'">
                    <span class="text-xs">📞</span>
                </button>
            </template>

            <!-- ЕСЛИ НЕ ДРУГ -->
            <template x-if="activeFriend?.status !== 'accepted'">
                <button @click="requestFriendFromChat()" 
                        :disabled="activeFriend?.status === 'pending'"
                        title="Establish Protocol (Add Friend)"
                        class="w-10 h-10 flex items-center justify-center rounded-xl transition-all"
                        :class="activeFriend?.status === 'pending' ? 'bg-amber-500/20 text-amber-500 cursor-not-allowed' : 'bg-white/[0.03] hover:bg-brand-indigo text-gray-400 hover:text-white'">
                    <span class="text-xs" x-text="activeFriend?.status === 'pending' ? '⏳' : '➕'"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Message Area (Без карточек приглашений) -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6 custom-scrollbar flex flex-col" x-ref="friendChatBox">
        <template x-for="(msg, index) in friendMessages" :key="msg.id || index">
            <div class="w-full flex flex-col" :class="String(msg.sender_id) === String(myHashid) ? 'items-end' : 'items-start'">
                
                <!-- System Accepted Message (уведомление об успешном линке) -->
                <template x-if="msg.message === 'SYSTEM_FRIEND_ACCEPTED'">
                    <div class="w-full flex justify-center my-4">
                        <div class="px-4 py-2 bg-green-500/10 border border-green-500/20 rounded-full">
                            <span class="text-[8px] font-black uppercase tracking-[0.3em] text-green-500">Protocol Linked Successfully</span>
                        </div>
                    </div>
                </template>

                <!-- Regular Message (Исключает любые SYSTEM_ сообщения из текстового потока) -->
                <template x-if="!String(msg.message || '').startsWith('SYSTEM_')">
                    <div class="group max-w-[85%] flex flex-col" :class="String(msg.sender_id) === String(myHashid) ? 'items-end' : 'items-start'">
                        <div :class="String(msg.sender_id) === String(myHashid) 
                                ? 'bg-brand-indigo text-white rounded-[1.5rem] rounded-br-none shadow-xl' 
                                : 'bg-white/[0.04] border border-white/[0.05] text-gray-200 rounded-[1.5rem] rounded-bl-none'" 
                             class="px-5 py-3 text-[13px] leading-relaxed transition-all group-hover:brightness-110">
                            <span x-text="msg.message"></span>
                        </div>
                        <span class="text-[7px] font-black text-gray-600 mt-2 px-2 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity" x-text="formatDateTime(msg)"></span>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-[#020202]/80 backdrop-blur-xl border-t border-white/5">
        <template x-if="activeFriend?.status === 'accepted'">
            <div class="flex items-center gap-3 bg-white/[0.03] p-2 rounded-[1.8rem] border border-white/10 focus-within:border-brand-indigo/50 focus-within:bg-white/[0.05] transition-all">
                <input type="text" 
                       x-model="friendChatInput" 
                       @input="sendTyping()"
                       @keyup.enter="sendFriendMsg()" 
                       placeholder="Enter secure message..." 
                       class="flex-1 bg-transparent border-none text-sm px-4 focus:ring-0 text-white placeholder-gray-600 font-medium">
                
                <button @click="sendFriendMsg()" 
                        :disabled="!friendChatInput.trim()"
                        class="bg-brand-indigo text-white w-11 h-11 rounded-2xl flex items-center justify-center hover:scale-105 active:scale-95 transition-all disabled:opacity-20 disabled:grayscale disabled:scale-100 shadow-lg shadow-brand-indigo/20">
                    <svg class="w-5 h-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </template>
        
        <template x-if="activeFriend?.status === 'pending'">
            <div class="py-3 text-center">
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-gray-600">Chat Locked: Awaiting Approval</span>
            </div>
        </template>
    </div>
</div>