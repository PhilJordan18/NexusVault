{{-- Confirmation fields for destructive actions: login password or a current TOTP code. --}}
@php($canUsePassword = ! $user->is_oauth)
@php($canUseTotp = (bool) $user->mfa_enabled)

@if ($canUsePassword)
    <div>
        <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Login password') }}</label>
        <input type="password" name="current_password" autocomplete="current-password"
               class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-4 py-3 outline-none transition focus:border-red-500"
               placeholder="••••••••">
    </div>
@endif

@if ($canUseTotp)
    <div>
        <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">
            {{ $canUsePassword ? __('Or a current authenticator code') : __('Authenticator code') }}
        </label>
        <input type="text" name="totp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
               class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-4 py-3 text-center font-mono tracking-widest outline-none transition focus:border-red-500"
               placeholder="000000">
    </div>
@endif

@if (! $canUsePassword && ! $canUseTotp)
    <p class="text-sm text-[var(--text-secondary)]">
        {{ __('Enable two-factor authentication first: this action must be confirmed with an authenticator code.') }}
    </p>
@endif

@error('current_password')
    <p class="text-sm text-red-500">{{ $message }}</p>
@enderror
