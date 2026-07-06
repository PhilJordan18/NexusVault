<x-layouts.app>
    @php
        $userSessions = \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderBy('last_activity', 'desc')
            ->get();

        $otherSessionsCount = $userSessions->filter(fn ($session) => $session->id !== session()->getId())->count();
    @endphp

    <div class="mx-auto max-w-6xl space-y-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-400">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>{{ __('Security') }}</span>
                </div>
                <h1 class="text-3xl font-semibold tracking-normal sm:text-4xl">{{ __('Settings') }}</h1>
                <p class="mt-2 text-[var(--text-secondary)]">{{ __('Manage your account security and preferences') }}</p>
            </div>
        </header>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <section class="card rounded-2xl p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold">{{ __('Language') }}</h2>
                            <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ __('Choose the language used by NexusVault.') }}</p>
                        </div>

                        @include('partials.language-switch')
                    </div>
                </section>

                <section class="card rounded-2xl p-6">
                    <div class="mb-6 flex items-start gap-4">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border border-emerald-500/20 bg-emerald-500/10">
                            <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold">{{ __('Two-Factor Authentication') }}</h2>
                            <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ __('Passkeys & MFA') }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            @if(auth()->user()->mfa_enabled)
                                <p class="font-medium">{{ __('MFA is enabled with TOTP') }}</p>
                                <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ __('Your account is protected with a 6-digit code') }}</p>
                            @else
                                <p class="font-medium">{{ __('MFA is not enabled') }}</p>
                                <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ __('Add a 6-digit authenticator code to your login flow.') }}</p>
                            @endif
                        </div>

                        @if(auth()->user()->mfa_enabled)
                            <form method="POST" action="{{ route('mfa.disable') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-medium text-red-500 transition hover:bg-red-500/10 hover:text-red-400">
                                    <i class="fa-solid fa-ban"></i>
                                    <span>{{ __('Disable MFA') }}</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('mfa.setup') }}"
                               class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-emerald-700">
                                <i class="fa-solid fa-qrcode"></i>
                                <span>{{ __('Enable MFA') }}</span>
                            </a>
                        @endif
                    </div>

                    <a href="{{ route('passkeys.index') }}"
                       class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] p-4 transition hover:border-emerald-500/40">
                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10">
                                <i class="fa-solid fa-fingerprint text-emerald-400"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">{{ __('Passkeys') }}</h3>
                                <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ __('Use your fingerprint, Face ID or security key to sign in without a password.') }}</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right flex-shrink-0 text-sm text-[var(--text-secondary)]"></i>
                    </a>

                    @unless(auth()->user()->is_oauth)
                        <div class="mt-6 border-t border-[var(--border-color)] pt-6">
                            <h3 class="text-lg font-semibold">{{ __('Change Password') }}</h3>
                            <form method="POST" action="{{ route('settings.password.update') }}" class="mt-5 grid gap-4">
                                @csrf
                                <div>
                                    <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Current Password') }}</label>
                                    <input type="password" name="current_password"
                                           class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 focus:border-emerald-500" required>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('New Password') }}</label>
                                        <input type="password" name="new_password"
                                               class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 focus:border-emerald-500" required>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm text-[var(--text-secondary)]">{{ __('Confirm New Password') }}</label>
                                        <input type="password" name="new_password_confirmation"
                                               class="w-full rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-3.5 focus:border-emerald-500" required>
                                    </div>
                                </div>
                                <div>
                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-emerald-700">
                                        {{ __('Update Password') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endunless
                </section>

                <section class="card rounded-2xl p-6">
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border border-emerald-500/20 bg-emerald-500/10">
                                <i class="fa-solid fa-laptop text-emerald-400"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">{{ __('Active Sessions') }}</h2>
                                <p class="mt-1 text-sm text-[var(--text-secondary)]">
                                    {{ __('These are the devices currently logged into your account.') }}
                                </p>
                            </div>
                        </div>

                        <button id="logout-all-sessions-btn"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm transition
                                {{ $otherSessionsCount >= 2 ? 'bg-red-500/10 text-red-500 hover:bg-red-500/20' : 'bg-gray-500/10 text-gray-500 cursor-not-allowed' }}"
                            {{ $otherSessionsCount < 2 ? 'disabled' : '' }}>
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>{{ __('Logout from all other devices') }}</span>
                        </button>
                    </div>

                    <div id="active-sessions-list" class="space-y-3">
                        @forelse($userSessions as $session)
                            @php
                                $isCurrent = $session->id === session()->getId();
                                $lastActive = \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans();

                                $ua = strtolower($session->user_agent ?? '');
                                if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) {
                                    $deviceName = 'Mac';
                                    $deviceIcon = 'fa-laptop';
                                } elseif (str_contains($ua, 'iphone')) {
                                    $deviceName = 'iPhone';
                                    $deviceIcon = 'fa-mobile-screen-button';
                                } elseif (str_contains($ua, 'ipad')) {
                                    $deviceName = 'iPad';
                                    $deviceIcon = 'fa-tablet-screen-button';
                                } elseif (str_contains($ua, 'android')) {
                                    $deviceName = 'Android Device';
                                    $deviceIcon = 'fa-mobile-screen-button';
                                } elseif (str_contains($ua, 'windows')) {
                                    $deviceName = 'Windows PC';
                                    $deviceIcon = 'fa-laptop';
                                } elseif (str_contains($ua, 'linux')) {
                                    $deviceName = 'Linux Device';
                                    $deviceIcon = 'fa-laptop';
                                } else {
                                    $deviceName = 'Unknown Device';
                                    $deviceIcon = 'fa-laptop';
                                }
                            @endphp

                            <div class="session-card flex flex-col gap-4 rounded-2xl border {{ $isCurrent ? 'current-session border-emerald-500/40' : 'border-[var(--border-color)]' }} bg-[var(--bg-input)] px-4 py-4 transition sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-center gap-4">
                                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10">
                                        <i class="fa-solid {{ $deviceIcon }} text-lg text-emerald-400"></i>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-medium">{{ __($deviceName) }}</p>
                                            @if($isCurrent)
                                                <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-medium text-emerald-400">{{ __('This device') }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 truncate text-xs text-[var(--text-secondary)]">
                                            {{ $lastActive }} • {{ $session->ip_address }}
                                        </p>
                                    </div>
                                </div>

                                @if(!$isCurrent)
                                    <button type="button"
                                            class="revoke-session-btn inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm text-red-500 transition hover:bg-red-500/10 hover:text-red-400"
                                            data-session-id="{{ $session->id }}">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                        <span>{{ __('Logout') }}</span>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-[var(--border-color)] py-10 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--bg-input)]">
                                    <i class="fa-solid fa-laptop text-xl text-[var(--text-secondary)]"></i>
                                </div>
                                <p class="text-[var(--text-secondary)]">{{ __('No other active sessions found.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="card rounded-2xl p-6">
                    <h2 class="text-lg font-semibold">{{ __('Profile Picture') }}</h2>
                    <div class="mt-5 flex items-center gap-4">
                        <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)]">
                            @if(auth()->user()->pfp)
                                <img src="{{ Storage::url(auth()->user()->pfp) }}" alt="{{ __('Current picture') }}" class="h-full w-full object-cover">
                            @else
                                <span class="text-2xl font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ auth()->user()->name }}</p>
                            <p class="text-sm text-[var(--text-secondary)]">{{ __('Current picture') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('settings.pfp.update') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                        @csrf
                        <input type="file" name="pfp" accept="image/*"
                               class="w-full text-sm file:mr-4 file:rounded-2xl file:border-0 file:bg-[var(--bg-input)] file:px-4 file:py-2 file:text-[var(--text-primary)]">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-emerald-700">
                            <i class="fa-solid fa-upload"></i>
                            <span>{{ __('Upload') }}</span>
                        </button>
                    </form>
                </section>

                <section class="card rounded-2xl p-6">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold">{{ __('Appearance') }}</h2>
                        <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ __('Choose how NexusVault looks') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="switchTheme('dark')"
                                class="theme-btn flex flex-col items-center gap-3 rounded-2xl border p-5 text-center
                                {{ auth()->user()->theme === 'dark' ? 'active border-emerald-500' : 'border-[var(--border-color)]' }}"
                                id="theme-dark">
                            <i class="fa-solid fa-moon text-3xl text-emerald-500"></i>
                            <div>
                                <div class="font-medium">{{ __('Dark') }}</div>
                                <div class="text-xs text-[var(--text-secondary)]">{{ __('Default') }}</div>
                            </div>
                        </button>

                        <button onclick="switchTheme('light')"
                                class="theme-btn flex flex-col items-center gap-3 rounded-2xl border p-5 text-center
                                {{ auth()->user()->theme === 'light' ? 'active border-emerald-500' : 'border-[var(--border-color)]' }}"
                                id="theme-light">
                            <i class="fa-solid fa-sun text-3xl text-amber-500"></i>
                            <div>
                                <div class="font-medium">{{ __('Light') }}</div>
                                <div class="text-xs text-[var(--text-secondary)]">{{ __('Clean & bright') }}</div>
                            </div>
                        </button>
                    </div>
                </section>

                <section class="card rounded-2xl border-red-500/30 p-6">
                    <div class="mb-5 flex items-start gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-red-500/10">
                            <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-red-500">{{ __('Danger Zone') }}</h2>
                            <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ __('This action cannot be undone.') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('settings.account.destroy') }}"
                          onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Are you sure? This action cannot be undone.')) }})">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-red-500/10 px-5 py-3 text-sm font-medium text-red-500 transition hover:bg-red-500/20 hover:text-red-400">
                            <i class="fa-solid fa-trash"></i>
                            <span>{{ __('Delete my account') }}</span>
                        </button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>
