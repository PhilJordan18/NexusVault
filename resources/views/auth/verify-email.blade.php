<x-layouts.auth>
    <div class="bg-white/5 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl">
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-nexus-500/10 rounded-2xl flex items-center justify-center border border-nexus-500/30">
                <i class="fa-solid fa-envelope-circle-check text-5xl text-nexus-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Verify Your Email</h1>
        <p class="text-white/60 text-center mb-8">
            We've sent a verification link to your email address.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-6 p-4 bg-nexus-500/10 border border-nexus-500/30 rounded-2xl text-nexus-500 text-center">
                A new verification link has been sent!
            </div>
        @endif

        <div class="space-y-4">
            <p class="text-white/70 text-center">
                Please check your inbox and click the verification link to continue.
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="text-center">
                @csrf
                <button type="submit"
                        class="text-nexus-500 hover:text-nexus-400 underline transition">
                    Resend verification email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit"
                        class="text-white/50 hover:text-white transition">
                    ← Log out and try another account
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>
