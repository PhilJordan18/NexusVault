<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} • {{ $title ?? 'Dashboard' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-[#0a0a0a] text-white min-h-screen flex overflow-hidden">

<!-- Sidebar -->
<div class="w-72 bg-white/5 border-r border-white/10 flex flex-col h-screen">
    <!-- Logo -->
    <div class="p-6 flex items-center gap-3 border-b border-white/10">
        <div class="w-9 h-9 bg-gradient-to-br from-nexus-500 to-emerald-400 rounded-2xl flex items-center justify-center text-2xl font-bold">N</div>
        <h1 class="text-2xl font-semibold tracking-tighter">NexusVault</h1>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 bg-white/10 text-white rounded-2xl font-medium cursor-pointer">
            <i class="fa-solid fa-list"></i>
            <span>All Items</span>
        </a>

        <div class="px-4 mt-8 text-xs uppercase tracking-widest text-white/40">Vault</div>
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-white/80 hover:bg-white/5 rounded-xl cursor-pointer">
            <i class="fa-solid fa-vault"></i>
            <span>Personal Vault</span>
            <span class="ml-auto text-xs bg-white/10 px-2 py-px rounded">4</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-white/60 hover:bg-white/5 rounded-xl cursor-pointer">
            <i class="fa-solid fa-user"></i>
            <span>Personal Info</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-white/60 hover:bg-white/5 rounded-xl cursor-pointer">
            <i class="fa-solid fa-credit-card"></i>
            <span>Payments</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-white/60 hover:bg-white/5 rounded-xl cursor-pointer">
            <i class="fa-solid fa-key"></i>
            <span>Accounts</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-white/60 hover:bg-white/5 rounded-xl cursor-pointer">
            <i class="fa-solid fa-note-sticky"></i>
            <span>Secure Notes</span>
        </a>
    </nav>

    <!-- Bottom -->
    <div class="mt-auto p-6 border-t border-white/10">
        <a href="{{ route('settings') }}" class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 rounded-2xl cursor-pointer">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>

        <div class="flex items-center gap-3 mt-6">
            <div class="w-9 h-9 bg-white/10 rounded-full flex items-center justify-center text-lg">👤</div>
            <div class="flex-1">
                <p class="font-medium text-sm">{{ auth()->user()->name ?? 'User' }}</p>
                <p class="text-xs text-white/50">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="flex-1 flex flex-col">
    <!-- Top Bar -->
    <div class="h-16 border-b border-white/10 bg-white/5 backdrop-blur-xl flex items-center px-8 z-50">
        <div class="flex-1 max-w-xl">
            <div class="relative">
                <input type="text"
                       class="w-full bg-white/10 border border-white/10 focus:border-nexus-500 rounded-2xl py-3 pl-12 text-white placeholder:text-white/40 outline-none transition"
                       placeholder="Search passwords or services...">
                <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-white/40"></i>
            </div>
        </div>

        <div class="flex items-center gap-6 ml-auto">
            <button class="w-10 h-10 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-2xl transition cursor-pointer">
                <i class="fa-solid fa-plus text-xl"></i>
            </button>
            <button class="w-10 h-10 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-2xl transition relative cursor-pointer">
                <i class="fa-solid fa-bell"></i>
                <span class="absolute top-1.5 right-1.5 w-3.5 h-3.5 bg-red-500 rounded-full ring-2 ring-[#0a0a0a]"></span>
            </button>
        </div>
    </div>

    <!-- Page Content -->
    <div class="flex-1 overflow-auto p-8">
        {{ $slot }}
    </div>
</div>
</div>

</body>
</html>
