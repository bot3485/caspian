<section class="space-y-8">
    <header>
        <h2 class="text-xl font-black tracking-tight text-white uppercase italic">{{ __('settings.Pesronal_Information') }}</h2>
        <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-1">{{ __('settings.Update_name_and_email') }}</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div class="space-y-2">
                <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-2">{{ __('settings.Name') }}</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 text-white focus:ring-2 focus:ring-brand-indigo outline-none transition-all">
            </div>

            <!-- Email -->
            <div class="space-y-2">
                <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-2">{{ __('settings.Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 text-white focus:ring-2 focus:ring-brand-indigo outline-none transition-all">
            </div>

            <!-- Gender -->
            <div class="space-y-2">
                <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-2">Gender</label>
                <div class="flex gap-2">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="gender" value="male" class="sr-only peer" {{ $user->gender === 'male' ? 'checked' : '' }}>
                        <div class="py-4 text-center rounded-xl bg-white/5 border border-white/10 text-[9px] font-black uppercase peer-checked:bg-brand-indigo transition-all">Male</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="gender" value="female" class="sr-only peer" {{ $user->gender === 'female' ? 'checked' : '' }}>
                        <div class="py-4 text-center rounded-xl bg-white/5 border border-white/10 text-[9px] font-black uppercase peer-checked:bg-brand-indigo transition-all">Female</div>
                    </label>
                </div>
            </div>

            <!-- Age -->
            <div class="space-y-2">
                <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-2">Age</label>
                <input type="number" name="age" value="{{ old('age', $user->age) }}" min="18" max="99"
                       class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 text-white focus:ring-2 focus:ring-brand-indigo outline-none transition-all">
            </div>
        </div>

        <button type="submit" class="bg-white text-black px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-brand-indigo hover:text-white transition-all">
            {{ __('settings.Save_Changes') }}
        </button>
    </form>
</section>