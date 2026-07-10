<x-app-layout>
    <div class="h-full overflow-y-auto custom-scrollbar bg-[#050505] text-white font-sans selection:bg-indigo-500/30 pb-32 lg:pb-10">
        
        <!-- ДЕКОР -->
        <div class="fixed inset-0 pointer-events-none">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-600/10 blur-[120px] rounded-full"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/10 blur-[120px] rounded-full"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8 relative">
            
            <!-- HEADER -->
            <div class="mb-10 text-center lg:text-left">
                <h1 class="text-4xl md:text-5xl font-black tracking-tighter uppercase italic leading-none">Settings</h1>
                <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.4em] mt-2">Управление вашим аккаунтом</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- ЛЕВАЯ КОЛОНКА (ПРОФИЛЬ) -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 text-center relative overflow-hidden group shadow-2xl">
                        <div class="relative z-10">
                            <div class="w-32 h-32 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-[2.5rem] flex items-center justify-center text-5xl font-black mx-auto mb-6 shadow-2xl transform group-hover:scale-105 transition-transform">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <h2 class="text-2xl font-black tracking-tight uppercase">{{ $user->name }}</h2>
                            <p class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.2em] mt-2">
                                {{ $user->rank_name }} • Level {{ $user->level }}
                            </p>

                            <!-- XP Progress -->
                            <div class="mt-8 space-y-2 text-left">
                                <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-gray-500">
                                    <span>Уровень {{ $user->level }}</span>
                                    <span>{{ $user->xp_progress }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-500 h-full transition-all duration-1000 shadow-[0_0_15px_rgba(99,102,241,0.5)]" 
                                         style="width: {{ $user->xp_progress }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Статистика -->
                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 shadow-2xl">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-6">Ваша статистика</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/5 p-5 rounded-3xl border border-white/5 text-center">
                                <div class="text-2xl font-black">{{ $friendsCount }}</div>
                                <div class="text-[8px] font-bold text-gray-500 uppercase mt-1">Друзей</div>
                            </div>
                            <div class="bg-white/5 p-5 rounded-3xl border border-white/5 text-center">
                                <div class="text-2xl font-black text-indigo-400">{{ $user->karma }}</div>
                                <div class="text-[8px] font-bold text-gray-500 uppercase mt-1">Карма</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА (ФОРМЫ) -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- Основная инфо -->
                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[3rem] p-8 md:p-12 shadow-2xl">
                        <section class="space-y-6">
                            <header>
                                <h2 class="text-xl font-black tracking-tight uppercase italic">Личные данные</h2>
                                <p class="mt-1 text-sm font-medium text-gray-500">Обновите профиль и интересы для более точного поиска.</p>
                            </header>

                            <form method="post" action="{{ route('profile.update') }}" class="space-y-8">
                                @csrf
                                @method('patch')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest mb-3 block ml-1">Имя пользователя</label>
                                        <input name="name" type="text" value="{{ old('name', $user->name) }}" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest mb-3 block ml-1">Email адрес</label>
                                        <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                                    </div>
                                </div>

                                <!-- ИНТЕРЕСЫ -->
                                <div>
                                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest mb-3 block ml-1">Ваши интересы (через запятую)</label>
                                    <input name="interests_string" type="text" value="{{ old('interests_string', $interestsString) }}" placeholder="Laravel, Gaming, Music, Coding..."
                                           class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 text-indigo-400 font-bold focus:ring-2 focus:ring-indigo-500 transition-all">
                                    <p class="text-[9px] text-gray-600 mt-2 ml-1 italic uppercase font-bold">Алгоритм предложит вам собеседников с такими же тегами</p>
                                </div>

                                <div class="flex items-center gap-4 pt-4">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all shadow-xl shadow-indigo-600/20 active:scale-95">
                                        Сохранить изменения
                                    </button>

                                    @if (session('status') === 'profile-updated')
                                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-xs text-green-400 font-black uppercase">✓ Обновлено</p>
                                    @endif
                                </div>
                            </form>
                        </section>
                    </div>

                    <!-- Безопасность (Спойлер на мобилках) -->
                    <div x-data="{ open: window.innerWidth > 1024 }" class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] overflow-hidden shadow-2xl">
                        <button @click="open = !open" class="w-full p-8 flex justify-between items-center text-left hover:bg-white/[0.02] transition-colors">
                            <div>
                                <h2 class="text-xl font-black tracking-tight uppercase italic">Безопасность</h2>
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Смена пароля аккаунта</p>
                            </div>
                            <span class="text-2xl text-indigo-500 font-light" x-text="open ? '−' : '+'"></span>
                        </button>
                        <div x-show="open" x-transition class="p-8 md:p-12 border-t border-white/5 bg-white/[0.01]">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <!-- Опасная зона -->
                    <div x-data="{ open: false }" class="bg-red-600/5 border border-red-600/10 rounded-[2.5rem] overflow-hidden shadow-2xl">
                        <button @click="open = !open" class="w-full p-8 flex justify-between items-center text-left hover:bg-red-600/5 transition-colors">
                            <h2 class="text-xl font-black tracking-tight uppercase italic text-red-500">Danger Zone</h2>
                            <span class="text-2xl text-red-500 font-light" x-text="open ? '−' : '+'"></span>
                        </button>
                        <div x-show="open" x-transition class="p-8 md:p-12 border-t border-red-600/10 bg-red-600/[0.02]">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>