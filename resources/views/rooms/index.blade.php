<x-app-layout>
    <div class="py-12 bg-[#050505] min-h-screen text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
                <div>
                    <h1 class="text-4xl font-black tracking-tighter">Live Spaces</h1>
                    <p class="text-gray-500 font-medium mt-1 uppercase text-[10px] tracking-[0.3em]">Присоединяйтесь к активным обсуждениям</p>
                </div>
                <button onclick="document.getElementById('createRoomModal').showModal()" 
                        class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-indigo-500/20 active:scale-95">
                    + Создать пространство
                </button>
            </div>

            <!-- GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($rooms as $room)
                    <div class="group bg-white/[0.03] border border-white/5 rounded-[2.5rem] p-8 hover:bg-white/[0.05] hover:border-white/10 transition-all duration-500 relative overflow-hidden flex flex-col h-full">
                        
                        <!-- Статус -->
                        <div class="flex justify-between items-start mb-8">
                            <div class="w-12 h-12 bg-indigo-600/20 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">🏠</div>
                            @if($room->password)
                                <span class="bg-amber-500/10 text-amber-500 text-[9px] font-black px-3 py-1.5 rounded-full border border-amber-500/20 uppercase tracking-wider">Private</span>
                            @else
                                <span class="bg-green-500/10 text-green-500 text-[9px] font-black px-3 py-1.5 rounded-full border border-green-500/20 uppercase tracking-wider">Public</span>
                            @endif
                        </div>

                        <h3 class="text-xl font-black mb-2 group-hover:text-indigo-400 transition-colors">{{ $room->title }}</h3>
                        <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mb-8">Host: {{ $room->creator->name }}</p>

                        <div class="mt-auto pt-6 border-t border-white/5 flex items-center justify-between">
                            <div class="flex -space-x-2">
                                @foreach(range(1, 3) as $i)
                                    <div class="w-7 h-7 rounded-full border-2 border-[#050505] bg-gray-800 flex items-center justify-center text-[8px] font-bold">U{{$i}}</div>
                                @endforeach
                            </div>
                            <a href="{{ route('rooms.show', $room->uuid) }}" 
                               class="bg-white text-black px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-500 hover:text-white transition-all">
                                Войти ➔
                            </a>
                        </div>

                        <!-- Декор фона -->
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-600/5 blur-2xl rounded-full group-hover:bg-indigo-600/10 transition-all"></div>
                    </div>
                @empty
                    <div class="col-span-full py-32 bg-white/[0.02] border-2 border-dashed border-white/5 rounded-[3rem] text-center">
                        <div class="text-5xl mb-4 opacity-20">🧊</div>
                        <p class="text-gray-600 font-black uppercase text-xs tracking-widest">Тишина... Создайте первую комнату!</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- MODAL CREATE -->
    <dialog id="createRoomModal" class="rounded-[3rem] p-0 backdrop:bg-black/80 border border-white/10 shadow-2xl overflow-hidden bg-[#0a0a0a]">
        <div class="w-[450px] p-12 text-white">
            <div class="mb-10 text-center">
                <h2 class="text-3xl font-black tracking-tighter">Новое пространство</h2>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-2">Настройте параметры встречи</p>
            </div>

            <form id="createRoomForm" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 ml-1">Название комнаты</label>
                    <input type="text" name="title" placeholder="Пятничный созвон" required 
                           class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-6 text-sm font-bold text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 ml-1">Пароль (опционально)</label>
                    <input type="password" name="password" placeholder="••••••••" 
                           class="w-full bg-white/5 border border-white/10 rounded-2xl py-5 px-6 text-sm font-bold text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <label class="flex items-center gap-4 p-4 bg-white/5 rounded-2xl border border-white/5 cursor-pointer hover:border-indigo-500/30 transition-all">
                    <input type="checkbox" name="is_public" value="1" checked 
                           class="w-6 h-6 rounded-lg border-white/10 bg-black text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <p class="text-sm font-black uppercase tracking-tight">Публичный доступ</p>
                        <p class="text-[10px] text-gray-500 font-bold">Отображать комнату в общем списке</p>
                    </div>
                </label>

                <div class="flex gap-4 pt-6">
                    <button type="button" onclick="this.closest('dialog').close()" 
                            class="flex-1 py-5 text-[10px] font-black text-gray-500 hover:text-white uppercase tracking-widest transition-all">Отмена</button>
                    <button type="submit" id="submitCreateBtn" 
                            class="flex-1 bg-white text-black py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all shadow-xl">
                        Создать
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script type="module">
        document.getElementById('createRoomForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitCreateBtn');
            const formData = new FormData(e.target);
            
            const data = {
                title: formData.get('title'),
                password: formData.get('password'),
                is_public: formData.get('is_public') ? 1 : 0
            };

            btn.disabled = true;
            btn.innerText = "Processing...";

            try {
                const res = await window.axios.post('/rooms', data);
                window.location.href = res.data.redirect;
            } catch (err) {
                btn.disabled = false;
                btn.innerText = "Создать";
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { msg: err.response?.data?.message || 'Ошибка при создании комнаты', type: 'error' } 
                }));
            }
        };
    </script>
</x-app-layout>