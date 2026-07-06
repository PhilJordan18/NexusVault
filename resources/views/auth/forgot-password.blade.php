<x-layouts.auth>
    <div class="card mx-auto rounded-2xl p-6 sm:p-8">

        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                <i class="fa-solid fa-key text-3xl text-emerald-400"></i>
            </div>
        </div>

        <h1 class="mb-1 text-center text-3xl font-semibold">{{ __('Forgot Password?') }}</h1>
        <p class="mb-8 text-center text-[var(--text-secondary)]">{{ __("We'll send you a reset link") }}</p>

        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-center text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-6">
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Email address') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 outline-none focus:border-emerald-500">
                @error('email')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                {{ __('Send Reset Link') }}
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]">
                <i class="fa-solid fa-arrow-left mr-1 text-xs"></i>
                {{ __('Back to login') }}
            </a>
        </div>
    </div>
</x-layouts.auth>
