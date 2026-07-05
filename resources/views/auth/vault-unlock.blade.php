<x-layouts.auth>
    <div class="card rounded-3xl p-8">
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                <i class="fa-solid fa-lock text-5xl text-emerald-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">{{ __('Unlock your vault') }}</h1>
        <p class="text-[var(--text-secondary)] text-center mb-8">
            {{ __('Your account is signed in. Enter your vault password to decrypt your data for this session.') }}
        </p>

        <form method="POST" action="{{ route('vault.unlock.store') }}" id="vault-unlock-form" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('Vault password') }}</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" id="vault_password" @if(!auth()->user()?->usesClientSideVault()) name="vault_password" @endif
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 pl-11 pr-12 outline-none transition"
                           placeholder="•••••••" autocomplete="current-password" autofocus>

                    <button type="button" id="toggle-password-vault"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] hover:text-emerald-500 transition">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                @error('vault_password')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-2xl text-lg transition">
                {{ __('Unlock Vault') }}
            </button>
        </form>

        @if(auth()->user()?->usesClientSideVault())
            <details class="mt-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4">
                <summary class="cursor-pointer select-none text-sm font-medium text-emerald-300">
                    {{ __('Use recovery key') }}
                </summary>

                <form method="POST" action="{{ route('vault.unlock.store') }}" id="vault-recovery-form" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('Recovery key') }}</label>
                        <textarea id="recovery_key"
                                  class="min-h-24 w-full resize-y bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-3 px-4 font-mono text-sm outline-none transition"
                                  placeholder="NV-0000-0000-0000-0000"
                                  autocomplete="off"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full border border-emerald-500/30 hover:bg-emerald-500/10 text-emerald-200 font-medium py-3 rounded-2xl transition">
                        {{ __('Unlock with recovery key') }}
                    </button>
                </form>
            </details>

            <details class="mt-4 rounded-2xl border border-red-500/20 bg-red-500/10 p-4">
                <summary class="cursor-pointer select-none text-sm font-medium text-red-300">
                    {{ __('Reset encrypted vault') }}
                </summary>

                <form method="POST" action="{{ route('vault.reset.store') }}" id="vault-reset-form" class="mt-4 space-y-4">
                    @csrf

                    <p class="text-sm text-red-200/90">
                        {{ __('This permanently deletes your vault items and creates a new empty encrypted vault.') }}
                    </p>

                    <div>
                        <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('New vault password') }}</label>
                        <input type="password" id="reset_vault_password"
                               class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-red-500 rounded-2xl py-3 px-4 outline-none transition"
                               placeholder="••••••••"
                               autocomplete="new-password">
                    </div>

                    <div>
                        <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('Confirm new vault password') }}</label>
                        <input type="password" id="reset_vault_password_confirmation"
                               class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-red-500 rounded-2xl py-3 px-4 outline-none transition"
                               placeholder="••••••••"
                               autocomplete="new-password">
                    </div>

                    <div>
                        <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('Type RESET to confirm') }}</label>
                        <input type="text" name="confirmation"
                               class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-red-500 rounded-2xl py-3 px-4 outline-none transition"
                               placeholder="RESET"
                               autocomplete="off">

                        @error('confirmation')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-2xl transition">
                        {{ __('Delete and reset vault') }}
                    </button>
                </form>
            </details>

            <script>
                window.nexusVaultKeyEnvelope = {{ Illuminate\Support\Js::from(auth()->user()->vault_key_envelope) }};
                window.nexusVaultRecoveryEnvelope = {{ Illuminate\Support\Js::from(auth()->user()->vault_recovery_envelope) }};
            </script>
        @endif

        @if($allowsLegacyUnlock)
            <div class="mt-6 rounded-2xl border border-yellow-500/20 bg-yellow-500/10 p-4">
                <p class="text-sm text-yellow-300">
                    {{ __('This OAuth vault still uses the legacy server-side unlock. We will migrate it to true zero-knowledge in the next steps.') }}
                </p>

                <form method="POST" action="{{ route('vault.unlock.store') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="legacy_unlock" value="1">
                    <button type="submit"
                            class="w-full border border-yellow-500/30 hover:bg-yellow-500/10 text-yellow-200 font-medium py-3 rounded-2xl transition">
                        {{ __('Unlock legacy OAuth vault') }}
                    </button>
                </form>
            </div>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition">
                {{ __('Sign in with a different account') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
