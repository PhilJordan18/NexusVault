<x-layouts.auth>
    <div class="card mx-auto rounded-2xl p-6 sm:p-8">

        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                <i class="fa-solid fa-shield-halved text-3xl text-emerald-400"></i>
            </div>
        </div>

        <h1 class="mb-1 text-center text-3xl font-semibold">{{ __('Two-Factor Authentication') }}</h1>
        <p class="mb-8 text-center text-[var(--text-secondary)]">{{ __('Enter the 6-digit code from your authenticator app') }}</p>

        <form method="POST" action="{{ route('mfa.verify') }}" class="space-y-6">
            @csrf
            <div>
                <label class="mb-2 block text-sm text-[var(--text-secondary)]">{{ __('Verification Code') }}</label>
                <input type="text" name="code" maxlength="6"
                       class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] py-3.5 text-center font-mono text-3xl tracking-widest outline-none focus:border-emerald-500"
                       placeholder="000000" required autofocus>
                @error('code')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                {{ __('Verify & Continue') }}
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="{{ route('logout') }}" class="text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]">
                <i class="fa-solid fa-arrow-left mr-1 text-xs"></i>
                {{ __('Sign in with a different account') }}
            </a>
        </div>
    </div>
</x-layouts.auth>
