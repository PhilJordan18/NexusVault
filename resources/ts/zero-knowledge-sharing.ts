import {
    createClientSyncSharePayload,
    decryptClientSharePayload,
    decryptSharedKeyFromRecipient,
    decryptSharedKeyFromVault,
    decryptSharedVaultString,
    decryptVaultString,
    encryptSharedKeyForVault,
    encryptVaultString,
    hasStoredVaultKey,
} from './zero-knowledge';
import type { ClientSharePayload, EncryptedPrivateKeyEnvelope, EncryptedString, SharedKeyEnvelope } from './zero-knowledge';

type ShareableAccount = {
    id: number;
    username: string;
    username_iv?: string | null;
    username_tag?: string | null;
    password: string;
    password_iv?: string | null;
    password_tag?: string | null;
    notes?: string | null;
    notes_iv?: string | null;
    notes_tag?: string | null;
    client_encrypted?: boolean;
    shared_key_envelope?: SharedKeyEnvelope | null;
};

type PreparedShareResponse = {
    recipient: {
        name?: string | null;
        email: string;
        public_key: string;
    };
};

type PendingShares = Record<number, ClientSharePayload>;

function t(key: string): string {
    const translations = (window as any).nexusVaultTranslations as Record<string, string> | undefined;

    return translations?.[key] ?? key;
}

function csrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;

    return meta?.content ?? '';
}

function usesClientEncryption(): boolean {
    return Boolean((window as any).nexusVaultUsesClientEncryption);
}

function ensureBrowserVaultKey(): boolean {
    if (hasStoredVaultKey()) {
        return true;
    }

    window.location.href = '/vault/unlock';

    return false;
}

function encryptedPrivateKey(): EncryptedPrivateKeyEnvelope | null {
    return ((window as any).nexusVaultEncryptedPrivateKey as EncryptedPrivateKeyEnvelope | null | undefined) ?? null;
}

function currentShareableAccount(): ShareableAccount | null {
    return ((window as any).nexusVaultCurrentAccount as ShareableAccount | null | undefined) ?? null;
}

function encryptedField(ciphertext?: string | null, iv?: string | null, tag?: string | null): EncryptedString | null {
    if (!ciphertext || !iv || !tag) {
        return null;
    }

    return { ciphertext, iv, tag };
}

async function decryptShareableAccount(account: ShareableAccount): Promise<ShareableAccount | null> {
    if (!account.client_encrypted) {
        return account;
    }

    if (!ensureBrowserVaultKey()) {
        return null;
    }

    const sharedKey = account.shared_key_envelope
        ? await decryptSharedKeyFromVault(account.shared_key_envelope)
        : null;
    const decryptString = sharedKey
        ? (encrypted: EncryptedString) => decryptSharedVaultString(encrypted, sharedKey)
        : decryptVaultString;
    const username = encryptedField(account.username, account.username_iv, account.username_tag);
    const password = encryptedField(account.password, account.password_iv, account.password_tag);
    const notes = encryptedField(account.notes, account.notes_iv, account.notes_tag);

    if (!username || !password) {
        return null;
    }

    return {
        ...account,
        username: await decryptString(username),
        password: await decryptString(password),
        notes: notes ? await decryptString(notes) : null,
        client_encrypted: false,
    };
}

async function accountById(serviceId: number): Promise<ShareableAccount | null> {
    const accounts = (window as any).accounts as Record<number, ShareableAccount> | undefined;
    const currentAccount = currentShareableAccount();
    const resolvedId = Number.isFinite(serviceId) && serviceId > 0 ? serviceId : currentAccount?.id;
    const account = resolvedId
        ? accounts?.[resolvedId] ?? (currentAccount?.id === resolvedId ? currentAccount : null)
        : null;

    if (!account) {
        return null;
    }

    const decryptedAccount = await decryptShareableAccount(account);

    if (!decryptedAccount) {
        return null;
    }

    if (accounts?.[decryptedAccount.id]) {
        accounts[decryptedAccount.id] = decryptedAccount;
    }

    (window as any).nexusVaultCurrentAccount = decryptedAccount;

    return decryptedAccount;
}

async function responseJson<T>(response: Response): Promise<T> {
    const json = await response.json().catch(() => ({ message: response.statusText }));

    if (!response.ok) {
        throw new Error(json.message ?? response.statusText);
    }

    return json as T;
}

function setFormDisabled(form: HTMLFormElement, disabled: boolean): void {
    form.querySelectorAll<HTMLButtonElement>('button').forEach(button => {
        button.disabled = disabled;
    });
}

async function prepareClientShare(serviceId: number, email: string): Promise<PreparedShareResponse> {
    const response = await fetch('/shares/prepare', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ service_id: serviceId, email }),
    });

    return responseJson<PreparedShareResponse>(response);
}

async function sendClientShare(form: HTMLFormElement, account: ShareableAccount, email: string): Promise<ClientSharePayload> {
    const preparedShare = await prepareClientShare(account.id, email);
    const payload = await createClientSyncSharePayload({
        username: account.username,
        password: account.password,
        notes: account.notes ?? null,
    }, preparedShare.recipient.public_key, account.shared_key_envelope ?? null);

    const response = await fetch(form.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            service_id: account.id,
            email,
            client_encrypted: 1,
            mode: payload.mode,
            encrypted_aes_key: payload.encrypted_aes_key,
            encrypted_data: payload.encrypted_data,
            shared_key_envelope: payload.shared_key_envelope,
            shared_fields: payload.shared_fields,
        }),
    });

    await responseJson(response);

    return payload;
}

function bindShareForm(): void {
    const form = document.getElementById('share-form') as HTMLFormElement | null;

    if (!form) {
        return;
    }

    form.addEventListener('submit', event => {
        if (!usesClientEncryption()) {
            return;
        }

        event.preventDefault();

        void (async () => {
            if (!ensureBrowserVaultKey()) {
                return;
            }

            const modalServiceId = Number((document.getElementById('modal-service-id') as HTMLInputElement | null)?.value);
            const serviceId = Number.isFinite(modalServiceId) && modalServiceId > 0
                ? modalServiceId
                : currentShareableAccount()?.id ?? 0;
            const email = (form.querySelector<HTMLInputElement>('input[name="email"]')?.value ?? '').trim();
            const account = await accountById(serviceId);

            if (!account || !email) {
                (window as any).showToast?.(t('Unable to prepare encrypted share.'), 'error');

                return;
            }

            setFormDisabled(form, true);

            try {
                const payload = await sendClientShare(form, account, email);
                account.shared_key_envelope = payload.shared_key_envelope ?? account.shared_key_envelope ?? null;
                (window as any).hideShareModal?.();
                (window as any).showToast?.(t('Share sent successfully!'), 'success');
            } catch (error) {
                console.error(error);
                (window as any).showToast?.(error instanceof Error ? error.message : t('Unable to send encrypted share.'), 'error');
            } finally {
                setFormDisabled(form, false);
            }
        })();
    });
}

async function encryptAcceptedShareFields(data: {
    username: string;
    password: string;
    notes?: string | null;
}): Promise<{
    username: EncryptedString;
    password: EncryptedString;
    notes: EncryptedString | null;
}> {
    return {
        username: await encryptVaultString(data.username),
        password: await encryptVaultString(data.password),
        notes: data.notes ? await encryptVaultString(data.notes) : null,
    };
}

function acceptedShareRequestBody(encryptedFields: Awaited<ReturnType<typeof encryptAcceptedShareFields>>) {
    return {
        client_encrypted: 1,
        username: encryptedFields.username.ciphertext,
        username_iv: encryptedFields.username.iv,
        username_tag: encryptedFields.username.tag,
        password: encryptedFields.password.ciphertext,
        password_iv: encryptedFields.password.iv,
        password_tag: encryptedFields.password.tag,
        notes: encryptedFields.notes?.ciphertext ?? null,
        notes_iv: encryptedFields.notes?.iv,
        notes_tag: encryptedFields.notes?.tag,
    };
}

function bindAcceptForms(): void {
    document.querySelectorAll<HTMLFormElement>('[data-client-share-accept-form]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();

            void (async () => {
                if (!ensureBrowserVaultKey()) {
                    return;
                }

                const privateKey = encryptedPrivateKey();
                const shareId = Number(form.dataset.shareId);
                const payload = ((window as any).nexusVaultPendingClientShares as PendingShares | undefined)?.[shareId];

                if (!privateKey || !payload) {
                    (window as any).showToast?.(t('Unable to accept encrypted share.'), 'error');

                    return;
                }

                setFormDisabled(form, true);

                try {
                    const body = payload.mode === 'client-encrypted-sync'
                        ? {
                            client_encrypted: 1,
                            shared_key_envelope: await encryptSharedKeyForVault(
                                await decryptSharedKeyFromRecipient(payload.encrypted_aes_key, privateKey)
                            ),
                        }
                        : acceptedShareRequestBody(
                            await encryptAcceptedShareFields(await decryptClientSharePayload(payload, privateKey))
                        );

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(body),
                    });
                    const json = await responseJson<{ redirect?: string; message?: string }>(response);

                    (window as any).showToast?.(json.message ?? t('Service added to your vault with success!'), 'success');
                    window.location.href = json.redirect ?? '/dashboard';
                } catch (error) {
                    console.error(error);
                    (window as any).showToast?.(error instanceof Error ? error.message : t('Unable to accept encrypted share.'), 'error');
                    setFormDisabled(form, false);
                }
            })();
        });
    });
}

export function bindZeroKnowledgeSharing(): void {
    bindShareForm();
    bindAcceptForms();
}
