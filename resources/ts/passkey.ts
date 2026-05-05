import { WebAuthn } from './WebAuthn';

const webauthn = new WebAuthn();

function getDeviceName(): string {
    const ua = navigator.userAgent;
    let browser = 'Navigateur';

    if (/Chrome/i.test(ua) && !/Opera|OPR/i.test(ua) && !/Edge/i.test(ua)) {
        browser = 'Chrome';
    } else if (/Firefox/i.test(ua)) {
        browser = 'Firefox';
    } else if (/Safari/i.test(ua) && !/Chrome/i.test(ua)) {
        browser = 'Safari';
    } else if (/Opera/i.test(ua) || /OPR/i.test(ua)) {
        browser = 'Opera';
    } else if (/Edge/i.test(ua)) {
        browser = 'Edge';
    }

    let os = navigator.platform || 'device';

    if (os.startsWith('Mac')) {
        os = 'macOS';
    } else if (os.startsWith('Win')) {
        os = 'Windows';
    } else if (os.startsWith('Linux')) {
        os = 'Linux';
    } else if (os === 'iPhone' || os === 'iPad') {
        os = 'iOS';
    } else if (os === 'Android') {
        os = 'Android';
    }

    return `${browser} on ${os}`;
}

export function initPasskeys() {
    const registerBtn = document.getElementById('register-passkey-btn');

    if (registerBtn) {
        registerBtn.addEventListener('click', async () => {
            try {
                const options = await webauthn.getRegisterOptions();
                const credential = await navigator.credentials.create({ publicKey: options });
                const deviceName = `${getDeviceName()}`;
                const success = await webauthn.register(credential, deviceName);

                if (success) {
                    (window as any).showToast?.('Passkey registered successfully!', 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    (window as any).showToast?.('Unable to register passkey.', 'error');
                }
            } catch (e: any) {
                console.error(e);

                if (e.name === 'NotAllowedError') {
                    (window as any).showToast?.('Action cancelled by the user.');
                } else {
                    (window as any).showToast?.('Unable to save the passkey. Check the console.');
                }
            }
        });
    }
}
