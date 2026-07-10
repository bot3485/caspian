<!-- Навигация по вкладкам -->
<div class="flex border-b border-white/5 bg-[#0a0a0a] px-2 shrink-0">
    <button @click="tab = 'chat'; activeFriend = null" 
            :class="tab === 'chat' && !activeFriend ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">Рулетка</button>
    
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

<!-- Контент вкладок -->
<div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">
    
    <!-- 1. ЧАТ РУЛЕТКИ -->
    <div x-show="tab === 'chat' && !activeFriend" class="flex-1 flex flex-col overflow-hidden">
        
        <!-- ПРОВЕРКА: Если мы в звонке с другом -->
        <template x-if="isCallingFriend && state === 'connected'">
            <div class="flex-1 flex flex-col items-center justify-center p-10 text-center space-y-4">
                <div class="w-16 h-16 bg-indigo-500/10 rounded-full flex items-center justify-center text-2xl">🔒</div>
                <p class="text-[11px] font-black uppercase tracking-widest text-indigo-400">Чат рулетки отключен</p>
                <p class="text-[10px] text-gray-500 leading-relaxed">Вы находитесь в приватном звонке. Используйте вкладку "Друзья" для переписки.</p>
                <button @click="openFriendChat(partnerData)" 
                        class="bg-indigo-600 px-6 py-3 rounded-xl text-[9px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20 active:scale-95 transition-all">
                    Перейти в чат с другом
                </button>
            </div>
        </template>

        <!-- ОБЫЧНЫЙ ЧАТ РУЛЕТКИ -->
        <template x-if="state === 'connected' && !isCallingFriend">
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" x-ref="chatBox">
                    <template x-for="msg in messages" :key="msg.timestamp">
                        <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col">
                            <div :class="msg.isMe ? 'bg-indigo-600 shadow-lg shadow-indigo-500/20' : 'bg-white/5 border border-white/10'" 
                                 class="p-4 text-[13px] font-medium max-w-[85%] rounded-2xl break-words" x-text="msg.text"></div>
                        </div>
                    </template>
                </div>
                <!-- Индикатор печати -->
                <div class="px-6 py-2 h-8 shrink-0" x-show="isPartnerTyping" x-transition>
                    <span class="text-[8px] font-black text-indigo-400 animate-pulse uppercase tracking-widest" x-text="typingPartnerName + ' печатает...'"></span>
                </div>
                <div class="p-4 bg-[#0a0a0a] border-t border-white/5 pb-safe shrink-0">
                    <div class="flex gap-2 bg-black/40 p-2 rounded-2xl border border-white/5">
                        <input type="text" x-model="chatInput" @input="sendTypingSignal()" @keyup.enter="sendMsg()" 
                               placeholder="Написать анонимно..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 px-4 h-12 text-white">
                        <button @click="sendMsg()" class="bg-indigo-600 text-white w-12 h-12 rounded-xl hover:bg-indigo-500 transition-colors">➔</button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="state !== 'connected'">
            <div class="flex-1 flex flex-col items-center justify-center opacity-30 p-10 text-center">
                <div class="text-5xl mb-6">🎲</div>
                <div class="text-[10px] font-black uppercase tracking-widest">Чат появится после соединения в рулетке</div>
            </div>
        </template>
    </div>

    <!-- 2. СПИСОК ДРУЗЕЙ -->
    <div x-show="tab === 'friends' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
        <template x-for="f in friendsList" :key="f.id">
            <div @click="openFriendChat(f)" class="p-4 bg-white/[0.02] border border-white/5 rounded-3xl flex items-center justify-between group cursor-pointer hover:bg-white/5 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-2xl flex items-center justify-center font-black text-xs" x-text="f.name[0]"></div>
                    <div>
                        <div class="text-xs font-bold text-white" x-text="f.name"></div>
                        <div class="text-[7px] font-black uppercase mt-1" :class="f.is_online ? 'text-green-500' : 'text-gray-500'" 
                             x-text="f.is_online ? 'В сети' : f.last_seen_human"></div>
                    </div>
                </div>
                <div class="w-8 h-8 flex items-center justify-center text-indigo-500 opacity-0 group-hover:opacity-100 transition-all">➔</div>
            </div>
        </template>
        <template x-if="friendsList.length === 0">
            <div class="text-center py-10 opacity-20 text-[10px] font-black uppercase tracking-widest">Список друзей пуст</div>
        </template>
    </div>

    <!-- 3. ПЕРСОНАЛЬНЫЙ ЧАТ С ДРУГОМ -->
    <div x-show="activeFriend" class="flex-1 flex flex-col overflow-hidden" x-transition>
        <div class="p-4 bg-[#0a0a0a] border-b border-white/5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <button @click="activeFriend = null" class="w-8 h-8 flex items-center justify-center bg-white/5 rounded-full text-gray-400 hover:text-white transition-colors">←</button>
                <div>
                    <div class="text-xs font-black uppercase tracking-widest text-white" x-text="activeFriend?.name"></div>
                    <div class="text-[7px] font-bold uppercase tracking-tighter" :class="activeFriend?.is_online ? 'text-green-500' : 'text-gray-500'" 
                         x-text="activeFriend?.is_online ? 'Онлайн' : 'Офлайн'"></div>
                </div>
            </div>
            <button @click="callFriend(activeFriend)" :disabled="!activeFriend?.is_online"
                    :class="activeFriend?.is_online ? 'bg-indigo-600 shadow-lg shadow-indigo-600/20' : 'bg-white/5 opacity-20'"
                    class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95">
                📞 Звонок
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" x-ref="friendChatBox">
            <template x-for="msg in friendMessages" :key="msg.id">
                <div :class="msg.sender_id === {{ auth()->id() }} ? 'items-end' : 'items-start'" class="flex flex-col">
                    <template x-if="msg.message.includes('📞')">
                         <div class="bg-red-500/10 border border-red-500/20 px-4 py-2 rounded-xl text-[10px] font-bold text-red-400 uppercase tracking-tighter mb-2" x-text="msg.message"></div>
                    </template>
                    <template x-if="!msg.message.includes('📞')">
                        <div :class="msg.sender_id === {{ auth()->id() }} ? 'bg-indigo-600 shadow-lg' : 'bg-white/5 border border-white/10'" 
                             class="p-4 text-[13px] font-medium max-w-[85%] rounded-2xl break-words" x-text="msg.message"></div>
                    </template>
                    <span class="text-[7px] text-gray-600 uppercase mt-1 px-2" x-text="new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                </div>
            </template>
        </div>

        <!-- Индикатор печати для друга -->
        <div class="px-6 py-2 h-8 shrink-0" x-show="isPartnerTyping" x-transition>
            <span class="text-[8px] font-black text-indigo-400 animate-pulse uppercase tracking-widest" x-text="typingPartnerName + ' печатает...'"></span>
        </div>

        <div class="p-4 bg-[#0a0a0a] border-t border-white/5 pb-safe shrink-0">
            <div class="flex gap-2 bg-black/40 p-2 rounded-2xl border border-white/5">
                <input type="text" x-model="friendChatInput" @input="sendTypingSignal()" @keyup.enter="sendFriendMsg()" 
                       placeholder="Написать сообщение..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 px-4 h-12 text-white">
                <button @click="sendFriendMsg()" class="bg-indigo-600 text-white w-12 h-12 rounded-xl hover:bg-indigo-500 transition-colors">➔</button>
            </div>
        </div>
    </div>

    <!-- 4. ИСТОРИЯ ВСТРЕЧ -->
    <div x-show="tab === 'history' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
        <template x-for="h in historyList" :key="h.id + h.last_at">
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-3xl flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/5 text-gray-500 rounded-2xl flex items-center justify-center font-black text-xs" x-text="h.name[0]"></div>
                    <div>
                        <div class="text-xs font-bold text-white" x-text="h.name"></div>
                        <div class="text-[7px] font-black uppercase text-gray-600 mt-1" x-text="'Встречались: ' + h.last_met_diff"></div>
                    </div>
                </div>
                <button @click="openFriendChat(h)" class="w-10 h-10 bg-white/5 text-white rounded-xl flex items-center justify-center hover:bg-white/10 active:scale-90 transition-all">💬</button>
            </div>
        </template>
        <template x-if="historyList.length === 0">
            <div class="text-center py-10 opacity-20 text-[10px] font-black uppercase tracking-widest">История пуста</div>
        </template>
    </div>

    <!-- 5. ЧЕРНЫЙ СПИСОК -->
    <div x-show="tab === 'blacklist' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
        <template x-for="b in blockedList" :key="b.id">
            <div class="p-4 bg-red-500/5 border border-red-500/10 rounded-3xl flex items-center justify-between group">
                <div class="text-xs font-bold text-red-200" x-text="b.name"></div>
                <button @click="unblock(b.id)" class="px-4 py-2 bg-white/5 hover:bg-red-600 hover:text-white rounded-xl text-[8px] font-black uppercase transition-all">Разблокировать</button>
            </div>
        </template>
    </div>
</div>