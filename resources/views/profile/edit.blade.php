<x-app-layout>
    <div class="py-12 bg-[#050505] min-h-screen text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- HEADER -->
            <div class="mb-10">
                <h1 class="text-4xl font-black tracking-tighter">Настройки аккаунта</h1>
                <p class="text-gray-500 font-medium mt-1 uppercase text-[10px] tracking-[0.3em]">Управление вашей личностью в Caspian</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- ЛЕВАЯ КОЛОНКА: ВИЗУАЛЬНЫЙ ПРОФИЛЬ -->
                <div class="md:col-span-1 space-y-6">
                    <!-- Карточка Аватара и Ранга -->
                    <div class="bg-white/[0.03] border border-white/5 rounded-[2.5rem] p-8 text-center relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="w-32 h-32 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-[2.5rem] flex items-center justify-center text-5xl font-black mx-auto mb-6 shadow-2xl shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <h2 class="text-2xl font-black tracking-tight">{{ Auth::user()->name }}</h2>
                            <p class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.2em] mt-2">
                                {{ Auth::user()->level > 5 ? 'Veteran' : 'Explorer' }} Level {{ Auth::user()->level }}
                            </p>

                    <!-- Прогресс-бар опыта -->
                    <div class="mt-8 space-y-2">
                        <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-gray-500">
                            <span>Опыт (XP)</span>
                            <span>{{ Auth::user()->current_level_xp }} / {{ Auth::user()->next_level_xp }}</span>
                        </div>
                        <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full shadow-[0_0_10px_rgba(79,70,229,0.5)]" style="width: {{ Auth::user()->xp_progress }}%"></div>
                        </div>
                    </div>

                    <!-- Карточка статистики -->
                    <div class="bg-white/[0.03] border border-white/5 rounded-[2.5rem] p-8">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-6">Ваша активность</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/5 p-4 rounded-2xl border border-white/5 text-center">
                                <div class="text-xl font-black">12</div>
                                <div class="text-[8px] font-bold text-gray-500 uppercase mt-1">Друзей</div>
                            </div>
                            <div class="bg-white/5 p-4 rounded-2xl border border-white/5 text-center">
                                <div class="text-xl font-black">{{ Auth::user()->karma ?? 100 }}</div>
                                <div class="text-[8px] font-bold text-gray-500 uppercase mt-1">Карма</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА: ФОРМЫ НАСТРОЕК -->
                <div class="md:col-span-2 space-y-6">
                    
                    <!-- Основная информация -->
                    <div class="bg-white/[0.03] border border-white/5 rounded-[2.5rem] p-8 md:p-12 shadow-2xl">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    <!-- Смена пароля -->
                    <div class="bg-white/[0.03] border border-white/5 rounded-[2.5rem] p-8 md:p-12 shadow-2xl">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <!-- Удаление (Опасная зона) -->
                    <div class="bg-red-600/5 border border-red-600/10 rounded-[2.5rem] p-8 md:p-12">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>