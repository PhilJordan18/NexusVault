// resources/js/pages/service.ts

type Account = {
    id: number;
    username: string;
    password: string;
    url?: string;
    notes?: string;
    name?: string;
};

type EntropyResult = {
    strength: 'weak' | 'strong' | 'very_strong';
    compromised?: boolean;
    reused?: boolean;
};

let currentAccount: Account | null = null;
let revealed = false;

// Make functions global so Blade can call them
(window as any).selectAccount = (id: number) => {
    const accounts = (window as any).accounts as Record<number, Account>;
    currentAccount = accounts[id];

    const panel = document.getElementById('detail-panel')!;
    panel.classList.remove('hidden');

    // Fill info
    (document.getElementById('detail-username') as HTMLElement).textContent = currentAccount.username;
    (document.getElementById('detail-name') as HTMLElement).textContent = currentAccount.name || '';

    const passwordEl = document.getElementById('detail-password') as HTMLElement;
    passwordEl.textContent = '••••••••••••';
    revealed = false;

    const urlEl = document.getElementById('detail-url') as HTMLAnchorElement;
    urlEl.href = currentAccount.url || '#';
    urlEl.textContent = currentAccount.url || '—';

    const notesContainer = document.getElementById('detail-notes-container')!;
    if (currentAccount.notes) {
        notesContainer.classList.remove('hidden');
        (document.getElementById('detail-notes') as HTMLElement).textContent = currentAccount.notes;
    } else {
        notesContainer.classList.add('hidden');
    }

    loadSecurity(currentAccount.password);
};

(window as any).togglePassword = () => {
    if (!currentAccount) return;

    const el = document.getElementById('detail-password') as HTMLElement;
    const btn = document.querySelector('#detail-panel button[onclick*="togglePassword"] i') as HTMLElement;

    if (!el || !btn) return;

    if (revealed) {
        el.textContent = '••••••••••••';
        revealed = false;
        btn.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        el.textContent = currentAccount.password;
        revealed = true;
        btn.classList.replace('fa-eye', 'fa-eye-slash');

        // Auto-hide après 5 secondes
        setTimeout(() => {
            if (revealed) {
                (window as any).togglePassword();
            }
        }, 5000);
    }
};

async function loadSecurity(password: string) {
    const res = await fetch('/password/entropy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content
        },
        body: JSON.stringify({ password })
    });

    const data: EntropyResult = await res.json();
    renderSecurity(data);
}

function renderSecurity(data: EntropyResult) {
    const panel = document.getElementById('security-panel')!;
    panel.classList.remove('hidden');
    panel.classList.add('glass', 'rounded-3xl', 'p-6', 'mt-6');

    let html = '';

    if (data.compromised) {
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
    }
    else if (data.reused) {
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
    }
    else if (data.strength === 'weak') {
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
    }
    else if (data.strength === 'very_strong') {
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

(window as any).shareCurrentAccount = () => {
    if (!currentAccount) return;
    (window as any).showShareModal(currentAccount.id);
};

(window as any).editAccount = () => {
    alert('Edit modal coming soon!');
};
