import { createInertiaApp } from '@inertiajs/vue3';
import { initPasskeys } from '../ts/passkey';
import { showShareModal, hideShareModal } from '../ts/utils/modals';
import { bindPasswordStrength } from '../ts/utils/password-utils';
import '../ts/sessions'
import './pages/service';

if (typeof window !== 'undefined') {
    (window as any).showShareModal = showShareModal;
    (window as any).hideShareModal = hideShareModal;
}

const appName = 'NexusVault';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
});

// Initialize Passkeys on every page (including Settings)
document.addEventListener('DOMContentLoaded', () => {
    initPasskeys();
    bindPasswordStrength(
        'create-password',
        'create-password-toggle',
        'create-strength-container',
        'create-strength-bar',
        'create-strength-text',
        'create-generate-btn'
    );
});
