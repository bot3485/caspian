<div x-show="filterModalOpen" class="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-3xl" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 scale-95">
    <div class="bg-[#080808] rgb-led-border border-transparent w-full max-w-sm rounded-[3rem] p-10 shadow-[0_0_100px_rgba(99,102,241,0.1)]" @click.away="filterModalOpen = false">
        
        <div class="flex items-center gap-4 mb-10">
            <div class="w-12 h-12 bg-brand-indigo/10 rounded-2xl flex items-center justify-center text-xl border border-brand-indigo/20">🎯</div>
            <h3 class="text-xl font-black uppercase italic tracking-tighter text-white">{{ __('chatroulette.Matching') }}</h3>
        </div>

        <div class="space-y-10">
            <div class="space-y-4">
                <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">{{ __('chatroulette.Looking_For') }}</label>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="g in ['male', 'female', 'all']">
                        <button @click="targetGender = g" :class="targetGender === g ? 'bg-brand-indigo text-white border-brand-indigo shadow-[0_0_15px_rgba(99,102,241,0.4)]' : 'bg-white/5 text-gray-500 border-white/5 hover:bg-white/10 hover:text-white'" class="py-3.5 rounded-xl border font-black text-[9px] uppercase tracking-widest transition-all" x-text="t(g)"></button>
                    </template>
                </div>
            </div>

            <div class="space-y-6">
                <div class="flex justify-between items-center ml-2">
                    <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em]">{{ __('chatroulette.Partner_Age') }}</label>
                    <div class="flex items-center gap-2 bg-brand-indigo/10 px-3 py-1.5 rounded-xl border border-brand-indigo/20">
                        <span class="text-[11px] font-black text-brand-indigo" x-text="targetAgeMin"></span>
                        <span class="text-gray-500">—</span>
                        <span class="text-[11px] font-black text-brand-indigo" x-text="targetAgeMax"></span>
                    </div>
                </div>
                <div class="px-2 space-y-8">
                    <div class="relative">
                        <input type="range" min="18" max="99" x-model="targetAgeMin" class="w-full h-1.5 bg-white/10 rounded-lg appearance-none cursor-pointer accent-brand-indigo">
                        <p class="text-[7px] font-black uppercase mt-3 text-gray-600 tracking-widest">{{ __('chatroulette.Minimum_Age') }}</p>
                    </div>
                    <div class="relative">
                        <input type="range" min="18" max="99" x-model="targetAgeMax" class="w-full h-1.5 bg-white/10 rounded-lg appearance-none cursor-pointer accent-brand-indigo">
                        <p class="text-[7px] font-black uppercase mt-3 text-gray-600 tracking-widest">{{ __('chatroulette.Maximum_Age') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-12">
            <button @click="filterModalOpen = false" class="py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:text-white hover:bg-white/5 transition-all">{{ __('chatroulette.Cancel') }}</button>
            <button @click="applyFilters()" class="bg-brand-indigo py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-white shadow-xl shadow-brand-indigo/20 hover:scale-105 active:scale-95 transition-all">{{ __('chatroulette.Apply') }} 🎯</button>
        </div>
    </div>
</div>