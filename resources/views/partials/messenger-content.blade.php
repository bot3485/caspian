<!-- Навигация по вкладкам -->
<div class="flex border-b border-white/5 bg-[#0a0a0a] px-2 shrink-0">
    <button @click="tab = 'chat'" 
            :class="tab === 'chat' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">Чат</button>
    
    <button @click="tab = 'friends'; loadFriends()" 
            :class="tab === 'friends' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">Друзья</button>
    
    <button @click="tab = 'history'; loadHistory()" 
            :class="tab === 'history' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">История</button>
    
    <button @click="tab = 'blacklist'; loadBlocked()" 
            :class="tab === 'blacklist' ? 'text-red-500 border-b-2 border-red-500' : 'text-gray-500'" 
            class="flex-1 py-5 text-[9px] font-black uppercase tracking-widest transition-all">ЧС</button>
</div>

<!-- Контент вкладок -->
<div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">
    
    <!-- ВКЛАДКА: ЧАТ -->
    <div x-show="tab === 'chat'" class="flex-1 flex flex-col overflow-hidden">
        <template x-if="state === 'connected'">
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Контейнер сообщений с x-ref -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" x-ref="chatBox">
                    <template x-for="msg in messages" :key="msg.timestamp">
                        <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col">
                            <div :class="msg.isMe ? 'bg-indigo-600 shadow-lg shadow-indigo-500/20' : 'bg-white/5 border border-white/10'" 
                                 class="p-4 text-[13px] font-medium max-w-[85%] rounded-2xl" x-text="msg.text"></div>
                        </div>
                    </template>
                </div>
                <!-- Индикатор печати -->
                <div class="px-6 py-2 h-8 shrink-0" x-show="isPartnerTyping" x-transition>
                    <span class="text-[8px] font-black text-indigo-400 animate-pulse uppercase tracking-widest">Печатает...</span>
                </div>
                <!-- Ввод сообщения -->
                <div class="p-4 bg-[#0a0a0a] border-t border-white/5 pb-12 md:pb-6 shrink-0">
                    <div class="flex gap-2 bg-black/40 p-2 rounded-2xl border border-white/5">
                        <input type="text" x-model="chatInput" @input="sendTypingSignal()" @keyup.enter="sendMsg()" 
                               placeholder="Написать..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 px-4 h-12 text-white">
                        <button @click="sendMsg()" class="bg-indigo-600 text-white w-12 h-12 rounded-xl hover:bg-indigo-500 transition-colors">➔</button>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="state !== 'connected'">
            <div class="flex-1 flex flex-col items-center justify-center opacity-30 p-10 text-center">
                <div class="text-5xl mb-6">💬</div>
                <div class="text-[10px] font-black uppercase tracking-widest">Чат доступен только во время звонка</div>
            </div>
        </template>
    </div>

    <!-- ВКЛАДКА: ДРУЗЬЯ -->
    <div x-show="tab === 'friends'" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
        <template x-for="f in friendsList" :key="f.id">
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-3xl flex items-center justify-between group hover:bg-white/[0.05] transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-2xl flex items-center justify-center font-black text-xs" x-text="f.name[0]"></div>
                    <div>
                        <div class="text-xs font-bold text-white" x-text="f.name"></div>
                        <div class="text-[7px] font-black uppercase text-green-500 mt-1" x-show="onlineList.some(u => u.id === f.id)">В сети</div>
                        <div class="text-[7px] font-black uppercase text-gray-500 mt-1" x-show="!onlineList.some(u => u.id === f.id)" x-text="f.last_seen_human"></div>
                    </div>
                </div>
                <button @click="callFriend(f); mobileSidebarOpen = false" :disabled="!onlineList.some(u => u.id === f.id)" 
                        class="w-10 h-10 bg-indigo-500 text-white rounded-xl flex items-center justify-center disabled:opacity-10 active:scale-90 transition-all shadow-lg">📞</button>
            </div>
        </template>
    </div>

    <!-- ВКЛАДКА: ИСТОРИЯ -->
    <div x-show="tab === 'history'" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
        <template x-for="h in historyList" :key="h.id + h.last_at">
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-3xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/5 text-gray-500 rounded-2xl flex items-center justify-center font-black text-xs" x-text="h.name[0]"></div>
                    <div>
                        <div class="text-xs font-bold text-white" x-text="h.name"></div>
                        <div class="text-[7px] font-black uppercase text-gray-600 mt-1" x-text="h.last_met_diff"></div>
                    </div>
                </div>
                <button @click="callFriend(h)" class="w-10 h-10 bg-white/5 text-white rounded-xl flex items-center justify-center hover:bg-white/10 active:scale-90 transition-all">📞</button>
            </div>
        </template>
    </div>

    <!-- ВКЛАДКА: ЧС -->
    <div x-show="tab === 'blacklist'" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
        <template x-for="b in blockedList" :key="b.id">
            <div class="p-4 bg-red-500/5 border border-red-500/10 rounded-3xl flex items-center justify-between group">
                <div class="text-xs font-bold text-red-200" x-text="b.name"></div>
                <button @click="unblock(b.id)" class="px-4 py-2 bg-white/5 hover:bg-red-600 hover:text-white rounded-xl text-[8px] font-black uppercase transition-all">Разблокировать</button>
            </div>
        </template>
    </div>
</div>