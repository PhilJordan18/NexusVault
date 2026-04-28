import { createInertiaApp } from '@inertiajs/vue3';
import './pages/service';
import { showShareModal, hideShareModal } from '../ts/utils/modals';

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
