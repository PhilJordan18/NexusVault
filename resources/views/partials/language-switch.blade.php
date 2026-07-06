@props(['variant' => 'app'])

@php
    $currentLocale = app()->getLocale();
    $isLanding = $variant === 'landing';
    $wrapperClasses = $isLanding
        ? 'border-white/10 bg-white/5 text-white/70'
        : 'border-[var(--border-color)] bg-[var(--bg-input)] text-[var(--text-secondary)]';
    $activeClasses = $isLanding
        ? 'bg-white text-zinc-950'
        : 'bg-emerald-600 text-white';
    $inactiveClasses = $isLanding
        ? 'hover:bg-white/10 hover:text-white'
        : 'hover:bg-white/10 hover:text-[var(--text-primary)]';
@endphp

<form method="POST"
      action="{{ route('locale.update') }}"
      aria-label="{{ __('Language') }}"
      class="inline-flex h-9 items-center rounded-2xl border p-1 text-xs font-semibold {{ $wrapperClasses }}">
    @csrf
    @foreach(['en' => __('EN'), 'fr' => __('FR')] as $locale => $label)
        <button type="submit"
                name="locale"
                value="{{ $locale }}"
                class="h-7 min-w-9 rounded-xl px-2 transition {{ $currentLocale === $locale ? $activeClasses : $inactiveClasses }}"
                aria-pressed="{{ $currentLocale === $locale ? 'true' : 'false' }}">
            {{ $label }}
        </button>
    @endforeach
</form>
