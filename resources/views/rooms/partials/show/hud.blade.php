<div class="absolute bottom-6 left-0 right-0 px-4 z-[120] flex justify-center pointer-events-none transition-all duration-500 ease-in-out"
     :class="isMaximized ? 'opacity-0 translate-y-6' : 'opacity-100 translate-y-0'">
    <div class="pointer-events-auto flex items-center bg-[#0a0a0a]/95 backdrop-blur-3xl border border-white/10 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.8)] overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]" 
         :class="controlsOpen ? 'max-w-[500px] p-1.5' : 'max-w-[60px] p-1.5'">
        
        <button @click="controlsOpen = !controlsOpen" class="w-12 h-12 relative rounded-full bg-white/[0.03] hover:bg-white/15 flex items-center justify-center transition-all duration-500 z-10 shrink-0">
            <svg class="w-5 h-5 text-white transition-transform duration-500" :class="controlsOpen ? 'rotate-180' : 'rotate-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        
        <div class="flex items-center transition-all duration-500" :class="controlsOpen ? 'opacity-100 translate-x-0 w-auto pl-1.5 pr-1.5' : 'opacity-0 -translate-x-10 w-0 px-0'">
            <div class="flex items-center gap-1.5 w-[max-content]">
                <button @click="toggleMic()" :class="micEnabled ? 'bg-white/[0.03] text-white hover:bg-white/10' : 'bg-red-500/20 text-red-400 border border-red-500/30'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all"><span x-text="micEnabled ? '🎤' : '🔇'"></span></button>
                <button @click="toggleCam()" :class="camEnabled ? 'bg-white/[0.03] text-white hover:bg-white/10' : 'bg-red-500/20 text-red-400 border border-red-500/30'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all"><span x-text="camEnabled ? '📷' : '🚫'"></span></button>
                <button @click="toggleScreenShare()" :class="isScreenSharing ? 'bg-brand-indigo/30 text-white border-brand-indigo/50' : 'bg-white/[0.03] text-white hover:bg-white/10'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all"><span>📺</span></button>
                <button @click="settingsOpen = true" class="w-12 h-12 rounded-full bg-white/[0.03] text-white hover:bg-white/10 flex items-center justify-center text-lg transition-all"><span>⚙️</span></button>
                <div class="w-px h-8 bg-white/10 mx-1 rounded-full shrink-0"></div>
                <a href="{{ route('rooms.index') }}" class="bg-red-600/10 border border-red-500/20 hover:bg-red-600 text-red-400 hover:text-white px-5 h-12 rounded-full flex items-center justify-center font-black text-[9px] uppercase tracking-[0.2em] transition-all">{{ __('rooms.Exit_Room') }}</a>
            </div>
        </div>
    </div>
</div>