<x-layouts.app>
    <div class="max-w-3xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-semibold flex items-center gap-3">
                <i class="fa-solid fa-fingerprint text-emerald-500"></i>
                Passkeys
            </h1>
            <p class="text-[var(--text-secondary)] mt-2">Passwordless authentication made simple and secure.</p>
        </div>

        <!-- Explanation -->
        <div class="card rounded-3xl p-8 mb-8">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-3">
                <i class="fa-solid fa-info-circle text-emerald-500"></i>
                What is a Passkey?
            </h2>
            <div class="text-[var(--text-secondary)] space-y-4 leading-relaxed">
                <p>
                    A <strong class="text-[var(--text-primary)]">Passkey</strong> is a modern, passwordless way to sign in to your accounts.
                    Instead of typing a password, you use the built-in security of your device —
                    such as Face ID, fingerprint, PIN, or a physical security key (like YubiKey).
                </p>
                <p>
                    Passkeys are <strong class="text-[var(--text-primary)]">much more secure</strong> than traditional passwords because they are
                    resistant to phishing, data breaches, and password reuse. They work seamlessly across
                    your devices and never leave your device.
                </p>
                <p class="text-sm text-[var(--text-secondary)]">
                    Supported on: iPhone, iPad, Mac, Android, Windows, and most modern browsers.
                </p>
            </div>
        </div>

        <!-- Passkeys List -->
        <div class="card rounded-3xl p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold">Your Passkeys</h2>
                    <p class="text-sm text-[var(--text-secondary)]">Manage devices you use to sign in without a password.</p>
                </div>
            </div>

            <!-- LIST OF PASSKEYS -->
            <div class="space-y-3 mb-8">
                @forelse(auth()->user()->webAuthnCredentials as $credential)
                    <div class="flex items-center justify-between bg-[var(--bg-input)] border border-[var(--border-color)] rounded-2xl px-5 py-4">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-emerald-500/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-fingerprint text-emerald-500 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-[var(--text-primary)]">{{ $credential->alias ?? 'Unnamed device' }}</p>
                                <p class="text-xs text-[var(--text-secondary)] mt-0.5">
                                    Created {{ $credential->created_at->format('M d, Y') }}
                                    • Last used: {{ $credential->last_used_at?->diffForHumans() ?? 'Never' }}
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('webauthn.destroy', $credential) }}" method="POST"
                              onsubmit="return confirm('Delete this passkey?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-red-500 hover:text-red-600 px-4 py-2 rounded-2xl hover:bg-red-500/10 transition flex items-center gap-2">
                                <i class="fa-solid fa-trash text-sm"></i>
                                <span class="hidden sm:inline text-sm">Delete</span>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <div class="mx-auto w-16 h-16 bg-[var(--bg-input)] rounded-full flex items-center justify-center mb-4">
                            <i class="fa-solid fa-fingerprint text-3xl text-[var(--text-secondary)]"></i>
                        </div>
                        <p class="text-[var(--text-secondary)]">No passkeys registered yet.</p>
                        <p class="text-sm text-[var(--text-secondary)] mt-1">Add one below to sign in without a password.</p>
                    </div>
                @endforelse
            </div>

            <!-- ADD PASSKEY BUTTON -->
            <div class="text-center">
                <button id="register-passkey-btn"
                        class="inline-flex items-center justify-center gap-3 w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold px-8 py-3.5 rounded-2xl text-base transition-all cursor-pointer active:scale-[0.985]">
                    <i class="fa-solid fa-key"></i>
                    <span>Add new Passkey</span>
                </button>

                <p class="text-xs text-[var(--text-secondary)] mt-4">
                    Works with iPhone, Android, Windows Hello, YubiKey, and more.
                </p>
            </div>
        </div>

    </div>
</x-layouts.app>
