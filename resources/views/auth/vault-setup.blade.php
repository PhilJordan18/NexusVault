<x-layouts.auth>
    <div class="card rounded-2xl p-6 sm:p-8">
        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                <i class="fa-solid fa-vault text-3xl text-emerald-400"></i>
            </div>
        </div>

        <h1 class="mb-1 text-center text-3xl font-semibold">{{ __('Create your encrypted vault') }}</h1>
        <p class="mb-8 text-center text-[var(--text-secondary)]">
            {{ __('Choose a vault password that is different from your login method.') }}
        </p>

        <form method="POST" action="{{ route('vault.setup.store') }}" id="vault-setup-form">
            @csrf

            <div class="mb-5">
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Vault password') }}</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" id="vault_password"
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] py-3.5 pl-11 pr-12 outline-none transition focus:border-emerald-500"
                           placeholder="••••••••"
                           autocomplete="new-password"
                           minlength="12"
                           required
                           autofocus>
                    <button type="button" id="toggle-password-vault"
                            data-confirm-target="vault_password_confirmation"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] transition hover:text-emerald-500">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div id="strength-container" class="mt-3 hidden">
                    <div class="mb-1 flex justify-between text-xs">
                        <span class="text-[var(--text-secondary)]">{{ __('Vault password strength') }}</span>
                        <span id="strength-text" class="font-medium">{{ __('Calculating...') }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-2xl bg-[var(--bg-input)]">
                        <div id="strength-bar" class="h-2 w-0 bg-emerald-500 transition-all duration-300"></div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Confirm vault password') }}</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" id="vault_password_confirmation"
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] py-3.5 pl-11 pr-12 outline-none transition focus:border-emerald-500"
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
                    class="mb-6 flex w-full items-center justify-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-400 transition hover:border-emerald-500/40 hover:bg-emerald-500/15">
                <i class="fa-solid fa-dice"></i>
                <span>{{ __('Generate strong vault password') }}</span>
            </button>

            <button type="submit"
                    class="w-full rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                {{ __('Create encrypted vault') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]">
                {{ __('Sign in with a different account') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
