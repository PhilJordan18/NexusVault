@props(['variant' => 'default'])
<nav class="sticky top-0 z-50 bg-zinc-950/80 backdrop-blur-xl border-b border-white/10">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Logo -->
        <a href="{{ route('home') }}">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-emerald-500 rounded-2xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V9a4 4 0 00-8 0v2" />
                    </svg>
                </div>
                <span class="text-2xl font-semibold tracking-tighter text-white">NexusVault</span>

            </div>
        </a>


        <!-- Menu desktop -->
        <div class="hidden md:flex items-center gap-10 text-sm font-medium text-white/80">
            <a href="#features" class="hover:text-white transition">Features</a>
            <a href="#securite" class="hover:text-white transition">Security</a>
            <a href="#comment" class="hover:text-white transition">About it</a>
        </div>

        <!-- Auth buttons -->
        <div class="flex items-center gap-4">
            <a href="{{ route('login') }}"
               class="px-5 py-2.5 text-sm font-medium text-white/90 hover:text-white transition">
                Sign Up
            </a>
            <a href="{{ route('register') }}"
               class="px-6 py-2.5 bg-white text-zinc-950 font-semibold text-sm rounded-3xl hover:bg-emerald-400 hover:text-white transition flex items-center gap-2">
                Free trial
            </a>
        </div>

        <!-- Mobile menu button -->
        <button class="md:hidden text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</nav>
