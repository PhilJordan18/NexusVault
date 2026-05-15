<x-layouts.app>

    <!-- STATS ROW -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
        <!-- Compromised -->
        <div class="card rounded-3xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 bg-red-500/10 rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-exclamation-triangle text-red-500 text-xl"></i>
            </div>
            <div>
                <p class="text-3xl font-semibold">{{ $stats['compromised'] }}</p>
                <p class="text-sm text-[var(--text-secondary)]">Compromised</p>
            </div>
        </div>

        <!-- Reused -->
        <div class="card rounded-3xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 bg-amber-500/10 rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-sync text-amber-500 text-xl"></i>
            </div>
            <div>
                <p class="text-3xl font-semibold">{{ $stats['reused'] }}</p>
                <p class="text-sm text-[var(--text-secondary)]">Reused Passwords</p>
            </div>
        </div>

        <!-- Weak -->
        <div class="card rounded-3xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 bg-yellow-500/10 rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-shield-halved text-yellow-500 text-xl"></i>
            </div>
            <div>
                <p class="text-3xl font-semibold">{{ $stats['weak'] }}</p>
                <p class="text-sm text-[var(--text-secondary)]">Weak Passwords</p>
            </div>
        </div>

        <!-- Secure -->
        <div class="card rounded-3xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 bg-emerald-500/10 rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-check-circle text-emerald-500 text-xl"></i>
            </div>
            <div>
                <p class="text-3xl font-semibold">{{ $stats['secure'] }}</p>
                <p class="text-sm text-[var(--text-secondary)]">Secure Items</p>
            </div>
        </div>
    </div>

    <!-- SERVICES SECTION -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold">All Services</h2>
            <p class="text-sm text-[var(--text-secondary)]">{{ count($grouped) }} services • {{ $grouped->sum('account_count') ?? 0 }} accounts</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-xs px-3 py-1 bg-[var(--bg-input)] rounded-full text-[var(--text-secondary)]">
                Last synced just now
            </div>
        </div>
    </div>

    <!-- SERVICES GRID -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @foreach ($grouped as $service)
            <a href="{{ route('services.show', $service->name) }}"
               class="group card hover:border-emerald-500/40 rounded-3xl p-5 transition-all duration-200 hover:-translate-y-0.5">

                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        @if ($service->favicon)
                            <img src="{{ $service->favicon }}" alt="" class="w-11 h-11 rounded-2xl object-contain bg-[var(--bg-input)] p-1">
                        @else
                            <div class="w-11 h-11 bg-[var(--bg-input)] rounded-2xl flex items-center justify-center text-2xl">
                                {{ strtoupper(substr($service->name, 0, 1)) }}
                            </div>
                        @endif

                        <div>
                            <p class="font-semibold text-lg group-hover:text-emerald-500 transition">{{ $service->name }}</p>
                            <p class="text-xs text-[var(--text-secondary)]">{{ $service->account_count }} accounts</p>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-xs text-[var(--text-secondary)]">Last used</div>
                        <div class="text-xs font-medium">{{ $service->last_modified?->diffForHumans() ?? '—' }}</div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-[var(--border-color)] flex items-center justify-between text-xs">
                    <div class="flex -space-x-2">
                        @for ($i = 0; $i < min(3, $service->account_count); $i++)
                            <div class="w-6 h-6 bg-[var(--bg-input)] border border-[var(--border-color)] rounded-full flex items-center justify-center text-[10px]">{{ $i }}</div>
                        @endfor
                    </div>
                    <span class="text-emerald-500 group-hover:underline">View all →</span>
                </div>
            </a>
        @endforeach
    </div>

    @if(count($grouped) === 0)
        <div class="text-center py-16">
            <div class="mx-auto w-16 h-16 bg-[var(--bg-input)] rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-key text-3xl text-[var(--text-secondary)]"></i>
            </div>
            <h3 class="text-xl font-medium mb-2">No services yet</h3>
            <p class="text-[var(--text-secondary)] mb-6">Start by adding your first password or login</p>
            <button onclick="showCreateModal()" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 rounded-2xl text-sm font-medium">
                Add your first item
            </button>
        </div>
    @endif

</x-layouts.app>
