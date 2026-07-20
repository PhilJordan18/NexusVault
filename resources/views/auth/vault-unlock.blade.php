<x-layouts.auth>
    <div class="card rounded-2xl p-6 sm:p-8">
        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                <i class="fa-solid fa-lock text-3xl text-emerald-400"></i>
            </div>
        </div>

        <h1 class="mb-1 text-center text-3xl font-semibold">{{ __('Unlock your vault') }}</h1>
        <p class="mb-8 text-center text-[var(--text-secondary)]">
            {{ __('Your account is signed in. Enter your vault password to decrypt your data for this session.') }}
        </p>

        <form method="POST" action="{{ route('vault.unlock.store') }}" id="vault-unlock-form" class="space-y-6">
            @csrf

            <div>
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Vault password') }}</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" id="vault_password" @if(!auth()->user()?->usesClientSideVault()) name="vault_password" @endif
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] py-3.5 pl-11 pr-12 outline-none transition focus:border-emerald-500"
                           placeholder="•••••••" autocomplete="current-password" autofocus>

                    <button type="button" id="toggle-password-vault"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] transition hover:text-emerald-500">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                @error('vault_password')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                {{ __('Unlock Vault') }}
            </button>
        </form>

        @if(auth()->user()?->usesClientSideVault())
            <details class="mt-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4">
                <summary class="cursor-pointer select-none text-sm font-medium text-emerald-300">
                    <i class="fa-solid fa-key mr-2"></i>{{ __('Use recovery key') }}
                </summary>

                <form method="POST" action="{{ route('vault.unlock.store') }}" id="vault-recovery-form" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Recovery key') }}</label>
                        <textarea id="recovery_key"
                                  class="min-h-24 w-full resize-y rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-4 py-3 font-mono text-sm outline-none transition focus:border-emerald-500"
                                  placeholder="NV-0000-0000-0000-0000"
                                  autocomplete="off"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full rounded-2xl border border-emerald-500/30 py-3 font-medium text-emerald-200 transition hover:bg-emerald-500/10">
                        {{ __('Unlock with recovery key') }}
                    </button>
                </form>
            </details>

            <details class="mt-4 rounded-2xl border border-red-500/20 bg-red-500/10 p-4">
                <summary class="cursor-pointer select-none text-sm font-medium text-red-300">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ __('Reset encrypted vault') }}
                </summary>

                <form method="POST" action="{{ route('vault.reset.store') }}" id="vault-reset-form" class="mt-4 space-y-4">
                    @csrf

                    <p class="text-sm text-red-200/90">
                        {{ __('This permanently deletes your vault items and creates a new empty encrypted vault.') }}
                    </p>

                    <div>
                        <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('New vault password') }}</label>
                        <input type="password" id="reset_vault_password"
                               class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-4 py-3 outline-none transition focus:border-red-500"
                               placeholder="••••••••"
                               autocomplete="new-password">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Confirm new vault password') }}</label>
                        <input type="password" id="reset_vault_password_confirmation"
                               class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-4 py-3 outline-none transition focus:border-red-500"
                               placeholder="••••••••"
                               autocomplete="new-password">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Type RESET to confirm') }}</label>
                        <input type="text" name="confirmation"
                               class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-4 py-3 outline-none transition focus:border-red-500"
                               placeholder="RESET"
                               autocomplete="off">

                        @error('confirmation')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full rounded-2xl bg-red-600 py-3 font-semibold text-white transition hover:bg-red-700">
                        {{ __('Delete and reset vault') }}
                    </button>
                </form>
            </details>

            <script>
                window.nexusVaultKeyEnvelope = {{ Illuminate\Support\Js::from(auth()->user()->vault_key_envelope) }};
                window.nexusVaultRecoveryEnvelope = {{ Illuminate\Support\Js::from(auth()->user()->vault_recovery_envelope) }};
            </script>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]">
                {{ __('Sign in with a different account') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
