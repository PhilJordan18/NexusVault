<x-layouts.auth>
    <div class="card rounded-3xl p-8 max-w-md mx-auto">

        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                <i class="fa-solid fa-key text-5xl text-emerald-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">{{ __('Reset Password') }}</h1>
        <p class="text-[var(--text-secondary)] text-center mb-8">{{ __('Create a new strong password') }}</p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <!-- New Password -->
            <div class="mb-5">
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('New Password') }}</label>
                <div class="relative">
                    <input type="password" name="password" id="password" minlength="8" required
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 px-5 pr-12 outline-none">
                    <button type="button" id="toggle-password"
                            data-confirm-target="password_confirmation"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] hover:text-emerald-500">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">{{ __('Confirm New Password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" minlength="8" required
                       class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 px-5 pr-12 outline-none">
            </div>

            <!-- Password Generator + Strength -->
            <div class="mb-6">
                <button type="button" id="generate-password-btn"
                        data-password-generate
                        data-password-target="password"
                        data-password-confirm-target="password_confirmation"
                        data-password-min-length="8"
                        class="w-full flex items-center justify-center gap-2 text-emerald-500 hover:text-emerald-400 text-sm font-medium mb-3 transition">
                    <i class="fa-solid fa-dice"></i>
                    <span>{{ __('Generate strong password') }}</span>
                </button>

                <div id="strength-container" class="hidden">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-[var(--text-secondary)]">{{ __('Password strength') }}</span>
                        <span id="strength-text" class="font-medium">{{ __('Very weak') }}</span>
                    </div>
                    <div class="h-2 bg-[var(--bg-input)] rounded-2xl overflow-hidden">
                        <div id="strength-bar" class="h-2 w-0 transition-all duration-300 bg-emerald-500 rounded-2xl"></div>
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-2xl text-lg transition">
                {{ __('Reset Password') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
