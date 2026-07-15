<section class="space-y-6">
    <header>
        <h2 class="text-xl font-black tracking-tight text-white">{{ __('settings.Safety') }}</h2>
        <p class="mt-1 text-sm font-medium text-gray-500">{{ __('settings.Make_Sure') }}</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <div class="space-y-4">
            <div>
                <x-input-label for="update_password_current_password" :value="__('settings.Current_Password')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password" :value="__('settings.New_Password')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                <x-text-input id="update_password_password" name="password" type="password" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('settings.Confirm_Password')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button class="!bg-white !text-black hover:!bg-indigo-500 hover:!text-white !rounded-xl !py-4 !px-8 !text-[10px] !font-black !uppercase !tracking-widest">
                {{ __('settings.Change_Password') }}
            </x-primary-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-indigo-400 font-bold uppercase">✓ {{ __('settings.Password_Changed') }}</p>
            @endif
        </div>
    </form>
</section>