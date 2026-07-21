<x-app-layout>
    <div class="relative min-h-screen text-white pb-32">
        <!-- Фон -->
        <div class="fixed inset-0 pointer-events-none z-0">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-600/10 blur-[120px] rounded-full"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/10 blur-[120px] rounded-full"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8 relative z-10">
            <div class="mb-10 text-center lg:text-left">
                <h1 class="text-4xl md:text-5xl font-black tracking-tighter uppercase italic">{{ __('settings.Settings') }}</h1>
                <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.4em] mt-2">{{ __('settings.Your_Profile') }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Sidebar -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 text-center shadow-2xl">
                        <div class="w-32 h-32 bg-indigo-600 rounded-[2.5rem] flex items-center justify-center text-5xl font-black mx-auto mb-6">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <h2 class="text-2xl font-black uppercase">{{ $user->name }}</h2>
                        <p class="text-indigo-400 text-[10px] font-black uppercase mt-2">{{ $user->rank_name }}</p>
                    </div>
                </div>

                <!-- Forms -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="led-frame rounded-[3rem]">
                        <div class="led-content bg-[#0a0a0a] border border-white/5 rounded-[3rem] p-8 md:p-12 shadow-2xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 md:p-12 shadow-2xl">
                        @include('profile.partials.update-password-form')
                    </div>

                    <div class="bg-red-600/5 border border-red-600/10 rounded-[2.5rem] p-8 md:p-12 shadow-2xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>