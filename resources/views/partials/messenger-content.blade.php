<!-- Навигация по вкладкам (Premium Tabs) -->
<div class="flex border-b border-white/[0.05] bg-[#020202]/80 backdrop-blur-2xl px-2 shrink-0 z-20 sticky top-0">
    <!-- Кнопка GLOBAL -->
    <button @click="if(!isFriend) { tab = 'chat'; activeFriend = null; }" 
            :disabled="isFriend && state === 'connected'"
            class="flex-1 py-4 text-[9px] font-black uppercase tracking-[0.15em] relative transition-all duration-300 group"
            :class="[
                tab === 'chat' ? 'text-white' : 'text-gray-500 hover:text-gray-300',
                (isFriend && state === 'connected') ? 'opacity-30 cursor-not-allowed' : ''
            ]">
        <span>{{ __('messenger.Roulette_Chat') }}</span>
        <!-- Активная линия -->
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] bg-brand-indigo rounded-t-full transition-all duration-300 shadow-[0_-2px_10px_rgba(99,102,241,0.5)]"
             :class="tab === 'chat' ? 'w-1/2 opacity-100' : 'w-0 opacity-0'"></div>
             
        <template x-if="isFriend && state === 'connected'">
            <svg class="absolute top-2.5 right-2.5 w-3 h-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </template>
    </button>

    <button @click="tab = 'friends'" 
            class="flex-1 py-4 text-[9px] font-black uppercase tracking-[0.15em] flex items-center justify-center gap-1.5 relative transition-all duration-300"
            :class="tab === 'friends' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
        <span>{{ __('messenger.Contacts') }}</span>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] bg-brand-indigo rounded-t-full transition-all duration-300 shadow-[0_-2px_10px_rgba(99,102,241,0.5)]"
             :class="tab === 'friends' ? 'w-1/2 opacity-100' : 'w-0 opacity-0'"></div>
             
        <template x-if="friendsList.some(f => f.has_new_message)">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_8px_#22c55e] animate-pulse"></span>
        </template>
    </button>
    
    <button @click="tab = 'history'" 
            class="flex-1 py-4 text-[9px] font-black uppercase tracking-[0.15em] relative transition-all duration-300"
            :class="tab === 'history' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
        {{ __('messenger.Logs') }}
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] bg-brand-indigo rounded-t-full transition-all duration-300 shadow-[0_-2px_10px_rgba(99,102,241,0.5)]"
             :class="tab === 'history' ? 'w-1/2 opacity-100' : 'w-0 opacity-0'"></div>
    </button>
    
    <button @click="tab = 'blacklist'" 
            class="flex-1 py-4 text-[9px] font-black uppercase tracking-[0.15em] relative transition-all duration-300"
            :class="tab === 'blacklist' ? 'text-red-400' : 'text-gray-500 hover:text-red-400/70'">
        {{ __('messenger.Blocked') }}
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] bg-red-500 rounded-t-full transition-all duration-300 shadow-[0_-2px_10px_rgba(239,68,68,0.5)]"
             :class="tab === 'blacklist' ? 'w-1/2 opacity-100' : 'w-0 opacity-0'"></div>
    </button>
</div>

<div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">
    
    <!-- ЧАТ РУЛЕТКИ -->
    <div x-show="tab === 'chat' && !activeFriend && callContext !== 'personal'" class="flex-1 flex flex-col overflow-hidden relative">
        <!-- Если соединены и это НЕ друг -->
        <template x-if="state === 'connected' && !isFriend"> 
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 custom-scrollbar" x-ref="chatBox">
                    <template x-for="msg in messages" :key="msg.timestamp">
                        <div :class="msg.isMe ? 'items-end' : 'items-start'" class="flex flex-col group">
                            <div :class="msg.isMe 
                                    ? 'bg-gradient-to-tr from-brand-indigo to-indigo-500 text-white rounded-[1.5rem] rounded-br-sm shadow-[0_4px_15px_rgba(99,102,241,0.2)]' 
                                    : 'bg-white/[0.04] border border-white/[0.05] text-gray-200 rounded-[1.5rem] rounded-bl-sm backdrop-blur-md'" 
                                 class="px-5 py-3.5 text-[13px] font-medium max-w-[85%] break-words" x-text="msg.text"></div>
                        </div>
                    </template>
                </div>
                
                <!-- Floating Input Area (Исправлено для мобильных) -->
                <div class="px-4 pt-2 pb-8 sm:pb-4 bg-gradient-to-t from-[#050505] via-[#050505] to-transparent z-10" style="padding-bottom: max(env(safe-area-inset-bottom, 1rem), 1.5rem);">
                    <div class="flex items-center gap-2 bg-white/[0.03] backdrop-blur-2xl p-1.5 rounded-[1.5rem] border border-white/[0.08] shadow-[0_10px_40px_rgba(0,0,0,0.5)] focus-within:border-brand-indigo/50 focus-within:bg-white/[0.05] transition-all duration-300">
                        <input type="text" x-model="chatInput" @input="sendTypingSignal()" @keyup.enter="sendMsg()" 
                               placeholder="Message..." 
                               class="flex-1 bg-transparent border-none text-sm px-4 py-2 focus:ring-0 text-white placeholder-gray-600">
                        <button @click="sendMsg()" 
                                class="bg-brand-indigo text-white w-10 h-10 rounded-[1rem] flex items-center justify-center hover:scale-105 active:scale-95 transition-all shadow-md">
                            <svg class="w-4 h-4 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Заглушка: Уже друзья -->
        <template x-if="state === 'connected' && isFriend">
            <div class="flex-1 flex flex-col items-center justify-center opacity-70 text-center p-6">
                <div class="w-16 h-16 bg-brand-indigo/10 rounded-full flex items-center justify-center mb-6 border border-brand-indigo/20 shadow-[0_0_30px_rgba(99,102,241,0.2)]">
                    <svg class="w-8 h-8 text-brand-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                </div>
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-white mb-2">{{ __('messenger.Already_Friends') }}</div>
                <p class="text-[11px] text-gray-500 max-w-[250px]">{{ __('messenger.Global_Chat_Disabled') }}</p>
                <button @click="openFriendChat(partnerId)" class="mt-8 px-8 py-3.5 bg-white/5 border border-white/10 hover:bg-brand-indigo hover:border-brand-indigo text-white rounded-full text-[9px] font-black uppercase tracking-[0.2em] transition-all duration-300">
                    {{ __('messenger.Personal_Chat') }}
                </button>
            </div>
        </template>

        <!-- Заглушка: Ожидание -->
        <template x-if="state !== 'connected'">
            <div class="flex-1 flex flex-col items-center justify-center opacity-40 text-center">
                <svg class="w-12 h-12 text-gray-500 mb-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-400">{{ __('messenger.Chat_After_Connection') }}</div>
            </div>
        </template>
    </div>

    <!-- Блокировка чата рулетки -->
    <div x-show="tab === 'chat' && callContext === 'personal'" class="flex-1 flex flex-col items-center justify-center opacity-50 text-center p-6">
        <svg class="w-12 h-12 text-indigo-400 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        <div class="text-[9px] font-black uppercase tracking-[0.2em] text-indigo-400">{{ __('messenger.Roulette_Chat_Disabled') }}</div>
    </div>

<!-- ДРУЗЬЯ (СПИСОК КОНТАКТОВ) -->
<div x-show="tab === 'friends' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
    <template x-for="(f, index) in friendsList" :key="f.id ? 'friend_' + f.id : 'f_' + index">
        <div class="p-3.5 border rounded-[1.5rem] flex items-center justify-between transition-all duration-300 group bg-white/[0.01] border-white/[0.03] hover:bg-white/[0.03]"
             :class="f && f.has_new_message ? 'border-brand-indigo/30 bg-white/[0.05]' : ''">
            
            <!-- Клик по инфо открывает чат -->
            <div @click="f && f.id ? openFriendChat(f.id) : null" class="flex items-center gap-4 cursor-pointer flex-1">
                <div class="relative">
                    <div class="w-11 h-11 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 border border-white/5 rounded-[1.2rem] flex items-center justify-center text-white font-black" 
                         x-text="f && f.name ? f.name.charAt(0).toUpperCase() : '?'"></div>
                    <template x-if="f && f.is_online">
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-[#050505] rounded-full shadow-[0_0_8px_#22c55e]"></span>
                    </template>
                </div>
                <div>
                    <div class="text-xs font-bold text-gray-200 group-hover:text-white" x-text="f ? f.name : 'Unknown'"></div>
                    <div class="text-[8px] font-black uppercase mt-1 tracking-widest text-gray-500" x-text="f ? (f.is_online ? 'online' : f.last_seen_human) : ''"></div>
                </div>
            </div>

            <!-- КНОПКА ВЫЗОВА (теперь вызывается здесь!) -->
            <div class="flex items-center gap-2">
                <button @click.stop="callFriend(f)" 
                        x-show="f.is_online"
                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-brand-indigo/10 text-brand-indigo border border-brand-indigo/20 hover:bg-brand-indigo hover:text-white transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                </button>
                <div class="w-8 h-8 flex items-center justify-center text-gray-600 group-hover:text-white" @click="openFriendChat(f.id)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </div>
        </div>
    </template>
</div>

    <!-- ЧАТ С ДРУГОМ -->
    <div x-show="activeFriend" class="flex-1 flex flex-col overflow-hidden relative">
        <!-- Header Чата -->
        <div class="px-4 py-3 bg-[#020202]/80 backdrop-blur-xl border-b border-white/[0.05] flex items-center justify-between z-10">
            <button @click="activeFriend = null" class="w-9 h-9 flex items-center justify-center bg-white/[0.03] hover:bg-white/10 rounded-[1rem] border border-white/5 transition-colors group">
                <svg class="w-4 h-4 text-gray-400 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <div class="flex flex-col items-center">
                <div class="text-[11px] font-black uppercase tracking-widest text-white" x-text="activeFriend?.name"></div>
                <div class="text-[8px] uppercase font-bold tracking-widest mt-0.5" :class="activeFriend?.is_online ? 'text-green-500' : 'text-gray-500'" x-text="activeFriend?.is_online ? 'Online' : 'Offline'"></div>
            </div>
            <button @click="callFriend(activeFriend)" 
                    :disabled="!activeFriend?.is_online" 
                    class="w-9 h-9 flex items-center justify-center rounded-[1rem] transition-all"
                    :class="activeFriend?.is_online ? 'bg-brand-indigo/20 text-brand-indigo border border-brand-indigo/30 hover:bg-brand-indigo hover:text-white' : 'bg-white/5 text-gray-600 opacity-50'">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
            </button>
        </div>
        
        <!-- Сообщения -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 custom-scrollbar" x-ref="friendChatBox">
            <template x-for="(msg, index) in friendMessages" :key="msg.id ? 'msg_' + msg.id : 'temp_' + index">
                <div :class="Number(msg.sender_id) === Number({{ auth()->id() }}) ? 'items-end' : 'items-start'" class="flex flex-col group">
                    <div :class="Number(msg.sender_id) === Number({{ auth()->id() }}) 
                            ? 'bg-gradient-to-tr from-brand-indigo to-indigo-500 text-white rounded-[1.5rem] rounded-br-sm shadow-[0_4px_15px_rgba(99,102,241,0.2)]' 
                            : 'bg-white/[0.04] border border-white/[0.05] text-gray-200 rounded-[1.5rem] rounded-bl-sm backdrop-blur-md'" 
                         class="px-5 py-3.5 text-[13px] break-words max-w-[85%]" 
                         x-text="msg.message || msg.text || ''">
                    </div>
                    <span class="text-[8px] font-bold text-gray-600 mt-1.5 px-2 select-none tracking-widest uppercase opacity-0 group-hover:opacity-100 transition-opacity" 
                          x-text="formatDateTime(msg)"></span>
                </div>
            </template>
        </div>
        
        <!-- Floating Input Area (С подъемом для мобилок) -->
        <div class="px-4 pt-2 pb-8 sm:pb-4 bg-gradient-to-t from-[#050505] via-[#050505] to-transparent z-10" style="padding-bottom: max(env(safe-area-inset-bottom, 1rem), 1.5rem);">
            <div class="flex items-center gap-2 bg-white/[0.03] backdrop-blur-2xl p-1.5 rounded-[1.5rem] border border-white/[0.08] shadow-[0_10px_40px_rgba(0,0,0,0.5)] focus-within:border-brand-indigo/50 focus-within:bg-white/[0.05] transition-all duration-300">
                <input type="text" x-model="friendChatInput" @input="sendTypingSignal()" @keyup.enter="sendFriendMsg()" 
                       placeholder="Message..." 
                       class="flex-1 bg-transparent border-none text-sm px-4 py-2 focus:ring-0 text-white placeholder-gray-600">
                <button @click="sendFriendMsg()" 
                        class="bg-brand-indigo text-white w-10 h-10 rounded-[1rem] flex items-center justify-center hover:scale-105 active:scale-95 transition-all shadow-md">
                    <svg class="w-4 h-4 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- ИСТОРИЯ (Logs) -->
    <div x-show="tab === 'history' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
        <template x-for="h in historyList" :key="h.id">
            <div class="p-5 bg-white/[0.02] border border-white/[0.05] rounded-[1.5rem] space-y-4 hover:bg-white/[0.03] transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-gray-800 to-[#111] border border-white/5 rounded-[1.2rem] flex items-center justify-center text-white font-black shadow-inner" x-text="h.name[0]"></div>
                        <div>
                            <div class="text-xs font-bold text-gray-200" x-text="h.name"></div>
                            <div class="text-[8px] font-black text-gray-600 uppercase tracking-widest mt-1" x-text="h.last_met_diff"></div>
                        </div>
                    </div>
                    <button @click="window.axios.post('/chat/block', {userId: h.id}).then(() => loadHistory())" class="w-9 h-9 flex items-center justify-center rounded-[1rem] bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500 hover:text-white transition-all group">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </button>
                </div>
                <div class="flex gap-2">
                    <button @click="openFriendChat(h.id)" class="flex-1 bg-white/[0.04] border border-white/5 hover:bg-brand-indigo hover:border-brand-indigo text-white py-3 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        {{ __('messenger.Message') }}
                    </button>
                    <!-- КНОПКА + -->
                    <template x-if="!h.is_friend">
                        <button @click="window.axios.post('/chat/contact/add', {contactId: h.id}).then(() => { h.is_friend = true; window.dispatchEvent(new CustomEvent('toast', {detail: {msg: 'Added'}})) })" 
                                class="w-12 bg-white/[0.04] border border-white/5 rounded-xl text-gray-400 hover:bg-white/10 hover:text-white transition-all flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- ЧЕРНЫЙ СПИСОК -->
    <div x-show="tab === 'blacklist'" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
        <template x-for="b in blockedList" :key="b.id">
            <div class="p-4 bg-red-500/[0.03] border border-red-500/10 rounded-[1.5rem] flex items-center justify-between backdrop-blur-md">
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-gray-300" x-text="b.name"></span>
                    <span class="text-[7px] font-black text-red-400/70 uppercase tracking-[0.2em] mt-1">{{ __('messenger.Protocol_Terminated') }}</span>
                </div>
                <button @click="unblock(b.id)" 
                        class="px-4 py-2 bg-white/5 border border-white/10 text-gray-300 rounded-xl text-[8px] font-black uppercase tracking-[0.1em] hover:bg-white/10 hover:text-white transition-all">
                    {{ __('messenger.Unblock') }}
                </button>
            </div>
        </template>
    </div>
</div>