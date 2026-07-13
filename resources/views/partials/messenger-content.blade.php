<!-- Навигация по вкладкам -->
<div class="flex border-b border-white/5 bg-[#0a0a0a] px-2 shrink-0">
    <button x-show="callContext !== 'personal'" 
            @click="tab = 'chat'; activeFriend = null" 
            :class="tab === 'chat' && !activeFriend ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">
        Roulette
    </button>
    
    <button @click="tab = 'friends'; activeFriend = null; loadFriends()" 
            :class="(tab === 'friends' || activeFriend) ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">Друзья</button>
    
    <button @click="tab = 'history'; activeFriend = null; loadHistory()" 
            :class="tab === 'history' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">История</button>
    
    <button @click="tab = 'blacklist'; activeFriend = null; loadBlocked()" 
            :class="tab === 'blacklist' ? 'text-red-500 border-b-2 border-red-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">ЧС</button>
</div>

<div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">
    <!-- ЧАТ РУЛЕТКИ -->
    <div x-show="tab === 'chat' && !activeFriend && callContext !== 'personal'" class="flex-1 flex flex-col overflow-hidden">
        <template x-if="state === 'connected'"> 
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" x-ref="chatBox">
                    <template x-for="msg in messages" :key="msg.timestamp">
                        <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col">
                            <div :class="msg.isMe ? 'bg-indigo-600' : 'bg-white/5 border border-white/10'" 
                                 class="p-4 text-[13px] font-medium max-w-[85%] rounded-2xl break-words" x-text="msg.text"></div>
                        </div>
                    </template>
                </div>
                <div class="p-4 bg-[#0a0a0a] border-t border-white/5">
                    <div class="flex gap-2 bg-black/40 p-2 rounded-2xl border border-white/5">
                        <input type="text" x-model="chatInput" @input="sendTypingSignal()" @keyup.enter="sendMsg()" placeholder="Написать..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 text-white">
                        <button @click="sendMsg()" class="bg-indigo-600 text-white w-12 h-12 rounded-xl">➔</button>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="state !== 'connected'"><div class="flex-1 flex flex-col items-center justify-center opacity-30 text-center"><div class="text-5xl mb-6">🎲</div><div class="text-[10px] font-black uppercase tracking-widest">Чат после соединения</div></div></template>
    </div>

    <!-- Дополнительно: Сообщение о блокировке (по желанию) -->
    <div x-show="tab === 'chat' && callContext === 'personal'" class="flex-1 flex flex-col items-center justify-center opacity-50 text-center p-6">
        <div class="text-4xl mb-4">🔒</div>
        <div class="text-[10px] font-black uppercase tracking-widest text-indigo-400">
            Roulette chat is disabled during personal call
        </div>
    </div>

    <!-- ДРУЗЬЯ -->
    <div x-show="tab === 'friends' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-2">
        <template x-for="f in friendsList" :key="f.id">
            <div @click="openFriendChat(f)" class="p-4 bg-white/[0.02] border border-white/5 rounded-3xl flex items-center justify-between cursor-pointer hover:bg-white/5 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-2xl flex items-center justify-center font-black" x-text="f.name[0]"></div>
                    <div>
                        <div class="text-xs font-bold" x-text="f.name"></div>
                        <div class="text-[7px] font-black uppercase mt-1" :class="f.is_online ? 'text-green-500' : 'text-gray-500'" x-text="f.is_online ? 'В сети' : f.last_seen_human"></div>
                    </div>
                </div>
                <div class="w-8 h-8 text-indigo-500">➔</div>
            </div>
        </template>
    </div>

    <!-- ЧАТ С ДРУГОМ -->
    <div x-show="activeFriend" class="flex-1 flex flex-col overflow-hidden">
        <div class="p-4 bg-[#0a0a0a] border-b border-white/5 flex items-center justify-between">
            <button @click="activeFriend = null" class="w-8 h-8 flex items-center justify-center bg-white/5 rounded-full">←</button>
            <div class="text-center">
                <div class="text-xs font-black uppercase" x-text="activeFriend?.name"></div>
                <div class="text-[7px] uppercase" :class="activeFriend?.is_online ? 'text-green-500' : 'text-gray-500'" x-text="activeFriend?.is_online ? 'Онлайн' : 'Офлайн'"></div>
            </div>
            <button @click="callFriend(activeFriend)" :disabled="!activeFriend?.is_online" :class="activeFriend?.is_online ? 'bg-indigo-600' : 'bg-white/5 opacity-20'" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase">📞 Звонок</button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" x-ref="friendChatBox">
            <template x-for="msg in friendMessages" :key="msg.id">
                <div :class="msg.sender_id === {{ auth()->id() }} ? 'items-end' : 'items-start'" class="flex flex-col">
                    <div :class="msg.sender_id === {{ auth()->id() }} ? 'bg-indigo-600' : 'bg-white/5 border border-white/10'" class="p-4 text-[13px] rounded-2xl break-words" x-text="msg.message"></div>
                </div>
            </template>
        </div>
        <div class="p-4 bg-[#0a0a0a] border-t border-white/5">
            <div class="flex gap-2 bg-black/40 p-2 rounded-2xl border border-white/5">
                <input type="text" x-model="friendChatInput" @input="sendTypingSignal()" @keyup.enter="sendFriendMsg()" placeholder="Сообщение..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 text-white">
                <button @click="sendFriendMsg()" class="bg-indigo-600 text-white w-12 h-12 rounded-xl">➔</button>
            </div>
        </div>
    </div>

    <!-- ИСТОРИЯ -->
    <div x-show="tab === 'history' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-3">
        <template x-for="h in historyList" :key="h.id">
            <div class="p-4 bg-white/[0.03] border border-white/5 rounded-[2rem] space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-800 rounded-2xl flex items-center justify-center font-black" x-text="h.name[0]"></div>
                        <div>
                            <div class="text-sm font-black" x-text="h.name"></div>
                            <div class="text-[9px] font-bold text-gray-500 uppercase" x-text="h.last_met_diff"></div>
                        </div>
                    </div>
                    <button @click="window.axios.post('/chat/block', {userId: h.id}).then(() => loadHistory())" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-500/10 text-red-500">🚩</button>
                </div>
                <div class="flex gap-2">
                    <button @click="openFriendChat(h)" class="flex-1 bg-white/5 hover:bg-indigo-600 py-3 rounded-xl text-[9px] font-black uppercase">💬 Написать</button>
                    <!-- КНОПКА + СКРЫВАЕТСЯ ЕСЛИ ЮЗЕР УЖЕ ДРУГ -->
                    <template x-if="!h.is_friend">
                        <button @click="window.axios.post('/chat/contact/add', {contactId: h.id}).then(() => { h.is_friend = true; window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Добавлен'}})) })" class="px-4 bg-white/5 rounded-xl text-lg">+</button>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- ЧЕРНЫЙ СПИСОК -->
    <div x-show="tab === 'blacklist' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-2">
        <template x-for="b in blockedList" :key="b.id">
            <div class="p-4 bg-red-500/5 border border-red-500/10 rounded-3xl flex items-center justify-between">
                <div class="text-xs font-bold text-red-200" x-text="b.name"></div>
                <button @click="unblock(b.id)" class="px-4 py-2 bg-white/5 hover:bg-red-600 rounded-xl text-[8px] font-black uppercase">Разблокировать</button>
            </div>
        </template>
    </div>
</div>