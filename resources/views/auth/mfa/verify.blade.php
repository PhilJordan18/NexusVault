<x-layouts.auth>
    <div class="bg-white/5 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl">
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-nexus-500/10 rounded-2xl flex items-center justify-center border border-nexus-500/30">
                <i class="fa-solid fa-shield-halved text-5xl text-nexus-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Two-Factor Authentication</h1>
        <p class="text-white/60 text-center mb-8">Enter the code from your authenticator app</p>

        <form method="POST" action="{{ route('mfa.verify') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm text-white/70 mb-1.5">Verification Code</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-white/40">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="text" name="code"
                           class="w-full bg-white/10 border border-white/10 focus:border-nexus-500 rounded-2xl py-4 pl-11 pr-4 text-white placeholder:text-white/40 outline-none transition text-center text-2xl tracking-widest"
                           placeholder="000000" maxlength="6" required autofocus>
                </div>

                @error('code')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-gradient-to-r from-nexus-600 to-nexus-500 hover:from-nexus-700 hover:to-nexus-600 text-white font-semibold py-4 rounded-2xl text-lg transition shadow-lg shadow-nexus-500/30">
                Verify & Continue
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center mt-6">
            @csrf
            <button type="submit" class="text-white/50 hover:text-white transition">
                ← Sign in with a different account
            </button>
        </form>
    </div>
</x-layouts.auth>
