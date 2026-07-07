@props([
    'title' => 'Legal',
    'description' => null,
])

@php
    $lastUpdated = app()->getLocale() === 'fr' ? '6 juillet 2026' : 'July 6, 2026';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ auth()->check() ? auth()->user()->theme : 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexusVault • {{ $title }}</title>
    @include('partials.favicons')
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[var(--bg-primary)] text-[var(--text-primary)]">
<nav class="sticky top-0 z-50 border-b border-[var(--border-color)] bg-[var(--bg-secondary)]/90 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)]">
                <img src="{{ asset('logo/LogoMonogramme.svg') }}" alt="" class="h-6 w-6">
            </div>
            <span class="truncate text-xl font-semibold tracking-normal sm:text-2xl">NexusVault</span>
        </a>

        <div class="flex flex-shrink-0 items-center gap-2 sm:gap-3">
            @include('partials.language-switch')

            @auth
                <a href="{{ route('dashboard') }}"
                   class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    {{ __('Dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="hidden text-sm text-[var(--text-secondary)] transition hover:text-[var(--text-primary)] sm:inline">
                    {{ __('Log in') }}
                </a>
                <a href="{{ route('register') }}"
                   class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    {{ __('Create account') }}
                </a>
            @endauth
        </div>
    </div>
</nav>

<main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-14">
    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-[0.24em] text-emerald-400">{{ __('Legal Center') }}</p>
        <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $title }}</h1>
        @if($description)
            <p class="mt-4 max-w-2xl text-lg text-[var(--text-secondary)]">{{ $description }}</p>
        @endif
        <p class="mt-5 text-sm text-[var(--text-secondary)]">{{ __('Last updated: :date', ['date' => $lastUpdated]) }}</p>
    </div>

    <article class="card rounded-2xl p-6 sm:p-8">
        <div class="space-y-8 text-[var(--text-secondary)]">
            {{ $slot }}
        </div>
    </article>
</main>

<footer class="border-t border-[var(--border-color)] px-4 py-8 text-sm text-[var(--text-secondary)] sm:px-6">
    <div class="mx-auto flex max-w-6xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p>{{ __('© :year NexusVault. Built with love and strong cryptography.', ['year' => date('Y')]) }}</p>
        <div class="flex flex-wrap gap-x-4 gap-y-2">
            <a href="{{ route('legal.terms') }}" class="transition hover:text-[var(--text-primary)]">{{ __('Terms of Use') }}</a>
            <a href="{{ route('legal.privacy') }}" class="transition hover:text-[var(--text-primary)]">{{ __('Privacy Policy') }}</a>
            <a href="{{ route('legal.cookies') }}" class="transition hover:text-[var(--text-primary)]">{{ __('Cookie Policy') }}</a>
            <a href="{{ route('legal.accessibility') }}" class="transition hover:text-[var(--text-primary)]">{{ __('Accessibility') }}</a>
        </div>
    </div>
</footer>
</body>
</html>
