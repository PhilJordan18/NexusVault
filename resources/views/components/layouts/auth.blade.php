<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ auth()->check() ? auth()->user()->theme : 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} • {{ $title ?? 'Auth' }}</title>
    @vite(['resources/css/app.css', 'resources/ts/auth.ts'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen flex flex-col bg-[var(--bg-primary)] text-[var(--text-primary)]">

<!-- Navbar Auth -->
<nav class="sticky top-0 z-50 bg-[var(--bg-secondary)]/80 backdrop-blur-xl border-b border-[var(--border-color)]">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-emerald-500 rounded-2xl flex items-center justify-center">
                <span class="text-white font-bold text-2xl">N</span>
            </div>
            <span class="text-2xl font-semibold tracking-tighter">NexusVault</span>
        </a>

        <a href="{{ route('home') }}"
           class="text-sm text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Back to home</span>
        </a>
    </div>
</nav>

<!-- Centered content -->
<div class="flex-1 flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        {{ $slot }}
    </div>
</div>

<!-- Toast -->
<div id="toast-container" class="fixed top-6 left-1/2 -translate-x-1/2 z-[200]"></div>

<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const bg = type === 'error'
            ? 'bg-red-500/20 border-red-500/30 text-red-400'
            : 'bg-emerald-500/20 border-emerald-500/30 text-emerald-400';

        toast.className = `px-6 py-3 rounded-2xl shadow-xl flex items-center gap-3 text-sm border ${bg}`;
        toast.innerHTML = `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-check-circle'}"></i><span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }
    window.showToast = showToast;

    document.addEventListener('DOMContentLoaded', () => {
        @if(session('success')) showToast(@json(session('success')), 'success'); @endif
        @if(session('error')) showToast(@json(session('error')), 'error'); @endif
    });
</script>
<script>
    function switchTheme(theme) {
        document.documentElement.classList.remove('dark', 'light');
        document.documentElement.classList.add(theme);

        document.querySelectorAll('.theme-btn').forEach(btn => btn.classList.remove('border-emerald-500', 'bg-emerald-500/10'));
        document.getElementById('theme-' + theme).classList.add('border-emerald-500', 'bg-emerald-500/10');

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
