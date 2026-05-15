<x-layouts.app>

    <div class="max-w-3xl mx-auto space-y-8">

        <h1 class="text-3xl font-semibold mb-2">Settings</h1>
        <p class="text-[var(--text-secondary)]">Manage your account security and preferences</p>

        <!-- Two-Factor Authentication -->
        <div class="card rounded-3xl p-8">
            <h2 class="text-xl font-medium mb-6 flex items-center gap-3">
                <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                Two-Factor Authentication
            </h2>
            @if(auth()->user()->mfa_enabled)
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-[var(--text-primary)]">MFA is enabled with TOTP</p>
                        <p class="text-sm text-[var(--text-secondary)]">Your account is protected with a 6-digit code</p>
                    </div>
                    <form method="POST" action="{{ route('mfa.disable') }}">
                        @csrf
                        <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Disable MFA</button>
                    </form>
                </div>
            @else
                <a href="{{ route('mfa.setup') }}"
                   class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-2xl transition">
                    <i class="fa-solid fa-qrcode"></i>
                    <span>Enable MFA</span>
                </a>
            @endif
        </div>

        <!-- Passkeys Card -->
        <a href="{{ route('passkeys.index') }}"
           class="block card hover:border-emerald-500/40 rounded-3xl p-8 transition group">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-2xl font-semibold mb-2 flex items-center gap-3">
                        <i class="fa-solid fa-fingerprint text-emerald-500"></i>
                        Passkeys
                    </h2>
                    <p class="text-[var(--text-secondary)] max-w-md">
                        Use your fingerprint, Face ID or security key to sign in without a password.
                        Manage your passkeys on a dedicated page.
                    </p>
                </div>
                <div class="text-emerald-500 group-hover:translate-x-1 transition">
                    <i class="fa-solid fa-arrow-right text-2xl"></i>
                </div>
            </div>
            <div class="mt-6 inline-flex items-center gap-2 text-sm text-emerald-500 font-medium">
                Manage Passkeys
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </div>
        </a>

        <!-- Change Password Section -->
        @unless(auth()->user()->is_oauth)
            <div class="card rounded-3xl p-8">
                <h2 class="text-xl font-medium mb-6">Change Password</h2>
                <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label class="block text-sm text-[var(--text-secondary)] mb-1.5">Current Password</label>
                        <input type="password" name="current_password"
                               class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 px-5" required>
                    </div>
                    <div>
                        <label class="block text-sm text-[var(--text-secondary)] mb-1.5">New Password</label>
                        <input type="password" name="new_password"
                               class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 px-5" required>
                    </div>
                    <div>
                        <label class="block text-sm text-[var(--text-secondary)] mb-1.5">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation"
                               class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 px-5" required>
                    </div>
                    <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-2xl font-semibold transition">
                        Update Password
                    </button>
                </form>
            </div>
        @endunless

        <!-- Profile Picture -->
        <div class="card rounded-3xl p-8">
            <h2 class="text-xl font-medium mb-6">Profile Picture</h2>
            <form method="POST" action="{{ route('settings.pfp.update') }}" enctype="multipart/form-data"
                  class="flex items-center gap-6">
                @csrf
                <input type="file" name="pfp" accept="image/*"
                       class="file:mr-4 file:py-2 file:px-6 file:rounded-2xl file:border-0 file:bg-[var(--bg-input)] file:text-[var(--text-primary)]">
                <button type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-2xl transition">
                    Upload
                </button>
            </form>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 bg-[var(--bg-input)] border border-[var(--border-color)] rounded-full flex items-center justify-center overflow-hidden">
                @if(auth()->user()->pfp)
                    <img src="{{ Storage::url(auth()->user()->pfp) }}" alt="avatar" class="w-full h-full object-cover rounded-full">
                @else
                    <span class="text-2xl">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                @endif
            </div>
            <p class="text-sm text-[var(--text-secondary)]">Current picture</p>
        </div>

        <!-- Active Sessions -->
        <div class="card rounded-3xl p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold flex items-center gap-3">
                        <i class="fa-solid fa-laptop text-emerald-500"></i>
                        Active Sessions
                    </h2>
                    <p class="text-sm text-[var(--text-secondary)] mt-1">
                        These are the devices currently logged into your account.
                    </p>
                </div>

                @php
                    $userSessions = \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('user_id', auth()->id())
                        ->orderBy('last_activity', 'desc')
                        ->get();

                    $otherSessionsCount = $userSessions->filter(fn($s) => $s->id !== session()->getId())->count();
                @endphp

                <button id="logout-all-sessions-btn"
                        class="px-4 py-2 text-sm rounded-2xl transition flex items-center gap-2
                       {{ $otherSessionsCount >= 2 ? 'bg-red-500/10 hover:bg-red-500/20 text-red-500' : 'bg-gray-500/10 text-gray-500 cursor-not-allowed' }}"
                    {{ $otherSessionsCount < 2 ? 'disabled' : '' }}>
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Logout from all other devices</span>
                </button>
            </div>

            <div id="active-sessions-list" class="space-y-3">
                @php
                    $userSessions = \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('user_id', auth()->id())
                        ->orderBy('last_activity', 'desc')
                        ->get();
                @endphp

                @forelse($userSessions as $session)
                    @php
                        $isCurrent = $session->id === session()->getId();
                        $lastActive = \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans();

                        $ua = strtolower($session->user_agent ?? '');
                        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) {
                            $deviceName = 'Mac'; $deviceIcon = 'fa-laptop';
                        } elseif (str_contains($ua, 'iphone')) {
                            $deviceName = 'iPhone'; $deviceIcon = 'fa-mobile-alt';
                        } elseif (str_contains($ua, 'ipad')) {
                            $deviceName = 'iPad'; $deviceIcon = 'fa-tablet-alt';
                        } elseif (str_contains($ua, 'android')) {
                            $deviceName = 'Android Device'; $deviceIcon = 'fa-mobile-alt';
                        } elseif (str_contains($ua, 'windows')) {
                            $deviceName = 'Windows PC'; $deviceIcon = 'fa-laptop';
                        } elseif (str_contains($ua, 'linux')) {
                            $deviceName = 'Linux Device'; $deviceIcon = 'fa-laptop';
                        } else {
                            $deviceName = 'Unknown Device'; $deviceIcon = 'fa-laptop';
                        }
                    @endphp

                    <div class="session-card flex items-center justify-between bg-[var(--bg-input)] border border-[var(--border-color)] rounded-2xl px-5 py-4
                                {{ $isCurrent ? 'border-emerald-500/40' : '' }}">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-white/10 rounded-2xl flex items-center justify-center">
                                <i class="fa-solid {{ $deviceIcon }} text-xl text-emerald-500"></i>
                            </div>

                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium">{{ $deviceName }}</p>
                                    @if($isCurrent)
                                        <span class="text-[10px] px-2 py-0.5 bg-emerald-500/20 text-emerald-500 rounded-full font-medium">This device</span>
                                    @endif
                                </div>
                                <p class="text-xs text-[var(--text-secondary)]">
                                    {{ $lastActive }} • {{ $session->ip_address }}
                                </p>
                            </div>
                        </div>

                        @if(!$isCurrent)
                            <button type="button"
                                    class="revoke-session-btn px-4 py-2 text-sm text-red-500 hover:text-red-600 hover:bg-red-500/10 rounded-2xl transition flex items-center gap-2"
                                    data-session-id="{{ $session->id }}">
                                <i class="fa-solid fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-8">
                        <div class="mx-auto w-12 h-12 bg-[var(--bg-input)] rounded-full flex items-center justify-center mb-3">
                            <i class="fa-solid fa-laptop text-[var(--text-secondary)] text-xl"></i>
                        </div>
                        <p class="text-[var(--text-secondary)]">No other active sessions found.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Theme Toggle -->
        <div class="card rounded-3xl p-6">
            <div class="mb-5">
                <h3 class="text-lg font-semibold">Appearance</h3>
                <p class="text-sm text-[var(--text-secondary)]">Choose how NexusVault looks</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <!-- Dark -->
                <button onclick="switchTheme('dark')"
                        class="theme-btn flex flex-col items-center gap-3 p-5 rounded-2xl border text-center
                               {{ auth()->user()->theme === 'dark' ? 'active border-emerald-500' : 'border-[var(--border-color)]' }}"
                        id="theme-dark">
                    <i class="fa-solid fa-moon text-3xl text-emerald-500"></i>
                    <div>
                        <div class="font-medium">Dark</div>
                        <div class="text-xs text-[var(--text-secondary)]">Default</div>
                    </div>
                </button>

                <!-- Light -->
                <button onclick="switchTheme('light')"
                        class="theme-btn flex flex-col items-center gap-3 p-5 rounded-2xl border text-center
                               {{ auth()->user()->theme === 'light' ? 'active border-emerald-500' : 'border-[var(--border-color)]' }}"
                        id="theme-light">
                    <i class="fa-solid fa-sun text-3xl text-amber-500"></i>
                    <div>
                        <div class="font-medium">Light</div>
                        <div class="text-xs text-[var(--text-secondary)]">Clean & bright</div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border border-red-500/30 rounded-3xl p-8">
            <h2 class="text-xl font-medium text-red-500 mb-6">Danger Zone</h2>
            <form method="POST" action="{{ route('settings.account.destroy') }}"
                  onsubmit="return confirm('Are you sure? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Delete my account</button>
            </form>
        </div>

    </div>

</x-layouts.app>
