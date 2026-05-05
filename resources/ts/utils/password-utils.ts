// resources/ts/password-utils.ts

/**
 * Calcule l'entropie et retourne un objet { entropy, strength, label }.
 * (appel API vers /password/entropy)
 */
export async function calculateEntropy(password: string): Promise<{ entropy: number; strength: string; label: string }> {
    if (password.length < 4) {
        return { entropy: 0, strength: 'very_weak', label: 'Very weak' };
    }

    const res = await fetch('/password/entropy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content,
        },
        body: JSON.stringify({ password }),
    });

    return res.json();
}

/**
 * Génére un mot de passe fort via l'API.
 */
export async function generatePassword(): Promise<string> {
    const res = await fetch('/password/generate', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content,
        },
    });
    const data = await res.json();
    return data.password;
}

/**
 * Initialise la barre de force et le bouton de génération pour un champ password donné.
 *
 * @param passwordInputId  - ID de l'input password
 * @param toggleBtnId      - ID du bouton pour afficher/masquer le mot de passe (peut être null)
 * @param strengthContainerId - ID du conteneur de la barre de force
 * @param strengthBarId    - ID de la barre de progression
 * @param strengthTextId   - ID du texte indiquant la force
 * @param generateBtnId    - ID du bouton "Generate strong password" (peut être null)
 * @param confirmationInputId - ID du champ confirmation (optionnel)
 */
export function bindPasswordStrength(
    passwordInputId: string,
    toggleBtnId: string | null,
    strengthContainerId: string,
    strengthBarId: string,
    strengthTextId: string,
    generateBtnId: string | null,
    confirmationInputId?: string
) {
    const passwordInput = document.getElementById(passwordInputId) as HTMLInputElement;
    if (!passwordInput) return;

    const strengthContainer = document.getElementById(strengthContainerId);
    const strengthBar = document.getElementById(strengthBarId) as HTMLElement;
    const strengthText = document.getElementById(strengthTextId) as HTMLElement;

    // Toggle visibility
    if (toggleBtnId) {
        const toggleBtn = document.getElementById(toggleBtnId);
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const icon = toggleBtn.querySelector('i') as HTMLElement;
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon?.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon?.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        }
    }

    // Entropy on input
    passwordInput.addEventListener('input', async () => {
        if (!strengthContainer || !strengthBar || !strengthText) return;

        const result = await calculateEntropy(passwordInput.value);
        const entropy = result.entropy;
        const percentage = Math.min((entropy / 100) * 100, 100);
        strengthBar.style.width = `${percentage}%`;
        strengthText.textContent = result.label;

        // Couleur
        if (result.strength === 'very_weak' || result.strength === 'weak') {
            strengthBar.style.backgroundColor = '#ff4444';
        } else if (result.strength === 'medium') {
            strengthBar.style.backgroundColor = '#ffaa00';
        } else {
            strengthBar.style.backgroundColor = '#00ff9d';
        }

        strengthContainer.classList.remove('hidden');
    });

    // Generate password
    if (generateBtnId) {
        const generateBtn = document.getElementById(generateBtnId);
        if (generateBtn) {
            generateBtn.addEventListener('click', async () => {
                const newPassword = await generatePassword();
                passwordInput.value = newPassword;
                // Déclencher l'événement input pour mettre à jour la force
                passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
                if (confirmationInputId) {
                    const confirmInput = document.getElementById(confirmationInputId) as HTMLInputElement;
                    if (confirmInput) confirmInput.value = newPassword;
                }
            });
        }
    }
}
