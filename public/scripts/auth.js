import { WebAuthn } from './WebAuthn';
const webauthn = new WebAuthn();
document.addEventListener('DOMContentLoaded', () => {
    initAuthPage();
});
function initAuthPage() {
    document.querySelectorAll('[id^="toggle-password"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const container = btn.closest('.relative');
            const input = container?.querySelector('input');
            const icon = btn.querySelector('i');
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                }
                else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            }
        });
    });
    // Passkey button
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
    const hiddenEmailInput = document.getElementById('hidden-email');
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
        // Get login options (not register options)
        const options = await webauthn.getLoginOptions();
        const credential = await navigator.credentials.get({
            publicKey: options
        });
        const success = await webauthn.login(credential);
        if (success) {
            window.location.href = '/dashboard';
        }
        else {
            alert('Passkey authentication failed');
        }
    }
    catch (e) {
        console.error('Passkey error:', e);
        alert('No passkey found for this device or authentication cancelled.');
    }
}
async function handleGeneratePassword() {
    try {
        const res = await fetch('/password/generate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const { password } = await res.json();
        const pwdInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        if (pwdInput) {
            pwdInput.value = password;
            pwdInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (confirmInput) {
            confirmInput.value = password;
        }
    }
    catch (e) {
        console.error('Failed to generate password:', e);
    }
}
async function calculateEntropy(password) {
    if (password.length < 4) {
        return { score: 0, label: 'Too weak' };
    }
    try {
        const res = await fetch('/password/entropy', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ password })
        });
        if (!res.ok) {
            throw new Error('Entropy calculation failed');
        }
        return res.json();
    }
    catch (e) {
        console.error('Entropy error:', e);
        return { score: 0, label: 'Error' };
    }
}
async function handlePasswordInput(e) {
    const input = e.target;
    const container = document.getElementById('strength-container');
    if (!container) {
        return;
    }
    const result = await calculateEntropy(input.value);
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    if (!bar || !text) {
        return;
    }
    container.classList.remove('hidden');
    const percentage = Math.min((result.score / 4) * 100, 100);
    bar.style.width = `${percentage}%`;
    if (result.score >= 4) {
        bar.style.backgroundColor = '#00ff9d';
        text.textContent = 'Very strong';
    }
    else if (result.score >= 3) {
        bar.style.backgroundColor = '#00cc7a';
        text.textContent = 'Strong';
    }
    else if (result.score >= 2) {
        bar.style.backgroundColor = '#ffaa00';
        text.textContent = 'Medium';
    }
    else {
        bar.style.backgroundColor = '#ff4444';
        text.textContent = 'Weak';
    }
}
//# sourceMappingURL=auth.js.map