<x-layouts.auth>
    <div class="card mx-auto rounded-2xl p-6 sm:p-8">

        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                <i class="fa-solid fa-key text-3xl text-emerald-400"></i>
            </div>
        </div>

        <h1 class="mb-1 text-center text-3xl font-semibold">{{ __('Reset Password') }}</h1>
        <p class="mb-8 text-center text-[var(--text-secondary)]">{{ __('Create a new strong password') }}</p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <!-- New Password -->
            <div class="mb-5">
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('New Password') }}</label>
                <div class="relative">
                    <input type="password" name="password" id="password" minlength="8" required
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 pr-12 outline-none focus:border-emerald-500">
                    <button type="button" id="toggle-password"
                            data-confirm-target="password_confirmation"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] transition hover:text-emerald-500">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Confirm New Password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" minlength="8" required
                       class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 pr-12 outline-none focus:border-emerald-500">
            </div>

            <!-- Password Generator + Strength -->
            <div class="mb-6">
                <button type="button" id="generate-password-btn"
                        data-password-generate
                        data-password-target="password"
                        data-password-confirm-target="password_confirmation"
                        data-password-min-length="8"
                        class="mb-3 flex w-full items-center justify-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-400 transition hover:border-emerald-500/40 hover:bg-emerald-500/15">
                    <i class="fa-solid fa-dice"></i>
                    <span>{{ __('Generate strong password') }}</span>
                </button>

                <div id="strength-container" class="hidden">
                    <div class="mb-1 flex justify-between text-xs">
                        <span class="text-[var(--text-secondary)]">{{ __('Password strength') }}</span>
                        <span id="strength-text" class="font-medium">{{ __('Very weak') }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-2xl bg-[var(--bg-input)]">
                        <div id="strength-bar" class="h-2 w-0 rounded-2xl bg-emerald-500 transition-all duration-300"></div>
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                {{ __('Reset Password') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
