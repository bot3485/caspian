<div x-show="tab === 'chat' && !activeFriend && callContext !== 'personal'" class="flex-1 flex flex-col overflow-hidden relative">
    <template x-if="state === 'connected' && !isFriend"> 
        <div class="flex-1 flex flex-col overflow-hidden">
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 custom-scrollbar" x-ref="chatBox">
                <template x-for="rouletteMsg in messages" :key="rouletteMsg.timestamp">
                    <div :class="rouletteMsg.isMe ? 'items-end' : 'items-start'" class="flex flex-col">
                        <div :class="rouletteMsg.isMe 
                                ? 'bg-brand-indigo text-white rounded-[1.5rem] rounded-br-sm shadow-lg' 
                                : 'bg-white/[0.04] border border-white/[0.05] text-gray-200 rounded-[1.5rem] rounded-bl-sm'" 
                             class="px-5 py-3.5 text-[13px] font-medium max-w-[85%] break-words" x-text="rouletteMsg.text"></div>
                    </div>
                </template>
            </div>
            
            <div class="p-4 bg-gradient-to-t from-[#050505] via-[#050505] to-transparent">
                <div class="flex items-center gap-2 bg-white/[0.03] p-1.5 rounded-[1.5rem] border border-white/[0.08] focus-within:border-brand-indigo/50 transition-all">
                    <input type="text" x-model="chatInput" @keyup.enter="sendMsg()" placeholder="Message..." class="flex-1 bg-transparent border-none text-sm px-4 focus:ring-0 text-white placeholder-gray-600">
                    <button @click="sendMsg()" class="bg-brand-indigo text-white w-10 h-10 rounded-[1rem] flex items-center justify-center hover:scale-105 transition-all">
                        <svg class="w-4 h-4 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="state !== 'connected'">
        <div class="flex-1 flex flex-col items-center justify-center opacity-40 text-center p-8">
            <div class="text-[9px] font-black uppercase tracking-[0.4em] text-gray-500">{{ __('messenger.Chat_After_Connection') }}</div>
        </div>
    </template>
</div>