<section class="space-y-6">
    <header>
        <h2 class="text-xl font-black tracking-tight text-white">{{ __('settings.Pesronal_Information') }}</h2>
        <p class="mt-1 text-sm font-medium text-gray-500">{{ __('settings.Update_name_and_email') }}</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 gap-6">
            <!-- Interests Field (Moved Inside) -->
<div>
    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4 block ml-1">
        {{ __('settings.Select_Your_Interests') }}
    </label>
    
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        @foreach(\App\Enums\UserInterest::cases() as $interest)
            <label class="relative cursor-pointer group">
                <input type="checkbox" name="interests[]" value="{{ $interest->value }}" 
                       {{ in_array($interest->value, $user->interests ?? []) ? 'checked' : '' }}
                       class="peer sr-only">
                <div class="p-3 text-center rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest transition-all
                            peer-checked:bg-brand-indigo peer-checked:text-white peer-checked:border-brand-indigo
                            group-hover:border-white/20">
                    {{ $interest->name }}
                </div>
            </label>
        @endforeach
    </div>
</div>

            <div>
                <x-input-label for="name" :value="__('settings.Name')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                <x-text-input id="name" name="name" type="text" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6 !text-white focus:!ring-indigo-500" :value="old('name', $user->name)" required autofocus />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('settings.Email')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1" />
                <x-text-input id="email" name="email" type="email" class="w-full !bg-white/5 !border-white/10 !rounded-2xl !py-4 !px-6 !text-white focus:!ring-indigo-500" :value="old('email', $user->email)" required />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button class="!bg-indigo-600 hover:!bg-indigo-500 !rounded-xl !py-4 !px-8 !text-[10px] !font-black !uppercase !tracking-widest transition-all">
                {{ __('settings.Save_Changes') }}
            </x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-indigo-400 font-bold">✓ {{ __('settings.Profile_Updated') }}</p>
            @endif
        </div>
    </form>
</section>