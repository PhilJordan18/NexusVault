<x-layouts.app>
    @php
        $itemCount = count($accounts);
        $firstAccount = $accounts->first();
        $lastUpdated = $firstAccount?->updated_at?->diffForHumans() ?? '';
        $firstUrl = $firstAccount->url ?? '';
        $itemType = $firstAccount->type ?? \App\Models\Service::TYPE_LOGIN;
        $addButtonLabel = $itemType === \App\Models\Service::TYPE_PAYMENT_CARD
            ? __('Add Card')
            : ($itemType === \App\Models\Service::TYPE_SECURE_NOTE ? __('Add Note') : __('Add Account'));
        $serviceTranslations = [
            'Username / Email' => __('Username / Email'),
            'Password' => __('Password'),
            'Notes (optional)' => __('Notes (optional)'),
            'Edit Account' => __('Edit Account'),
            'Cardholder Name' => __('Cardholder Name'),
            'Card Number' => __('Card Number'),
            'Expiry, CVC, PIN, billing notes' => __('Expiry, CVC, PIN, billing notes'),
            'Edit Card' => __('Edit Card'),
            'Reference' => __('Reference'),
            'Secure Content' => __('Secure Content'),
            'Extra Notes' => __('Extra Notes'),
            'Edit Note' => __('Edit Note'),
            'Please select an account first.' => __('Please select an account first.'),
            'Username and password are required.' => __('Username and password are required.'),
            'Account updated successfully!' => __('Account updated successfully!'),
            'Error' => __('Error'),
            'Failed to update' => __('Failed to update'),
            'Network error while updating account.' => __('Network error while updating account.'),
            'Share feature not available.' => __('Share feature not available.'),
            'Revoke access for this recipient?' => __('Revoke access for this recipient?'),
            'Failed to revoke access.' => __('Failed to revoke access.'),
            'Shared access revoked.' => __('Shared access revoked.'),
            'Shared copy' => __('Shared copy'),
            'Shared sync' => __('Shared sync'),
            'Failed to revoke shared access.' => __('Failed to revoke shared access.'),
            'Unknown user' => __('Unknown user'),
            'Accepted' => __('Accepted'),
            'Pending' => __('Pending'),
            'Revoke' => __('Revoke'),
            'Compromised Password' => __('Compromised Password'),
            'This password was found in a data breach. Change it immediately.' => __('This password was found in a data breach. Change it immediately.'),
            'Reused Password' => __('Reused Password'),
            'This password is used on multiple accounts. For better security, use a unique one.' => __('This password is used on multiple accounts. For better security, use a unique one.'),
            'Weak Password' => __('Weak Password'),
            'This password is too easy to guess. We recommend using a stronger one.' => __('This password is too easy to guess. We recommend using a stronger one.'),
            'Very Strong Password' => __('Very Strong Password'),
            'Excellent entropy. This password is highly secure.' => __('Excellent entropy. This password is highly secure.'),
            'No Issues Found' => __('No Issues Found'),
            'This password is strong and secure.' => __('This password is strong and secure.'),
            'Change Password' => __('Change Password'),
            'Delete this shared account for everyone? This will revoke access for all recipients.' => __('Delete this shared account for everyone? This will revoke access for all recipients.'),
            'Remove this shared account from your vault? The original will remain available to its owner.' => __('Remove this shared account from your vault? The original will remain available to its owner.'),
            'Are you sure you want to delete this account?' => __('Are you sure you want to delete this account?'),
            'Failed to delete account.' => __('Failed to delete account.'),
            'Network error.' => __('Network error.'),
        ];
    @endphp

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-3xl font-semibold tracking-tight md:text-4xl">{{ $name }}</h1>
                <p class="mt-2 text-sm text-[var(--text-secondary)]">
                    {{ $itemCount }} {{ trans_choice('item_count', $itemCount) }} • {{ __('Last updated') }} {{ $lastUpdated }}
                </p>
            </div>

            <button onclick="showCreateModalForService('{{ $name }}', '{{ $firstUrl }}', '{{ $itemType }}')"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700">
                <i class="fa-solid fa-plus"></i>
                <span>{{ $addButtonLabel }}</span>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

            <!-- ACCOUNTS LIST -->
            <div class="lg:col-span-4">
                <div class="card rounded-2xl p-2">
                    @foreach ($accounts as $account)
                        <div onclick="window.selectAccount({{ $account->id }})"
                             data-account-list-item="{{ $account->id }}"
                             class="group flex cursor-pointer items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-[var(--bg-input)] {{ $loop->first ? 'bg-[var(--bg-input)]' : '' }}">

                            <div data-account-list-icon="{{ $account->id }}" class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] text-sm font-semibold">
                                {{ strtoupper(substr($account->client_encrypted ? ($account->name ?? $name) : $account->username, 0, 1)) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <p data-account-list-label="{{ $account->id }}" class="font-medium truncate">
                                    {{ $account->client_encrypted ? ($account->name ?? $name) : $account->username }}
                                </p>
                                <div class="flex items-center gap-2 text-xs text-[var(--text-secondary)]">
                                    <span>{{ $account->updated_at->diffForHumans() }}</span>
                                    @if ($account->shared_group_id)
                                        <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-emerald-400">
                                            {{ auth()->user()->usesClientSideVault() && ! $account->shared_key_envelope ? __('Shared copy') : __('Shared sync') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="opacity-0 group-hover:opacity-100 transition">
                                <i class="fa-solid fa-chevron-right text-[var(--text-secondary)]"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- DETAIL PANEL -->
            <div class="lg:col-span-8">
                <div id="detail-panel" class="hidden card rounded-2xl p-6">

                    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1 min-w-0">
                            <h2 id="detail-username" class="truncate text-2xl font-semibold" title=""></h2>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <p id="detail-name" class="text-[var(--text-secondary)] truncate"></p>
                                <span id="detail-shared-badge" class="hidden rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs text-emerald-400">
                                    {{ __('Shared sync') }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-2 flex-shrink-0">
                            <button onclick="window.editAccount()"
                                    class="px-4 py-2 text-sm bg-[var(--bg-input)] hover:bg-white/10 rounded-2xl flex items-center gap-2 whitespace-nowrap">
                                <i class="fa-solid fa-edit"></i>
                                <span>{{ __('Edit') }}</span>
                            </button>

                            <button id="share-account-button" onclick="window.shareCurrentAccount()"
                                    class="px-4 py-2 text-sm bg-emerald-600 hover:bg-emerald-700 rounded-2xl flex items-center gap-2 text-white whitespace-nowrap">
                                <i class="fa-solid fa-share"></i>
                                <span>{{ __('Share') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Secret -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-3">
                            <div id="detail-secret-label" class="text-xs uppercase tracking-widest text-[var(--text-secondary)]">{{ __('Password') }}</div>
                            <button onclick="window.togglePassword()"
                                    class="text-xs flex items-center gap-1.5 text-emerald-500 hover:text-emerald-400">
                                <i class="fa-solid fa-eye"></i>
                                <span>{{ __('Show') }}</span>
                            </button>
                        </div>

                        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-5 py-4 font-mono text-base" id="detail-password">
                            ••••••••••••
                        </div>
                    </div>

                    <!-- Website -->
                    <div id="detail-url-container" class="mb-8">
                        <div class="text-xs uppercase tracking-widest text-[var(--text-secondary)] mb-2">{{ __('Website') }}</div>
                        <a id="detail-url" target="_blank" class="text-emerald-500 hover:underline break-all"></a>
                    </div>

                    <!-- Notes -->
                    <div id="detail-notes-container" class="hidden">
                        <div class="text-xs uppercase tracking-widest text-[var(--text-secondary)] mb-2">{{ __('Notes') }}</div>
                        <div id="detail-notes" class="rounded-2xl bg-[var(--bg-input)] p-4 text-sm leading-relaxed text-[var(--text-secondary)]"></div>
                    </div>

                    <div id="detail-shares-container" class="hidden mt-8">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-xs uppercase tracking-widest text-[var(--text-secondary)]">{{ __('Shared with') }}</div>
                        </div>
                        <div id="detail-shares-list" class="space-y-3"></div>
                    </div>

                </div>

                <!-- Empty State -->
                <div id="empty-state" class="h-full flex items-center justify-center text-center py-12">
                    <div>
                        <div class="mx-auto w-14 h-14 bg-[var(--bg-input)] rounded-full flex items-center justify-center mb-4">
                            <i class="fa-solid fa-key text-2xl text-[var(--text-secondary)]"></i>
                        </div>
                        <p class="text-[var(--text-secondary)]">{!! __('Select an account from the list<br>to view details') !!}</p>
                    </div>
                </div>

                <div id="security-panel" class="hidden mt-6"></div>

                <div id="delete-account-container" class="hidden mt-6 text-center">
                    <button onclick="window.deleteAccount()"
                            class="text-red-500 hover:text-red-600 px-4 py-2 text-sm rounded-2xl hover:bg-red-500/10 transition">
                        <i class="fa-solid fa-trash mr-2"></i>{{ __('Delete this account') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT ACCOUNT MODAL -->
    <div id="edit-account-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4">
        <div class="card max-h-[calc(100vh-2rem)] w-full max-w-md overflow-y-auto rounded-2xl p-6 sm:p-8">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10">
                        <i class="fa-solid fa-pen-to-square text-emerald-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold">{{ __('Edit Account') }}</h3>
                </div>
                <button type="button" onclick="hideEditModal()"
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl text-[var(--text-secondary)] transition hover:bg-[var(--bg-input)] hover:text-[var(--text-primary)]">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="edit-account-form" onsubmit="submitEditAccount(event)">
                <input type="hidden" id="edit-service-id">
                <input type="hidden" id="edit-type">
                <input type="hidden" id="edit-name">
                <input type="hidden" id="edit-url">
                <!-- Username / Email -->
                <div class="mb-5">
                    <label id="edit-username-label" class="block text-sm text-[var(--text-secondary)] mb-2">{{ __('Username / Email') }}</label>
                    <input type="text" id="edit-username" required
                           class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl px-5 py-3.5">
                </div>

                <!-- Password -->
                <div class="mb-5">
                    <label id="edit-password-label" class="block text-sm text-[var(--text-secondary)] mb-2">{{ __('Password') }}</label>
                    <div class="relative">
                        <input type="password" id="edit-password" required
                               class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl px-5 py-3.5 pr-12 font-mono">
                        <button type="button" id="edit-password-toggle"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div id="edit-strength-container" class="mt-2 hidden">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-[var(--text-secondary)]">{{ __('Password strength') }}</span>
                            <span id="edit-strength-text" class="font-medium">{{ __('Very weak') }}</span>
                        </div>
                        <div class="h-1.5 bg-[var(--bg-input)] rounded-full overflow-hidden">
                            <div id="edit-strength-bar" class="h-full w-0 transition-all duration-300 bg-red-500"></div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label id="edit-notes-label" class="block text-sm text-[var(--text-secondary)] mb-2">{{ __('Notes (optional)') }}</label>
                    <textarea id="edit-notes" rows="3"
                              class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl px-5 py-3.5 resize-y"></textarea>
                </div>

                <button type="button" id="edit-generate-btn"
                        data-password-generate
                        data-password-target="edit-password"
                        data-password-min-length="8"
                        class="w-full flex items-center justify-center gap-2 text-emerald-500 hover:text-emerald-400 text-sm font-medium mb-4 transition">
                    <i class="fa-solid fa-dice"></i>
                    <span>{{ __('Generate strong password') }}</span>
                </button>

                <div class="flex flex-col-reverse gap-3 sm:flex-row">
                    <button type="button" onclick="hideEditModal()"
                            class="flex-1 py-3.5 rounded-2xl border border-[var(--border-color)] hover:bg-[var(--bg-input)] transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                            class="flex-1 py-3.5 bg-emerald-600 hover:bg-emerald-700 rounded-2xl text-white font-medium transition">
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.accounts = {{ Illuminate\Support\Js::from($accountsPayload) }};
        window.nexusVaultTranslations = Object.assign(
            {},
            window.nexusVaultTranslations || {},
            {{ Illuminate\Support\Js::from($serviceTranslations) }}
        );
    </script>
</x-layouts.app>
