<div id="share-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
    <div class="bg-[#1a1a1c] rounded-3xl w-full max-w-md p-8 border border-white/10">

        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-share-nodes text-emerald-400 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-semibold">Share this login</h3>
                <p class="text-sm text-white/50">The recipient will receive an encrypted copy</p>
            </div>
        </div>

        <form action="{{ route('shares.store') }}" method="POST" id="share-form">
            @csrf
            <input type="hidden" name="service_id" id="modal-service-id">

            <div class="mb-6">
                <label class="block text-sm text-white/60 mb-2">Recipient email</label>
                <input type="email" name="email"
                       class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4 text-white placeholder:text-white/40 focus:border-emerald-500"
                       placeholder="friend@example.com" required>
            </div>

            <div class="flex gap-4">
                <button type="button" onclick="hideShareModal()"
                        class="flex-1 py-4 text-white/70 font-medium hover:text-white transition">Cancel</button>
                <button type="submit"
                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-2xl transition flex items-center justify-center gap-2">
                    <span>Send Share</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="success-toast" class="hidden fixed bottom-6 right-6 bg-emerald-600 text-white px-6 py-4 rounded-2xl shadow-xl flex items-center gap-3 z-[200]">
    <i class="fa-solid fa-check-circle text-xl"></i>
    <span id="toast-message">Share sent successfully!</span>
</div>

<script>
    window.showToast = function(message = 'Share sent successfully!') {
        const toast = document.getElementById('success-toast');
        const msg = document.getElementById('toast-message');

        if (toast && msg) {
            msg.textContent = message;
            toast.classList.remove('hidden');
            toast.classList.add('flex');

            setTimeout(() => {
                toast.classList.remove('flex');
                toast.classList.add('hidden');
            }, 3000);
        }
    }
</script>
