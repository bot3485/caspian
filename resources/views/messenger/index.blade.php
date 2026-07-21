<!-- Tabs -->
@include('messenger.partials.tabs')

<div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">
    
    <!-- 1. Roulette Chat -->
    @include('messenger.partials.roulette-chat')

    <!-- 2. Contacts List -->
    <div x-show="tab === 'friends' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
        @include('messenger.partials.contacts-list')
    </div>

    <!-- 3. Private Chat View -->
    @include('messenger.partials.private-chat')

    <!-- 4. History (Logs) -->
    <div x-show="tab === 'history' && !activeFriend" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
        @include('messenger.partials.history-list')
    </div>

    <!-- 5. Blacklist -->
    <div x-show="tab === 'blacklist'" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
        @include('messenger.partials.blacklist')
    </div>
</div>