<x-layouts.auth>
    <div class="card rounded-2xl p-6 sm:p-8">
        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                <i class="fa-solid fa-key text-3xl text-emerald-400"></i>
            </div>
        </div>

        <h1 class="mb-2 text-center text-3xl font-semibold">{{ __('Sign In') }}</h1>
        <p id="user-email" class="mx-auto mb-2 max-w-full truncate text-center text-sm font-medium text-emerald-400"></p>
        <p class="mb-8 text-center text-[var(--text-secondary)]">{{ __('Enter your password') }}</p>

        <form method="POST" action="{{ route('login.authenticate.password') }}">
            @csrf
            <input type="hidden" name="email" id="hidden-email">

            <div class="mb-6">
                <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Password') }}</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" name="password" id="password"
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] py-3.5 pl-11 pr-12 outline-none transition focus:border-emerald-500"
                           placeholder="•••••••" required>

                    <button type="button" id="toggle-password"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] transition hover:text-emerald-500">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                @error('password')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                {{ __('Sign In') }}
            </button>

            <div class="text-center mt-4">
                <a href="{{ route('password.request') }}" class="text-sm text-emerald-500 hover:text-emerald-400 transition">
                    {{ __('Forgot your password?') }}
                </a>
            </div>
        </form>

        <a href="{{ route('login') }}" class="mt-6 block text-center text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]">
            <i class="fa-solid fa-arrow-left mr-1 text-xs"></i>
            {{ __('Change email address') }}
        </a>
    </div>
</x-layouts.auth>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let email = urlParams.get('email') || localStorage.getItem('temp_email');
    if (email) {
        document.getElementById('user-email').textContent = email;
        document.getElementById('hidden-email').value = email;
        localStorage.setItem('temp_email', email);
    }
</script>
