<x-layouts.app>
    <div class="max-w-5xl mx-auto">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-semibold">{{ $name }}</h1>
                <p class="text-white/50">{{ count($accounts) }} accounts • Last updated {{ $accounts->first()->updated_at->diffForHumans() ?? '' }}</p>
            </div>

            @php
                $firstUrl = $accounts->first()->url ?? '';
            @endphp

            <button onclick="showCreateModalForService('{{ $name }}', '{{ $firstUrl }}')"
                    class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 rounded-2xl text-sm font-medium">
                <i class="fa-solid fa-plus"></i>
                <span>Add Account</span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- ACCOUNTS LIST -->
            <div class="lg:col-span-5">
                <div class="bg-[#111111] border border-white/10 rounded-3xl p-2">
                    @foreach ($accounts as $account)
                        <div onclick="window.selectAccount({{ $account->id }})"
                             class="px-4 py-4 hover:bg-white/5 rounded-2xl cursor-pointer flex items-center gap-4 group transition {{ $loop->first ? 'bg-white/5' : '' }}">

                            <div class="w-10 h-10 bg-white/10 rounded-2xl flex items-center justify-center flex-shrink-0 text-xl">
                                {{ strtoupper(substr($account->username, 0, 1)) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="font-medium truncate">{{ $account->username }}</p>
                                <p class="text-xs text-white/40">{{ $account->updated_at->diffForHumans() }}</p>
                            </div>

                            <div class="opacity-0 group-hover:opacity-100 transition">
                                <i class="fa-solid fa-chevron-right text-white/30"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- DETAIL PANEL -->
            <div class="lg:col-span-7">
                <div id="detail-panel" class="hidden bg-[#111111] border border-white/10 rounded-3xl p-8">

                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h2 id="detail-username" class="text-3xl font-semibold"></h2>
                            <p id="detail-name" class="text-white/50 mt-1"></p>
                        </div>

                        <div class="flex gap-2">
                            <button onclick="window.editAccount()"
                                    class="px-4 py-2 text-sm bg-white/5 hover:bg-white/10 rounded-2xl flex items-center gap-2">
                                <i class="fa-solid fa-edit"></i>
                                <span>Edit</span>
                            </button>

                            <button onclick="window.shareCurrentAccount()"
                                    class="px-4 py-2 text-sm bg-emerald-600 hover:bg-emerald-700 rounded-2xl flex items-center gap-2 text-white">
                                <i class="fa-solid fa-share"></i>
                                <span>Share</span>
                            </button>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-xs uppercase tracking-widest text-white/50">Password</div>
                            <button onclick="window.togglePassword()"
                                    class="text-xs flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300">
                                <i class="fa-solid fa-eye"></i>
                                <span>Show</span>
                            </button>
                        </div>

                        <div class="bg-black/40 border border-white/10 rounded-2xl px-5 py-4 font-mono text-xl tracking-[3px] text-white/90" id="detail-password">
                            ••••••••••••
                        </div>
                    </div>

                    <!-- Website -->
                    <div class="mb-8">
                        <div class="text-xs uppercase tracking-widest text-white/50 mb-2">Website</div>
                        <a id="detail-url" target="_blank" class="text-emerald-400 hover:underline break-all"></a>
                    </div>

                    <!-- Notes -->
                    <div id="detail-notes-container" class="hidden">
                        <div class="text-xs uppercase tracking-widest text-white/50 mb-2">Notes</div>
                        <div id="detail-notes" class="text-sm text-white/70 leading-relaxed bg-black/30 p-4 rounded-2xl"></div>
                    </div>

                </div>

                <!-- Empty State -->
                <div id="empty-state" class="h-full flex items-center justify-center text-center py-12">
                    <div>
                        <div class="mx-auto w-14 h-14 bg-white/5 rounded-full flex items-center justify-center mb-4">
                            <i class="fa-solid fa-key text-2xl text-white/30"></i>
                        </div>
                        <p class="text-white/50">Select an account from the list<br>to view details</p>
                    </div>
                </div>
                <div id="security-panel" class="hidden mt-6"></div>

                <div id="delete-account-container" class="hidden mt-6 text-center">
                    <button onclick="window.deleteAccount()"
                            class="text-red-400 hover:text-red-500 px-4 py-2 text-sm rounded-2xl hover:bg-red-500/10 transition">
                        <i class="fa-solid fa-trash mr-2"></i>Delete this account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== EDIT ACCOUNT MODAL ==================== -->
    <div id="edit-account-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
        <div class="bg-[#111111] border border-white/10 rounded-3xl w-full max-w-md mx-4 p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-semibold">Edit Account</h3>
                <button onclick="hideEditModal()" class="text-white/50 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            <form id="edit-account-form" onsubmit="submitEditAccount(event)">
                <input type="hidden" id="edit-service-id">

                <!-- Username / Email -->
                <div class="mb-5">
                    <label class="block text-sm text-white/60 mb-2">Username / Email</label>
                    <input type="text" id="edit-username" required
                           class="w-full bg-white/5 border border-white/10 focus:border-emerald-500 rounded-2xl px-5 py-3.5 text-white placeholder:text-white/40">
                </div>

                <!-- Password -->
                <div class="mb-5">
                    <label class="block text-sm text-white/60 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="edit-password" required
                               class="w-full bg-white/5 border border-white/10 focus:border-emerald-500 rounded-2xl px-5 py-3.5 pr-12 text-white placeholder:text-white/40 font-mono">
                        <button type="button" id="edit-password-toggle"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-white">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <!-- Barre de force -->
                    <div id="edit-strength-container" class="mt-2 hidden">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-white/60">Password strength</span>
                            <span id="edit-strength-text" class="font-medium">Very weak</span>
                        </div>
                        <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                            <div id="edit-strength-bar" class="h-full w-0 transition-all duration-300 bg-red-500"></div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label class="block text-sm text-white/60 mb-2">Notes (optional)</label>
                    <textarea id="edit-notes" rows="3"
                              class="w-full bg-white/5 border border-white/10 focus:border-emerald-500 rounded-2xl px-5 py-3.5 text-white placeholder:text-white/40 resize-y"></textarea>
                </div>

                <!-- Generate password button -->
                <button type="button" id="edit-generate-btn"
                        class="w-full flex items-center justify-center gap-2 text-emerald-400 hover:text-emerald-300 text-sm font-medium mb-4 transition">
                    <i class="fa-solid fa-dice"></i>
                    <span>Generate strong password</span>
                </button>

                <div class="flex gap-3">
                    <button type="button" onclick="hideEditModal()"
                            class="flex-1 py-3.5 rounded-2xl border border-white/20 text-white/70 hover:bg-white/5 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-3.5 bg-emerald-600 hover:bg-emerald-700 rounded-2xl text-white font-medium transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.accounts = @json($accounts->keyBy('id'));
    </script>
</x-layouts.app>
