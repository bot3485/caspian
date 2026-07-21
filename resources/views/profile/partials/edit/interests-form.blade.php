<section class="space-y-6 mt-12 pt-12 border-t border-white/5">
    <header>
        <h2 class="text-xl font-black tracking-tight text-white uppercase italic">Interests Matrix</h2>
        <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-1">{{ __('settings.Select_Your_Interests') }}</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach(\App\Enums\UserInterest::cases() as $interest)
                <label class="cursor-pointer group">
                    <input type="checkbox" name="interests[]" value="{{ $interest->value }}" 
                           {{ in_array($interest->value, $user->interests ?? []) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="p-4 text-center rounded-2xl bg-white/[0.02] border border-white/5 text-[9px] font-black uppercase tracking-widest transition-all peer-checked:bg-brand-indigo peer-checked:text-white group-hover:border-white/20">
                        {{ $interest->name }}
                    </div>
                </label>
            @endforeach
        </div>

        <button type="submit" class="mt-8 bg-brand-indigo/10 border border-brand-indigo/30 text-brand-indigo px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-brand-indigo hover:text-white transition-all">
            Update Tags
        </button>
    </form>
</section>