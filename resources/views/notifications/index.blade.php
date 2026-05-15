<x-layouts.app>
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-semibold">Notifications</h1>
                <p class="text-[var(--text-secondary)]">Pending share requests</p>
            </div>
            <span class="text-sm text-[var(--text-secondary)]">{{ $pendingShares->count() }} pending</span>
        </div>

        @if($pendingShares->isEmpty())
            <div class="card rounded-3xl p-12 text-center">
                <div class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-bell text-4xl text-emerald-500"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">All caught up!</h3>
                <p class="text-[var(--text-secondary)]">You have no pending share requests.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($pendingShares as $share)
                    <div class="card rounded-3xl p-6 flex items-center justify-between hover:border-emerald-500/40 transition">
                        <div class="flex items-center gap-4">
                            <!-- Avatar / Icon -->
                            <div class="w-12 h-12 bg-[var(--bg-input)] rounded-2xl flex items-center justify-center">
                                <i class="fa-solid fa-user text-2xl text-emerald-500"></i>
                            </div>

                            <div>
                                <div class="flex items-center gap-3">
                                    <p class="font-semibold">{{ $share->service->name ?? 'Unknown Service' }}</p>
                                    <span class="text-xs bg-emerald-500/20 text-emerald-500 px-2 py-0.5 rounded-full">New Share</span>
                                </div>
                                <p class="text-sm text-[var(--text-secondary)]">
                                    From <span class="font-medium text-[var(--text-primary)]">{{ $share->fromUser->name }}</span> • {{ $share->shared_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Accept -->
                            <form action="{{ route('shares.accept', $share) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-medium flex items-center gap-2 transition">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Accept</span>
                                </button>
                            </form>

                            <!-- Reject -->
                            <form action="{{ route('shares.reject', $share) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-6 py-2.5 bg-[var(--bg-input)] hover:bg-white/20 text-[var(--text-primary)] rounded-2xl text-sm font-medium flex items-center gap-2 transition">
                                    <i class="fa-solid fa-times"></i>
                                    <span>Reject</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
