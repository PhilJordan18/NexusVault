<x-layouts.app>
    @php
        $cards = [
            ['label' => __('Users'), 'value' => $stats['users'], 'icon' => 'fa-users', 'tone' => 'text-emerald-300', 'surface' => 'border-emerald-500/20 bg-emerald-500/10'],
            ['label' => __('Client vaults'), 'value' => $stats['client_vault_users'], 'icon' => 'fa-vault', 'tone' => 'text-sky-300', 'surface' => 'border-sky-500/20 bg-sky-500/10'],
            ['label' => __('Encrypted items'), 'value' => $stats['client_encrypted_items'], 'icon' => 'fa-lock', 'tone' => 'text-indigo-300', 'surface' => 'border-indigo-500/20 bg-indigo-500/10'],
            ['label' => __('Pending shares'), 'value' => $stats['pending_shares'], 'icon' => 'fa-share-nodes', 'tone' => 'text-amber-300', 'surface' => 'border-amber-500/20 bg-amber-500/10'],
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300">
                    <i class="fa-solid fa-user-shield text-[11px]"></i>
                    <span>{{ __('Admin') }}</span>
                </div>
                <h1 class="text-3xl font-semibold tracking-tight md:text-4xl">{{ __('NexusVault Admin') }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-[var(--text-secondary)]">
                    {{ __('Operational metadata only. Vault contents, recovery keys, and decrypted secrets are not available here.') }}
                </p>
            </div>

            <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] px-4 py-3 text-sm text-[var(--text-secondary)]">
                {{ __('Active shares') }}:
                <span class="font-semibold text-[var(--text-primary)]">{{ $stats['active_shares'] }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <div class="card rounded-2xl p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
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

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="card rounded-2xl p-4">
                <p class="text-xs text-[var(--text-secondary)]">{{ __('Verified emails') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ $stats['verified_users'] }} / {{ $stats['users'] }}</p>
            </div>
            <div class="card rounded-2xl p-4">
                <p class="text-xs text-[var(--text-secondary)]">{{ __('OAuth accounts') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ $stats['oauth_users'] }}</p>
            </div>
            <div class="card rounded-2xl p-4">
                <p class="text-xs text-[var(--text-secondary)]">{{ __('Legacy vault users') }}</p>
                <p class="mt-1 text-xl font-semibold {{ $stats['legacy_vault_users'] > 0 ? 'text-amber-300' : 'text-emerald-300' }}">
                    {{ $stats['legacy_vault_users'] }}
                </p>
            </div>
        </div>

        <div class="card overflow-hidden rounded-2xl">
            <div class="flex flex-col gap-1 border-b border-[var(--border-color)] px-5 py-4">
                <h2 class="text-lg font-semibold">{{ __('Users') }}</h2>
                <p class="text-sm text-[var(--text-secondary)]">{{ __('No vault secrets are displayed or decrypted.') }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-[var(--border-color)] text-xs uppercase text-[var(--text-secondary)]">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Vault') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Items') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Created') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                    @foreach ($users as $user)
                        @php
                            $vaultStatus = $user->usesClientSideVault()
                                ? __('Client encrypted')
                                : ($user->requiresClientVaultSetup() ? __('Setup required') : __('Legacy / inactive'));
                        @endphp

                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-medium">{{ $user->name }}</div>
                                <div class="text-xs text-[var(--text-secondary)]">{{ $user->email }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full border border-[var(--border-color)] px-2 py-1 text-xs">
                                        {{ $user->hasVerifiedEmail() ? __('Verified') : __('Unverified') }}
                                    </span>
                                    @if ($user->mfa_enabled)
                                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-xs text-emerald-300">{{ __('MFA') }}</span>
                                    @endif
                                    @if ($user->is_oauth)
                                        <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-xs text-sky-300">{{ __('OAuth') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-[var(--text-secondary)]">{{ $vaultStatus }}</td>
                            <td class="px-5 py-4 font-medium">{{ $user->services_count }}</td>
                            <td class="px-5 py-4 text-[var(--text-secondary)]">{{ $user->created_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[var(--border-color)] px-5 py-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
