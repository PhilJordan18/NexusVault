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
<body class="bg-[#0a0a0a] text-white min-h-screen flex items-center justify-center py-12 overflow-auto relative">

<div class="absolute inset-0 pointer-events-none">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(0,255,157,0.08)_0%,transparent_50%)]"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_80%,rgba(0,255,157,0.06)_0%,transparent_60%)]"></div>
</div>

<div class="max-w-md w-full mx-auto px-4 z-10">
    {{ $slot }}
</div>
</body>
</html>
