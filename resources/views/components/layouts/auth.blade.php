<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} • {{ $title ?? 'Auth' }}</title>
    @vite(['resources/css/app.css', 'resources/ts/auth.ts'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-[#0a0a0a] text-white min-h-screen overflow-auto relative">

<!-- Navbar -->
<x-navbar variant="auth"/>

<!-- Background with gradient -->
<div class="absolute inset-0 pointer-events-none">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(16,185,129,0.07)_0%,transparent_55%)]"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_75%,rgba(16,185,129,0.05)_0%,transparent_60%)]"></div>
</div>

<!-- Focused content under navbar -->
<div class="min-h-[calc(100vh-5rem)] flex items-center justify-center py-12 relative z-10">
    <div class="max-w-md w-full mx-auto px-4">
        {{ $slot }}
    </div>
</div>

</body>
</html>
