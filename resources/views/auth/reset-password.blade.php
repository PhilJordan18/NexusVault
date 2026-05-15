<x-layouts.auth>
    <div class="card rounded-3xl p-8 max-w-md mx-auto">

        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                <i class="fa-solid fa-key text-5xl text-emerald-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Reset Password</h1>
        <p class="text-[var(--text-secondary)] text-center mb-8">Create a new strong password</p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <!-- New Password -->
            <div class="mb-5">
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">New Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 px-5 pr-12 outline-none">
                    <button type="button" id="toggle-password"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] hover:text-emerald-500">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">Confirm New Password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 px-5 outline-none">
            </div>

            <!-- Password Generator + Strength -->
            <div class="mb-6">
                <button type="button" id="generate-password-btn"
                        class="w-full flex items-center justify-center gap-2 text-emerald-500 hover:text-emerald-400 text-sm font-medium mb-3 transition">
                    <i class="fa-solid fa-dice"></i>
                    <span>Generate strong password</span>
                </button>

                <div id="strength-container" class="hidden">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-[var(--text-secondary)]">Password strength</span>
                        <span id="strength-text" class="font-medium">Very weak</span>
                    </div>
                    <div class="h-2 bg-[var(--bg-input)] rounded-2xl overflow-hidden">
                        <div id="strength-bar" class="h-2 w-0 transition-all duration-300 bg-emerald-500 rounded-2xl"></div>
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-2xl text-lg transition">
                Reset Password
            </button>
        </form>
    </div>
</x-layouts.auth>

<script>
    // Password generator
    document.getElementById('generate-password-btn')?.addEventListener('click', async () => {
        const res = await fetch("{{ route('password.generate') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ length: 18, upper: true, lower: true, numbers: true, symbols: true })
        });
        const data = await res.json();
        document.getElementById('password').value = data.password;
        document.getElementById('password_confirmation').value = data.password;
        updateStrength(data.password);
    });

    async function updateStrength(password) {
        const container = document.getElementById('strength-container');
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');

        if (!password) {
            container.classList.add('hidden');
            return;
        }
        container.classList.remove('hidden');

        const res = await fetch("{{ route('password.entropy') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ password })
        });
        const result = await res.json();

        bar.style.width = Math.min(result.entropy, 100) + '%';
        text.textContent = result.label;

        if (result.strength === 'very_strong') bar.style.backgroundColor = '#10b981';
        else if (result.strength === 'strong') bar.style.backgroundColor = '#22c55e';
        else if (result.strength === 'medium') bar.style.backgroundColor = '#eab308';
        else bar.style.backgroundColor = '#ef4444';
    }

    document.getElementById('password')?.addEventListener('input', (e) => {
        updateStrength(e.target.value);
    });
</script>
