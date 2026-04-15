export class WebAuthn {
    private readonly csrfToken: string;

    constructor() {
        const metaTag = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
        this.csrfToken = metaTag?.content || '';
    }

    async login(credential: any): Promise<boolean> {
        const response = await fetch('/webauthn/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({ credential })
        });

        return response.ok;
    }

    async register(credential: any): Promise<boolean> {
        const response = await fetch('/webauthn/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({ credential })
        });

        return response.ok;
    }

    async getRegisterOptions(): Promise<any> {
        const res = await fetch('/webauthn/register/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return res.json();
    }

    async getLoginOptions(): Promise<any> {
        const response = await fetch('/webauthn/login/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        return response.json();
    }
}
