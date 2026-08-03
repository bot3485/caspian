<div x-show="deviceModalOpen" class="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-3xl" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 scale-95">
    <div class="bg-[#080808] rgb-led-border border-transparent w-full max-w-sm rounded-[3rem] p-10 shadow-[0_0_100px_rgba(0,0,0,1)]" @click.away="deviceModalOpen = false">
        
        <div class="flex items-center gap-4 mb-10">
            <div class="w-12 h-12 bg-brand-indigo/10 rounded-2xl flex items-center justify-center text-xl">⚙️</div>
            <h3 class="text-xl font-black uppercase italic tracking-tighter text-white">{{ __('chatroulette.Hardware') }}</h3>
        </div>

        <div class="space-y-8">
            <div class="space-y-3">
                <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">{{ __('chatroulette.Video_Interface') }}</label>
                <select x-model="selectedVideoId" class="w-full bg-white/5 rgb-led-border border-transparent rounded-2xl py-5 px-6 text-xs font-bold text-white focus:ring-2 focus:ring-brand-indigo outline-none transition-all appearance-none cursor-pointer">
                    <template x-for="dev in videoDevices" :key="dev.deviceId">
                        <option :value="dev.deviceId" x-text="dev.label || 'Camera ' + (videoDevices.indexOf(dev)+1)"></option>
                    </template>
                </select>
            </div>
            <div class="space-y-3">
                <label class="text-[9px] font-black uppercase text-gray-500 tracking-[0.3em] ml-2">{{ __('chatroulette.Audio_Interface') }}</label>
                <select x-model="selectedAudioId" class="w-full bg-white/5 rgb-led-border border-transparent rounded-2xl py-5 px-6 text-xs font-bold text-white focus:ring-2 focus:ring-brand-indigo outline-none transition-all appearance-none cursor-pointer">
                    <template x-for="dev in audioDevices" :key="dev.deviceId">
                        <option :value="dev.deviceId" x-text="dev.label || 'Microphone ' + (audioDevices.indexOf(dev)+1)"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-12">
            <button @click="deviceModalOpen = false" class="py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:text-white transition-all hover:bg-white/5">{{ __('chatroulette.Cancel') }}</button>
            <button @click="changeVideoDevice()" class="bg-brand-indigo py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-white shadow-xl shadow-brand-indigo/30 hover:scale-105 active:scale-95 transition-all">{{ __('chatroulette.Apply_Changes') }}</button>
        </div>
    </div>
</div>