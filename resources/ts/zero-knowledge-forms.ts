import { encryptVaultString, hasStoredVaultKey } from './zero-knowledge';

function shouldUseClientEncryption(): boolean {
    return Boolean((window as any).nexusVaultUsesClientEncryption);
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

async function encryptFieldIntoForm(form: HTMLFormElement, fieldName: string, ivName: string, tagName: string): Promise<void> {
    const field = form.querySelector<HTMLInputElement | HTMLTextAreaElement>(`[name="${fieldName}"]`);

    if (!field || field.value === '') {
        setHiddenInput(form, ivName, '');
        setHiddenInput(form, tagName, '');

        return;
    }

    const encrypted = await encryptVaultString(field.value);
    field.value = encrypted.ciphertext;
    setHiddenInput(form, ivName, encrypted.iv);
    setHiddenInput(form, tagName, encrypted.tag);
}

async function submitEncryptedCreateForm(form: HTMLFormElement): Promise<void> {
    if (!hasStoredVaultKey()) {
        window.location.href = '/vault/unlock';

        return;
    }

    await encryptFieldIntoForm(form, 'username', 'username_iv', 'username_tag');
    await encryptFieldIntoForm(form, 'password', 'password_iv', 'password_tag');
    await encryptFieldIntoForm(form, 'notes', 'notes_iv', 'notes_tag');
    setHiddenInput(form, 'client_encrypted', '1');
    form.dataset.zkEncrypted = '1';

    HTMLFormElement.prototype.submit.call(form);
}

export function bindZeroKnowledgeCreateForm(): void {
    const form = document.getElementById('create-service-form') as HTMLFormElement | null;

    if (!form || !shouldUseClientEncryption()) {
        return;
    }

    form.addEventListener('submit', event => {
        if (form.dataset.zkEncrypted === '1') {
            return;
        }

        event.preventDefault();
        void submitEncryptedCreateForm(form);
    });
}
