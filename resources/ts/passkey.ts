import { WebAuthn } from './WebAuthn';

const webauthn = new WebAuthn();

export function initPasskeys() {
    const registerBtn = document.getElementById('register-passkey-btn');

    if (registerBtn) {
        registerBtn.addEventListener('click', async () => {
            try {
                const options = await webauthn.getRegisterOptions();
                const credential = await navigator.credentials.create({ publicKey: options });
                const success = await webauthn.register(credential);

                if (success) {
                    alert('✅ Passkey enregistrée avec succès !');
                    window.location.reload();
                } else {
                    alert('❌ Erreur lors de l’enregistrement');
                }
            } catch (e: any) {
                console.error(e);

                if (e.name === 'NotAllowedError') {
                    alert('Action annulée par l’utilisateur.');
                } else {
                    alert('Impossible d’enregistrer la passkey. Vérifie la console.');
                }
            }
        });
    }
}
