<!-- INCOMING CALL -->
<div x-show="incomingCall" class="fixed top-8 left-1/2 -translate-x-1/2 z-[600] w-full max-w-sm px-4" x-cloak x-transition>
    <div class="caspian-glass p-4 rounded-[2.5rem] shadow-2xl flex items-center justify-between border-brand-indigo/30">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-brand-indigo rounded-2xl flex items-center justify-center animate-pulse font-black shadow-lg" 
                 x-text="incomingCall?.fromName ? incomingCall.fromName[0] : '?'"></div>
            <div>
                <p class="text-[8px] font-black text-brand-indigo uppercase tracking-[0.3em]">{{ __('app.Incoming_Session') }}</p>
                <p class="text-sm font-black uppercase italic" x-text="incomingCall?.fromName"></p>
            </div>
        </div>
        <div class="flex gap-2">
            <button @click="rejectCall()" class="w-12 h-12 bg-white/5 hover:bg-red-600 rounded-full flex items-center justify-center transition-all">✕</button>
            <button @click="acceptCall()" class="w-12 h-12 bg-brand-indigo hover:scale-110 rounded-full flex items-center justify-center shadow-indigo-500/50 shadow-lg transition-all">📞</button>
        </div>
    </div>
</div>

<!-- LEVEL UP CELEBRATION -->
<div x-show="showLevelUp" x-transition.opacity.scale.origin.center class="fixed inset-0 z-[3000] flex items-center justify-center pointer-events-none" x-cloak>
    <div class="relative bg-[#0a0a0a]/90 backdrop-blur-3xl border-2 border-brand-indigo p-10 rounded-[3rem] shadow-[0_0_100px_rgba(99,102,241,0.5)] text-center">
        <div class="text-6xl mb-4">🏆</div>
        <h2 class="text-4xl font-black uppercase italic tracking-tighter text-white">{{ __('app.Level_UP') }}!</h2>
        <div class="mt-2 flex items-center justify-center gap-3">
            <span class="text-2xl font-black text-brand-indigo" x-text="'LVL ' + currentLevel"></span>
        </div>
    </div>
</div>