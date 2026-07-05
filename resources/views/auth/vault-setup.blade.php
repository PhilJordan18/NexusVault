<x-layouts.auth>
    <div class="card rounded-3xl p-8">
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                <i class="fa-solid fa-vault text-5xl text-emerald-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">{{ __('Create your encrypted vault') }}</h1>
        <p class="text-[var(--text-secondary)] text-center mb-8">
            {{ __('Choose a vault password that is different from your login method.') }}
        </p>

        <form method="POST" action="{{ route('vault.setup.store') }}" id="vault-setup-form">
            @csrf

            <div class="mb-5">
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('Vault password') }}</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" id="vault_password"
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 pl-11 pr-12 outline-none transition"
                           placeholder="••••••••"
                           autocomplete="new-password"
                           minlength="12"
                           required
                           autofocus>
                    <button type="button" id="toggle-password-vault"
                            data-confirm-target="vault_password_confirmation"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] hover:text-emerald-500 transition">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div id="strength-container" class="mt-3 hidden">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-[var(--text-secondary)]">{{ __('Vault password strength') }}</span>
                        <span id="strength-text" class="font-medium">{{ __('Calculating...') }}</span>
                    </div>
                    <div class="h-2 bg-[var(--bg-input)] rounded-2xl overflow-hidden">
                        <div id="strength-bar" class="h-2 w-0 transition-all duration-300 bg-emerald-500"></div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('Confirm vault password') }}</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" id="vault_password_confirmation"
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 pl-11 pr-12 outline-none transition"
                           placeholder="••••••••"
                           autocomplete="new-password"
                           minlength="12"
                           required>
                </div>
            </div>

            <button type="button" id="generate-vault-password"
                    data-password-generate
                    data-password-target="vault_password"
                    data-password-confirm-target="vault_password_confirmation"
                    data-password-min-length="12"
                    class="w-full flex items-center justify-center gap-2 text-emerald-500 hover:text-emerald-400 text-sm font-medium mb-6 transition">
                <i class="fa-solid fa-dice"></i>
                <span>{{ __('Generate strong vault password') }}</span>
            </button>

            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-2xl text-lg transition">
                {{ __('Create encrypted vault') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition">
                {{ __('Sign in with a different account') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
