<x-app-layout>
    <div class="py-12 bg-[#050505] min-h-screen text-white">
        <div class="max-w-3xl mx-auto px-4">
            <h1 class="text-4xl font-black tracking-tighter mb-10 text-center uppercase">Hall of Fame</h1>
            
            <div class="space-y-4">
                @php 
                    $topUsers = \App\Models\User::orderBy('xp', 'desc')->take(10)->get();
                @endphp
                
                @foreach($topUsers as $index => $u)
                    <div class="flex items-center gap-6 p-6 bg-white/[0.03] border border-white/5 rounded-3xl hover:bg-white/[0.06] transition-all">
                        <div class="text-2xl font-black text-gray-700 w-8">#{{ $index + 1 }}</div>
                        <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center font-black">{{ $u->name[0] }}</div>
                        <div class="flex-1">
                            <h3 class="font-black text-lg">{{ $u->name }}</h3>
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Level {{ $u->level }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-indigo-400 font-black">{{ number_format($u->xp) }} XP</div>
                            <div class="text-[10px] font-bold text-gray-600 uppercase">{{ $u->total_minutes }} MINS</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>