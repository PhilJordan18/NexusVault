<x-layouts.auth>
    <div class="max-w-md mx-auto">
        <div class="card rounded-3xl p-8">

            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                    <i class="fa-solid fa-shield-halved text-5xl text-emerald-500"></i>
                </div>
            </div>

            <h1 class="text-3xl font-semibold text-center mb-1">Two-Factor Authentication</h1>
            <p class="text-[var(--text-secondary)] text-center mb-8">Add an extra layer of security to your account</p>

            <div class="card bg-[var(--bg-input)] border border-[var(--border-color)] rounded-2xl p-6 mb-8">
                <h2 class="text-lg font-medium mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-qrcode text-emerald-500"></i>
                    Scan this QR code
                </h2>

                <div class="flex justify-center my-6 bg-white p-4 rounded-xl min-h-[240px] items-center">
                    @if($qrUrl)
                        <img src="{{ $qrUrl }}" alt="MFA QR Code" class="max-w-[220px] shadow-md">
                    @else
                        <div class="text-red-500 text-center">
                            <i class="fa-solid fa-triangle-exclamation text-3xl mb-2"></i>
                            <p>QR Code failed to load</p>
                        </div>
                    @endif
                </div>

                <div class="text-center">
                    <p class="text-[var(--text-secondary)] text-sm mb-2">Or enter this code manually:</p>
                    <code class="font-mono text-emerald-500 text-xl tracking-[4px] break-all">{{ $totp_secret }}</code>
                </div>
            </div>

            <form method="POST" action="{{ route('mfa.setup.verify') }}" class="space-y-6">
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
                    Verify & Enable MFA
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>
