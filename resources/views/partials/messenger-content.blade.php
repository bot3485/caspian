<!-- Навигация по вкладкам -->
<!-- Навигация по вкладкам -->
<div class="flex border-b border-white/5 bg-[#0a0a0a] px-2 shrink-0">
    <!-- Кнопка GLOBAL (Блокируется, если собеседник — друг) -->
    <button @click="if(!isFriend) { tab = 'chat'; activeFriend = null; }" 
            :disabled="isFriend && state === 'connected'"
            :class="[
                tab === 'chat' ? 'text-brand-indigo border-b-2 border-brand-indigo' : 'text-gray-600',
                (isFriend && state === 'connected') ? 'opacity-30 cursor-not-allowed' : ''
            ]" 
            class="flex-1 py-5 text-[10px] font-black uppercase tracking-widest relative">
        <span>{{ __('messenger.Roulette_Chat') }}</span>
        <template x-if="isFriend && state === 'connected'">
            <span class="absolute top-2 right-2 text-[8px]">🔒</span>
        </template>
    </button>

    <button @click="tab = 'friends'" :class="tab === 'friends' ? 'text-brand-indigo border-b-2 border-brand-indigo' : 'text-gray-600'" 
            class="flex-1 py-5 text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-1.5">
        <span>{{ __('messenger.Contacts') }}</span>
        <template x-if="friendsList.some(f => f.has_new_message)">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_8px_#22c55e] animate-pulse"></span>
        </template>
    </button>
    <button @click="tab = 'history'" :class="tab === 'history' ? 'text-brand-indigo border-b-2 border-brand-indigo' : 'text-gray-600'" 
            class="flex-1 py-5 text-[10px] font-black uppercase tracking-widest">{{ __('messenger.Logs') }}</button>
    <button @click="tab = 'blacklist'" :class="tab === 'blacklist' ? 'text-red-500 border-b-2 border-red-500' : 'text-gray-600'" 
            class="flex-1 py-5 text-[10px] font-black uppercase tracking-widest">{{ __('messenger.Blocked') }}</button>
</div>

<div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">
    <!-- ЧАТ РУЛЕТКИ -->
<!-- ЧАТ РУЛЕТКИ -->
    <div x-show="tab === 'chat' && !activeFriend && callContext !== 'personal'" class="flex-1 flex flex-col overflow-hidden">
        <!-- Если соединены и это НЕ друг -->
        <template x-if="state === 'connected' && !isFriend"> 
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

        <!-- Если соединены, но это ДРУГ (показываем заглушку-перенаправление) -->
        <template x-if="state === 'connected' && isFriend">
            <div class="flex-1 flex flex-col items-center justify-center opacity-50 text-center p-6">
                <div class="text-4xl mb-4">💬</div>
                <div class="text-[10px] font-black uppercase tracking-widest text-brand-indigo mb-2">
                    {{ __('messenger.Already_Friends') }}
                </div>
                <p class="text-xs text-gray-500 max-w-[250px]">
                    {{ __('messenger.Global_Chat_Disabled') }}
                </p>
                <button @click="openFriendChat(partnerId)" class="mt-6 px-6 py-3 bg-brand-indigo text-white rounded-full text-[9px] font-black uppercase tracking-widest">
                    {{ __('messenger.Personal_Chat') }}
                </button>
            </div>
        </template>

        <template x-if="state !== 'connected'"><div class="flex-1 flex flex-col items-center justify-center opacity-30 text-center"><div class="text-5xl mb-6">🎲</div><div class="text-[10px] font-black uppercase tracking-widest">{{ __('messenger.Chat_After_Connection') }}</div></div></template>
    </div>

    <!-- Дополнительно: Сообщение о блокировке -->
    <div x-show="tab === 'chat' && callContext === 'personal'" class="flex-1 flex flex-col items-center justify-center opacity-50 text-center p-6">
        <div class="text-4xl mb-4">🔒</div>
        <div class="text-[10px] font-black uppercase tracking-widest text-indigo-400">
            {{ __('messenger.Roulette_Chat_Disabled') }}
        </div>
    </div>

<!-- ДРУЗЬЯ (СПИСОК КОНТАКТОВ) -->
    <div x-show="tab === 'friends' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-2">
        <template x-for="(f, index) in friendsList" :key="f.id ? 'friend_' + f.id : 'f_' + index">
            <!-- Безопасный клик -->
            <div @click="f && f.id ? openFriendChat(f.id) : null" 
                 :class="f && f.has_new_message ? 'bg-green-500/[0.03] border-green-500/30 shadow-[0_0_15px_rgba(34,197,94,0.05)]' : 'bg-white/[0.02] border-white/5'"
                 class="p-4 border rounded-3xl flex items-center justify-between cursor-pointer hover:bg-white/5 transition-all">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <!-- Безопасное получение первой буквы имени -->
                        <div class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-2xl flex items-center justify-center font-black" 
                             x-text="f && f.name ? f.name.charAt(0).toUpperCase() : '?'"></div>
                        
                        <!-- Точка-индикатор -->
                        <template x-if="f && f.has_new_message">
                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                        </template>
                    </div>
                    <div>
                        <div class="text-xs font-bold flex items-center gap-2">
                            <span x-text="f ? f.name : 'Unknown'"></span>
                            
                            <!-- Кол-во непрочитанных -->
                            <template x-if="f && f.unread_count > 1">
                                <span class="bg-green-500 text-black text-[8px] font-black px-1.5 py-0.5 rounded-full" x-text="f.unread_count"></span>
                            </template>
                        </div>
                        <div class="text-[7px] font-black uppercase mt-1" 
                             :class="f && f.is_online ? 'text-green-500' : 'text-gray-500'" 
                             x-text="f ? (f.is_online ? 'online' : f.last_seen_human) : ''"></div>
                    </div>
                </div>
                <div class="w-8 h-8 flex items-center justify-center text-indigo-500" :class="f && f.has_new_message ? 'animate-bounce text-green-500' : ''">➔</div>
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
            <button @click="callFriend(activeFriend)" :disabled="!activeFriend?.is_online" :class="activeFriend?.is_online ? 'bg-indigo-600' : 'bg-white/5 opacity-20'" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase">📞 {{ __('messenger.Call') }}</button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" x-ref="friendChatBox">
            <template x-for="(msg, index) in friendMessages" :key="msg.id ? 'msg_' + msg.id : 'temp_' + index">
                <div :class="Number(msg.sender_id) === Number({{ auth()->id() }}) ? 'items-end' : 'items-start'" class="flex flex-col">
                    <!-- Баббл сообщения -->
                    <div :class="Number(msg.sender_id) === Number({{ auth()->id() }}) ? 'bg-indigo-600' : 'bg-white/5 border border-white/10'" 
                         class="p-4 text-[13px] rounded-2xl break-words max-w-[85%]" 
                         x-text="msg.message || msg.text || ''">
                    </div>
                    
                    <!-- Время и дата отправки -->
                    <span class="text-[9px] text-gray-500 mt-1 px-1 select-none" 
                          x-text="formatDateTime(msg)"></span>
                </div>
            </template>
        </div>
        <div class="p-4 bg-[#0a0a0a] border-t border-white/5">
            <div class="flex gap-2 bg-black/40 p-2 rounded-2xl border border-white/5">
                <input type="text" x-model="friendChatInput" @input="sendTypingSignal()" @keyup.enter="sendFriendMsg()" placeholder="Message..." class="flex-1 bg-transparent border-none text-sm focus:ring-0 text-white">
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
                    <button @click="openFriendChat(h.id)" class="flex-1 bg-white/5 hover:bg-indigo-600 py-3 rounded-xl text-[9px] font-black uppercase">💬 {{ __('messenger.Message') }}</button>
                    <!-- КНОПКА + СКРЫВАЕТСЯ ЕСЛИ ЮЗЕР УЖЕ ДРУГ -->
                    <template x-if="!h.is_friend">
                        <button @click="window.axios.post('/chat/contact/add', {contactId: h.id}).then(() => { h.is_friend = true; window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Добавлен'}})) })" class="px-4 bg-white/5 rounded-xl text-lg">+</button>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- ЧЕРНЫЙ СПИСОК -->
    <div x-show="tab === 'blacklist'" class="flex-1 overflow-y-auto p-4 space-y-2">
        <template x-for="b in blockedList" :key="b.id">
            <div class="p-4 caspian-glass rounded-3xl flex items-center justify-between border-red-500/10 bg-red-500/5">
                <div class="flex flex-col">
                    <span class="text-xs font-black uppercase italic" x-text="b.name"></span>
                    <span class="text-[7px] text-gray-500 uppercase tracking-widest">{{ __('messenger.Protocol_Terminated') }}</span>
                </div>
                <button @click="unblock(b.id)" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-[8px] font-black uppercase hover:bg-indigo-500 transition-all shadow-lg">
                    {{ __('messenger.Unblock') }}
                </button>
            </div>
        </template>
    </div>
</div>