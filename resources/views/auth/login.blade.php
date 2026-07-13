<x-guest-layout>
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-black uppercase italic tracking-tighter">Welcome Back</h1>
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-2">Initialize your session</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <div class="space-y-2">
            <label class="text-[9px] font-black uppercase tracking-widest text-brand-indigo ml-2">Access Key (Email)</label>
            <input type="email" name="email" :value="old('email')" required autofocus
                   class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-brand-indigo focus:border-transparent outline-none transition-all">
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="space-y-2">
            <label class="text-[9px] font-black uppercase tracking-widest text-brand-indigo ml-2">Secret Hash (Password)</label>
            <input type="password" name="password" required
                   class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-brand-indigo focus:border-transparent outline-none transition-all">
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between px-2">
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox" name="remember" class="w-5 h-5 rounded-lg bg-white/5 border-white/10 text-brand-indigo focus:ring-0">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-500 group-hover:text-gray-300 transition-colors">Keep Alive</span>
            </label>
        </div>

        <button type="submit" class="w-full bg-white text-black font-black uppercase text-xs py-5 rounded-2xl hover:bg-brand-indigo hover:text-white transition-all shadow-xl active:scale-95">
            Authorize ➔
        </button>

        @if (Route::has('register'))
            <div class="text-center mt-6">
                <a href="{{ route('register') }}" class="text-[9px] font-black uppercase tracking-widest text-gray-500 hover:text-brand-indigo transition-colors">
                    Don't have an ID? Create Account
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>