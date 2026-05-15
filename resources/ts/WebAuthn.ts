function base64urlToArrayBuffer(base64url: string): ArrayBuffer {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const buffer = new ArrayBuffer(raw.length);
    const view = new Uint8Array(buffer);

    for (let i = 0; i < raw.length; i++) {
        view[i] = raw.charCodeAt(i);
    }

    return buffer;
}

function decodeAttestationOptions(options: any): any {
    const copy = { ...options };

    if (copy.challenge) {
        copy.challenge = base64urlToArrayBuffer(copy.challenge);
    }

    if (copy.user && copy.user.id) {
        copy.user = { ...copy.user, id: base64urlToArrayBuffer(copy.user.id) };
    }

    return copy;
}

function decodeAssertionOptions(options: any): any {
    const copy = { ...options };

    if (copy.challenge) {
        copy.challenge = base64urlToArrayBuffer(copy.challenge);
    }

    if (copy.allowCredentials) {

        copy.allowCredentials = copy.allowCredentials.map((cred: any) => ({
            ...cred,
            id: base64urlToArrayBuffer(cred.id),
        }));
    }

    return copy;
}

function arrayBufferToBase64url(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';

    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }

    const base64 = btoa(binary);

    // Replace the non-compatible url characters
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

// Convert PublicKeyCredential in JSON'S object
function serializeCredential(credential: PublicKeyCredential): any {
    const response = credential.response as AuthenticatorAttestationResponse;

    return {
        id: credential.id,
        type: credential.type,
        rawId: arrayBufferToBase64url(credential.rawId),
        response: {
            clientDataJSON: arrayBufferToBase64url(response.clientDataJSON),
            attestationObject: arrayBufferToBase64url(response.attestationObject),
        },
    };
}

function serializeAssertionCredential(credential: PublicKeyCredential): any {
    const response = credential.response as AuthenticatorAssertionResponse;

    return {
        id: credential.id,
        type: credential.type,
        rawId: arrayBufferToBase64url(credential.rawId),
        response: {
            authenticatorData: arrayBufferToBase64url(response.authenticatorData),
            clientDataJSON: arrayBufferToBase64url(response.clientDataJSON),
            signature: arrayBufferToBase64url(response.signature),
            userHandle: response.userHandle ? arrayBufferToBase64url(response.userHandle) : null,
        },
    };
}

export class WebAuthn {
    private readonly csrfToken: string;

    constructor() {
        const metaTag = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
        this.csrfToken = metaTag?.content || '';
    }

    async getRegisterOptions(): Promise<any> {
        const res = await fetch('/webauthn/register/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json',
            },
        });

        if (!res.ok) {
            const text = await res.text();
            console.error('Server returned non-JSON:', text);

            throw new Error(`Server error ${res.status}: ${text.substring(0, 200)}`);
        }

        const data = await res.json();

        return decodeAttestationOptions(data);
    }

    async register(credential: any, alias?: string): Promise<boolean> {
        const serialized = serializeCredential(credential);

        const body: any = { ...serialized };

        if (alias) {
            body.alias = alias;
        }

        const res = await fetch('/webauthn/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });

        if (!res.ok) {
            const errorText = await res.text();
            console.error('Register failed:', errorText);

            return false;
        }

        return true;
    }

    async getLoginOptions(): Promise<any> {
        const res = await fetch('/webauthn/login/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json',
            },
        });

        if (!res.ok) {
            const text = await res.text();
            console.error('Login options error:', text);

            throw new Error(`Server error ${res.status}`);
        }

        const data = await res.json();

        return decodeAssertionOptions(data);
    }

    async login(credential: any): Promise<{ success: boolean; redirect?: string }> {
        const serialized = serializeAssertionCredential(credential);

        const res = await fetch('/webauthn/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(serialized),
        });

        if (res.ok) {
            const data = await res.json();

            return { success: true, redirect: data.redirect };
        }

        return { success: false };
    }
}
