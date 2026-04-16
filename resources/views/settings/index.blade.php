<x-layouts.app>

    <div class="max-w-3xl mx-auto space-y-8">

        <h1 class="text-3xl font-semibold mb-2">Settings</h1>
        <p class="text-white/60">Manage your account security and preferences</p>

        <!-- Two-Factor Authentication -->
        <div class="bg-white/5 border border-white/10 rounded-3xl p-8">
            <h2 class="text-xl font-medium mb-6 flex items-center gap-3">
                <i class="fa-solid fa-shield-halved text-nexus-500"></i>
                Two-Factor Authentication
            </h2>
            @if(auth()->user()->mfa_enabled)
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-white/80">MFA is enabled with TOTP</p>
                        <p class="text-sm text-white/50">Your account is protected with a 6-digit code</p>
                    </div>
                    <form method="POST" action="{{ route('mfa.disable') }}">
                        @csrf
                        <button type="submit" class="text-red-400 hover:text-red-500 font-medium">Disable MFA</button>
                    </form>
                </div>
            @else
                <a href="{{ route('mfa.setup') }}" class="inline-flex items-center gap-2 bg-nexus-500 hover:bg-nexus-600 text-white px-6 py-3 rounded-2xl transition">
                    <i class="fa-solid fa-qrcode"></i>
                    <span>Enable MFA</span>
                </a>
            @endif
        </div>

        <!-- Passkeys -->
        <div class="bg-white/5 border border-white/10 rounded-3xl p-8">
            <h2 class="text-xl font-medium mb-6 flex items-center gap-3">
                <i class="fa-solid fa-fingerprint text-nexus-500"></i>
                Passkeys
            </h2>
            <button id="register-passkey-btn"
                    class="flex items-center gap-3 bg-white/10 hover:bg-white/20 px-6 py-3 rounded-2xl transition cursor-pointer">
                <i class="fa-solid fa-fingerprint"></i>
                <span>Add new Passkey</span>
            </button>
        </div>

        <!-- Change Password -->
        <div class="bg-white/5 border border-white/10 rounded-3xl p-8">
            <h2 class="text-xl font-medium mb-6">Change Password</h2>
            <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-sm text-white/70 mb-1.5">Current Password</label>
                    <input type="password" name="current_password" class="w-full bg-white/10 border border-white/10 focus:border-nexus-500 rounded-2xl py-4 px-5" required>
                </div>
                <div>
                    <label class="block text-sm text-white/70 mb-1.5">New Password</label>
                    <input type="password" name="new_password" class="w-full bg-white/10 border border-white/10 focus:border-nexus-500 rounded-2xl py-4 px-5" required>
                </div>
                <div>
                    <label class="block text-sm text-white/70 mb-1.5">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" class="w-full bg-white/10 border border-white/10 focus:border-nexus-500 rounded-2xl py-4 px-5" required>
                </div>
                <button type="submit" class="bg-gradient-to-r from-nexus-600 to-nexus-500 text-white px-8 py-4 rounded-2xl font-semibold">Update Password</button>
            </form>
        </div>

        <!-- Profile Picture -->
        <div class="bg-white/5 border border-white/10 rounded-3xl p-8">
            <h2 class="text-xl font-medium mb-6">Profile Picture</h2>
            <form method="POST" action="{{ route('settings.pfp.update') }}" enctype="multipart/form-data" class="flex items-center gap-6">
                @csrf
                <input type="file" name="pfp" accept="image/*" class="file:mr-4 file:py-2 file:px-6 file:rounded-2xl file:border-0 file:bg-white/10 file:text-white">
                <button type="submit" class="bg-nexus-500 hover:bg-nexus-600 text-white px-6 py-3 rounded-2xl">Upload</button>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="bg-white/5 border border-red-500/30 rounded-3xl p-8">
            <h2 class="text-xl font-medium text-red-400 mb-6">Danger Zone</h2>
            <form method="POST" action="{{ route('settings.account.destroy') }}" onsubmit="return confirm('Are you sure? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-400 hover:text-red-500 font-medium">Delete my account</button>
            </form>
        </div>

    </div>

</x-layouts.app>
