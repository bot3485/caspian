<div x-show="settingsOpen" style="display: none;" class="absolute inset-0 z-[200] flex items-center justify-center px-4 bg-black/60 backdrop-blur-md transition-opacity"
     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
    <div @click.away="settingsOpen = false" class="w-full max-w-sm bg-[#0a0a0a] border border-white/10 rounded-[2rem] p-6 shadow-[0_25px_50px_rgba(0,0,0,0.8)] transform transition-all">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-sm font-black uppercase tracking-widest text-white">Hardware</h3>
            <button @click="settingsOpen = false" class="text-gray-400 hover:text-white transition-colors">✕</button>
        </div>
        <div class="space-y-5">
            <div>
                <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Microphone</label>
                <select x-model="selectedAudio" @change="applyDeviceChanges()" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none cursor-pointer">
                    <template x-for="device in audioDevices" :key="device.deviceId">
                        <option :value="device.deviceId" x-text="device.label" class="bg-[#0a0a0a]"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Camera</label>
                <select x-model="selectedVideo" @change="applyDeviceChanges()" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none cursor-pointer">
                    <template x-for="device in videoDevices" :key="device.deviceId">
                        <option :value="device.deviceId" x-text="device.label" class="bg-[#0a0a0a]"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>
</div>