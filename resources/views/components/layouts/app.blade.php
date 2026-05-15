<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ auth()->check() ? auth()->user()->theme : 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NexusVault • {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen flex bg-[var(--bg-primary)] text-[var(--text-primary)]">

@php
    $totalItems = auth()->user()->services()->count();
@endphp

    <!-- SIDEBAR -->
<aside id="sidebar"
       class="sidebar w-72 flex flex-col h-screen fixed top-0 left-0 z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">

    <!-- Logo -->
    <div class="p-6 flex items-center gap-3 border-b border-[var(--border-color)]">
        <div class="w-9 h-9 bg-emerald-500 rounded-2xl flex items-center justify-center">
            <span class="text-white font-bold text-2xl">N</span>
        </div>
        <div>
            <h1 class="text-2xl font-semibold tracking-tighter">NexusVault</h1>
            <p class="text-[10px] text-[var(--text-secondary)] -mt-1">Password Manager</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-6 space-y-1 text-sm">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl nav-active">
            <i class="fa-solid fa-key w-4"></i>
            <span>All Items</span>
            <span class="ml-auto text-xs bg-white/20 px-2 py-0.5 rounded-full">{{ $totalItems }}</span>
        </a>

        <a href="{{ route('passkeys.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/5 text-[var(--text-secondary)]">
            <i class="fa-solid fa-fingerprint w-4"></i>
            <span>Passkeys</span>
        </a>

        <a href="{{ route('settings') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/5 text-[var(--text-secondary)]">
            <i class="fa-solid fa-gear w-4"></i>
            <span>Settings</span>
        </a>

        <form method="POST" action="{{ route('logout') }}"
              class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-red-500/10 text-red-400 text-sm transition">
            @csrf
            <i class="fa-solid fa-sign-out-alt w-4"></i>
            <button type="submit">Logout</button>
        </form>
    </nav>

    <!-- User -->
    <div class="p-4 border-t border-[var(--border-color)] mt-auto">
        <div class="flex items-center gap-3 px-3 py-2 rounded-2xl hover:bg-white/5 cursor-pointer"
             onclick="window.location='{{ route('settings') }}'">
            <div class="w-9 h-9 bg-white/10 rounded-full flex items-center justify-center text-lg overflow-hidden">
                @if(auth()->user()->pfp)
                    <img src="{{ Storage::url(auth()->user()->pfp) }}" alt="avatar" class="w-full h-full object-cover rounded-full">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-[var(--text-secondary)] truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
</aside>

<!-- OVERLAY MOBILE -->
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/60 z-40 md:hidden"></div>

<!-- MAIN CONTENT -->
<div class="flex-1 flex flex-col md:ml-72">

    <!-- TOP BAR -->
    <div class="topbar h-16 md:h-20 flex items-center px-4 md:px-8 sticky top-0 z-30">

        <!-- Hamburger + Search -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <button id="mobile-menu-btn"
                    class="md:hidden flex items-center justify-center w-10 h-10 -ml-1 hover:bg-white/10 rounded-2xl transition">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>

            <div class="flex-1 max-w-md relative">
                <div class="relative">
                    <input type="text" id="search-input" autocomplete="off"
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-3xl py-2.5 pl-11 pr-4 text-sm placeholder:text-[var(--text-secondary)] outline-none"
                           placeholder="Search passwords, services...">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)]"></i>
                </div>
                <div id="search-dropdown"
                     class="absolute top-full mt-2 w-full bg-[var(--bg-card)] border border-[var(--border-color)] rounded-2xl shadow-2xl hidden overflow-hidden z-50">
                </div>
            </div>
        </div>

        <!-- Right side actions -->
        <div class="flex items-center gap-1 md:gap-3 ml-2">
            <button onclick="showCreateModal()"
                    class="flex items-center gap-2 px-3 md:px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-medium transition">
                <i class="fa-solid fa-plus"></i>
                <span class="hidden sm:inline">New Item</span>
            </button>

            <!-- Notifications -->
            <div class="relative">
                @php
                    $pendingShares = \App\Models\Share::where('to_user_id', auth()->id())
                        ->whereNull('accepted_at')->where('rejected', false)->count();
                @endphp
                <button onclick="window.location.href='{{ route('notifications.index') }}'"
                        class="w-9 h-9 flex items-center justify-center hover:bg-white/10 rounded-2xl transition relative">
                    <i class="fa-solid fa-bell text-lg"></i>
                    @if($pendingShares > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full">
                                {{ $pendingShares }}
                            </span>
                    @endif
                </button>
            </div>

            <!-- Avatar -->
            <div class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center text-sm cursor-pointer overflow-hidden"
                 onclick="window.location='{{ route('settings') }}'">
                @if(auth()->user()->pfp)
                    <img src="{{ Storage::url(auth()->user()->pfp) }}" alt="avatar" class="w-full h-full object-cover rounded-full">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
        </div>
    </div>

    <!-- PAGE CONTENT -->
    <div class="flex-1 overflow-auto p-4 md:p-8">
        {{ $slot }}
    </div>
</div>

<!-- Toast -->
<div id="toast-container" class="fixed top-6 left-1/2 -translate-x-1/2 z-[200] flex flex-col items-center gap-2"></div>

@include('services.create-modal')
@include('shares.modal')

<script>
    // === SIDEBAR MOBILE ===
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggleBtn = document.getElementById('mobile-menu-btn');

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    toggleBtn.addEventListener('click', () => {
        if (sidebar.classList.contains('-translate-x-full')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    });
    overlay.addEventListener('click', closeSidebar);

    // === TOAST ===
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const bg = type === 'error' ? 'bg-red-500/20 border-red-500/30 text-red-400' : 'bg-emerald-500/20 border-emerald-500/30 text-emerald-400';
        const icon = type === 'error' ? 'fa-circle-exclamation' : 'fa-check-circle';

        toast.className = `px-6 py-3 rounded-2xl shadow-xl flex items-center gap-3 text-sm border ${bg}`;
        toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    window.showToast = showToast;

    // === MODALS ===
    function showCreateModal() {
        const form = document.getElementById('create-service-form');
        if (form) form.reset();

        const nameInput = document.getElementById('service-name');
        const domainInput = document.getElementById('service-domain');
        const urlPreview = document.getElementById('service-url-preview');
        const suggestions = document.getElementById('name-suggestions');

        if (nameInput) nameInput.value = '';
        if (domainInput) domainInput.value = '';
        if (urlPreview) urlPreview.innerHTML = 'Sera généré automatiquement';
        if (suggestions) suggestions.classList.add('hidden');

        document.getElementById('create-modal').classList.remove('hidden');
    }

    function showCreateModalForService(serviceName, serviceUrl = '') {
        const modal = document.getElementById('create-modal');
        const nameInput = document.getElementById('service-name');
        const domainInput = document.getElementById('service-domain');
        const urlPreview = document.getElementById('service-url-preview');

        if (nameInput) nameInput.value = serviceName || '';

        if (serviceUrl && domainInput && urlPreview) {
            try {
                const urlObj = new URL(serviceUrl);
                domainInput.value = urlObj.hostname.replace('www.', '');
                urlPreview.innerHTML = `<span class="text-emerald-400">${serviceUrl}</span>`;
            } catch (e) {
                urlPreview.innerHTML = 'Sera généré automatiquement';
            }
        }

        modal.classList.remove('hidden');
    }

    function hideCreateModal() {
        const modal = document.getElementById('create-modal');
        modal.classList.add('hidden');

        const suggestions = document.getElementById('name-suggestions');
        if (suggestions) suggestions.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        @if(session('success'))
        showToast(@json(session('success')), 'success');
        @endif
        @if(session('error'))
        showToast(@json(session('error')), 'error');
        @endif
    });
</script>

<script>
    function switchTheme(theme) {
        // Mise à jour visuelle immédiate
        document.documentElement.classList.remove('dark', 'light');
        document.documentElement.classList.add(theme);

        // Mise à jour des boutons
        document.querySelectorAll('.theme-btn').forEach(btn => btn.classList.remove('border-emerald-500', 'bg-emerald-500/10'));
        document.getElementById('theme-' + theme).classList.add('border-emerald-500', 'bg-emerald-500/10');

        // Sauvegarde côté serveur
        fetch("{{ route('settings.theme.update') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ theme: theme })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.showToast('Theme updated successfully', 'success');
                }
            })
            .catch(() => {
                window.showToast('Failed to update theme', 'error');
            });
    }
</script>
</body>
</html>
