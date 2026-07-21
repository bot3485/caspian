<div x-show="uiShowPartnerCard"
     x-cloak 
     class="fixed inset-0 z-[3000] pointer-events-none flex items-start justify-center md:items-start md:justify-start"
     style="transform: translateZ(0);">
     
     <!-- Невидимый слой для закрытия по клику вне карточки (Только для мобилок) -->
     <div class="absolute inset-0 bg-black/40 backdrop-blur-sm pointer-events-auto md:hidden" 
          x-show="uiShowPartnerCard" 
          x-transition.opacity 
          @click="uiShowPartnerCard = false"></div>

     <!-- Сама карточка -->
     <div @click.stop=""
          x-transition:enter="transition cubic-bezier(0.34, 1.56, 0.64, 1) duration-500"
          x-transition:enter-start="opacity-0 translate-y-8 md:translate-y-0 md:-translate-x-8 blur-xl scale-95"
          x-transition:enter-end="opacity-100 translate-y-0 md:translate-x-0 blur-0 scale-100"
          x-transition:leave="transition ease-in duration-300"
          x-transition:leave-start="opacity-100 translate-y-0 scale-100"
          x-transition:leave-end="opacity-0 translate-y-8 scale-95"
          class="pointer-events-auto relative md:absolute mt-36 md:mt-0 md:top-24 left-4 right-4 md:left-24 md:right-auto 
                 md:w-[320px] bg-[#0a0a0a]/95 backdrop-blur-3xl p-5 md:p-6 rounded-[2rem] md:rounded-[2.5rem] 
                 border border-white/10 shadow-[0_30px_100px_rgba(0,0,0,0.9)] overflow-hidden">
         
         <div class="relative z-10 flex flex-col gap-5">
             
             <div class="absolute -inset-1 rounded-[1.8rem] blur-[30px] opacity-40 pointer-events-none transition-colors duration-500 -z-10"
         :style="{ backgroundColor: partnerData?.gender === 'female' ? '#db2777' : '#2563eb' }"></div>
             
             <!-- HEADER -->
             <div class="flex justify-between items-start">
                 <div class="flex flex-col gap-1">
                     <div class="flex items-center flex-wrap gap-2">
                         <h3 class="text-xl md:text-2xl font-black uppercase italic tracking-tighter text-white" x-text="partnerData?.name"></h3>
                         
                         <template x-if="partnerData?.ban_count > 0">
                             <span class="px-2 py-0.5 rounded bg-red-600 text-white text-[7px] font-black uppercase tracking-tighter animate-bounce">
                                 {{ __('chatroulette.Recidivist') }}
                             </span>
                         </template>
                         
                         <template x-if="partnerData?.vpn">
                             <span class="px-2 py-0.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-500 text-[8px] font-black uppercase tracking-widest flex items-center gap-1 shadow-[0_0_10px_rgba(245,158,11,0.2)]">
                                 <span class="animate-pulse">🛡️</span> Masked IP
                             </span>
                         </template>
                         
                         <span class="px-2 py-0.5 rounded-lg text-[8px] md:text-[9px] font-black uppercase tracking-widest border"
                               :class="partnerData?.gender === 'female' ? 'bg-pink-500/10 text-pink-500 border-pink-500/20' : 'bg-blue-500/10 text-blue-500 border-blue-500/20'"
                               x-text="partnerData?.gender === 'female' ? '{{ __('chatroulette.Female') }}' : '{{ __('chatroulette.Male') }}'"></span>
                     </div>
                     
                     <div class="flex items-center gap-3">
                         <span class="text-[8px] md:text-[9px] font-black uppercase tracking-[0.3em] text-gray-500" x-text="partnerData?.rank_name"></span>
                         <div class="h-1 w-1 rounded-full bg-white/20"></div>
                         <span class="text-[9px] md:text-[10px] font-bold text-gray-300" x-text="partnerData?.age + ' {{ __('chatroulette.Years_Old') }}'"></span>
                     </div>
                     
                     <div class="flex items-center gap-1.5 mt-0.5 opacity-60">
                         <span class="text-[7px] text-brand-indigo">📍</span>
                         <span class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-400 italic" 
                               x-text="countryNames[partnerData?.country_code]?.replace(/.[^\s]*\s/, '') || '{{ __('chatroulette.Unknown_Location') }}'">
                         </span>
                     </div>
                 </div>

                 <button @click="uiShowPartnerCard = false" class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                     <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                 </button>
             </div>

             <!-- TRUST & STATS -->
             <div class="grid grid-cols-3 gap-2">
                 <div class="bg-white/[0.03] border border-white/[0.06] py-3 px-1 rounded-2xl flex flex-col items-center gap-1">
                     <span class="text-[6px] md:text-[7px] font-black uppercase text-gray-500">{{ __('chatroulette.Karma') }}</span>
                     <span class="text-xs font-black text-amber-500" x-text="partnerData?.karma"></span>
                 </div>

                 <div class="bg-white/[0.03] border border-white/[0.06] py-3 px-1 rounded-2xl flex flex-col items-center gap-1">
                     <span class="text-[6px] md:text-[7px] font-black uppercase text-gray-500">{{ __('chatroulette.Level') }}</span>
                     <span class="text-xs font-black text-white" x-text="partnerData?.level"></span>
                 </div>

                 <div class="bg-white/[0.03] border border-white/[0.06] py-3 px-1 rounded-2xl flex flex-col items-center gap-1">
                     <span class="text-[6px] md:text-[7px] font-black uppercase text-gray-500">{{ __('chatroulette.Reports') }}</span>
                     <div class="flex items-center gap-1">
                         <span class="text-[10px]">🚩</span>
                         <span class="text-xs font-black text-red-500" x-text="partnerData?.blocked_count || 0"></span>
                     </div>
                 </div>
             </div>

             <template x-if="partnerData?.ban_count > 0">
                 <div class="mt-4 px-4 py-2 bg-red-950/30 border border-red-500/20 rounded-xl flex items-center justify-between">
                     <div class="flex items-center gap-2">
                         <span class="text-xs">⚠️</span>
                         <span class="text-[8px] font-black uppercase tracking-widest text-red-400">{{ __('chatroulette.Past_Violations') }}</span>
                     </div>
                     <span class="text-[10px] font-black text-red-500" x-text="partnerData.ban_count + ' BANS'"></span>
                 </div>
             </template>

             <!-- PRESTIGE FOOTER -->
             <template x-if="partnerData?.badge">
                 <div class="pt-4 border-t border-white/5 flex items-center justify-between">
                     <span class="text-[7px] md:text-[8px] font-black uppercase tracking-[0.25em] text-gray-600">{{ __('chatroulette.Prestige_Status') }}</span>
                     <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/[0.02] border border-white/10 shadow-inner"
                          :style="{ borderColor: partnerData.badge.color + '30' }">
                         <span class="text-sm" x-text="partnerData.badge.icon"></span>
                         <span class="text-[9px] font-black uppercase tracking-widest" 
                               :style="{ color: partnerData.badge.color, 'text-shadow': '0 0 10px ' + partnerData.badge.color + '40' }" 
                               x-text="partnerData.badge.name"></span>
                     </div>
                 </div>
             </template>

         </div>
     </div>
</div>