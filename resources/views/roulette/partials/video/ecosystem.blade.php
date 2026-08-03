<div class="flex flex-col md:flex-row w-full h-full gap-3 md:gap-6 transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)]"
     :class="isBlitzActive ? 'blitz-grid-warp' : ''">

    <!-- PARTNER CONTAINER (REMOTE) -->
    @include('roulette.partials.video.remote')

    <!-- MY CONTAINER (LOCAL) -->
    @include('roulette.partials.video.local')

</div>