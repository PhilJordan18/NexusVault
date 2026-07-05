export type VaultKeyEnvelope = {
    version: 1;
    algorithm: 'AES-GCM';
    kdf: 'PBKDF2-SHA-256';
    iterations: number;
    salt: string;
    iv: string;
    ciphertext: string;
    tag: string;
};

export type EncryptedString = {
    ciphertext: string;
    iv: string;
    tag: string;
};

export type VaultRecoveryEnvelope = EncryptedString & {
    version: 1;
    algorithm: 'AES-GCM';
    keySource: 'recovery-key';
};

export type EncryptedPrivateKeyEnvelope = EncryptedString & {
    version: 1;
    algorithm: 'AES-GCM';
    keyFormat: 'pkcs8';
};

export type SharedKeyEnvelope = EncryptedString & {
    version: 1;
    algorithm: 'AES-GCM';
    keySource: 'shared-item-key';
};

export type ClientShareSensitiveData = {
    username: string;
    password: string;
    notes?: string | null;
};

export type SharedItemFields = {
    username: EncryptedString;
    password: EncryptedString;
    notes?: EncryptedString | null;
};

export type ClientSharePayload = {
    version: 1 | 2;
    mode: 'client-encrypted' | 'client-encrypted-sync';
    encrypted_aes_key: string;
    encrypted_data: EncryptedString;
    shared_key_envelope?: SharedKeyEnvelope;
    shared_fields?: SharedItemFields;
};

type RegistrationVaultPackage = {
    vaultKeyEnvelope: VaultKeyEnvelope;
    vaultRecoveryEnvelope: VaultRecoveryEnvelope;
    recoveryKey: string;
    publicKey: string;
    encryptedPrivateKey: EncryptedPrivateKeyEnvelope;
};

const vaultKeyStorageKey = 'nexusvault:vault-key:v1';
const vaultIterations = 600_000;
const aesTagLengthBits = 128;
const aesTagLengthBytes = aesTagLengthBits / 8;

function cryptoApi(): Crypto {
    if (!window.crypto?.subtle) {
        throw new Error('WebCrypto is not available in this browser.');
    }

    return window.crypto;
}

function randomBytes(length: number): Uint8Array {
    const bytes = new Uint8Array(length);
    cryptoApi().getRandomValues(bytes);

    return bytes;
}

function textBytes(value: string): Uint8Array {
    return new TextEncoder().encode(value);
}

function toArrayBuffer(bytes: Uint8Array): ArrayBuffer {
    return bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength) as ArrayBuffer;
}

function bytesText(bytes: Uint8Array): string {
    return new TextDecoder().decode(bytes);
}

function bytesToBase64(bytes: Uint8Array): string {
    let binary = '';
    bytes.forEach(byte => {
        binary += String.fromCharCode(byte);
    });

    return btoa(binary);
}

function base64ToBytes(base64: string): Uint8Array {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);

    for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
    }

    return bytes;
}

function pemToBytes(pem: string): Uint8Array {
    const base64 = pem
        .replace(/-----BEGIN PUBLIC KEY-----/g, '')
        .replace(/-----END PUBLIC KEY-----/g, '')
        .replace(/\s/g, '');

    return base64ToBytes(base64);
}

function bytesToHex(bytes: Uint8Array): string {
    return Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
}

function hexToBytes(hex: string): Uint8Array {
    const bytes = new Uint8Array(hex.length / 2);

    for (let index = 0; index < bytes.length; index += 1) {
        bytes[index] = Number.parseInt(hex.slice(index * 2, index * 2 + 2), 16);
    }

    return bytes;
}

function formatRecoveryKey(bytes: Uint8Array): string {
    const groups = bytesToHex(bytes).match(/.{1,4}/g) ?? [];

    return `NV-${groups.join('-')}`;
}

function recoveryKeyToBytes(recoveryKey: string): Uint8Array {
    const normalized = recoveryKey
        .trim()
        .replace(/^NV-/i, '')
        .replace(/[\s-]/g, '')
        .toLowerCase();

    if (!/^[0-9a-f]{64}$/.test(normalized)) {
        throw new Error('Invalid recovery key format.');
    }

    return hexToBytes(normalized);
}

function splitCiphertextAndTag(payload: ArrayBuffer): { ciphertext: Uint8Array; tag: Uint8Array } {
    const bytes = new Uint8Array(payload);

    return {
        ciphertext: bytes.slice(0, -aesTagLengthBytes),
        tag: bytes.slice(-aesTagLengthBytes),
    };
}

function joinCiphertextAndTag(ciphertextBase64: string, tagHex: string): Uint8Array {
    const ciphertext = base64ToBytes(ciphertextBase64);
    const tag = hexToBytes(tagHex);
    const payload = new Uint8Array(ciphertext.length + tag.length);

    payload.set(ciphertext);
    payload.set(tag, ciphertext.length);

    return payload;
}

async function deriveWrappingKey(vaultPassword: string, saltBase64: string, iterations: number): Promise<CryptoKey> {
    const passwordKey = await cryptoApi().subtle.importKey(
        'raw',
        toArrayBuffer(textBytes(vaultPassword)),
        'PBKDF2',
        false,
        ['deriveKey']
    );

    return cryptoApi().subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt: toArrayBuffer(base64ToBytes(saltBase64)),
            iterations,
            hash: 'SHA-256',
        },
        passwordKey,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt']
    );
}

async function importVaultAesKey(vaultKeyBytes: Uint8Array): Promise<CryptoKey> {
    return cryptoApi().subtle.importKey(
        'raw',
        toArrayBuffer(vaultKeyBytes),
        { name: 'AES-GCM' },
        false,
        ['encrypt', 'decrypt']
    );
}

async function importRecipientPublicKey(publicKeyPem: string): Promise<CryptoKey> {
    return cryptoApi().subtle.importKey(
        'spki',
        toArrayBuffer(pemToBytes(publicKeyPem)),
        { name: 'RSA-OAEP', hash: 'SHA-256' },
        false,
        ['encrypt']
    );
}

async function importPrivateKey(privateKeyBytes: Uint8Array): Promise<CryptoKey> {
    return cryptoApi().subtle.importKey(
        'pkcs8',
        toArrayBuffer(privateKeyBytes),
        { name: 'RSA-OAEP', hash: 'SHA-256' },
        false,
        ['decrypt']
    );
}

async function encryptBytes(key: CryptoKey, bytes: Uint8Array): Promise<EncryptedString> {
    const iv = randomBytes(12);
    const encrypted = await cryptoApi().subtle.encrypt(
        { name: 'AES-GCM', iv: toArrayBuffer(iv), tagLength: aesTagLengthBits },
        key,
        toArrayBuffer(bytes)
    );
    const { ciphertext, tag } = splitCiphertextAndTag(encrypted);

    return {
        ciphertext: bytesToBase64(ciphertext),
        iv: bytesToHex(iv),
        tag: bytesToHex(tag),
    };
}

async function decryptBytes(key: CryptoKey, encrypted: EncryptedString): Promise<Uint8Array> {
    const payload = joinCiphertextAndTag(encrypted.ciphertext, encrypted.tag);

    const decrypted = await cryptoApi().subtle.decrypt(
        {
            name: 'AES-GCM',
            iv: toArrayBuffer(hexToBytes(encrypted.iv)),
            tagLength: aesTagLengthBits,
        },
        key,
        toArrayBuffer(payload)
    );

    return new Uint8Array(decrypted);
}

function storeVaultKey(vaultKeyBytes: Uint8Array): void {
    sessionStorage.setItem(vaultKeyStorageKey, bytesToBase64(vaultKeyBytes));
}

export function clearStoredVaultKey(): void {
    sessionStorage.removeItem(vaultKeyStorageKey);
}

export function hasStoredVaultKey(): boolean {
    return sessionStorage.getItem(vaultKeyStorageKey) !== null;
}

async function getStoredVaultCryptoKey(): Promise<CryptoKey> {
    const stored = sessionStorage.getItem(vaultKeyStorageKey);

    if (!stored) {
        throw new Error('Vault is locked.');
    }

    return importVaultAesKey(base64ToBytes(stored));
}

export async function createRegistrationVaultPackage(vaultPassword: string): Promise<RegistrationVaultPackage> {
    const salt = randomBytes(16);
    const vaultKeyBytes = randomBytes(32);
    const recoveryKeyBytes = randomBytes(32);
    const wrappingKey = await deriveWrappingKey(vaultPassword, bytesToBase64(salt), vaultIterations);
    const wrappedVaultKey = await encryptBytes(wrappingKey, vaultKeyBytes);
    const vaultKey = await importVaultAesKey(vaultKeyBytes);
    const recoveryWrappingKey = await importVaultAesKey(recoveryKeyBytes);
    const wrappedRecoveryVaultKey = await encryptBytes(recoveryWrappingKey, vaultKeyBytes);
    const rsaAlgorithm: RsaHashedKeyGenParams = {
        name: 'RSA-OAEP',
        modulusLength: 2048,
        publicExponent: new Uint8Array([1, 0, 1]) as Uint8Array<ArrayBuffer>,
        hash: 'SHA-256',
    };

    const rsaKeys = await cryptoApi().subtle.generateKey(
        rsaAlgorithm,
        true,
        ['encrypt', 'decrypt']
    );

    const publicKey = await exportPublicKeyPem(rsaKeys.publicKey);
    const privateKeyBytes = new Uint8Array(await cryptoApi().subtle.exportKey('pkcs8', rsaKeys.privateKey));
    const encryptedPrivateKey = await encryptBytes(vaultKey, privateKeyBytes);

    storeVaultKey(vaultKeyBytes);

    return {
        vaultKeyEnvelope: {
            version: 1,
            algorithm: 'AES-GCM',
            kdf: 'PBKDF2-SHA-256',
            iterations: vaultIterations,
            salt: bytesToBase64(salt),
            ...wrappedVaultKey,
        },
        vaultRecoveryEnvelope: {
            version: 1,
            algorithm: 'AES-GCM',
            keySource: 'recovery-key',
            ...wrappedRecoveryVaultKey,
        },
        recoveryKey: formatRecoveryKey(recoveryKeyBytes),
        publicKey,
        encryptedPrivateKey: {
            version: 1,
            algorithm: 'AES-GCM',
            keyFormat: 'pkcs8',
            ...encryptedPrivateKey,
        },
    };
}

export async function unlockVaultKey(envelope: VaultKeyEnvelope, vaultPassword: string): Promise<void> {
    const wrappingKey = await deriveWrappingKey(vaultPassword, envelope.salt, envelope.iterations);
    const vaultKeyBytes = await decryptBytes(wrappingKey, envelope);

    storeVaultKey(vaultKeyBytes);
}

export async function unlockVaultKeyWithRecovery(envelope: VaultRecoveryEnvelope, recoveryKey: string): Promise<void> {
    const recoveryWrappingKey = await importVaultAesKey(recoveryKeyToBytes(recoveryKey));
    const vaultKeyBytes = await decryptBytes(recoveryWrappingKey, envelope);

    storeVaultKey(vaultKeyBytes);
}

export async function encryptVaultString(value: string): Promise<EncryptedString> {
    const vaultKey = await getStoredVaultCryptoKey();

    return encryptBytes(vaultKey, textBytes(value));
}

export async function decryptVaultString(encrypted: EncryptedString): Promise<string> {
    const vaultKey = await getStoredVaultCryptoKey();
    const decrypted = await decryptBytes(vaultKey, encrypted);

    return bytesText(decrypted);
}

export async function generateSharedItemKey(): Promise<string> {
    return bytesToBase64(randomBytes(32));
}

async function importSharedItemKey(sharedKeyBase64: string): Promise<CryptoKey> {
    return importVaultAesKey(base64ToBytes(sharedKeyBase64));
}

export async function encryptSharedKeyForVault(sharedKeyBase64: string): Promise<SharedKeyEnvelope> {
    const vaultKey = await getStoredVaultCryptoKey();
    const encrypted = await encryptBytes(vaultKey, base64ToBytes(sharedKeyBase64));

    return {
        version: 1,
        algorithm: 'AES-GCM',
        keySource: 'shared-item-key',
        ...encrypted,
    };
}

export async function decryptSharedKeyFromVault(envelope: SharedKeyEnvelope): Promise<string> {
    const vaultKey = await getStoredVaultCryptoKey();
    const sharedKeyBytes = await decryptBytes(vaultKey, envelope);

    return bytesToBase64(sharedKeyBytes);
}

export async function encryptSharedVaultString(value: string, sharedKeyBase64: string): Promise<EncryptedString> {
    const sharedKey = await importSharedItemKey(sharedKeyBase64);

    return encryptBytes(sharedKey, textBytes(value));
}

export async function decryptSharedVaultString(encrypted: EncryptedString, sharedKeyBase64: string): Promise<string> {
    const sharedKey = await importSharedItemKey(sharedKeyBase64);
    const decrypted = await decryptBytes(sharedKey, encrypted);

    return bytesText(decrypted);
}

export async function encryptSharedItemFields(
    sensitiveData: ClientShareSensitiveData,
    sharedKeyBase64: string
): Promise<SharedItemFields> {
    return {
        username: await encryptSharedVaultString(sensitiveData.username, sharedKeyBase64),
        password: await encryptSharedVaultString(sensitiveData.password, sharedKeyBase64),
        notes: sensitiveData.notes ? await encryptSharedVaultString(sensitiveData.notes, sharedKeyBase64) : null,
    };
}

export async function encryptSharedKeyForRecipient(
    sharedKeyBase64: string,
    recipientPublicKeyPem: string
): Promise<string> {
    const recipientPublicKey = await importRecipientPublicKey(recipientPublicKeyPem);
    const encryptedShareKey = await cryptoApi().subtle.encrypt(
        { name: 'RSA-OAEP' },
        recipientPublicKey,
        toArrayBuffer(base64ToBytes(sharedKeyBase64))
    );

    return bytesToBase64(new Uint8Array(encryptedShareKey));
}

async function decryptPrivateKeyEnvelope(encryptedPrivateKey: EncryptedPrivateKeyEnvelope): Promise<CryptoKey> {
    const vaultKey = await getStoredVaultCryptoKey();
    const privateKeyBytes = await decryptBytes(vaultKey, encryptedPrivateKey);

    return importPrivateKey(privateKeyBytes);
}

export async function decryptSharedKeyFromRecipient(
    encryptedSharedKey: string,
    encryptedPrivateKey: EncryptedPrivateKeyEnvelope
): Promise<string> {
    const privateKey = await decryptPrivateKeyEnvelope(encryptedPrivateKey);
    const sharedKeyBytes = await cryptoApi().subtle.decrypt(
        { name: 'RSA-OAEP' },
        privateKey,
        toArrayBuffer(base64ToBytes(encryptedSharedKey))
    );

    return bytesToBase64(new Uint8Array(sharedKeyBytes));
}

export async function createClientSyncSharePayload(
    sensitiveData: ClientShareSensitiveData,
    recipientPublicKeyPem: string,
    existingSharedKeyEnvelope?: SharedKeyEnvelope | null
): Promise<ClientSharePayload & { shared_key_envelope: SharedKeyEnvelope; shared_fields: SharedItemFields }> {
    const sharedKeyBase64 = existingSharedKeyEnvelope
        ? await decryptSharedKeyFromVault(existingSharedKeyEnvelope)
        : await generateSharedItemKey();
    const sharedKey = await importSharedItemKey(sharedKeyBase64);
    const sharedKeyEnvelope = existingSharedKeyEnvelope ?? await encryptSharedKeyForVault(sharedKeyBase64);
    const sharedFields = await encryptSharedItemFields(sensitiveData, sharedKeyBase64);
    const encryptedData = await encryptBytes(sharedKey, textBytes(JSON.stringify(sensitiveData)));
    const encryptedShareKey = await encryptSharedKeyForRecipient(sharedKeyBase64, recipientPublicKeyPem);

    return {
        version: 2,
        mode: 'client-encrypted-sync',
        encrypted_aes_key: encryptedShareKey,
        encrypted_data: encryptedData,
        shared_key_envelope: sharedKeyEnvelope,
        shared_fields: sharedFields,
    };
}

export async function createClientSharePayload(
    sensitiveData: ClientShareSensitiveData,
    recipientPublicKeyPem: string
): Promise<ClientSharePayload> {
    const shareKeyBytes = randomBytes(32);
    const shareKey = await importVaultAesKey(shareKeyBytes);
    const recipientPublicKey = await importRecipientPublicKey(recipientPublicKeyPem);

    const encryptedData = await encryptBytes(shareKey, textBytes(JSON.stringify(sensitiveData)));
    const encryptedShareKey = await cryptoApi().subtle.encrypt(
        { name: 'RSA-OAEP' },
        recipientPublicKey,
        toArrayBuffer(shareKeyBytes)
    );

    return {
        version: 1,
        mode: 'client-encrypted',
        encrypted_aes_key: bytesToBase64(new Uint8Array(encryptedShareKey)),
        encrypted_data: encryptedData,
    };
}

export async function decryptClientSharePayload(
    payload: ClientSharePayload,
    encryptedPrivateKey: EncryptedPrivateKeyEnvelope
): Promise<ClientShareSensitiveData> {
    const privateKey = await decryptPrivateKeyEnvelope(encryptedPrivateKey);
    const shareKeyBytes = await cryptoApi().subtle.decrypt(
        { name: 'RSA-OAEP' },
        privateKey,
        toArrayBuffer(base64ToBytes(payload.encrypted_aes_key))
    );
    const shareKey = await importVaultAesKey(new Uint8Array(shareKeyBytes));
    const decryptedData = await decryptBytes(shareKey, payload.encrypted_data);

    return JSON.parse(bytesText(decryptedData)) as ClientShareSensitiveData;
}

async function exportPublicKeyPem(publicKey: CryptoKey): Promise<string> {
    const spki = new Uint8Array(await cryptoApi().subtle.exportKey('spki', publicKey));
    const base64 = bytesToBase64(spki);
    const lines = base64.match(/.{1,64}/g) ?? [];

    return `-----BEGIN PUBLIC KEY-----\n${lines.join('\n')}\n-----END PUBLIC KEY-----`;
}
