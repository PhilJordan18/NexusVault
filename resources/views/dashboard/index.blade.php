<x-layouts.app>
    @php
        $groupedCount = count($grouped);
        $accountCount = $grouped->sum('account_count') ?? 0;
        $securityIssueCount = $stats['compromised'] + $stats['reused'] + $stats['weak'];
        $statCards = [
            [
                'label' => __('Compromised'),
                'value' => $stats['compromised'],
                'icon' => 'fa-triangle-exclamation',
                'tone' => 'text-red-400',
                'surface' => 'bg-red-500/10 border-red-500/20',
            ],
            [
                'label' => __('Reused Passwords'),
                'value' => $stats['reused'],
                'icon' => 'fa-repeat',
                'tone' => 'text-amber-300',
                'surface' => 'bg-amber-500/10 border-amber-500/20',
            ],
            [
                'label' => __('Weak Passwords'),
                'value' => $stats['weak'],
                'icon' => 'fa-shield-halved',
                'tone' => 'text-yellow-300',
                'surface' => 'bg-yellow-500/10 border-yellow-500/20',
            ],
            [
                'label' => __('Payment Cards'),
                'value' => $stats['cards'],
                'icon' => 'fa-credit-card',
                'tone' => 'text-sky-300',
                'surface' => 'bg-sky-500/10 border-sky-500/20',
            ],
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300">
                    <i class="fa-solid fa-lock text-[11px]"></i>
                    <span>{{ __('Security') }}</span>
                </div>
                <h1 class="text-3xl font-semibold tracking-tight md:text-4xl">{{ __('All Items') }}</h1>
                <p class="mt-2 text-sm text-[var(--text-secondary)]">
                    {{ $groupedCount }} {{ trans_choice('service_count', $groupedCount) }} •
                    {{ $accountCount }} {{ trans_choice('account_count', $accountCount) }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center">
                <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] px-4 py-3">
                    <p class="text-xs text-[var(--text-secondary)]">{{ __('All Services') }}</p>
                    <p class="text-xl font-semibold">{{ $groupedCount }}</p>
                </div>
                <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] px-4 py-3">
                    <p class="text-xs text-[var(--text-secondary)]">{{ __('Security') }}</p>
                    <p class="text-xl font-semibold {{ $securityIssueCount > 0 ? 'text-amber-300' : 'text-emerald-300' }}">{{ $securityIssueCount }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($statCards as $card)
                <div class="card rounded-2xl p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-[var(--text-secondary)]">{{ $card['label'] }}</p>
                            <p class="mt-1 text-2xl font-semibold">{{ $card['value'] }}</p>
                        </div>
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border {{ $card['surface'] }}">
                            <i class="fa-solid {{ $card['icon'] }} {{ $card['tone'] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">{{ __('All Services') }}</h2>
                <p class="text-sm text-[var(--text-secondary)]">{{ __('Last synced just now') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($grouped as $service)
                @php
                    $isCard = $service->type === \App\Models\Service::TYPE_PAYMENT_CARD;
                    $isNote = $service->type === \App\Models\Service::TYPE_SECURE_NOTE;
                    $typeLabel = $isCard ? __('Payment card') : ($isNote ? __('Secure note') : $service->account_count.' '.trans_choice('account_count', $service->account_count));
                    $iconClass = $isCard ? 'fa-credit-card text-sky-300' : ($isNote ? 'fa-note-sticky text-indigo-300' : 'fa-key text-emerald-300');
                    $iconSurface = $isCard ? 'bg-sky-500/10 border-sky-500/20' : ($isNote ? 'bg-indigo-500/10 border-indigo-500/20' : 'bg-emerald-500/10 border-emerald-500/20');
                @endphp

                <a href="{{ route('services.show', ['name' => $service->name, 'type' => $service->type]) }}"
                   class="group card flex min-h-36 flex-col justify-between rounded-2xl p-5 transition hover:-translate-y-0.5 hover:border-emerald-500/40">
                    <div class="flex items-start gap-4">
                        @if ($service->favicon && ! $isCard && ! $isNote)
                            <img src="{{ $service->favicon }}" alt="" class="h-11 w-11 flex-shrink-0 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] object-contain p-1"
                                 onerror="this.onerror=null;this.src='{{ asset('logo/LogoMonogramme.svg') }}';">
                        @else
                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border {{ $iconSurface }}">
                                <i class="fa-solid {{ $iconClass }}"></i>
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-base font-semibold transition group-hover:text-emerald-300">{{ $service->name }}</p>
                                    <p class="mt-1 truncate text-xs text-[var(--text-secondary)]">{{ $typeLabel }}</p>
                                </div>
                                <i class="fa-solid fa-chevron-right mt-1 text-xs text-[var(--text-secondary)] opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100"></i>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between border-t border-[var(--border-color)] pt-4 text-xs text-[var(--text-secondary)]">
                        <span>{{ __('Last updated') }}</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $service->last_modified?->diffForHumans() ?? '-' }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        @if(count($grouped) === 0)
            <div class="card rounded-2xl px-6 py-14 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-500/20 bg-emerald-500/10">
                    <i class="fa-solid fa-key text-2xl text-emerald-300"></i>
                </div>
                <h3 class="text-lg font-semibold">{{ __('No services yet') }}</h3>
                <p class="mx-auto mt-2 max-w-sm text-sm text-[var(--text-secondary)]">{{ __('Start by adding your first password or login') }}</p>
                <button onclick="showCreateModal()" class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-emerald-700">
                    <i class="fa-solid fa-plus"></i>
                    <span>{{ __('Add your first item') }}</span>
                </button>
            </div>
        @endif
    </div>

</x-layouts.app>
