import { bindPasswordStrength } from '../../ts/utils/password-utils';

// Types
type Account = {
    id: number;
    username: string;
    password: string;
    url?: string;
    notes?: string;
    name?: string;
    shared_user_id?: number | null;
    strength?: string;          // 'very_weak', 'weak', 'strong', 'very_strong'
    compromised?: boolean;
    reused?: boolean;
    shared_group_id?: string | null;
    shared_with?: ShareRecipient[];
};

type ShareRecipient = {
    id: number;
    name?: string | null;
    email?: string | null;
    status: 'Accepted' | 'Pending';
    shared_at?: string | null;
};

// GLOBAL STATE
let currentAccount: Account | null = null;

// Helper CSRF
function csrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;

    return meta?.content || '';
}

// ===== PUBLIC API =====

(window as any).selectAccount = (id: number) => {
    const accounts = (window as any).accounts as Record<number, Account>;
    const account = accounts[id];

    if (!account) {
        return;
    }

    currentAccount = account;

    const panel = document.getElementById('detail-panel')!;
    panel.classList.remove('hidden');
    document.getElementById('empty-state')?.classList.add('hidden');

    document.getElementById('delete-account-container')?.classList.remove('hidden');

    (document.getElementById('detail-username') as HTMLElement).textContent = account.username;
    (document.getElementById('detail-name') as HTMLElement).textContent = account.name || '';

    const sharedBadge = document.getElementById('detail-shared-badge');

    if (account.shared_group_id) {
        sharedBadge?.classList.remove('hidden');
    } else {
        sharedBadge?.classList.add('hidden');
    }

    const passwordEl = document.getElementById('detail-password') as HTMLElement;
    passwordEl.textContent = '••••••••••••';
    passwordEl.dataset.hidden = 'true';
    passwordEl.dataset.realPassword = account.password || '';

    const urlEl = document.getElementById('detail-url') as HTMLAnchorElement;

    if (account.url) {
        urlEl.href = account.url;
        urlEl.textContent = account.url;
        urlEl.style.display = '';
    } else {
        urlEl.style.display = 'none';
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
        el.textContent = '••••••••••••';
        el.dataset.hidden = 'true';
    }
};

// ===== EDIT =====

(window as any).editAccount = () => {
    if (!currentAccount) {
        alert('Please select an account first.');

        return;
    }

    (document.getElementById('edit-service-id') as HTMLInputElement).value = currentAccount.id.toString();
    (document.getElementById('edit-username') as HTMLInputElement).value = currentAccount.username;
    (document.getElementById('edit-password') as HTMLInputElement).value = currentAccount.password;
    (document.getElementById('edit-notes') as HTMLTextAreaElement).value = currentAccount.notes || '';
    (document.getElementById('edit-name') as HTMLInputElement).value = currentAccount.name ?? '';
    (document.getElementById('edit-url') as HTMLInputElement).value = currentAccount.url ?? '';

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

    const serviceId = Number((document.getElementById('edit-service-id') as HTMLInputElement).value);
    const username = (document.getElementById('edit-username') as HTMLInputElement).value.trim();
    const password = (document.getElementById('edit-password') as HTMLInputElement).value.trim();
    const notes = (document.getElementById('edit-notes') as HTMLTextAreaElement).value.trim();

    if (!username || !password) {
        alert('Username and password are required.');

        return;
    }

    const buttons = document.querySelectorAll<HTMLButtonElement>('#edit-account-form button');
    buttons.forEach(b => (b.disabled = true));

    try {
        const response = await fetch(`/services/${serviceId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: (document.getElementById('edit-name') as HTMLInputElement).value,
                url: (document.getElementById('edit-url') as HTMLInputElement).value || null,
                username,
                password,
                notes: notes || null,
            }),
        });

        if (response.ok) {
            const updated = await response.json();
            const accounts = (window as any).accounts as Record<number, Account>;

            accounts[serviceId] = { ...accounts[serviceId], ...updated };
            (window as any).selectAccount(serviceId);
            (window as any).hideEditModal();

            if ((window as any).showToast) {
                (window as any).showToast('Account updated successfully!');
            } else {
                alert('Account updated successfully!');
            }
        } else {
            const error = await response.json();
            alert('Error: ' + (error.message || 'Failed to update'));
        }
    } catch (err) {
        console.error(err);
        alert('Network error while updating account.');
    } finally {
        buttons.forEach(b => (b.disabled = false));
    }
};

// ===== SHARE =====

(window as any).shareCurrentAccount = () => {

    if (!currentAccount) {
        return;
    }

    if ((window as any).showShareModal) {
        (window as any).showShareModal(currentAccount.id);
    } else {
        alert('Share feature not available.');
    }
};

async function revokeShare(shareId: number): Promise<void> {
    if (!currentAccount) {
        return;
    }

    if (!confirm('Revoke access for this recipient?')) {
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

            throw new Error(error.message || 'Failed to revoke access.');
        }

        currentAccount.shared_with = (currentAccount.shared_with ?? []).filter(share => share.id !== shareId);
        const accounts = (window as any).accounts as Record<number, Account>;
        accounts[currentAccount.id] = currentAccount;
        renderShareRecipients(currentAccount);
        (window as any).showToast?.('Shared access revoked.', 'success');
    } catch (error) {
        console.error(error);
        (window as any).showToast?.('Failed to revoke shared access.', 'error');
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
        name.textContent = recipient.name || recipient.email || 'Unknown user';

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
        status.textContent = recipient.status;

        const revokeButton = document.createElement('button');
        revokeButton.type = 'button';
        revokeButton.className = 'text-xs px-3 py-1.5 rounded-xl text-red-500 hover:text-red-400 hover:bg-red-500/10 transition';
        revokeButton.textContent = 'Revoke';
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
                    <p class="font-semibold text-red-400">Compromised Password</p>
                    <p class="text-sm text-white/70 mt-1">This password was found in a data breach. Change it immediately.</p>
                    <button onclick="window.editAccount()"
                            class="mt-4 px-5 py-2 bg-red-500/90 hover:bg-red-600 text-white text-sm rounded-2xl font-medium">
                        Change Password
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
                    <p class="font-semibold text-yellow-400">Reused Password</p>
                    <p class="text-sm text-white/70 mt-1">This password is used on multiple accounts. For better security, use a unique one.</p>
                    <button onclick="window.editAccount()"
                            class="mt-4 px-5 py-2 bg-yellow-500/90 hover:bg-yellow-600 text-black text-sm rounded-2xl font-medium">
                        Change Password
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
                    <p class="font-semibold text-red-400">Weak Password</p>
                    <p class="text-sm text-white/70 mt-1">This password is too easy to guess. We recommend using a stronger one.</p>
                    <button onclick="window.editAccount()"
                            class="mt-4 px-5 py-2 bg-red-500/90 hover:bg-red-600 text-white text-sm rounded-2xl font-medium">
                        Change Password
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
                    <p class="font-semibold text-emerald-400">Very Strong Password</p>
                    <p class="text-sm text-white/70 mt-0.5">Excellent entropy. This password is highly secure.</p>
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
                    <p class="font-semibold text-emerald-400">No Issues Found</p>
                    <p class="text-sm text-white/70 mt-0.5">This password is strong and secure.</p>
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
        ? 'Delete this shared account for everyone? This will revoke access for all recipients.'
        : currentAccount.shared_group_id
            ? 'Remove this shared account from your vault? The original will remain available to its owner.'
            : 'Are you sure you want to delete this account?';

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
            (window as any).showToast?.('Failed to delete account.', 'error');
        }
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (e) {
        (window as any).showToast?.('Network error.', 'error');
    }
};

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', () => {
    const accounts = (window as any).accounts as Record<number, Account> | undefined;

    if (accounts) {

        const firstId = Object.keys(accounts)[0];

        if (firstId) {
            setTimeout(() => (window as any).selectAccount(firstId), 80);
        }
    }

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
});
