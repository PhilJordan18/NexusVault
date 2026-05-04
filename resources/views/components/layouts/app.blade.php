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

<!-- SIDEBAR -->
<div class="w-72 bg-[#111111] border-r border-white/10 flex flex-col h-screen fixed top-0 left-0 z-50">
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
            <span class="ml-auto text-xs bg-white/20 px-2 py-0.5 rounded-full">{{ $totalItems ?? 986 }}</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/5 text-white/70">
            <i class="fa-solid fa-fingerprint w-4"></i>
            <span>Passkeys</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/5 text-white/70">
            <i class="fa-solid fa-shield-halved w-4"></i>
            <span>Security</span>
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
            <div class="w-9 h-9 bg-white/10 rounded-full flex items-center justify-center text-lg">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-white/50 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="flex-1 flex flex-col ml-72">

    <!-- TOP BAR -->
    <div class="h-23 border-b border-white/10 bg-[#111111]/80 backdrop-blur-xl flex items-center px-8 sticky top-0 z-40">
        <div class="flex-1 max-w-md">
            <div class="relative">
                <input type="text"
                       id="search-input"
                       class="w-full bg-white/5 border border-white/10 focus:border-emerald-500 rounded-3xl py-2.5 pl-11 text-sm placeholder:text-white/40 outline-none"
                       placeholder="Search passwords, services...">
                <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-white/40"></i>
            </div>
        </div>

        <div class="ml-auto flex items-center gap-4">
            <!-- New Button -->
            <button onclick="showCreateModal()"
                    class="flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-medium transition">
                <i class="fa-solid fa-plus"></i>
                <span>New Item</span>
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
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4.5 h-4.5 flex items-center justify-center rounded-full">
                            {{ $pendingShares }}
                        </span>
                    @endif
                </button>
            </div>

            <!-- User Avatar -->
            <div class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center text-sm cursor-pointer" onclick="window.location='{{ route('settings') }}'">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </div>

    <!-- PAGE CONTENT -->
    <div class="flex-1 overflow-auto p-8">
        {{ $slot }}
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed bottom-6 right-6 z-[100]"></div>

@include('services.create-modal')
@include('shares.modal')

<script>

    function showToast(message) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `px-6 py-3 rounded-2xl shadow-xl flex items-center gap-3 text-sm border border-white/10`;
        toast.style.background = 'rgba(17,17,17,0.95)';
        toast.innerHTML = `
            <i class="fa-solid fa-check-circle text-emerald-400"></i>
            <span>${message}</span>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    // --- Modal functions ---
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

    window.showToast = showToast;
</script>

</body>
</html>
