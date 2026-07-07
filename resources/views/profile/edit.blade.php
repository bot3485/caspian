<x-app-layout>
    <div class="py-12 bg-[#050505] min-h-screen text-white font-sans">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- HEADER -->
            <div class="mb-10">
                <h1 class="text-4xl font-black tracking-tighter uppercase">Настройки аккаунта</h1>
                <p class="text-gray-500 font-medium mt-1 uppercase text-[10px] tracking-[0.3em]">Управление вашей личностью в Caspian</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- ЛЕВАЯ КОЛОНКА: ВИЗУАЛЬНЫЙ ПРОФИЛЬ -->
                <div class="md:col-span-1 space-y-6">
                    <!-- Карточка Аватара и Ранга -->
                    <div class="bg-white/[0.03] border border-white/5 rounded-[3rem] p-8 text-center relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="w-32 h-32 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-[2.5rem] flex items-center justify-center text-5xl font-black mx-auto mb-6 shadow-2xl shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <h2 class="text-2xl font-black tracking-tight text-white">{{ $user->name }}</h2>
                            <p class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.2em] mt-2">
                                {{ $user->level > 5 ? 'Veteran' : 'Explorer' }} Level {{ $user->level }}
                            </p>

                            <!-- Прогресс-бар опыта -->
                            <div class="mt-8 space-y-2 text-left">
                                <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-gray-500">
                                    <span>Прогресс уровня</span>
                                    <span>{{ $user->current_level_xp }} / {{ $user->next_level_xp }} XP</span>
                                </div>
                                <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-500 h-full shadow-[0_0_10px_rgba(79,70,229,0.5)] transition-all duration-1000" 
                                         style="width: {{ $user->xp_progress }}%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Декор фона -->
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-600/10 blur-3xl rounded-full"></div>
                    </div>

                    <!-- Карточка статистики (Ваша активность и Карма) -->
                    <div class="bg-white/[0.03] border border-white/5 rounded-[3rem] p-8">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-6">Ваша активность</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/5 p-5 rounded-3xl border border-white/5 text-center">
                                <div class="text-2xl font-black text-white">{{ $friendsCount ?? 0 }}</div>
                                <div class="text-[8px] font-bold text-gray-500 uppercase mt-1">Друзей</div>
                            </div>
                            <div class="bg-white/5 p-5 rounded-3xl border border-white/5 text-center">
                                <div class="text-2xl font-black {{ $user->karma >= 100 ? 'text-green-500' : 'text-red-500' }}">
                                    {{ $user->karma ?? 100 }}
                                </div>
                                <div class="text-[8px] font-bold text-gray-500 uppercase mt-1">Карма</div>
                            </div>
                        </div>
                        
                        <div class="mt-6 p-4 bg-indigo-500/5 rounded-2xl border border-indigo-500/10">
                            <p class="text-[9px] text-indigo-300/60 leading-relaxed font-medium">
                                <b>Карма</b> — это ваш рейтинг доверия. Общайтесь чаще и не получайте жалоб, чтобы ваша карма росла. Высокая карма дает приоритет в поиске.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА: ФОРМЫ -->
                <div class="md:col-span-2 space-y-6">
                    
                    <!-- Основная информация + ИНТЕРЕСЫ -->
                    <div class="bg-white/[0.03] border border-white/5 rounded-[3rem] p-8 md:p-12 shadow-2xl">
                        <section class="space-y-6">
                            <header>
                                <h2 class="text-xl font-black tracking-tight text-white uppercase">Личные данные</h2>
                                <p class="mt-1 text-sm font-medium text-gray-500">Обновите имя профиля, email и ваши интересы для подбора собеседников.</p>
                            </header>

                            <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
                                @csrf
                                @method('patch')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="name" :value="__('Имя пользователя')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                                        <x-text-input id="name" name="name" type="text" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6 !text-white focus:!ring-indigo-500" :value="old('name', $user->name)" required autofocus />
                                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                    </div>

                                    <div>
                                        <x-input-label for="email" :value="__('Email адрес')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                                        <x-text-input id="email" name="email" type="email" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6 !text-white focus:!ring-indigo-500" :value="old('email', $user->email)" required />
                                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                                    </div>
                                </div>

                                <!-- БЛОК ИНТЕРЕСОВ -->
                                <div>
                                    <x-input-label for="interests" :value="__('Ваши интересы (через запятую)')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                                    
                                        <input id="interests" name="interests_string" type="text" 
                                            class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 text-indigo-400 font-bold focus:outline-none focus:border-indigo-500 transition-colors" 
                                            value="{{ old('interests_string', $interestsString) }}" 
                                            placeholder="Например: Laravel, PHP, Video Games" />
                                        
                                    <x-input-error class="mt-2" :messages="$errors->get('interests_string')" />
                                </div>

                                <div class="flex items-center gap-4 pt-4">
                                    <x-primary-button class="!bg-indigo-600 hover:!bg-indigo-500 !rounded-xl !py-4 !px-10 !text-[10px] !font-black !uppercase !tracking-widest transition-all shadow-xl shadow-indigo-600/20">
                                        {{ __('Сохранить изменения') }}
                                    </x-primary-button>

                                    @if (session('status') === 'profile-updated')
                                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm text-green-400 font-black uppercase tracking-widest">✓ Сохранено</p>
                                    @endif
                                </div>
                            </form>
                        </section>
                    </div>

                    <!-- Безопасность (Смена пароля) -->
                    <div class="bg-white/[0.03] border border-white/5 rounded-[3rem] p-8 md:p-12">
                        @include('profile.partials.update-password-form')
                    </div>

                    <!-- Опасная зона (Удаление аккаунта) -->
                    <div class="bg-red-600/5 border border-red-600/10 rounded-[3rem] p-8 md:p-12">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>