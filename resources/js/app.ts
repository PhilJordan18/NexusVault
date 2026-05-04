import { createInertiaApp } from '@inertiajs/vue3';
import { initPasskeys } from '../ts/passkey';
import { showShareModal, hideShareModal } from '../ts/utils/modals';
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
});
