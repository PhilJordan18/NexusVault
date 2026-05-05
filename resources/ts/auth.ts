import { WebAuthn } from './WebAuthn';

const webauthn = new WebAuthn();

document.addEventListener('DOMContentLoaded', () => {
    initAuthPage();
});

function initAuthPage() {
    // Password visibility toggle
    document.querySelectorAll('[id^="toggle-password"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const container = btn.closest('.relative');
            const input = container?.querySelector('input') as HTMLInputElement;
            const icon = btn.querySelector('i') as HTMLElement;

            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
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
    const passwordInput = document.querySelector('input[name="password"]');

    if (passwordInput) {
        passwordInput.addEventListener('input', handlePasswordInput);
    }

    // Generate password button (register page)
    const generateBtn = document.getElementById('generate-password');

    if (generateBtn) {
        generateBtn.addEventListener('click', handleGeneratePassword);
    }

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

async function handlePasskeyLogin() {
    try {
        const options = await webauthn.getLoginOptions();
        const credential = await navigator.credentials.get({ publicKey: options });
        const result = await webauthn.login(credential);

        if (result.success && result.redirect) {
            (window as any).showToast?.('Connected with passkey.', 'success');
            setTimeout(() => {
                if (result.redirect != null) {
                    window.location.href = result.redirect;
                }
            }, 300);
        } else {
            (window as any).showToast?.('Authentication failed with passkey.', 'error');
        }
    } catch (e: any) {
        console.error('Passkey login error:', e);

        if (e.name === 'NotAllowedError') {
            (window as any).showToast?.('Action cancelled.', 'error');
        } else {
            (window as any).showToast?.('Unable to use passkey. Try another method.', 'error');
        }
    }
}

async function handleGeneratePassword() {
    try {
        const res = await fetch('/password/generate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')!.getAttribute('content')!
            }
        });

        const { password } = await res.json();

        const pwdInput = document.getElementById('password') as HTMLInputElement;
        const confirmInput = document.getElementById('password_confirmation') as HTMLInputElement;

        if (pwdInput) {
            pwdInput.value = password;
            pwdInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        if (confirmInput) {
            confirmInput.value = password;
        }
    } catch (e) {
        console.error('Failed to generate password:', e);
    }
}

async function calculateEntropy(password: string) {
    if (password.length < 4) {
        return { entropy: 0, strength: 'very_weak', label: 'Very weak' };
    }

    const res = await fetch('/password/entropy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')!.getAttribute('content')!
        },
        body: JSON.stringify({ password })
    });

    return res.json();
}

async function handlePasswordInput(e: Event) {
    const input = e.target as HTMLInputElement;
    const container = document.getElementById('strength-container');

    if (!container) {
        return;
    }

    const result = await calculateEntropy(input.value);
    const bar = document.getElementById('strength-bar') as HTMLElement;
    const text = document.getElementById('strength-text') as HTMLElement;

    if (!bar || !text) {
        return;
    }

    container.classList.remove('hidden');

    const percentage = Math.min((result.entropy / 100) * 100, 100);
    bar.style.width = `${percentage}%`;

    const label = result.label.toLowerCase();

    if (label.includes('very strong') || label.includes('strong')) {
        bar.style.backgroundColor = '#00ff9d';
    } else if (label.includes('medium')) {
        bar.style.backgroundColor = '#ffaa00';
    } else {
        bar.style.backgroundColor = '#ff4444';
    }

    text.textContent = result.label;
}
