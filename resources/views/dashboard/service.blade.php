<x-layouts.app>
    <div class="flex h-full gap-8">

        <!-- LEFT LIST -->
        <div class="w-96 glass rounded-3xl p-6 overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold">{{ $name }}</h2>
                <span class="text-xs text-white/40">{{ count($accounts) }} accounts</span>
            </div>

            @foreach ($accounts as $account)
                <div onclick="window.selectAccount({{ $account->id }})"
                     class="group p-4 hover:bg-white/10 rounded-2xl cursor-pointer mb-2 flex items-center gap-4 border border-transparent hover:border-emerald-500/50 transition-all active:scale-[0.985]">

                    <!-- Mini avatar / favicon -->
                    <div class="w-9 h-9 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-lg">{{ strtoupper(substr($account->name ?? 'S', 0, 1)) }}</span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-medium truncate">{{ $account->username }}</p>
                        <p class="text-xs text-white/50">{{ $account->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- RIGHT DETAIL PANEL -->
        <div id="detail-panel" class="flex-1 hidden">

            <!-- MAIN INFO CARD -->
            <div class="glass rounded-3xl p-8 space-y-8">

                <!-- Header -->
                <div class="flex justify-between items-start">
                    <div>
                        <h1 id="detail-username" class="text-3xl font-semibold"></h1>
                        <p id="detail-name" class="text-white/50 text-sm mt-1"></p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="window.editAccount()"
                                class="px-5 py-2 bg-white/10 hover:bg-white/20 rounded-2xl text-sm font-medium transition">
                            Edit
                        </button>
                        <button onclick="window.shareCurrentAccount()"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-medium transition">
                            Share
                        </button>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-white/50">Password</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <p id="detail-password"
                           class="font-mono text-2xl tracking-[4px] select-none text-white/90">
                            ••••••••••••
                        </p>
                        <button onclick="window.togglePassword()"
                                class="w-10 h-10 flex items-center justify-center rounded-2xl bg-white/10 hover:bg-white/20 transition">
                            <i class="fa-solid fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Website -->
                <div>
                    <p class="text-xs text-white/50 mb-1">Website</p>
                    <a id="detail-url" target="_blank"
                       class="text-emerald-400 hover:underline font-medium"></a>
                </div>

                <!-- Notes -->
                <div id="detail-notes-container" class="hidden">
                    <p class="text-xs text-white/50 mb-1">Notes</p>
                    <p id="detail-notes" class="text-sm text-white/80 leading-relaxed"></p>
                </div>
            </div>

            <!-- DYNAMIC SECURITY PANEL -->
            <div id="security-panel" class="hidden mt-6">
                <!-- JS will inject content here -->
            </div>

        </div>
    </div>

    <script>
        window.accounts = @json($accounts->keyBy('id'));
    </script>
</x-layouts.app>
