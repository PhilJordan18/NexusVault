<x-layouts.auth>
    <div class="card rounded-3xl p-8">
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                <i class="fa-solid fa-key text-5xl text-emerald-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Hi, <span id="user-email" class="text-emerald-500"></span> !</h1>
        <p class="text-[var(--text-secondary)] text-center mb-8">Enter your password</p>

        <form method="POST" action="{{ route('login.authenticate.password') }}">
            @csrf
            <input type="hidden" name="email" id="hidden-email">

            <div class="mb-6">
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">Password</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" name="password" id="password"
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 pl-11 pr-12 outline-none transition"
                           placeholder="•••••••" required>

                    <button type="button" id="toggle-password"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] hover:text-emerald-500 transition">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                @error('password')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-2xl text-lg transition">
                Sign In
            </button>

            <div class="text-center mt-4">
                <a href="{{ route('password.request') }}" class="text-sm text-emerald-500 hover:text-emerald-400 transition">
                    Forgot your password?
                </a>
            </div>
        </form>

        <a href="{{ route('login') }}" class="block text-center text-[var(--text-secondary)] mt-6 hover:text-[var(--text-primary)]">← Change email address</a>
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
