<section class="space-y-6">
    <header>
        <h2 class="text-xl font-black tracking-tight text-white">Личные данные</h2>
        <p class="mt-1 text-sm font-medium text-gray-500">Обновите имя профиля и адрес электронной почты.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 gap-6">
            <!-- Interests Field (Moved Inside) -->
            <div>
                <x-input-label for="interests" :value="__('Ваши интересы (через запятую)')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                <x-text-input id="interests" name="interests_string" type="text" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6 !text-white" :value="implode(', ', $user->interests ?? [])" placeholder="Gaming, Coding, Music" />
            </div>

            <div>
                <x-input-label for="name" :value="__('Имя')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                <x-text-input id="name" name="name" type="text" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6 !text-white focus:!ring-indigo-500" :value="old('name', $user->name)" required autofocus />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                <x-text-input id="email" name="email" type="email" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6 !text-white focus:!ring-indigo-500" :value="old('email', $user->email)" required />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button class="!bg-indigo-600 hover:!bg-indigo-500 !rounded-xl !py-4 !px-8 !text-[10px] !font-black !uppercase !tracking-widest transition-all">
                {{ __('Сохранить изменения') }}
            </x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-indigo-400 font-bold">✓ Обновлено</p>
            @endif
        </div>
    </form>
</section>