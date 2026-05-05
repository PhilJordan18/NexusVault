<div id="create-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
    <div class="bg-white dark:bg-[#1a1a1c] rounded-3xl w-full max-w-lg p-8">
        <h3 class="text-xl font-semibold mb-6">New Service</h3>

        <form action="{{ route('services.store') }}" method="POST">
            @csrf
            <div class="space-y-5">
                <div>
                    <label class="block text-sm mb-1">Service Name</label>
                    <input type="text" name="name" id="service-name" required
                           class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4">
                </div>
                <div>
                    <label class="block text-sm mb-1">URL (optional)</label>
                    <input type="url" name="url" id="service-url"
                           class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4">
                </div>
                <div>
                    <label class="block text-sm mb-1">Username / Email</label>
                    <input type="text" name="username" required
                           class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4">
                </div>
                <div>
                    <label class="block text-sm mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="create-password" required
                               class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4 pr-12">
                        <button type="button" id="create-password-toggle"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-white">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <!-- Barre de force -->
                    <div id="create-strength-container" class="mt-2 hidden">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-white/60">Password strength</span>
                            <span id="create-strength-text" class="font-medium">Very weak</span>
                        </div>
                        <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                            <div id="create-strength-bar" class="h-full w-0 transition-all duration-300 bg-red-500"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4"></textarea>
                </div>
                <!-- Generate password button -->
                <button type="button" id="create-generate-btn"
                        class="w-full flex items-center justify-center gap-2 text-emerald-400 hover:text-emerald-300 text-sm font-medium transition">
                    <i class="fa-solid fa-dice"></i>
                    <span>Generate strong password</span>
                </button>
            </div>

            <div class="flex gap-4 mt-6">
                <button type="button" onclick="hideCreateModal()"
                        class="flex-1 py-4 text-white/70 font-medium">Cancel</button>
                <button type="submit"
                        class="flex-1 py-4 bg-emerald-600 text-white font-medium rounded-2xl">Create Service</button>
            </div>
        </form>
    </div>
</div>
