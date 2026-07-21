<div class="md:col-span-6 bg-[#050505]/40 backdrop-blur-xl border border-white/[0.04] rounded-[2.5rem] p-8 flex flex-col justify-center items-center text-center shadow-2xl min-h-[280px]">
    <div class="w-16 h-16 bg-brand-indigo/10 rounded-2xl flex items-center justify-center text-3xl border border-brand-indigo/10 mb-4">🌍</div>
    <div class="text-4xl sm:text-5xl font-black tracking-tighter bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
        {{ number_format(\App\Models\User::count()) }}
    </div>
    <h4 class="text-[9px] font-black uppercase text-brand-indigo tracking-[0.35em] mt-3">{{ __('dashboard.Registered_Citizens') }}</h4>
    <p class="text-[8.5px] text-gray-500 font-bold uppercase tracking-wider mt-2 max-w-[240px]">{{ __('dashboard.Registered_Citizens_Desc') }}</p>
</div>