<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusVault • Secure Password Manager</title>
    @vite(['resources/css/app.css'])

    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" defer></script>

    <style>
        .flowing-lines {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(transparent, rgba(16, 185, 129, 0.08)),
                radial-gradient(circle at 30% 40%, rgba(129, 140, 248, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, rgba(167, 139, 250, 0.12) 0%, transparent 60%);
            pointer-events: none;
            transition: transform 0.1s ease-out;
        }

        .hero-bg {
            transition: transform 0.1s ease-out;
        }
    </style>
</head>
<body class="bg-zinc-950 text-white overflow-x-hidden">

<!-- NAVBAR -->
<nav x-data="{ open: false }" class="sticky top-0 z-50 bg-zinc-950/90 backdrop-blur-xl border-b border-white/10">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-emerald-500 rounded-2xl flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V9a4 4 0 00-8 0v2" />
                </svg>
            </div>
            <span class="text-2xl font-semibold tracking-tighter text-white">NexusVault</span>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center gap-10 text-sm font-medium text-white/80">
            <a href="#features" class="hover:text-white transition">Features</a>
            <a href="#security" class="hover:text-white transition">Security</a>
            <a href="#how-it-works" class="hover:text-white transition">How it Works</a>
        </div>

        <!-- Auth buttons -->
        <div class="hidden md:flex items-center gap-4">
            <a href="{{ route('login') }}" class="px-5 py-2.5 text-sm font-medium text-white/90 hover:text-white transition">Log in</a>
            <a href="{{ route('register') }}" class="px-6 py-2.5 bg-white text-zinc-950 font-semibold text-sm rounded-3xl hover:bg-emerald-400 hover:text-white transition flex items-center gap-2">
                Get started free
            </a>
        </div>

        <!-- Mobile Hamburger -->
        <button @click="open = !open" class="md:hidden text-white p-2">
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6h12v12" /></svg>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div x-show="open" x-transition class="md:hidden bg-zinc-950 border-t border-white/10 px-6 py-6">
        <div class="flex flex-col gap-4 text-sm font-medium">
            <a href="#features" @click="open = false" class="py-2 hover:text-emerald-400">Features</a>
            <a href="#security" @click="open = false" class="py-2 hover:text-emerald-400">Security</a>
            <a href="#how-it-works" @click="open = false" class="py-2 hover:text-emerald-400">How it Works</a>
            <div class="pt-4 border-t border-white/10 flex flex-col gap-3">
                <a href="{{ route('login') }}" class="py-3 text-center border border-white/20 rounded-2xl hover:bg-white/5">Log in</a>
                <a href="{{ route('register') }}" class="py-3 text-center bg-white text-zinc-950 font-semibold rounded-2xl hover:bg-emerald-400 hover:text-white">Get started free</a>
            </div>
        </div>
    </div>
</nav>

<!-- HERO -->
<div class="relative min-h-[100dvh] flex items-center justify-center pt-20 overflow-hidden" id="hero">
    <div class="absolute inset-0 bg-[radial-gradient(#27272a_0.8px,transparent_1px)] bg-[length:4px_4px] hero-bg" id="hero-grid"></div>
    <div class="flowing-lines" id="flowing-lines"></div>

    <!-- Lignes SVG avec parallax -->
    <svg class="absolute inset-0 w-full h-full opacity-60 hero-bg" id="hero-svg" viewBox="0 0 1440 900" fill="none">
        <path d="M0 400 Q360 200 720 450 T1440 300" stroke="#10b981" stroke-width="1.5" stroke-opacity="0.6"/>
        <path d="M0 550 Q400 300 800 520 T1440 380" stroke="#6366f1" stroke-width="1.2" stroke-opacity="0.5"/>
        <path d="M0 300 Q500 550 900 280 T1440 480" stroke="#a78bfa" stroke-width="1" stroke-opacity="0.4"/>
    </svg>

    <div class="relative z-10 max-w-5xl px-6 text-center">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 mb-6"
             data-aos="fade-down" data-aos-delay="100">
            <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
            <span class="text-xs font-medium tracking-[2px] text-emerald-400">NEW: PASSKEYS + SECURE SHARING 2.0</span>
        </div>

        <!-- Titre -->
        <h1 class="text-7xl md:text-8xl font-semibold tracking-tighter leading-none mb-6" data-aos="fade-up" data-aos-delay="200">
            Your vault.<br>
            <span class="bg-gradient-to-r from-emerald-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">Truly secure.</span>
        </h1>

        <!-- Description -->
        <p class="max-w-2xl mx-auto text-2xl text-white/70 mb-10" data-aos="fade-up" data-aos-delay="350">
            End-to-end encryption • Secure sharing • Passkeys & MFA.<br>
            Zero knowledge. Zero compromise.
        </p>

        <!-- Boutons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up" data-aos-delay="500">
            <a href="{{ route('register') }}"
               class="px-10 py-4 bg-emerald-500 hover:bg-emerald-600 text-lg font-semibold rounded-3xl transition flex items-center justify-center gap-3">
                Get started for free <span class="text-xl">→</span>
            </a>
            <a href="{{ route('login') }}"
               class="px-10 py-4 border border-white/20 hover:bg-white/5 text-lg font-medium rounded-3xl transition">
                Log in
            </a>
        </div>

        <p class="mt-6 text-xs text-white/50" data-aos="fade-up" data-aos-delay="650">No credit card required • 14-day Pro trial included</p>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-12 left-1/2 -translate-x-1/2 flex flex-col items-center" data-aos="fade-up" data-aos-delay="800">
        <span class="text-[10px] tracking-[2px] text-white/40 mb-1">SCROLL TO DISCOVER</span>
        <div class="w-px h-8 bg-gradient-to-b from-white/40 to-transparent animate-bounce"></div>
    </div>
</div>

<!-- FEATURES -->
<div id="features" class="max-w-7xl mx-auto px-6 py-24">
    <div class="text-center mb-16" data-aos="fade-up">
        <span class="text-emerald-400 text-sm font-semibold tracking-[3px]">WHY PEOPLE SWITCH TO NEXUSVAULT</span>
        <h2 class="text-5xl font-semibold tracking-tight mt-3">Everything you need.<br>Nothing you don’t.</h2>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        @foreach([
            ['icon' => '🔐', 'title' => 'AES-256-GCM Encryption', 'desc' => 'Military-grade encryption used by banks and governments.'],
            ['icon' => '🔄', 'title' => 'Secure Sharing', 'desc' => 'Share passwords without ever revealing them.'],
            ['icon' => '🔑', 'title' => 'Passkeys & MFA', 'desc' => 'Passwordless login + two-factor authentication.'],
            ['icon' => '⚡', 'title' => 'Smart Generator', 'desc' => 'Strong passwords generated locally in one click.'],
            ['icon' => '📱', 'title' => 'Multi-Device Recovery', 'desc' => 'Switch phones? Restore everything in 30 seconds.'],
            ['icon' => '🛡️', 'title' => 'Zero-Knowledge', 'desc' => 'Even if we get hacked, your data stays unreadable.'],
        ] as $feature)
            <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8 hover:border-emerald-500/50 transition group"
                 data-aos="fade-up" data-aos-delay="{{ $loop->index * 70 }}">
                <div class="text-4xl mb-6">{{ $feature['icon'] }}</div>
                <h3 class="text-2xl font-semibold mb-3">{{ $feature['title'] }}</h3>
                <p class="text-white/60">{{ $feature['desc'] }}</p>
            </div>
        @endforeach
    </div>
</div>

<!-- SECURITY -->
<div id="security" class="bg-zinc-900 py-24 border-y border-white/10">
    <div class="max-w-5xl mx-auto px-6">
        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div data-aos="fade-right">
                <span class="text-emerald-400 font-semibold">BANK-LEVEL SECURITY</span>
                <h2 class="text-6xl font-semibold tracking-tighter mt-4 leading-none">We don’t store your passwords.<br>We store noise.</h2>
                <div class="mt-10 space-y-6 text-lg text-white/70">
                    <p>All your data is encrypted <strong>locally</strong> on your device with your Master Key before it ever leaves your browser.</p>
                    <p>When you share a password, we use <strong>asymmetric RSA encryption</strong>. The recipient gets a key encrypted with <strong>their</strong> public key.</p>
                    <p class="font-medium text-white">Result: Even if our servers are breached, attackers find only unreadable data.</p>
                </div>
            </div>

            <div class="bg-zinc-950 border border-white/10 rounded-3xl p-10 text-sm font-mono text-emerald-400/80"
                 data-aos="fade-left" data-aos-delay="120">
                <div class="mb-4">→ Symmetric encryption: <span class="text-white">AES-256-GCM</span></div>
                <div class="mb-4">→ Asymmetric encryption: <span class="text-white">RSA-2048 + OAEP</span></div>
                <div class="mb-4">→ Key derivation: <span class="text-white">Argon2id</span></div>
                <div>→ Authentication: <span class="text-white">WebAuthn (Passkeys) + TOTP</span></div>
                <div class="mt-8 pt-8 border-t border-white/10 text-xs text-white/40">
                    Audited by cryptography experts • GDPR compliant • Hosted in Europe
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HOW IT WORKS -->
<div id="how-it-works" class="max-w-5xl mx-auto px-6 py-24">
    <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-5xl font-semibold tracking-tight">3 minutes to secure your entire digital life.</h2>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
        @foreach([
            ['num' => '01', 'title' => 'Create your account', 'desc' => 'Sign up in 20 seconds with email or Passkey.'],
            ['num' => '02', 'title' => 'Add your accounts', 'desc' => 'Import from Chrome, LastPass, or enter them manually.'],
            ['num' => '03', 'title' => 'Enjoy the peace of mind', 'desc' => 'Auto-fill, secure sharing, and breach alerts.'],
        ] as $step)
            <div class="relative pl-16" data-aos="fade-up" data-aos-delay="{{ $loop->index * 120 }}">
                <div class="absolute left-0 top-1 text-[80px] font-black text-white/5">{{ $step['num'] }}</div>
                <h3 class="text-3xl font-semibold mb-3">{{ $step['title'] }}</h3>
                <p class="text-white/60 text-lg">{{ $step['desc'] }}</p>
            </div>
        @endforeach
    </div>
</div>

<!-- FINAL CTA -->
<div class="bg-gradient-to-br from-emerald-950 via-zinc-950 to-indigo-950 py-20 border-t border-white/10"
     data-aos="zoom-in-up">
    <div class="max-w-2xl mx-auto text-center px-6">
        <h2 class="text-6xl font-semibold tracking-tighter">Ready to take back control?</h2>
        <p class="mt-4 text-xl text-white/70">Join thousands of people who already sleep better at night.</p>

        <a href="{{ route('register') }}"
           class="mt-10 inline-flex px-14 py-4 bg-white text-zinc-950 font-semibold text-xl rounded-3xl hover:bg-emerald-400 hover:text-white transition">
            Create my vault for free
        </a>
        <p class="mt-4 text-xs text-white/50">No credit card • Cancel anytime</p>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-zinc-950 border-t border-white/10 py-16" data-aos="fade-up">
    <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-12 gap-y-12">
        <div class="md:col-span-5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-emerald-500 rounded-2xl flex items-center justify-center">
                    <span class="text-white text-xl">🔒</span>
                </div>
                <span class="font-semibold text-2xl">NexusVault</span>
            </div>
            <p class="text-white/50 max-w-xs">The only password manager that actually respects you.</p>
        </div>

        <div class="md:col-span-3">
            <div class="font-semibold mb-4">Product</div>
            <div class="space-y-2 text-white/70 text-sm">
                <div>Features</div><div>Security</div><div>Pricing</div><div>Changelog</div>
            </div>
        </div>

        <div class="md:col-span-4 text-right md:text-left">
            <div class="font-semibold mb-4">Legal</div>
            <div class="space-y-2 text-white/70 text-sm">
                <div>Privacy</div><div>Terms</div><div>GDPR</div>
            </div>
            <div class="mt-10 text-xs text-white/40">
                © {{ date('Y') }} NexusVault. Built with ❤️ and strong cryptography.
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // === AOS Configuration ===
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 900,
            easing: 'ease-out-cubic',
            once: false,
            offset: 60,
            delay: 0
        });
    });

    // === PARALLAX LÉGER SUR LE HERO ===
    const hero = document.getElementById('hero');
    const grid = document.getElementById('hero-grid');
    const lines = document.getElementById('flowing-lines');
    const svg = document.getElementById('hero-svg');

    function parallaxHero() {
        const scrollY = window.scrollY;
        const speed = 0.3;

        if (grid) grid.style.transform = `translateY(${scrollY * speed * 0.4}px)`;
        if (lines) lines.style.transform = `translateY(${scrollY * speed * 0.6}px)`;
        if (svg) svg.style.transform = `translateY(${scrollY * speed * 0.35}px)`;
    }

    window.addEventListener('scroll', () => {
        if (hero.getBoundingClientRect().bottom > 0) {
            parallaxHero();
        }
    });

    setTimeout(() => {
        if (grid) grid.style.transition = 'transform 0.4s ease-out';
        if (lines) lines.style.transition = 'transform 0.4s ease-out';
        if (svg) svg.style.transition = 'transform 0.4s ease-out';
    }, 800);
</script>

</body>
</html>
