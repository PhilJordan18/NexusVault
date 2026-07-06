<x-layouts.app>
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-400">
                    <i class="fa-solid fa-fingerprint"></i>
                    <span>{{ __('Passwordless login') }}</span>
                </div>
                <h1 class="text-3xl font-semibold tracking-normal sm:text-4xl">{{ __('Passkeys') }}</h1>
                <p class="mt-2 text-[var(--text-secondary)]">{{ __('Manage devices you use to sign in without a password.') }}</p>
            </div>

            <button id="register-passkey-btn"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-emerald-700 active:scale-[0.985]">
                <i class="fa-solid fa-key"></i>
                <span>{{ __('Add new Passkey') }}</span>
            </button>
        </header>

        <section class="card rounded-2xl p-6">
            <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold">{{ __('Your Passkeys') }}</h2>
                    <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ __('Passwordless authentication made simple and secure.') }}</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-3 py-1.5 text-sm text-[var(--text-secondary)]">
                    <i class="fa-solid fa-fingerprint text-emerald-400"></i>
                    {{ auth()->user()->webAuthnCredentials->count() }} {{ trans_choice('passkey_count', auth()->user()->webAuthnCredentials->count()) }}
                </span>
            </div>

            <div class="space-y-3">
                @forelse(auth()->user()->webAuthnCredentials as $credential)
                    @php
                        $lastUsedAt = $credential->last_used_at
                            ? \Illuminate\Support\Carbon::parse($credential->last_used_at)->diffForHumans()
                            : __('Never');
                    @endphp

                    <div class="flex flex-col gap-4 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10">
                                <i class="fa-solid fa-fingerprint text-xl text-emerald-400"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-[var(--text-primary)]">{{ $credential->alias ?? __('Unnamed device') }}</p>
                                <p class="mt-0.5 text-xs text-[var(--text-secondary)]">
                                    {{ __('Created') }} {{ $credential->created_at->format('M d, Y') }} • {{ __('Last used') }}: {{ $lastUsedAt }}
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('webauthn.destroy', $credential) }}" method="POST"
                              onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Delete this passkey?')) }})">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm text-red-500 transition hover:bg-red-500/10 hover:text-red-400 sm:w-auto">
                                <i class="fa-solid fa-trash text-sm"></i>
                                <span>{{ __('Delete') }}</span>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-[var(--border-color)] py-12 text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--bg-input)]">
                            <i class="fa-solid fa-fingerprint text-3xl text-[var(--text-secondary)]"></i>
                        </div>
                        <h3 class="text-lg font-semibold">{{ __('No passkeys registered yet.') }}</h3>
                        <p class="mt-2 text-sm text-[var(--text-secondary)]">{{ __('Add one below to sign in without a password.') }}</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
