<x-app-layout>
    <div class="relative min-h-[calc(100svh-80px)] text-white pb-24 overflow-y-auto custom-scrollbar bg-[#020202]">
        
        <!-- Background Decor -->
        <div class="fixed inset-0 pointer-events-none">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-brand-indigo/5 blur-[120px] rounded-full"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/5 blur-[120px] rounded-full"></div>
        </div>

        <div class="max-w-7xl mx-auto px-6 py-12 relative z-10">
            <div class="mb-16">
                <h1 class="text-4xl md:text-6xl font-black tracking-tighter uppercase italic bg-gradient-to-r from-white to-white/40 bg-clip-text text-transparent">
                    {{ __('settings.Settings') }}
                </h1>
                <p class="text-brand-indigo font-black text-[9px] uppercase tracking-[0.5em] mt-4 ml-1">{{ __('settings.Your_Profile') }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left: Profile Summary -->
                <div class="lg:col-span-4 space-y-6">
                    @include('profile.partials.edit.sidebar')
                </div>

                <!-- Right: Configuration Forms -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- Main Data (LED Frame for emphasis) -->
                    <div class="led-frame rounded-[3rem]">
                        <div class="led-content bg-[#0a0a0a] p-8 md:p-12 border border-white/5 shadow-2xl">
                            @include('profile.partials.edit.info-form')
                            @include('profile.partials.edit.interests-form')
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="bg-[#0a0a0a] border border-white/5 rounded-[2.5rem] p-8 md:p-12 shadow-2xl">
                        @include('profile.partials.update-password-form')
                    </div>

                    <!-- Danger Zone -->
                    <div class="bg-red-600/5 border border-red-950/30 rounded-[2.5rem] p-8 md:p-12 shadow-2xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>