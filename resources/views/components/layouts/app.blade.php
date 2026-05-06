<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NexusVault • {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .glass {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .nav-active {
            background: #10b981;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-[#0a0a0a] text-white min-h-screen flex">

@php
    $totalItems = auth()->user()->services()->count();
@endphp

    <!-- SIDEBAR -->
<aside id="sidebar"
       class="w-72 bg-[#111111] border-r border-white/10 flex flex-col h-screen fixed top-0 left-0 z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">

    <!-- Logo -->
    <div class="p-6 flex items-center gap-3 border-b border-white/10">
        <div class="w-9 h-9 bg-emerald-500 rounded-2xl flex items-center justify-center">
            <span class="text-white font-bold text-2xl">N</span>
        </div>
        <div>
            <h1 class="text-2xl font-semibold tracking-tighter">NexusVault</h1>
            <p class="text-[10px] text-white/40 -mt-1">Password Manager</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-6 space-y-1 text-sm">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl nav-active">
            <i class="fa-solid fa-key w-4"></i>
            <span>All Items</span>
            <span class="ml-auto text-xs bg-white/20 px-2 py-0.5 rounded-full">{{ $totalItems }}</span>
        </a>

        <a href="{{ route('passkeys.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/5 text-white/70">
            <i class="fa-solid fa-fingerprint w-4"></i>
            <span>Passkeys</span>
        </a>

        <a href="{{ route('settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/5 text-white/70">
            <i class="fa-solid fa-gear w-4"></i>
            <span>Settings</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-red-500/10 text-red-400 text-sm transition">
            @csrf
            <i class="fa-solid fa-sign-out-alt w-4"></i>
            <button type="submit">Logout</button>
        </form>
    </nav>

    <!-- User -->
    <div class="p-4 border-t border-white/10 mt-auto">
        <div class="flex items-center gap-3 px-3 py-2 rounded-2xl hover:bg-white/5 cursor-pointer" onclick="window.location='{{ route('settings') }}'">
            <div class="w-9 h-9 bg-white/10 rounded-full flex items-center justify-center text-lg overflow-hidden">
                @if(auth()->user()->pfp)
                    <img src="{{ Storage::url(auth()->user()->pfp) }}" alt="avatar" class="w-full h-full object-cover rounded-full">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-white/50 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
</aside>

<!-- OVERLAY MOBILE -->
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/60 z-40 md:hidden"></div>

<!-- MAIN CONTENT -->
<div class="flex-1 flex flex-col md:ml-72">

    <!-- TOP BAR -->
    <div class="h-16 md:h-20 border-b border-white/10 bg-[#111111]/80 backdrop-blur-xl flex items-center px-4 md:px-8 sticky top-0 z-30">

        <!-- Partie gauche : Hamburger + Search -->
        <div class="flex items-center gap-3 flex-1 min-w-0">

            <!-- Hamburger Mobile -->
            <button id="mobile-menu-btn"
                    class="md:hidden flex items-center justify-center w-10 h-10 -ml-1 text-white hover:bg-white/10 rounded-2xl transition">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>

            <!-- Search Bar -->
            <div class="flex-1 max-w-md relative">
                <div class="relative">
                    <input type="text"
                           id="search-input"
                           autocomplete="off"
                           class="w-full bg-white/5 border border-white/10 focus:border-emerald-500 rounded-3xl py-2.5 pl-11 pr-4 text-sm placeholder:text-white/40 outline-none"
                           placeholder="Search passwords, services...">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-white/40"></i>
                </div>

                <!-- Search Dropdown -->
                <div id="search-dropdown"
                     class="absolute top-full mt-2 w-full bg-[#1a1a1c] border border-white/10 rounded-2xl shadow-2xl hidden overflow-hidden z-50">
                </div>
            </div>
        </div>

        <!-- Partie droite : Actions -->
        <div class="flex items-center gap-1 md:gap-3 ml-2">

            <!-- New Item Button -->
            <button onclick="showCreateModal()"
                    class="flex items-center gap-2 px-3 md:px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-medium transition">
                <i class="fa-solid fa-plus"></i>
                <span class="hidden sm:inline">New Item</span>
            </button>

            <!-- Notifications -->
            <div class="relative">
                @php
                    $pendingShares = \App\Models\Share::where('to_user_id', auth()->id())
                        ->whereNull('accepted_at')
                        ->where('rejected', false)
                        ->count();
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

            <!-- User Avatar -->
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
        const nameInput = document.getElementById('service-name');
        const urlInput  = document.getElementById('service-url');
        nameInput.value = '';
        urlInput.value  = '';
        nameInput.readOnly = false;
        urlInput.readOnly  = false;
        document.getElementById('create-modal').classList.remove('hidden');
    }
    function hideCreateModal() {
        document.getElementById('create-modal').classList.add('hidden');
    }
    function showCreateModalForService(serviceName, serviceUrl = '') {
        const nameInput = document.getElementById('service-name');
        const urlInput  = document.getElementById('service-url');
        nameInput.value = serviceName;
        urlInput.value  = serviceUrl;
        nameInput.readOnly = true;
        urlInput.readOnly  = true;
        document.getElementById('create-modal').classList.remove('hidden');
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
</body>
</html>
