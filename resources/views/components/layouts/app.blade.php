<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NexusVault • {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-[#f5f5f7] dark:bg-[#0a0a0a] text-[#1d1d1f] dark:text-white min-h-screen flex">

<!-- LEFT SIDEBAR (comme Apple Passwords) -->
<div class="w-72 bg-white/90 dark:bg-white/5 border-r border-gray-200 dark:border-white/10 flex flex-col h-screen fixed top-0 left-0 z-50 overflow-y-auto">
    <div class="p-6 border-b border-gray-200 dark:border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-emerald-500 rounded-2xl flex items-center justify-center text-white font-bold text-2xl">N</div>
            <h1 class="text-2xl font-semibold tracking-tighter">NexusVault</h1>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-blue-600 text-white font-medium">
            <i class="fa-solid fa-key"></i>
            <span>All Items</span>
            <span class="ml-auto text-xs bg-white/30 px-2 py-0.5 rounded-full">9</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/10 text-white/70">
            <i class="fa-solid fa-user-secret"></i>
            <span>Passkeys</span>
            <span class="ml-auto text-xs bg-white/10 px-2 py-0.5 rounded-full">0</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/10 text-white/70">
            <i class="fa-solid fa-qrcode"></i>
            <span>Codes</span>
            <span class="ml-auto text-xs bg-white/10 px-2 py-0.5 rounded-full">0</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/10 text-white/70">
            <i class="fa-solid fa-wifi"></i>
            <span>Wi-Fi</span>
            <span class="ml-auto text-xs bg-white/10 px-2 py-0.5 rounded-full">12</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/10 text-white/70">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Security</span>
            <span class="ml-auto text-xs bg-white/10 px-2 py-0.5 rounded-full">0</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/10 text-white/70">
            <i class="fa-solid fa-trash"></i>
            <span>Deleted</span>
            <span class="ml-auto text-xs bg-white/10 px-2 py-0.5 rounded-full">0</span>
        </a>

        <a href="{{ route('settings') }}" class="flex items-center gap-3 px-6 py-4 text-white/70 hover:bg-white/5">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
    </nav>

    <!-- User bottom -->
    <div class="mt-auto p-4 border-t border-gray-200 dark:border-white/10">
        <div class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-white/10">
            <div class="w-9 h-9 bg-white/10 rounded-2xl flex items-center justify-center text-xl">👤</div>
            <div class="flex-1">
                <p class="font-medium">{{ auth()->user()->name }}</p>
                <p class="text-xs text-white/50">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
</div>

<!-- MAIN AREA -->
<div class="flex-1 flex flex-col ml-72">
    <!-- Top bar -->
    <div class="h-21 border-b border-gray-200 dark:border-white/10 bg-white/90 dark:bg-white/5 backdrop-blur-xl flex items-center px-8">
        <div class="flex-1 max-w-md">
            <div class="relative">
                <input type="text" id="search-input" class="w-full bg-white dark:bg-white/10 border border-gray-300 dark:border-white/20 focus:border-emerald-500 rounded-3xl py-3 pl-12 text-sm placeholder:text-gray-400 outline-none" placeholder="Search passwords or services...">
                <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>

        <div class="ml-auto flex items-center gap-6">
            <button onclick="showCreateModal()" class="flex items-center gap-2 px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-3xl text-sm font-medium">
                <i class="fa-solid fa-plus"></i>
                <span>New</span>
            </button>

                <!-- NOTIFICATION BELL -->
            <div class="relative">
                @php
                    $pendingShares = \App\Models\Share::where('to_user_id', auth()->id())
                        ->whereNull('accepted_at')
                        ->where('rejected', false)
                        ->count();
                @endphp
                <button onclick="window.location.href='{{ route('notifications.index') }}'"
                        class="w-9 h-9 flex items-center justify-center hover:bg-white/10 hover:text-emerald-400 rounded-2xl transition-all">
                    <i class="fa-solid fa-bell text-xl"></i>
                    @if($pendingShares > 0)
                        <div class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full">
                            {{ $pendingShares }}
                        </div>
                    @endif
                </button>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-8">
        {{ $slot }}
    </div>
</div>
@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.showToast === 'function') {
                window.showToast('{{ session('success') }}');
            }
        });
    </script>
@endif
<!-- MODAL PARTAGE -->
@include('services.create-modal')
@include('shares.modal')
</body>
<script>
    function toggleNotifications() {
        window.location.href = '/dashboard';
    }
</script>
</html>
