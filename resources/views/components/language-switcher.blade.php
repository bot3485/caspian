<div class="relative" x-data="{ langOpen: false }">
    <button @click="langOpen = !langOpen" 
            class="flex items-center gap-2 px-3 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all text-[10px] font-black uppercase tracking-widest">
        <span x-text="'{{ strtoupper(App::getLocale()) }}'"></span>
    </button>
    
    <div x-show="langOpen" 
         @click.away="langOpen = false"
         x-cloak
         class="absolute right-0 mt-2 w-32 bg-[#0a0a0a]/90 backdrop-blur-2xl border border-white/10 rounded-2xl p-2 z-[9999]">
        <button @click="changeLanguage('en')" class="w-full text-left px-3 py-2 text-[10px] uppercase text-gray-400 hover:text-white">🇺🇸 EN</button>
        <button @click="changeLanguage('ru')" class="w-full text-left px-3 py-2 text-[10px] uppercase text-gray-400 hover:text-white">🇷🇺 RU</button>
        <button @click="changeLanguage('tr')" class="w-full text-left px-3 py-2 text-[10px] uppercase text-gray-400 hover:text-white">🇹🇷 TR</button>
    </div>
</div>