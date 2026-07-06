<x-layouts.auth>
    <div class="mx-auto">
        <div class="card rounded-2xl p-6 sm:p-8">

            <div class="mb-6 flex justify-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                    <i class="fa-solid fa-shield-halved text-3xl text-emerald-400"></i>
                </div>
            </div>

            <h1 class="mb-1 text-center text-3xl font-semibold">{{ __('Two-Factor Authentication') }}</h1>
            <p class="mb-8 text-center text-[var(--text-secondary)]">{{ __('Add an extra layer of security to your account') }}</p>

            <div class="mb-8 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] p-5">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-medium">
                    <i class="fa-solid fa-qrcode text-emerald-400"></i>
                    {{ __('Scan this QR code') }}
                </h2>

                <div class="my-6 flex min-h-[240px] items-center justify-center rounded-xl bg-white p-4">
                    @if($qrUrl)
                        <img src="{{ $qrUrl }}" alt="{{ __('MFA QR Code') }}" class="max-w-[220px] shadow-md">
                    @else
                        <div class="text-center text-red-500">
                            <i class="fa-solid fa-triangle-exclamation mb-2 text-3xl"></i>
                            <p>{{ __('QR Code failed to load') }}</p>
                        </div>
                    @endif
                </div>

                <div class="text-center">
                    <p class="mb-2 text-sm text-[var(--text-secondary)]">{{ __('Or enter this code manually:') }}</p>
                    <code class="break-all font-mono text-lg text-emerald-400 sm:text-xl">{{ $totp_secret }}</code>
                </div>
            </div>

            <form method="POST" action="{{ route('mfa.setup.verify') }}" class="space-y-6">
                @csrf
                <div>
                    <label class="mb-2 block text-sm text-[var(--text-secondary)]">{{ __('Verification Code') }}</label>
                    <input type="text" name="code" maxlength="6"
                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] py-3.5 text-center font-mono text-3xl tracking-widest outline-none focus:border-emerald-500"
                           placeholder="000000" required autofocus>
                    @error('code')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white transition hover:bg-emerald-700">
                    {{ __('Verify & Enable MFA') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>
