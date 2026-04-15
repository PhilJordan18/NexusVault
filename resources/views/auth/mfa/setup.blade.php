<x-layouts.auth>
    <div class="bg-white/5 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl">
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-nexus-500/10 rounded-2xl flex items-center justify-center border border-nexus-500/30">
                <i class="fa-solid fa-shield-halved text-5xl text-nexus-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Two-Factor Authentication</h1>
        <p class="text-white/60 text-center mb-8">Add an extra layer of security</p>

        <div class="space-y-6">
            <div class="bg-white/5 rounded-2xl p-6 border border-white/10">
                <h2 class="text-lg font-medium mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-qrcode text-nexus-500"></i>
                    Scan QR Code
                </h2>

                <div class="flex justify-center mb-4">
                    <div class="bg-white p-4 rounded-xl">
                        {!! $qrCode !!}
                    </div>
                </div>

                <p class="text-white/60 text-sm text-center mb-4">
                    Scan this QR code with Google Authenticator, Authy, or any TOTP app
                </p>

                <div class="bg-nexus-500/10 border border-nexus-500/30 rounded-xl p-4">
                    <p class="text-white/60 text-xs mb-1">Manual setup code:</p>
                    <code class="text-nexus-500 font-mono text-lg break-all">{{ $secret }}</code>
                </div>
            </div>

            <form method="POST" action="{{ route('mfa.setup.verify') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="secret" value="{{ $secret }}">

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
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-nexus-600 to-nexus-500 hover:from-nexus-700 hover:to-nexus-600 text-white font-semibold py-4 rounded-2xl text-lg transition shadow-lg shadow-nexus-500/30">
                    Verify & Enable
                </button>
            </form>

            <form method="POST" action="{{ route('mfa.skip') }}" class="text-center">
                @csrf
                <button type="submit" class="text-white/50 hover:text-white transition">
                    Skip for now
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>
