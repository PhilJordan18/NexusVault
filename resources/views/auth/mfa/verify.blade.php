<x-layouts.auth>
    <div class="max-w-md mx-auto card rounded-3xl p-8">

        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                <i class="fa-solid fa-shield-halved text-5xl text-emerald-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Two-Factor Authentication</h1>
        <p class="text-[var(--text-secondary)] text-center mb-8">Enter the 6-digit code from your authenticator app</p>

        <form method="POST" action="{{ route('mfa.verify') }}" class="space-y-6">
            @csrf
            <div>
                <label class="block text-sm text-[var(--text-secondary)] mb-2">Verification Code</label>
                <input type="text" name="code" maxlength="6"
                       class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 text-center text-3xl tracking-widest font-mono outline-none"
                       placeholder="000000" required autofocus>
                @error('code')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-2xl text-lg transition">
                Verify & Continue
            </button>
        </form>

        <div class="text-center mt-8">
            <a href="{{ route('logout') }}" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition">
                ← Sign in with a different account
            </a>
        </div>
    </div>
</x-layouts.auth>
