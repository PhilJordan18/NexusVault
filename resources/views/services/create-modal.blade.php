<div id="create-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
    <div class="bg-white dark:bg-[#1a1a1c] rounded-3xl w-full max-w-lg p-8">
        <h3 class="text-xl font-semibold mb-6">New Service</h3>

        <form action="{{ route('services.store') }}" method="POST">
            @csrf
            <div class="space-y-5">
                <div>
                    <label class="block text-sm mb-1">Service Name</label>
                    <input type="text" name="name" required class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4">
                </div>
                <div>
                    <label class="block text-sm mb-1">URL (optional)</label>
                    <input type="url" name="url" class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4">
                </div>
                <div>
                    <label class="block text-sm mb-1">Username / Email</label>
                    <input type="text" name="username" required class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4">
                </div>
                <div>
                    <label class="block text-sm mb-1">Password</label>
                    <input type="password" name="password" required class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4">
                </div>
                <div>
                    <label class="block text-sm mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4"></textarea>
                </div>
            </div>

            <div class="flex gap-4 mt-8">
                <button type="button" onclick="hideCreateModal()"
                        class="flex-1 py-4 text-white/70 font-medium">Cancel</button>
                <button type="submit"
                        class="flex-1 py-4 bg-emerald-600 text-white font-medium rounded-2xl">Create Service</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showCreateModal() {
        document.getElementById('create-modal').classList.remove('hidden');
    }
    function hideCreateModal() {
        document.getElementById('create-modal').classList.add('hidden');
    }
</script>
