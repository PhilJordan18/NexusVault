import { createInertiaApp, router } from '@inertiajs/vue3';

const appName = 'NexusVault';

function syncDocumentLocale(locale: unknown): void {
    if (typeof locale === 'string' && locale !== '') {
        document.documentElement.lang = locale;
    }
}

function bootInertiaApp(): void {
    if (!document.querySelector('[data-page]')) {
        return;
    }

    router.on('navigate', (event) => {
        syncDocumentLocale(event.detail.page.props.locale);
    });
    router.on('success', (event) => {
        syncDocumentLocale(event.detail.page.props.locale);
    });

    void createInertiaApp({
        title: (title) => (title ? `${title} - ${appName}` : appName),
        progress: {
            color: '#4B5563',
        },
    });
}

async function bootBrowserModules(): Promise<void> {
    const [
        { initPasskeys },
        { showShareModal, hideShareModal },
        { bindPasswordGeneratorCustomization, bindPasswordStrength },
        { bindZeroKnowledgeCreateForm },
        { bindZeroKnowledgeSharing },
    ] = await Promise.all([
        import('../ts/passkey'),
        import('../ts/utils/modals'),
        import('../ts/utils/password-utils'),
        import('../ts/zero-knowledge-forms'),
        import('../ts/zero-knowledge-sharing'),
        import('../ts/search'),
        import('../ts/sessions'),
        import('./pages/service'),
    ]);

    (window as any).showShareModal = showShareModal;
    (window as any).hideShareModal = hideShareModal;

    initPasskeys();
    bindZeroKnowledgeCreateForm();
    bindZeroKnowledgeSharing();
    bindPasswordStrength(
        'create-password',
        'create-password-toggle',
        'create-strength-container',
        'create-strength-bar',
        'create-strength-text',
        'create-generate-btn',
    );
    bindPasswordGeneratorCustomization();
}

if (typeof window !== 'undefined') {
    bootInertiaApp();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            void bootBrowserModules();
        });
    } else {
        void bootBrowserModules();
    }
}
