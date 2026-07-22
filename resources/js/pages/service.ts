import { bindPasswordStrength } from '../../ts/utils/password-utils';
import {
    decryptSharedKeyFromVault,
    decryptSharedVaultString,
    decryptVaultString,
    encryptSharedItemFields,
    encryptVaultString,
    hasStoredVaultKey,
} from '../../ts/zero-knowledge';
import type { EncryptedString, SharedKeyEnvelope } from '../../ts/zero-knowledge';

// Types
type Account = {
    id: number;
    type?: ItemType;
    username: string;
    username_iv?: string | null;
    username_tag?: string | null;
    password: string;
    password_iv?: string | null;
    password_tag?: string | null;
    url?: string;
    notes?: string;
    notes_iv?: string | null;
    notes_tag?: string | null;
    name?: string;
    client_encrypted?: boolean;
    shared_user_id?: number | null;
    strength?: string;          // 'very_weak', 'weak', 'strong', 'very_strong'
    compromised?: boolean;
    reused?: boolean;
    shared_group_id?: string | null;
    shared_key_envelope?: SharedKeyEnvelope | null;
    shared_with?: ShareRecipient[];
};

type ItemType = 'login' | 'payment_card' | 'secure_note';

type ShareRecipient = {
    id: number;
    name?: string | null;
    email?: string | null;
    status: 'Accepted' | 'Pending';
    shared_at?: string | null;
};

// GLOBAL STATE
let currentAccount: Account | null = null;

function t(key: string): string {
    const translations = (window as any).nexusVaultTranslations as Record<string, string> | undefined;

    return translations?.[key] ?? key;
}

const itemTypeLabels: Record<ItemType, {
    username: string;
    secret: string;
    notes: string;
    hiddenSecret: string;
    editTitle: string;
}> = {
    login: {
        username: t('Username / Email'),
        secret: t('Password'),
        notes: t('Notes (optional)'),
        hiddenSecret: '••••••••••••',
        editTitle: t('Edit Account'),
    },
    payment_card: {
        username: t('Cardholder Name'),
        secret: t('Card Number'),
        notes: t('Expiry, CVC, PIN, billing notes'),
        hiddenSecret: '•••• •••• •••• ••••',
        editTitle: t('Edit Card'),
    },
    secure_note: {
        username: t('Reference'),
        secret: t('Secure Content'),
        notes: t('Extra Notes'),
        hiddenSecret: '••••••••••••',
        editTitle: t('Edit Note'),
    },
};

function typeOf(account: Account): ItemType {
    return account.type ?? 'login';
}

function labelsFor(account: Account) {
    return itemTypeLabels[typeOf(account)];
}

// Helper CSRF
function csrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;

    return meta?.content || '';
}

function normalizeUrl(value: string): string | null {
    const trimmed = value.trim();

    if (!trimmed) {
        return null;
    }

    return trimmed.includes('://') ? trimmed : `https://${trimmed}`;
}

function usesClientEncryption(): boolean {
    return Boolean((window as any).nexusVaultUsesClientEncryption);
}

async function decryptAccount(account: Account): Promise<Account> {
    if (!account.client_encrypted) {
        return account;
    }

    if (!hasStoredVaultKey()) {
        window.location.href = '/vault/unlock';

        return account;
    }

    const sharedKey = account.shared_key_envelope
        ? await decryptSharedKeyFromVault(account.shared_key_envelope)
        : null;
    const decryptString = sharedKey
        ? (encrypted: EncryptedString) => decryptSharedVaultString(encrypted, sharedKey)
        : decryptVaultString;

    const username = await decryptString({
        ciphertext: account.username,
        iv: account.username_iv ?? '',
        tag: account.username_tag ?? '',
    });

    const password = await decryptString({
        ciphertext: account.password,
        iv: account.password_iv ?? '',
        tag: account.password_tag ?? '',
    });

    const notes = account.notes && account.notes_iv && account.notes_tag
        ? await decryptString({
            ciphertext: account.notes,
            iv: account.notes_iv,
            tag: account.notes_tag,
        })
        : undefined;

    return {
        ...account,
        username,
        password,
        notes,
        client_encrypted: false,
    };
}

async function decryptLoadedAccounts(): Promise<void> {
    const accounts = (window as any).accounts as Record<number, Account> | undefined;

    if (!accounts || !usesClientEncryption()) {
        return;
    }

    const entries = await Promise.all(
        Object.entries(accounts).map(async ([id, account]) => [id, await decryptAccount(account)] as const)
    );

    (window as any).accounts = Object.fromEntries(entries);
    entries.forEach(([, account]) => updateAccountListItem(account));
}

async function encryptEditableFields(account: Account, username: string, password: string, notes: string | null): Promise<{
    username: EncryptedString;
    password: EncryptedString;
    notes: EncryptedString | null;
}> {
    if (account.shared_key_envelope) {
        const sharedKey = await decryptSharedKeyFromVault(account.shared_key_envelope);
        const sharedFields = await encryptSharedItemFields({ username, password, notes }, sharedKey);

        return {
            username: sharedFields.username,
            password: sharedFields.password,
            notes: sharedFields.notes ?? null,
        };
    }

    return {
        username: await encryptVaultString(username),
        password: await encryptVaultString(password),
        notes: notes ? await encryptVaultString(notes) : null,
    };
}

function accountListLabel(account: Account): string {
    return account.username?.trim() || account.name?.trim() || t('Encrypted item');
}

function accountListInitial(account: Account): string {
    return accountListLabel(account).trim().charAt(0).toUpperCase() || '•';
}

function updateAccountListItem(account: Account): void {
    const label = document.querySelector<HTMLElement>(`[data-account-list-label="${account.id}"]`);
    const icon = document.querySelector<HTMLElement>(`[data-account-list-icon="${account.id}"]`);

    if (label) {
        label.textContent = accountListLabel(account);
    }

    if (icon) {
        icon.textContent = accountListInitial(account);
    }
}

function updateSelectedListItem(id: number): void {
    document.querySelectorAll<HTMLElement>('[data-account-list-item]').forEach(item => {
        item.classList.toggle('bg-[var(--bg-input)]', item.dataset.accountListItem === id.toString());
    });
}

// ===== PUBLIC API =====

(window as any).selectAccount = (id: number) => {
    const accounts = (window as any).accounts as Record<number, Account>;
    const account = accounts[id];

    if (!account) {
        return;
    }

    currentAccount = account;
    (window as any).nexusVaultCurrentAccount = account;
    const labels = labelsFor(account);
    updateSelectedListItem(id);

    const panel = document.getElementById('detail-panel')!;
    panel.classList.remove('hidden');
    document.getElementById('empty-state')?.classList.add('hidden');

    document.getElementById('delete-account-container')?.classList.remove('hidden');

    (document.getElementById('detail-username') as HTMLElement).textContent = account.username;
    (document.getElementById('detail-name') as HTMLElement).textContent = account.name || '';
    (document.getElementById('detail-secret-label') as HTMLElement).textContent = labels.secret;

    const sharedBadge = document.getElementById('detail-shared-badge');

    if (account.shared_group_id) {
        if (sharedBadge) {
            sharedBadge.classList.remove('hidden');
            sharedBadge.textContent = account.shared_key_envelope ? t('Shared sync') : t('Shared copy');
        }
    } else {
        sharedBadge?.classList.add('hidden');
    }

    document.getElementById('share-account-button')?.classList.toggle(
        'hidden',
        account.shared_user_id !== null && account.shared_user_id !== undefined
    );

    const passwordEl = document.getElementById('detail-password') as HTMLElement;
    passwordEl.textContent = labels.hiddenSecret;
    passwordEl.dataset.hidden = 'true';
    passwordEl.dataset.realPassword = account.password || '';

    const urlEl = document.getElementById('detail-url') as HTMLAnchorElement;
    const urlContainer = document.getElementById('detail-url-container');

    if (account.url && typeOf(account) === 'login') {
        urlEl.href = account.url;
        urlEl.textContent = account.url;
        urlContainer?.classList.remove('hidden');
    } else {
        urlContainer?.classList.add('hidden');
    }

    const notesContainer = document.getElementById('detail-notes-container')!;
    const notesEl = document.getElementById('detail-notes')!;

    if (account.notes) {
        notesEl.textContent = account.notes;
        notesContainer.classList.remove('hidden');
    } else {
        notesContainer.classList.add('hidden');
    }

    renderShareRecipients(account);
    renderSecurityFromAccount(account);
};

(window as any).togglePassword = () => {

    if (!currentAccount) {
        return;
    }

    const el = document.getElementById('detail-password') as HTMLElement;

    if (el.dataset.hidden === 'true') {
        el.textContent = currentAccount.password;
        el.dataset.hidden = 'false';
    } else {
        el.textContent = labelsFor(currentAccount).hiddenSecret;
        el.dataset.hidden = 'true';
    }
};

// ===== EDIT =====

(window as any).editAccount = () => {
    if (!currentAccount) {
        alert(t('Please select an account first.'));

        return;
    }

    const labels = labelsFor(currentAccount);
    (document.getElementById('edit-service-id') as HTMLInputElement).value = currentAccount.id.toString();
    (document.getElementById('edit-type') as HTMLInputElement).value = typeOf(currentAccount);
    (document.getElementById('edit-username') as HTMLInputElement).value = currentAccount.username;
    (document.getElementById('edit-password') as HTMLInputElement).value = currentAccount.password;
    (document.getElementById('edit-notes') as HTMLTextAreaElement).value = currentAccount.notes || '';
    (document.getElementById('edit-name') as HTMLInputElement).value = currentAccount.name ?? '';
    (document.getElementById('edit-url') as HTMLInputElement).value = currentAccount.url ?? '';
    (document.getElementById('edit-username-label') as HTMLElement).textContent = labels.username;
    (document.getElementById('edit-password-label') as HTMLElement).textContent = labels.secret;
    (document.getElementById('edit-notes-label') as HTMLElement).textContent = labels.notes;
    document.querySelector('#edit-account-modal h3')!.textContent = labels.editTitle;

    const editPassword = document.getElementById('edit-password') as HTMLInputElement;
    const isLogin = typeOf(currentAccount) === 'login';
    editPassword.type = isLogin ? 'password' : 'text';
    editPassword.dataset.passwordStrengthDisabled = isLogin ? 'false' : 'true';
    document.getElementById('edit-generate-btn')?.classList.toggle('hidden', !isLogin);
    document.querySelector('[data-password-generator-controls-for="edit-generate-btn"]')?.classList.toggle('hidden', !isLogin);
    document.getElementById('edit-strength-container')?.classList.toggle('hidden', !isLogin);
    document.getElementById('edit-url-container')?.classList.toggle('hidden', !isLogin);

    document.getElementById('edit-account-modal')!.classList.remove('hidden');
};

(window as any).hideEditModal = () => {
    document.getElementById('edit-account-modal')!.classList.add('hidden');
};

(window as any).toggleEditPasswordVisibility = () => {
    const input = document.getElementById('edit-password') as HTMLInputElement;
    const icon = document.getElementById('edit-password-icon') as HTMLElement;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
};

(window as any).submitEditAccount = async (e: Event) => {
    e.preventDefault();

    if (!currentAccount) {
        alert(t('Please select an account first.'));

        return;
    }

    const serviceId = Number((document.getElementById('edit-service-id') as HTMLInputElement).value);
    const type = (document.getElementById('edit-type') as HTMLInputElement).value as ItemType;
    const username = (document.getElementById('edit-username') as HTMLInputElement).value.trim();
    const password = (document.getElementById('edit-password') as HTMLInputElement).value.trim();
    const notes = (document.getElementById('edit-notes') as HTMLTextAreaElement).value.trim();

    if (!username || !password) {
        alert(t('Username and password are required.'));

        return;
    }

    const buttons = document.querySelectorAll<HTMLButtonElement>('#edit-account-form button');
    buttons.forEach(b => (b.disabled = true));

    try {
        const encryptedFields = usesClientEncryption()
            ? await encryptEditableFields(currentAccount, username, password, notes || null)
            : null;

        const response = await fetch(`/services/${serviceId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: (document.getElementById('edit-name') as HTMLInputElement).value,
                type,
                url: type === 'login' ? normalizeUrl((document.getElementById('edit-url') as HTMLInputElement).value) : null,
                username: encryptedFields?.username.ciphertext ?? username,
                username_iv: encryptedFields?.username.iv,
                username_tag: encryptedFields?.username.tag,
                password: encryptedFields?.password.ciphertext ?? password,
                password_iv: encryptedFields?.password.iv,
                password_tag: encryptedFields?.password.tag,
                notes: encryptedFields?.notes?.ciphertext ?? (notes || null),
                notes_iv: encryptedFields?.notes?.iv,
                notes_tag: encryptedFields?.notes?.tag,
                client_encrypted: encryptedFields ? 1 : 0,
            }),
        });

        if (response.ok) {
            const updated = await response.json();
            const accounts = (window as any).accounts as Record<number, Account>;

            accounts[serviceId] = {
                ...accounts[serviceId],
                ...updated,
                username,
                password,
                notes: notes || undefined,
                client_encrypted: false,
            };
            updateAccountListItem(accounts[serviceId]);
            (window as any).selectAccount(serviceId);
            (window as any).hideEditModal();

            if ((window as any).showToast) {
                (window as any).showToast(t('Account updated successfully!'));
            } else {
                alert(t('Account updated successfully!'));
            }
        } else {
            const error = await response.json();
            alert(`${t('Error')}: ${error.message || t('Failed to update')}`);
        }
    } catch (err) {
        console.error(err);
        alert(t('Network error while updating account.'));
    } finally {
        buttons.forEach(b => (b.disabled = false));
    }
};

// ===== SHARE =====

(window as any).shareCurrentAccount = () => {

    if (!currentAccount) {
        const accounts = (window as any).accounts as Record<number, Account> | undefined;
        const firstId = accounts ? Number(Object.keys(accounts)[0]) : Number.NaN;

        if (Number.isFinite(firstId)) {
            (window as any).selectAccount(firstId);
        }
    }

    if (!currentAccount) {
        alert(t('Please select an account first.'));

        return;
    }

    if ((window as any).showShareModal) {
        (window as any).showShareModal(currentAccount.id);
    } else {
        alert(t('Share feature not available.'));
    }
};

async function revokeShare(shareId: number): Promise<void> {
    if (!currentAccount) {
        return;
    }

    if (!confirm(t('Revoke access for this recipient?'))) {
        return;
    }

    try {
        const response = await fetch(`/shares/${shareId}/revoke`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            const error = await response.json();

            throw new Error(error.message || t('Failed to revoke access.'));
        }

        currentAccount.shared_with = (currentAccount.shared_with ?? []).filter(share => share.id !== shareId);
        const accounts = (window as any).accounts as Record<number, Account>;
        accounts[currentAccount.id] = currentAccount;
        renderShareRecipients(currentAccount);
        (window as any).showToast?.(t('Shared access revoked.'), 'success');
    } catch (error) {
        console.error(error);
        (window as any).showToast?.(t('Failed to revoke shared access.'), 'error');
    }
}

function renderShareRecipients(account: Account): void {
    const container = document.getElementById('detail-shares-container');
    const list = document.getElementById('detail-shares-list');

    if (!container || !list) {
        return;
    }

    list.replaceChildren();

    const recipients = account.shared_with ?? [];

    if (recipients.length === 0) {
        container.classList.add('hidden');

        return;
    }

    container.classList.remove('hidden');

    recipients.forEach(recipient => {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-3 bg-[var(--bg-input)] border border-[var(--border-color)] rounded-2xl px-4 py-3';

        const identity = document.createElement('div');
        identity.className = 'min-w-0';

        const name = document.createElement('p');
        name.className = 'font-medium truncate';
        name.textContent = recipient.name || recipient.email || t('Unknown user');

        const details = document.createElement('p');
        details.className = 'text-xs text-[var(--text-secondary)] truncate';
        details.textContent = `${recipient.email ?? ''}${recipient.shared_at ? ` • ${recipient.shared_at}` : ''}`;

        identity.append(name, details);

        const actions = document.createElement('div');
        actions.className = 'flex items-center gap-2 flex-shrink-0';

        const status = document.createElement('span');
        status.className = recipient.status === 'Accepted'
            ? 'text-xs px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-400'
            : 'text-xs px-2 py-1 rounded-full bg-yellow-500/10 text-yellow-400';
        status.textContent = t(recipient.status);

        const revokeButton = document.createElement('button');
        revokeButton.type = 'button';
        revokeButton.className = 'text-xs px-3 py-1.5 rounded-xl text-red-500 hover:text-red-400 hover:bg-red-500/10 transition';
        revokeButton.textContent = t('Revoke');
        revokeButton.addEventListener('click', () => void revokeShare(recipient.id));

        actions.append(status, revokeButton);
        row.append(identity, actions);
        list.append(row);
    });
}

// ===== SÉCURITÉ (basée sur les données stockées) =====

function renderSecurityFromAccount(account: Account) {
    const panel = document.getElementById('security-panel');

    if (!panel) {
        return;
    }

    if (typeOf(account) !== 'login') {
        panel.classList.add('hidden');
        panel.replaceChildren();

        return;
    }

    panel.classList.remove('hidden');

    let html = '';

    // Priorité à la compromission (données réelles de HIBP)
    if (account.compromised) {
        html = `
            <div class="security-red border rounded-2xl p-5 flex gap-4">
                <div class="w-10 h-10 flex-shrink-0 bg-red-500/20 text-red-400 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-red-400">${t('Compromised Password')}</p>
                    <p class="text-sm text-white/70 mt-1">${t('This password was found in a data breach. Change it immediately.')}</p>
                    <button onclick="window.editAccount()"
                            class="mt-4 px-5 py-2 bg-red-500/90 hover:bg-red-600 text-white text-sm rounded-2xl font-medium">
                        ${t('Change Password')}
                    </button>
                </div>
            </div>
        `;
    } else if (account.reused) {
        html = `
            <div class="security-yellow border rounded-2xl p-5 flex gap-4">
                <div class="w-10 h-10 flex-shrink-0 bg-yellow-500/20 text-yellow-400 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-exclamation-triangle text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-yellow-400">${t('Reused Password')}</p>
                    <p class="text-sm text-white/70 mt-1">${t('This password is used on multiple accounts. For better security, use a unique one.')}</p>
                    <button onclick="window.editAccount()"
                            class="mt-4 px-5 py-2 bg-yellow-500/90 hover:bg-yellow-600 text-black text-sm rounded-2xl font-medium">
                        ${t('Change Password')}
                    </button>
                </div>
            </div>
        `;
    } else if (account.strength === 'very_weak' || account.strength === 'weak') {
        html = `
            <div class="security-red border rounded-2xl p-5 flex gap-4">
                <div class="w-10 h-10 flex-shrink-0 bg-red-500/20 text-red-400 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-red-400">${t('Weak Password')}</p>
                    <p class="text-sm text-white/70 mt-1">${t('This password is too easy to guess. We recommend using a stronger one.')}</p>
                    <button onclick="window.editAccount()"
                            class="mt-4 px-5 py-2 bg-red-500/90 hover:bg-red-600 text-white text-sm rounded-2xl font-medium">
                        ${t('Change Password')}
                    </button>
                </div>
            </div>
        `;
    } else if (account.strength === 'very_strong') {
        html = `
            <div class="border border-emerald-500/50 bg-emerald-500/10 rounded-3xl p-5 flex gap-4">
                <div class="w-9 h-9 flex-shrink-0 bg-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <p class="font-semibold text-emerald-400">${t('Very Strong Password')}</p>
                    <p class="text-sm text-white/70 mt-0.5">${t('Excellent entropy. This password is highly secure.')}</p>
                </div>
            </div>
        `;
    } else {
        html = `
            <div class="border border-emerald-500/50 bg-emerald-500/10 rounded-3xl p-5 flex gap-4">
                <div class="w-9 h-9 flex-shrink-0 bg-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div>
                    <p class="font-semibold text-emerald-400">${t('No Issues Found')}</p>
                    <p class="text-sm text-white/70 mt-0.5">${t('This password is strong and secure.')}</p>
                </div>
            </div>
        `;
    }

    panel.innerHTML = html;
}

(window as any).deleteAccount = async () => {

    if (!currentAccount) {
        return;
    }

    const confirmation = currentAccount.shared_group_id && !currentAccount.shared_user_id
        ? t('Delete this shared account for everyone? This will revoke access for all recipients.')
        : currentAccount.shared_group_id
            ? t('Remove this shared account from your vault? The original will remain available to its owner.')
            : t('Are you sure you want to delete this account?');

    if (!confirm(confirmation)) {
        return;
    }

    try {
        const res = await fetch(`/services/${currentAccount.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
        });

        if (res.ok) {
            window.location.href = '/dashboard';
        } else {
            (window as any).showToast?.(t('Failed to delete account.'), 'error');
        }
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (e) {
        (window as any).showToast?.(t('Network error.'), 'error');
    }
};

// ===== INITIALISATION =====
function initServicePage(): void {
    const accounts = (window as any).accounts as Record<number, Account> | undefined;

    void decryptLoadedAccounts().then(() => {
        if (!accounts) {
            return;
        }

        const firstId = Object.keys(accounts)[0];

        if (firstId) {
            setTimeout(() => (window as any).selectAccount(firstId), 80);
        }
    });

    if (document.getElementById('edit-password')) {
        bindPasswordStrength(
            'edit-password',
            'edit-password-toggle',
            'edit-strength-container',
            'edit-strength-bar',
            'edit-strength-text',
            'edit-generate-btn'
        );
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initServicePage);
} else {
    initServicePage();
}
