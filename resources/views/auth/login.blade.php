<x-layouts.auth>
    <div class="card rounded-2xl p-6 sm:p-8">

        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                <i class="fa-solid fa-fingerprint text-3xl text-emerald-400"></i>
            </div>
        </div>

        <h1 class="mb-1 text-center text-3xl font-semibold">{{ __('Sign In') }}</h1>
        <p class="mb-8 text-center text-[var(--text-secondary)]">{{ __('Keep it all together') }}</p>

        <form method="POST" action="{{ route('login.authenticate.email') }}" id="login-form">
            @csrf

            <div class="mb-5">
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Email address') }}</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="text" name="email" id="email"
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] py-3.5 pl-11 pr-4 outline-none transition focus:border-emerald-500"
                           placeholder="myemail@service.com" required autofocus>
                </div>
                @error('email')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                <span>{{ __('Next') }}</span>
                <i class="fa-solid fa-arrow-right text-sm"></i>
            </button>
        </form>

        <button id="passkey-btn"
                class="mt-4 flex w-full items-center justify-center gap-3 rounded-2xl border border-[var(--border-color)] py-3.5 text-[var(--text-primary)] transition hover:border-emerald-500/50 hover:bg-[var(--bg-input)]">
            <i class="fa-solid fa-fingerprint text-xl text-emerald-400"></i>
            <span class="font-medium">{{ __('Sign in using passkey') }}</span>
        </button>

        <div class="my-5 flex items-center gap-4">
            <div class="h-px flex-1 bg-[var(--border-color)]"></div>
            <span class="text-sm text-[var(--text-secondary)]">{{ __('Or') }}</span>
            <div class="h-px flex-1 bg-[var(--border-color)]"></div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <a href="/auth/google"
               class="flex items-center justify-center gap-3 rounded-2xl border border-[var(--border-color)] py-3.5 transition hover:border-emerald-500/40 hover:bg-[var(--bg-input)]">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.51h5.92c-.25 1.22-.98 2.26-2.07 2.96v2.75h3.34c1.95-1.8 3.07-4.44 3.07-7.97z"/>
                    <path d="M12 23c3.1 0 5.69-1.03 7.58-2.78l-3.34-2.75c-.93.63-2.12 1-3.98 1-3.06 0-5.66-2.07-6.58-4.85H2.4v3.04C4.3 20.7 7.8 23 12 23z"/>
                    <path d="M5.42 14.42c-.25-.78-.4-1.6-.4-2.46s.15-1.68.4-2.46V6.23H2.4C1.5 8.07 1 10 1 12s.5 3.93 1.4 5.77l3.02-2.35z"/>
                    <path d="M12 4.78c1.72 0 3.27.6 4.48 1.58l3.36-3.36C17.7 1.58 15.1 1 12 1 7.8 1 4.3 3.3 2.4 6.23l3.02 2.35c.92-2.78 3.52-4.85 6.58-4.85z"/>
                </svg>
                <span class="font-medium">Google</span>
            </a>

            <a href="/auth/github"
               class="flex items-center justify-center gap-3 rounded-2xl border border-[var(--border-color)] py-3.5 transition hover:border-emerald-500/40 hover:bg-[var(--bg-input)]">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.868-.013-1.703-2.782.604-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.464-1.11-1.464-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.03-2.682-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.91-1.294 2.75-1.025 2.75-1.025.544 1.377.202 2.394.1 2.647.64.698 1.03 1.591 1.03 2.682 0 3.841-2.338 4.687-4.566 4.935.359.31.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482C19.137 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
                </svg>
                <span class="font-medium">GitHub</span>
            </a>
        </div>

        <p class="mt-8 text-center text-[var(--text-secondary)]">
            {{ __("Don't have an account?") }} <a href="{{ route('register') }}" class="text-emerald-500 hover:text-emerald-400">{{ __('Sign Up') }}</a>
        </p>
    </div>
</x-layouts.auth>
