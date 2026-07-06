<div id="share-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4">
    <div class="card w-full max-w-md rounded-2xl p-6 sm:p-8">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10">
                    <i class="fa-solid fa-share-nodes text-emerald-400"></i>
                </div>
                <div>
                    <h3 class="text-xl font-semibold">{{ __('Share this item') }}</h3>
                    <p class="text-sm text-[var(--text-secondary)]">{{ __('The recipient will receive encrypted access to this item.') }}</p>
                </div>
            </div>
            <button type="button" onclick="hideShareModal()"
                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl text-[var(--text-secondary)] transition hover:bg-[var(--bg-input)] hover:text-[var(--text-primary)]">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('shares.store') }}" method="POST" id="share-form">
            @csrf
            <input type="hidden" name="service_id" id="modal-service-id">

            <div class="mb-6">
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Recipient email') }}</label>
                <input type="email" name="email"
                       class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 placeholder:text-[var(--text-secondary)] focus:border-emerald-500"
                       placeholder="friend@example.com" required>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row">
                <button type="button" onclick="hideShareModal()"
                        class="flex-1 rounded-2xl border border-[var(--border-color)] py-3.5 font-medium text-[var(--text-secondary)] transition hover:bg-[var(--bg-input)] hover:text-[var(--text-primary)]">{{ __('Cancel') }}</button>
                <button type="submit"
                        class="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3.5 font-medium text-white transition hover:bg-emerald-700">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>{{ __('Send Share') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Toast -->
<div id="success-toast" class="fixed bottom-6 right-6 z-[200] hidden items-center gap-3 rounded-2xl bg-emerald-600 px-6 py-4 text-white shadow-xl">
    <i class="fa-solid fa-check-circle text-xl"></i>
    <span id="toast-message">{{ __('Share sent successfully!') }}</span>
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
