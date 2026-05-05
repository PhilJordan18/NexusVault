<x-layouts.app>
    <div class="max-w-3xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-semibold flex items-center gap-3">
                <i class="fa-solid fa-fingerprint text-emerald-500"></i>
                Passkeys
            </h1>
            <p class="text-white/60 mt-2">Passwordless authentication made simple and secure.</p>
        </div>

        <!-- Explanation -->
        <div class="bg-white/5 border border-white/10 rounded-3xl p-8 mb-8">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-3">
                <i class="fa-solid fa-info-circle text-emerald-400"></i>
                What is a Passkey?
            </h2>
            <div class="text-white/80 space-y-4 leading-relaxed">
                <p>
                    A <strong>Passkey</strong> is a modern, passwordless way to sign in to your accounts.
                    Instead of typing a password, you use the built-in security of your device —
                    such as Face ID, fingerprint, PIN, or a physical security key (like YubiKey).
                </p>
                <p>
                    Passkeys are <strong>much more secure</strong> than traditional passwords because they are
                    resistant to phishing, data breaches, and password reuse. They work seamlessly across
                    your devices and never leave your device.
                </p>
                <p class="text-sm text-white/60">
                    Supported on: iPhone, iPad, Mac, Android, Windows, and most modern browsers.
                </p>
            </div>
        </div>

        <!-- Passkeys List -->
        <div class="bg-white/5 border border-white/10 rounded-3xl p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold">Your Passkeys</h2>
                    <p class="text-sm text-white/50">Manage devices you use to sign in without a password.</p>
                </div>
            </div>

            <!-- LIST OF PASSKEYS -->
            <div class="space-y-3 mb-8">
                @forelse(auth()->user()->webAuthnCredentials as $credential)
                    <div class="flex items-center justify-between bg-white/5 border border-white/10 rounded-2xl px-5 py-4">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-emerald-500/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-fingerprint text-emerald-500 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-white">{{ $credential->alias ?? 'Unnamed device' }}</p>
                                <p class="text-xs text-white/50 mt-0.5">
                                    Created {{ $credential->created_at->format('M d, Y') }}
                                    • Last used: {{ $credential->last_used_at?->diffForHumans() ?? 'Never' }}
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('webauthn.destroy', $credential->id) }}" method="POST"
                              onsubmit="return confirm('Delete this passkey?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-red-400 hover:text-red-500 px-4 py-2 rounded-2xl hover:bg-red-500/10 transition flex items-center gap-2">
                                <i class="fa-solid fa-trash text-sm"></i>
                                <span class="hidden sm:inline text-sm">Delete</span>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <div class="mx-auto w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-4">
                            <i class="fa-solid fa-fingerprint text-3xl text-white/30"></i>
                        </div>
                        <p class="text-white/60">No passkeys registered yet.</p>
                        <p class="text-sm text-white/40 mt-1">Add one below to sign in without a password.</p>
                    </div>
                @endforelse
            </div>

            <!-- ADD PASSKEY BUTTON -->
            <div class="text-center">
                <button id="register-passkey-btn"
                        class="inline-flex items-center justify-center gap-3 w-full sm:w-auto bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-white font-semibold px-8 py-3.5 rounded-2xl text-base transition-all cursor-pointer active:scale-[0.985]">
                    <i class="fa-solid fa-key"></i>
                    <span>Add new Passkey</span>
                </button>

                <p class="text-xs text-white/40 mt-4">
                    Works with iPhone, Android, Windows Hello, YubiKey, and more.
                </p>
            </div>
        </div>

    </div>
</x-layouts.app>
