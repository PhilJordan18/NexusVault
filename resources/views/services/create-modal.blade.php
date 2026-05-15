<div id="create-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
    <div class="card rounded-3xl w-full max-w-lg p-8">
        <h3 class="text-xl font-semibold mb-6">New Service</h3>

        <form action="{{ route('services.store') }}" method="POST" id="create-service-form">
            @csrf

            <div class="space-y-5">
                <!-- Service Name + Autocomplete -->
                <div class="relative">
                    <label class="block text-sm text-[var(--text-secondary)] mb-1">Service Name</label>
                    <input type="text"
                           name="name"
                           id="service-name"
                           autocomplete="off"
                           required
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl px-5 py-4"
                           placeholder="Ex: Hetzner, Netflix, GitHub...">

                    <!-- Suggestions -->
                    <div id="name-suggestions"
                         class="hidden absolute z-[60] mt-1 w-full bg-[var(--bg-card)] border border-[var(--border-color)] rounded-2xl shadow-xl max-h-64 overflow-auto">
                    </div>

                    <input type="hidden" name="domain" id="service-domain">
                </div>

                <!-- URL -->
                <div>
                    <label class="block text-sm text-[var(--text-secondary)] mb-1">URL</label>
                    <div id="service-url-preview"
                         class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-[var(--text-secondary)] text-sm">
                        Sera généré automatiquement
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-sm text-[var(--text-secondary)] mb-1">Username / Email</label>
                    <input type="text" name="username" required
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl px-5 py-4">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm text-[var(--text-secondary)] mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="create-password" required
                               class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl px-5 py-4 pr-12">
                        <button type="button" id="create-password-toggle"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    <div id="create-strength-container" class="mt-2 hidden">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-[var(--text-secondary)]">Password strength</span>
                            <span id="create-strength-text" class="font-medium">Very weak</span>
                        </div>
                        <div class="h-1.5 bg-[var(--bg-input)] rounded-full overflow-hidden">
                            <div id="create-strength-bar" class="h-full w-0 transition-all duration-300 bg-red-500"></div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-[var(--text-secondary)] mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl px-5 py-4"></textarea>
                </div>

                <button type="button" id="create-generate-btn"
                        class="w-full flex items-center justify-center gap-2 text-emerald-500 hover:text-emerald-400 text-sm font-medium transition">
                    <i class="fa-solid fa-dice"></i>
                    <span>Generate strong password</span>
                </button>
            </div>

            <div class="flex gap-4 mt-6">
                <button type="button" onclick="hideCreateModal()"
                        class="flex-1 py-4 text-[var(--text-secondary)] font-medium">Cancel</button>
                <button type="submit"
                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-2xl">
                    Create Service
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let debounceTimer;

    const nameInput = document.getElementById('service-name');
    const suggestionsBox = document.getElementById('name-suggestions');

    if (nameInput) {
        nameInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                suggestionsBox.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`/api/services/suggest?name=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        suggestionsBox.innerHTML = '';

                        if (!data.length) {
                            suggestionsBox.classList.add('hidden');
                            return;
                        }

                        data.forEach(service => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-3 hover:bg-white/5 cursor-pointer flex items-center gap-3 text-sm';

                            const favicon = service.favicon || 'https://www.google.com/s2/favicons?domain=example.com&sz=32';

                            div.innerHTML = `
                            <img src="${favicon}" class="w-5 h-5 rounded flex-shrink-0" alt="">
                            <div>
                                <div class="font-medium">${service.name}</div>
                                ${service.url ? `<div class="text-xs text-[var(--text-secondary)]">${service.url}</div>` : ''}
                            </div>
                        `;

                            div.onclick = () => {
                                nameInput.value = service.name;
                                document.getElementById('service-domain').value = service.domain || '';

                                const preview = document.getElementById('service-url-preview');
                                preview.innerHTML = service.url
                                    ? `<span class="text-emerald-400">${service.url}</span>`
                                    : 'Will be generated automatically';

                                suggestionsBox.classList.add('hidden');
                            };

                            suggestionsBox.appendChild(div);
                        });

                        suggestionsBox.classList.remove('hidden');
                    })
                    .catch(() => suggestionsBox.classList.add('hidden'));
            }, 250);
        });
    }

    document.addEventListener('click', function(e) {
        if (suggestionsBox && !suggestionsBox.contains(e.target) && e.target !== nameInput) {
            suggestionsBox.classList.add('hidden');
        }
    });
</script>
