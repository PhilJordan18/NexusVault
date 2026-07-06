<x-layouts.auth>
    <div class="card rounded-2xl p-6 sm:p-8">
        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                <i class="fa-solid fa-envelope-circle-check text-3xl text-emerald-400"></i>
            </div>
        </div>

        <h1 class="mb-1 text-center text-3xl font-semibold">{{ __('Verify Your Email') }}</h1>
        <p class="mb-8 text-center text-[var(--text-secondary)]">
            {{ __("We've sent a verification link to your email address.") }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-center text-emerald-400">
                {{ __('A new verification link has been sent!') }}
            </div>
        @endif

        <div class="space-y-4">
            <p class="text-center text-[var(--text-secondary)]">
                {{ __('Please check your inbox and click the verification link to continue.') }}
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="text-center">
                @csrf
                <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>{{ __('Resend verification email') }}</span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit"
                        class="text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]">
                    <i class="fa-solid fa-arrow-left mr-1 text-xs"></i>
                    {{ __('Log out and try another account') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>
