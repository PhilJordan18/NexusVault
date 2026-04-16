<x-layouts.auth>
    <div class="bg-white/5 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl">
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-nexus-500/10 rounded-2xl flex items-center justify-center border border-nexus-500/30">
                <i class="fa-solid fa-key text-5xl text-nexus-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Hi, <span id="user-email" class="text-nexus-500"></span> !</h1>
        <p class="text-white/60 text-center mb-8">Enter your password</p>

        <form method="POST" action="{{ route('login.authenticate.password') }}">
            @csrf
            <input type="hidden" name="email" id="hidden-email">

            <div class="mb-6">
                <label class="block text-sm text-white/70 mb-1.5">Password</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-white/40">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" name="password" id="password"
                           class="w-full bg-white/10 border border-white/10 focus:border-nexus-500 rounded-2xl py-4 pl-11 pr-12 text-white placeholder:text-white/40 outline-none transition"
                           placeholder="•••••••" required>

                    <button type="button" id="toggle-password"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-nexus-500 transition">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div id="strength-container" class="mt-3 hidden">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-white/60">Password strength</span>
                        <span id="strength-text" class="font-medium">Calculating...</span>
                    </div>
                    <div class="h-2 bg-white/10 rounded-2xl overflow-hidden">
                        <div id="strength-bar" class="h-2 w-0 transition-all duration-300 bg-nexus-500"></div>
                    </div>
                </div>
            </div>

            <a>
                <button type="submit"
                        class="w-full bg-gradient-to-r from-nexus-600 to-nexus-500 hover:from-nexus-700 hover:to-nexus-600 text-white font-semibold py-4 rounded-2xl text-lg transition shadow-lg shadow-nexus-500/30">
                    Sign In
                </button>
            </a>
        </form>

        <a href="{{ route('login') }}" class="block text-center text-white/50 mt-6 hover:text-white">← Change email address</a>
    </div>
</x-layouts.auth>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let email = urlParams.get('email') || localStorage.getItem('temp_email');
    if (email) {
        document.getElementById('user-email').textContent = email;
        document.getElementById('hidden-email').value = email;
        localStorage.setItem('temp_email', email);
    }
</script>

