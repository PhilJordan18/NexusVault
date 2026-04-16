<x-layouts.app>

    <div class="max-w-md mx-auto bg-white/5 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl">

        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-nexus-500/10 rounded-2xl flex items-center justify-center border border-nexus-500/30">
                <i class="fa-solid fa-shield-halved text-5xl text-nexus-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Two-Factor Authentication</h1>
        <p class="text-white/60 text-center mb-8">Enter the 6-digit code from your authenticator app</p>

        <form method="POST" action="{{ route('mfa.verify') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm text-white/70 mb-2">Verification Code</label>
                <input type="text" name="code" maxlength="6"
                       class="w-full bg-white/10 border border-white/10 focus:border-nexus-500 rounded-2xl py-4 text-center text-3xl tracking-widest font-mono text-white outline-none"
                       placeholder="000000" required autofocus>
                @error('code')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-gradient-to-r from-nexus-600 to-nexus-500 hover:from-nexus-700 hover:to-nexus-600 text-white font-semibold py-4 rounded-2xl text-lg transition">
                Verify & Continue
            </button>
        </form>

        <div class="text-center mt-8">
            <a href="{{ route('logout') }}" class="text-white/50 hover:text-white transition">
                ← Sign in with a different account
            </a>
        </div>

    </div>

</x-layouts.app>
