<x-layouts.app>
    @php
        $pendingCount = $pendingShares->count();
        $clientSharePayloads = $pendingShares
            ->mapWithKeys(function ($share) {
                $payload = json_decode($share->shared_data, true) ?: [];

                return in_array($payload['mode'] ?? null, ['client-encrypted', 'client-encrypted-sync'], true)
                    ? [$share->id => [
                        'version' => $payload['version'] ?? 1,
                        'mode' => $payload['mode'],
                        'encrypted_aes_key' => $payload['encrypted_aes_key'] ?? '',
                        'encrypted_data' => $payload['encrypted_data'] ?? [],
                        'shared_fields' => $payload['shared_fields'] ?? null,
                    ]]
                    : [];
            });
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-400">
                    <i class="fa-solid fa-share-nodes"></i>
                    <span>{{ __('Secure Sharing') }}</span>
                </div>
                <h1 class="text-3xl font-semibold tracking-normal sm:text-4xl">{{ __('Notifications') }}</h1>
                <p class="mt-2 text-[var(--text-secondary)]">{{ __('Pending share requests') }}</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] px-4 py-2 text-sm text-[var(--text-secondary)]">
                <i class="fa-solid fa-inbox text-emerald-400"></i>
                {{ $pendingCount }} {{ trans_choice('pending_share_count', $pendingCount) }}
            </span>
        </div>

        @if($pendingShares->isEmpty())
            <div class="card rounded-2xl px-6 py-14 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/10">
                    <i class="fa-solid fa-bell text-3xl text-emerald-400"></i>
                </div>
                <h2 class="text-xl font-semibold">{{ __('All caught up!') }}</h2>
                <p class="mt-2 text-[var(--text-secondary)]">{{ __('You have no pending share requests.') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($pendingShares as $share)
                    @php
                        $payload = json_decode($share->shared_data, true) ?: [];
                        $isClientEncrypted = in_array($payload['mode'] ?? null, ['client-encrypted', 'client-encrypted-sync'], true);
                        $serviceName = $share->service->name ?? __('Unknown Service');
                        $serviceInitial = strtoupper(substr($serviceName, 0, 1));
                    @endphp

                    <div class="card flex flex-col gap-4 rounded-2xl p-4 transition hover:border-emerald-500/40 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                        <div class="flex min-w-0 items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] text-lg font-semibold text-emerald-400">
                                {{ $serviceInitial }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate font-semibold">{{ $serviceName }}</p>
                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-400">{{ __('New Share') }}</span>
                                </div>
                                <p class="mt-1 text-sm text-[var(--text-secondary)]">
                                    {{ __('From') }} <span class="font-medium text-[var(--text-primary)]">{{ $share->fromUser->name ?? __('Unknown user') }}</span> • {{ $share->shared_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <form action="{{ route('shares.accept', $share) }}"
                                  method="POST"
                                  @if($isClientEncrypted) data-client-share-accept-form data-share-id="{{ $share->id }}" @endif>
                                @csrf
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 sm:w-auto">
                                    <i class="fa-solid fa-check"></i>
                                    <span>{{ __('Accept') }}</span>
                                </button>
                            </form>

                            <form action="{{ route('shares.reject', $share) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[var(--bg-input)] px-5 py-2.5 text-sm font-medium text-[var(--text-primary)] transition hover:bg-white/10 sm:w-auto">
                                    <i class="fa-solid fa-times"></i>
                                    <span>{{ __('Reject') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($clientSharePayloads->isNotEmpty())
        <script>
            window.nexusVaultPendingClientShares = Object.assign(
                {},
                window.nexusVaultPendingClientShares || {},
                {{ Illuminate\Support\Js::from($clientSharePayloads) }}
            );
            window.nexusVaultEncryptedPrivateKey = {{ Illuminate\Support\Js::from(json_decode(auth()->user()->private_key, true)) }};
        </script>
    @endif
</x-layouts.app>
