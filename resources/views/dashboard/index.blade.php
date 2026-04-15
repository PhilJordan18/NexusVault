<x-layouts.app>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-6 mb-10">
        <div class="bg-white/5 border border-red-400/30 rounded-3xl p-6 flex items-center gap-4">
            <div class="w-4 h-4 bg-red-500 rounded"></div>
            <div>
                <p class="text-3xl font-semibold">201</p>
                <p class="text-sm text-white/60">compromised passwords</p>
            </div>
        </div>
        <div class="bg-white/5 border border-purple-400/30 rounded-3xl p-6 flex items-center gap-4">
            <div class="w-4 h-4 bg-purple-500 rounded"></div>
            <div>
                <p class="text-3xl font-semibold">342</p>
                <p class="text-sm text-white/60">reused passwords</p>
            </div>
        </div>
        <div class="bg-white/5 border border-amber-400/30 rounded-3xl p-6 flex items-center gap-4">
            <div class="w-4 h-4 bg-amber-500 rounded"></div>
            <div>
                <p class="text-3xl font-semibold">231</p>
                <p class="text-sm text-white/60">weak passwords</p>
            </div>
        </div>
    </div>

    <!-- All Items Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold">All Items (986)</h2>
        <div class="text-sm text-white/50 flex items-center gap-2">
            Last checked just now
            <i class="fa-solid fa-rotate"></i>
        </div>
    </div>

    <!-- Services Grid -->
    <div class="grid grid-cols-4 gap-6">
        @for ($i = 0; $i < 12; $i++)
            <div class="bg-white/5 border border-white/10 rounded-3xl p-5 hover:border-nexus-500/30 transition group cursor-pointer">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 bg-white/10 rounded-2xl flex items-center justify-center text-3xl">🔥</div>
                    <div class="flex-1">
                        <p class="font-medium">Netflix</p>
                        <p class="text-xs text-white/50">3 accounts</p>
                    </div>
                </div>
                <div class="mt-6 text-xs text-white/40">Last modified 2 days ago</div>
            </div>
        @endfor
    </div>

</x-layouts.app>
