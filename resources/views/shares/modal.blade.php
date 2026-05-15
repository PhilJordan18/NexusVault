<div id="share-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
    <div class="card rounded-3xl w-full max-w-md p-8">

        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-share-nodes text-emerald-500 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-semibold">Share this login</h3>
                <p class="text-sm text-[var(--text-secondary)]">The recipient will receive an encrypted copy</p>
            </div>
        </div>

        <form action="{{ route('shares.store') }}" method="POST" id="share-form">
            @csrf
            <input type="hidden" name="service_id" id="modal-service-id">

            <div class="mb-6">
                <label class="block text-sm text-[var(--text-secondary)] mb-2">Recipient email</label>
                <input type="email" name="email"
                       class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl px-5 py-4 placeholder:text-[var(--text-secondary)]"
                       placeholder="friend@example.com" required>
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="hideShareModal()"
                        class="flex-1 py-4 text-[var(--text-secondary)] font-medium hover:text-[var(--text-primary)] transition">Cancel</button>
                <button type="submit"
                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-2xl transition flex items-center justify-center gap-2">
                    <span>Send Share</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Toast -->
<div id="success-toast" class="hidden fixed bottom-6 right-6 bg-emerald-600 text-white px-6 py-4 rounded-2xl shadow-xl flex items-center gap-3 z-[200]">
    <i class="fa-solid fa-check-circle text-xl"></i>
    <span id="toast-message">Share sent successfully!</span>
</div>

<script>
    window.showShareModal = function(serviceId) {
        const modal = document.getElementById('share-modal');
        const input = document.getElementById('modal-service-id');
        if (modal && input) {
            input.value = serviceId;
            modal.classList.remove('hidden');
        }
    };

    window.hideShareModal = function() {
        const modal = document.getElementById('share-modal');
        if (modal) modal.classList.add('hidden');
    };
</script>
