import { bindPasswordGeneratorCustomization, calculateEntropy, generatePasswordForButton } from './utils/password-utils';
import type { PasswordEntropy } from './utils/password-utils';
import { WebAuthn } from './WebAuthn';
import { createRegistrationVaultPackage, unlockVaultKey, unlockVaultKeyWithRecovery } from './zero-knowledge';
import type { VaultKeyEnvelope, VaultRecoveryEnvelope } from './zero-knowledge';

const webauthn = new WebAuthn();

type ValidationResponse = {
    message?: string;
    errors?: Record<string, string[]>;
};

let entropyTimer: number | null = null;
let entropyRequestId = 0;

function t(key: string): string {
    const translations = (window as any).nexusVaultTranslations as Record<string, string> | undefined;

    return translations?.[key] ?? key;
}

document.addEventListener('DOMContentLoaded', () => {
    initAuthPage();
});

function initAuthPage() {
    bindZeroKnowledgeRegister();
    bindZeroKnowledgeVaultSetup();
    bindZeroKnowledgeUnlock();
    bindZeroKnowledgeRecoveryUnlock();
    bindZeroKnowledgeVaultReset();

    // Password visibility toggle
    document.querySelectorAll('[id^="toggle-password"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const primaryInput = passwordInputForToggle(btn as HTMLElement);
            const confirmInput = confirmationInputForToggle(btn as HTMLElement);
            const icon = btn.querySelector('i') as HTMLElement;

            if (primaryInput && icon) {
                const nextType = primaryInput.type === 'password' ? 'text' : 'password';

                primaryInput.type = nextType;

                if (confirmInput) {
                    confirmInput.type = nextType;
                }

                if (nextType === 'text') {
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            }
        });
    });

    //Passkeys
    const passkeyBtn = document.getElementById('passkey-btn');

    if (passkeyBtn) {
        passkeyBtn.addEventListener('click', handlePasskeyLogin);
    }

    // Password entropy on register page
    const passwordInput = document.getElementById('vault_password') ?? document.querySelector('input[name="password"]');

    if (passwordInput) {
        passwordInput.addEventListener('input', handlePasswordInput);
    }

    // Generate password button (register page)
    document.querySelectorAll<HTMLElement>('[data-password-generate], #generate-password').forEach(generateBtn => {
        generateBtn.addEventListener('click', () => handleGeneratePassword(generateBtn));
    });
    bindPasswordGeneratorCustomization();

    // Pre-fill email on login-password page
    const userEmailSpan = document.getElementById('user-email');
    const hiddenEmailInput = document.getElementById('hidden-email') as HTMLInputElement;

    if (userEmailSpan && hiddenEmailInput) {
        const urlParams = new URLSearchParams(window.location.search);
        const email = urlParams.get('email') || localStorage.getItem('temp_email');

        if (email) {
            userEmailSpan.textContent = email;
            hiddenEmailInput.value = email;
            localStorage.setItem('temp_email', email);
        }
    }
}

function passwordInputForToggle(button: HTMLElement): HTMLInputElement | null {
    const targetId = button.dataset.toggleTarget;

    if (targetId) {
        return document.getElementById(targetId) as HTMLInputElement | null;
    }

    return button.closest('.relative')?.querySelector('input') as HTMLInputElement | null;
}

function confirmationInputForToggle(button: HTMLElement): HTMLInputElement | null {
    const targetId = button.dataset.confirmTarget;

    if (!targetId) {
        return null;
    }

    return document.getElementById(targetId) as HTMLInputElement | null;
}

function clearCustomValidity(...inputs: Array<HTMLInputElement | null>): void {
    inputs.forEach(input => input?.setCustomValidity(''));
}

function validateFormBeforeVaultPackage(form: HTMLFormElement): boolean {
    const loginPassword = document.getElementById('password') as HTMLInputElement | null;
    const loginPasswordConfirmation = document.getElementById('password_confirmation') as HTMLInputElement | null;
    const vaultPassword = document.getElementById('vault_password') as HTMLInputElement | null;
    const vaultPasswordConfirmation = document.getElementById('vault_password_confirmation') as HTMLInputElement | null;

    clearCustomValidity(loginPassword, loginPasswordConfirmation, vaultPassword, vaultPasswordConfirmation);

    if (loginPassword && loginPasswordConfirmation && loginPassword.value !== loginPasswordConfirmation.value) {
        loginPasswordConfirmation.setCustomValidity(t('Login password confirmation does not match.'));
    }

    if (vaultPassword && vaultPasswordConfirmation && vaultPassword.value !== vaultPasswordConfirmation.value) {
        vaultPasswordConfirmation.setCustomValidity(t('Vault password confirmation does not match.'));
    }

    if (loginPassword && vaultPassword && loginPassword.value === vaultPassword.value) {
        vaultPassword.setCustomValidity(t('Login and vault passwords must be different.'));
    }

    return form.reportValidity();
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

function disableSubmitButton(form: HTMLFormElement): HTMLButtonElement | null {
    const submitButton = form.querySelector<HTMLButtonElement>('button[type="submit"]');
    submitButton?.setAttribute('disabled', 'disabled');

    return submitButton;
}

function setServerValidationErrors(form: HTMLFormElement, errors: Record<string, string[]> | undefined): string | null {
    let firstMessage: string | null = null;

    Object.entries(errors ?? {}).forEach(([name, messages]) => {
        const input = form.querySelector<HTMLInputElement>(`[name="${name}"]`);
        const message = messages[0];

        if (!input || !message) {
            return;
        }

        firstMessage ??= message;
        input.setCustomValidity(message);
        input.addEventListener('input', () => input.setCustomValidity(''), { once: true });
    });

    return firstMessage;
}

async function validateRegistrationOnServer(form: HTMLFormElement): Promise<boolean> {
    const validationUrl = form.dataset.validationUrl;

    if (!validationUrl) {
        return true;
    }

    const response = await fetch(validationUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
            name: form.querySelector<HTMLInputElement>('[name="name"]')?.value ?? '',
            email: form.querySelector<HTMLInputElement>('[name="email"]')?.value ?? '',
            password: form.querySelector<HTMLInputElement>('[name="password"]')?.value ?? '',
            password_confirmation: form.querySelector<HTMLInputElement>('[name="password_confirmation"]')?.value ?? '',
        }),
    });

    if (response.ok) {
        return true;
    }

    if (response.status === 422) {
        const payload = (await response.json()) as ValidationResponse;
        const firstMessage = setServerValidationErrors(form, payload.errors);

        form.reportValidity();
        (window as any).showToast?.(firstMessage ?? payload.message ?? t('Please fix the highlighted fields.'), 'error');

        return false;
    }

    if (response.status === 429) {
        (window as any).showToast?.(t('Too many attempts. Please wait a moment and try again.'), 'error');

        return false;
    }

    (window as any).showToast?.(t('Unable to validate registration. Please try again.'), 'error');

    return false;
}

function bindZeroKnowledgeVaultSetup(): void {
    const form = document.getElementById('vault-setup-form') as HTMLFormElement | null;

    if (!form) {
        return;
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        void submitZeroKnowledgeVaultSetup(form);
    });
}

async function submitZeroKnowledgeVaultSetup(form: HTMLFormElement): Promise<void> {
    const vaultPassword = (document.getElementById('vault_password') as HTMLInputElement | null)?.value ?? '';
    const vaultPasswordConfirmation = (document.getElementById('vault_password_confirmation') as HTMLInputElement | null)?.value ?? '';

    if (!validateFormBeforeVaultPackage(form)) {
        return;
    }

    if (vaultPassword.length < 12) {
        (window as any).showToast?.(t('Vault password must be at least 12 characters.'), 'error');

        return;
    }

    if (vaultPassword !== vaultPasswordConfirmation) {
        (window as any).showToast?.(t('Vault password confirmation does not match.'), 'error');

        return;
    }

    const submitButton = disableSubmitButton(form);

    try {
        const setupVault = await createRegistrationVaultPackage(vaultPassword);

        setHiddenInput(form, 'vault_key_envelope', JSON.stringify(setupVault.vaultKeyEnvelope));
        setHiddenInput(form, 'vault_recovery_envelope', JSON.stringify(setupVault.vaultRecoveryEnvelope));
        setHiddenInput(form, 'public_key', setupVault.publicKey);
        setHiddenInput(form, 'encrypted_private_key', JSON.stringify(setupVault.encryptedPrivateKey));

        await showRecoveryKeyConfirmation(setupVault.recoveryKey);

        HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        console.error(error);
        (window as any).showToast?.(t('Unable to prepare your encrypted vault.'), 'error');
        submitButton?.removeAttribute('disabled');
    }
}

function bindZeroKnowledgeRegister(): void {
    const form = document.getElementById('register-form') as HTMLFormElement | null;

    if (!form) {
        return;
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        void submitZeroKnowledgeRegister(form);
    });
}

async function submitZeroKnowledgeRegister(form: HTMLFormElement): Promise<void> {
    const loginPassword = (document.getElementById('password') as HTMLInputElement | null)?.value ?? '';
    const vaultPassword = (document.getElementById('vault_password') as HTMLInputElement | null)?.value ?? '';
    const vaultPasswordConfirmation = (document.getElementById('vault_password_confirmation') as HTMLInputElement | null)?.value ?? '';

    if (!validateFormBeforeVaultPackage(form)) {
        return;
    }

    if (vaultPassword.length < 12) {
        (window as any).showToast?.(t('Vault password must be at least 12 characters.'), 'error');

        return;
    }

    if (vaultPassword !== vaultPasswordConfirmation) {
        (window as any).showToast?.(t('Vault password confirmation does not match.'), 'error');

        return;
    }

    if (loginPassword === vaultPassword) {
        (window as any).showToast?.(t('Login and vault passwords must be different.'), 'error');

        return;
    }

    const submitButton = disableSubmitButton(form);

    try {
        const registrationIsValid = await validateRegistrationOnServer(form);

        if (!registrationIsValid) {
            submitButton?.removeAttribute('disabled');

            return;
        }

        const registrationVault = await createRegistrationVaultPackage(vaultPassword);

        setHiddenInput(form, 'vault_key_envelope', JSON.stringify(registrationVault.vaultKeyEnvelope));
        setHiddenInput(form, 'vault_recovery_envelope', JSON.stringify(registrationVault.vaultRecoveryEnvelope));
        setHiddenInput(form, 'public_key', registrationVault.publicKey);
        setHiddenInput(form, 'encrypted_private_key', JSON.stringify(registrationVault.encryptedPrivateKey));

        await showRecoveryKeyConfirmation(registrationVault.recoveryKey);

        HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        console.error(error);
        (window as any).showToast?.(t('Unable to prepare your encrypted vault.'), 'error');
        submitButton?.removeAttribute('disabled');
    }
}

function bindZeroKnowledgeUnlock(): void {
    const form = document.getElementById('vault-unlock-form') as HTMLFormElement | null;
    const envelope = (window as any).nexusVaultKeyEnvelope as VaultKeyEnvelope | null | undefined;

    if (!form) {
        return;
    }

    if (!envelope) {
        form.addEventListener('submit', () => {
            disableSubmitButton(form);
        });

        return;
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        void submitZeroKnowledgeUnlock(form, envelope);
    });
}

async function submitZeroKnowledgeUnlock(form: HTMLFormElement, envelope: VaultKeyEnvelope): Promise<void> {
    const vaultPassword = (document.getElementById('vault_password') as HTMLInputElement | null)?.value ?? '';
    const submitButton = disableSubmitButton(form);

    try {
        await unlockVaultKey(envelope, vaultPassword);
        setHiddenInput(form, 'client_unlocked', '1');

        HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        console.error(error);
        (window as any).showToast?.(t('Unable to unlock vault. Check your vault password.'), 'error');
        submitButton?.removeAttribute('disabled');
    }
}

function bindZeroKnowledgeRecoveryUnlock(): void {
    const form = document.getElementById('vault-recovery-form') as HTMLFormElement | null;
    const envelope = (window as any).nexusVaultRecoveryEnvelope as VaultRecoveryEnvelope | null | undefined;

    if (!form || !envelope) {
        return;
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        void submitZeroKnowledgeRecoveryUnlock(form, envelope);
    });
}

async function submitZeroKnowledgeRecoveryUnlock(form: HTMLFormElement, envelope: VaultRecoveryEnvelope): Promise<void> {
    const recoveryKey = (document.getElementById('recovery_key') as HTMLInputElement | null)?.value ?? '';
    const submitButton = disableSubmitButton(form);

    try {
        await unlockVaultKeyWithRecovery(envelope, recoveryKey);
        setHiddenInput(form, 'client_unlocked', '1');

        HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        console.error(error);
        (window as any).showToast?.(t('Unable to unlock vault. Check your recovery key.'), 'error');
        submitButton?.removeAttribute('disabled');
    }
}

function bindZeroKnowledgeVaultReset(): void {
    const form = document.getElementById('vault-reset-form') as HTMLFormElement | null;

    if (!form) {
        return;
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        void submitZeroKnowledgeVaultReset(form);
    });
}

async function submitZeroKnowledgeVaultReset(form: HTMLFormElement): Promise<void> {
    const vaultPassword = (document.getElementById('reset_vault_password') as HTMLInputElement | null)?.value ?? '';
    const vaultPasswordConfirmation = (document.getElementById('reset_vault_password_confirmation') as HTMLInputElement | null)?.value ?? '';

    if (vaultPassword.length < 12) {
        (window as any).showToast?.(t('Vault password must be at least 12 characters.'), 'error');

        return;
    }

    if (vaultPassword !== vaultPasswordConfirmation) {
        (window as any).showToast?.(t('Vault password confirmation does not match.'), 'error');

        return;
    }

    const submitButton = disableSubmitButton(form);

    try {
        const resetVault = await createRegistrationVaultPackage(vaultPassword);

        setHiddenInput(form, 'vault_key_envelope', JSON.stringify(resetVault.vaultKeyEnvelope));
        setHiddenInput(form, 'vault_recovery_envelope', JSON.stringify(resetVault.vaultRecoveryEnvelope));
        setHiddenInput(form, 'public_key', resetVault.publicKey);
        setHiddenInput(form, 'encrypted_private_key', JSON.stringify(resetVault.encryptedPrivateKey));

        await showRecoveryKeyConfirmation(resetVault.recoveryKey);

        HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        console.error(error);
        (window as any).showToast?.(t('Unable to prepare your encrypted vault.'), 'error');
        submitButton?.removeAttribute('disabled');
    }
}

function setHiddenInput(form: HTMLFormElement, name: string, value: string): void {
    let input = form.querySelector<HTMLInputElement>(`input[name="${name}"]`);

    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    }

    input.value = value;
}

function showRecoveryKeyConfirmation(recoveryKey: string): Promise<void> {
    return new Promise(resolve => {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 px-4 backdrop-blur-sm';

        const modal = document.createElement('div');
        modal.className = 'w-full max-w-lg rounded-3xl border border-emerald-500/30 bg-[var(--bg-card)] p-6 shadow-2xl';

        const icon = document.createElement('div');
        icon.className = 'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10 text-2xl text-emerald-400';
        icon.innerHTML = '<i class="fa-solid fa-key"></i>';

        const title = document.createElement('h2');
        title.className = 'mb-2 text-center text-2xl font-semibold';
        title.textContent = t('Save your recovery key');

        const description = document.createElement('p');
        description.className = 'mb-4 text-center text-sm text-[var(--text-secondary)]';
        description.textContent = t('This is the only backup that can unlock your encrypted vault if you forget the vault password.');

        const keyBox = document.createElement('div');
        keyBox.className = 'mb-4 break-all rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] p-4 text-center font-mono text-sm leading-6 text-emerald-300';
        keyBox.textContent = recoveryKey;

        const copyButton = document.createElement('button');
        copyButton.type = 'button';
        copyButton.className = 'mb-3 w-full rounded-2xl border border-emerald-500/30 py-3 font-medium text-emerald-300 transition hover:bg-emerald-500/10';
        copyButton.textContent = t('Copy recovery key');
        copyButton.addEventListener('click', () => {
            void navigator.clipboard?.writeText(recoveryKey);
            (window as any).showToast?.(t('Recovery key copied.'), 'success');
        });

        const confirmButton = document.createElement('button');
        confirmButton.type = 'button';
        confirmButton.className = 'w-full rounded-2xl bg-emerald-600 py-3 font-semibold text-white transition hover:bg-emerald-700';
        confirmButton.textContent = t('I saved my recovery key');
        confirmButton.addEventListener('click', () => {
            overlay.remove();
            resolve();
        });

        modal.append(icon, title, description, keyBox, copyButton, confirmButton);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
    });
}

async function handlePasskeyLogin() {
    try {
        const options = await webauthn.getLoginOptions();
        const credential = await navigator.credentials.get({ publicKey: options });
        const result = await webauthn.login(credential);

        if (result.success && result.redirect) {
            (window as any).showToast?.(t('Connected with passkey.'), 'success');
            setTimeout(() => {
                if (result.redirect != null) {
                    window.location.href = result.redirect;
                }
            }, 300);
        } else {
            (window as any).showToast?.(t('Authentication failed with passkey.'), 'error');
        }
    } catch (e: any) {
        console.error('Passkey login error:', e);

        if (e.name === 'NotAllowedError') {
            (window as any).showToast?.(t('Action cancelled.'), 'error');
        } else {
            (window as any).showToast?.(t('Unable to use passkey. Try another method.'), 'error');
        }
    }
}

function handleGeneratePassword(button: HTMLElement): void {
    try {
        const password = generatePasswordForButton(button);
        const targetId = button.dataset.passwordTarget;
        const confirmTargetId = button.dataset.passwordConfirmTarget;

        const pwdInput = (targetId
            ? document.getElementById(targetId)
            : document.getElementById('vault_password') ?? document.getElementById('password')) as HTMLInputElement | null;
        const confirmInput = (confirmTargetId
            ? document.getElementById(confirmTargetId)
            : document.getElementById('vault_password_confirmation') ?? document.getElementById('password_confirmation')) as HTMLInputElement | null;

        if (pwdInput) {
            pwdInput.value = password;
            pwdInput.setCustomValidity('');
            pwdInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        if (confirmInput) {
            confirmInput.value = password;
            confirmInput.setCustomValidity('');
        }
    } catch (e) {
        console.error('Failed to generate password:', e);
    }
}

function renderPasswordStrength(result: PasswordEntropy): void {
    const container = document.getElementById('strength-container');

    if (!container) {
        return;
    }

    const bar = document.getElementById('strength-bar') as HTMLElement;
    const text = document.getElementById('strength-text') as HTMLElement;

    if (!bar || !text) {
        return;
    }

    container.classList.remove('hidden');

    const percentage = Math.min((result.entropy / 100) * 100, 100);
    bar.style.width = `${percentage}%`;

    if (result.strength === 'very_strong' || result.strength === 'strong') {
        bar.style.backgroundColor = '#00ff9d';
    } else if (result.strength === 'medium') {
        bar.style.backgroundColor = '#ffaa00';
    } else {
        bar.style.backgroundColor = '#ff4444';
    }

    text.textContent = t(result.label);
}

function handlePasswordInput(e: Event): void {
    const input = e.target as HTMLInputElement;
    const requestId = entropyRequestId + 1;
    entropyRequestId = requestId;

    if (entropyTimer !== null) {
        window.clearTimeout(entropyTimer);
    }

    if (input.value.length < 4) {
        renderPasswordStrength({ entropy: 0, strength: 'very_weak', label: t('Very weak') });

        return;
    }

    entropyTimer = window.setTimeout(() => {
        void (async () => {
            try {
                const result = await calculateEntropy(input.value);

                if (requestId !== entropyRequestId) {
                    return;
                }

                renderPasswordStrength(result);
            } catch (error) {
                console.error(error);
            }
        })();
    }, 350);
}
