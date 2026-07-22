<div id="create-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4">
    <div class="card max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl p-6 sm:p-8">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10">
                    <i class="fa-solid fa-plus text-emerald-400"></i>
                </div>
                <div>
                    <h3 class="text-xl font-semibold">{{ __('New Item') }}</h3>
                    <p class="text-sm text-[var(--text-secondary)]">{{ __('Keep it all together') }}</p>
                </div>
            </div>
            <button type="button" onclick="hideCreateModal()"
                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl text-[var(--text-secondary)] transition hover:bg-[var(--bg-input)] hover:text-[var(--text-primary)]">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('services.store') }}" method="POST" id="create-service-form">
            @csrf
            <input type="hidden" name="type" id="service-type" value="login">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-[var(--text-secondary)] mb-2">{{ __('Item Type') }}</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" data-service-type="login"
                                class="item-type-btn active flex items-center justify-center gap-2 rounded-2xl border border-emerald-500 bg-emerald-500/10 px-3 py-3 text-sm font-medium transition">
                            <i class="fa-solid fa-key"></i><span>{{ __('Login') }}</span>
                        </button>
                        <button type="button" data-service-type="payment_card"
                                class="item-type-btn flex items-center justify-center gap-2 rounded-2xl border border-[var(--border-color)] px-3 py-3 text-sm font-medium transition">
                            <i class="fa-solid fa-credit-card"></i><span>{{ __('Card') }}</span>
                        </button>
                        <button type="button" data-service-type="secure_note"
                                class="item-type-btn flex items-center justify-center gap-2 rounded-2xl border border-[var(--border-color)] px-3 py-3 text-sm font-medium transition">
                            <i class="fa-solid fa-note-sticky"></i><span>{{ __('Note') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Service Name + Autocomplete -->
                <div class="relative">
                    <label id="service-name-label" class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Service Name') }}</label>
                    <input type="text"
                           name="name"
                           id="service-name"
                           autocomplete="off"
                           required
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 focus:border-emerald-500"
                           placeholder="Ex: Hetzner, Netflix, GitHub...">

                    <!-- Suggestions -->
                    <div id="name-suggestions"
                         class="absolute z-[60] mt-1 hidden max-h-64 w-full overflow-auto rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] shadow-xl">
                    </div>

                    <input type="hidden" name="domain" id="service-domain">
                </div>

                <!-- URL -->
                <div id="service-url-section">
                    <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('URL') }}</label>
                    <input type="text"
                           name="url"
                           id="service-url"
                           autocomplete="off"
                           inputmode="url"
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 text-sm focus:border-emerald-500"
                           placeholder="https://laughtube.ca">
                    <p class="mt-2 text-xs text-[var(--text-secondary)]">
                        {{ __('Suggestions are optional. You can type the exact website if NexusVault does not know it yet.') }}
                    </p>
                </div>

                <!-- Username -->
                <div>
                    <label id="service-username-label" class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Username / Email') }}</label>
                    <input type="text" name="username" required
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 focus:border-emerald-500">
                </div>

                <!-- Password -->
                <div>
                    <label id="service-password-label" class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Password') }}</label>
                    <div class="relative">
                        <input type="password" name="password" id="create-password" required
                               class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 pr-12 focus:border-emerald-500">
                        <button type="button" id="create-password-toggle"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    <div id="create-strength-container" class="mt-2 hidden">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-[var(--text-secondary)]">{{ __('Password strength') }}</span>
                            <span id="create-strength-text" class="font-medium">{{ __('Very weak') }}</span>
                        </div>
                        <div class="h-1.5 bg-[var(--bg-input)] rounded-full overflow-hidden">
                            <div id="create-strength-bar" class="h-full w-0 transition-all duration-300 bg-red-500"></div>
                        </div>
                    </div>
                </div>

                <div>
                    <label id="service-notes-label" class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Notes') }}</label>
                    <textarea name="notes" rows="3"
                              class="w-full resize-y rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 focus:border-emerald-500"></textarea>
                </div>

                <button type="button" id="create-generate-btn"
                        data-password-generate
                        data-password-target="create-password"
                        data-password-min-length="8"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-400 transition hover:border-emerald-500/40 hover:bg-emerald-500/15">
                    <i class="fa-solid fa-dice"></i>
                    <span>{{ __('Generate strong password') }}</span>
                </button>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row">
                <button type="button" onclick="hideCreateModal()"
                        class="flex-1 rounded-2xl border border-[var(--border-color)] py-3.5 font-medium text-[var(--text-secondary)] transition hover:bg-[var(--bg-input)] hover:text-[var(--text-primary)]">{{ __('Cancel') }}</button>
                <button type="submit"
                        class="flex-1 rounded-2xl bg-emerald-600 py-3.5 font-medium text-white transition hover:bg-emerald-700">
                    {{ __('Create Service') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let debounceTimer;
    const defaultServiceIcon = @json(asset('logo/LogoMonogramme.svg'));

    const nameInput = document.getElementById('service-name');
    const suggestionsBox = document.getElementById('name-suggestions');
    const typeInput = document.getElementById('service-type');
    const urlSection = document.getElementById('service-url-section');
    const urlInput = document.getElementById('service-url');
    const usernameInput = document.querySelector('#create-service-form input[name="username"]');
    const passwordInput = document.getElementById('create-password');
    const notesInput = document.querySelector('#create-service-form textarea[name="notes"]');
    const createStrengthContainer = document.getElementById('create-strength-container');
    const createGenerateButton = document.getElementById('create-generate-btn');
    const createTypeLabels = {
        login: {
            name: {{ Illuminate\Support\Js::from(__('Service Name')) }},
            username: {{ Illuminate\Support\Js::from(__('Username / Email')) }},
            password: {{ Illuminate\Support\Js::from(__('Password')) }},
            notes: {{ Illuminate\Support\Js::from(__('Notes')) }},
            namePlaceholder: 'Ex: Hetzner, Netflix, GitHub...',
            usernamePlaceholder: '',
            passwordPlaceholder: '',
            notesPlaceholder: '',
        },
        payment_card: {
            name: {{ Illuminate\Support\Js::from(__('Card Name')) }},
            username: {{ Illuminate\Support\Js::from(__('Cardholder Name')) }},
            password: {{ Illuminate\Support\Js::from(__('Card Number')) }},
            notes: {{ Illuminate\Support\Js::from(__('Expiry, CVC, PIN, billing notes')) }},
            namePlaceholder: 'Ex: Visa Desjardins, Mastercard Scotia...',
            usernamePlaceholder: 'Name printed on card',
            passwordPlaceholder: '4111 1111 1111 1111',
            notesPlaceholder: 'MM/YY, CVC, PIN, billing address...',
        },
        secure_note: {
            name: {{ Illuminate\Support\Js::from(__('Note Title')) }},
            username: {{ Illuminate\Support\Js::from(__('Reference')) }},
            password: {{ Illuminate\Support\Js::from(__('Secure Content')) }},
            notes: {{ Illuminate\Support\Js::from(__('Extra Notes')) }},
            namePlaceholder: 'Ex: Recovery codes, server notes...',
            usernamePlaceholder: 'Optional reference',
            passwordPlaceholder: 'Private note content',
            notesPlaceholder: 'Extra context...',
        },
    };

    function setCreateItemType(type) {
        const labels = createTypeLabels[type] || createTypeLabels.login;
        const isLogin = type === 'login';

        typeInput.value = type;
        document.getElementById('service-name-label').textContent = labels.name;
        document.getElementById('service-username-label').textContent = labels.username;
        document.getElementById('service-password-label').textContent = labels.password;
        document.getElementById('service-notes-label').textContent = labels.notes;

        nameInput.placeholder = labels.namePlaceholder;
        usernameInput.placeholder = labels.usernamePlaceholder;
        passwordInput.placeholder = labels.passwordPlaceholder;
        notesInput.placeholder = labels.notesPlaceholder;
        passwordInput.type = isLogin ? 'password' : 'text';
        passwordInput.dataset.passwordStrengthDisabled = isLogin ? 'false' : 'true';
        urlSection.classList.toggle('hidden', !isLogin);
        createGenerateButton.classList.toggle('hidden', !isLogin);
        document.querySelector('[data-password-generator-controls-for="create-generate-btn"]')?.classList.toggle('hidden', !isLogin);

        if (!isLogin) {
            suggestionsBox.classList.add('hidden');
            document.getElementById('service-domain').value = '';
            urlInput.value = '';
            createStrengthContainer.classList.add('hidden');
        }

        document.querySelectorAll('.item-type-btn').forEach(button => {
            button.classList.toggle('border-emerald-500', button.dataset.serviceType === type);
            button.classList.toggle('bg-emerald-500/10', button.dataset.serviceType === type);
            button.classList.toggle('active', button.dataset.serviceType === type);
            button.classList.toggle('border-[var(--border-color)]', button.dataset.serviceType !== type);
        });
    }

    window.setCreateItemType = setCreateItemType;

    document.querySelectorAll('.item-type-btn').forEach(button => {
        button.addEventListener('click', () => setCreateItemType(button.dataset.serviceType || 'login'));
    });

    function normalizeUrlInput() {
        if (!urlInput || typeInput.value !== 'login') {
            return;
        }

        const value = urlInput.value.trim();

        if (!value || value.includes('://')) {
            urlInput.value = value;
            return;
        }

        urlInput.value = `https://${value}`;
    }

    document.getElementById('create-service-form')?.addEventListener('submit', normalizeUrlInput, { capture: true });

    if (nameInput) {
        nameInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (typeInput.value !== 'login' || query.length < 2) {
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

                            const favicon = service.favicon || defaultServiceIcon;

                            const img = document.createElement('img');
                            img.src = favicon;
                            img.alt = '';
                            img.className = 'w-5 h-5 rounded flex-shrink-0';
                            img.onerror = () => {
                                img.onerror = null;
                                img.src = defaultServiceIcon;
                            };

                            const content = document.createElement('div');
                            const serviceName = document.createElement('div');
                            serviceName.className = 'font-medium';
                            serviceName.textContent = service.name;
                            content.appendChild(serviceName);

                            if (service.url) {
                                const serviceUrl = document.createElement('div');
                                serviceUrl.className = 'text-xs text-[var(--text-secondary)]';
                                serviceUrl.textContent = service.url;
                                content.appendChild(serviceUrl);
                            }

                            div.append(img, content);

                            div.onclick = () => {
                                nameInput.value = service.name;
                                document.getElementById('service-domain').value = service.domain || '';
                                urlInput.value = service.url || '';

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
