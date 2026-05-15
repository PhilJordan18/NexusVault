export class WebAuthn {
    constructor() {
        Object.defineProperty(this, "csrfToken", {
            enumerable: true,
            configurable: true,
            writable: true,
            value: void 0
        });
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        this.csrfToken = metaTag?.content || '';
    }
    async login(credential) {
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
    async register(credential) {
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
    async getRegisterOptions() {
        const res = await fetch('/webauthn/register/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });
        return res.json();
    }
    async getLoginOptions() {
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
//# sourceMappingURL=WebAuthn.js.map