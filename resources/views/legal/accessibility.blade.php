@php
    $isFrench = app()->getLocale() === 'fr';
    $title = __('Accessibility Statement');
    $description = $isFrench
        ? 'Notre engagement pour rendre NexusVault utilisable au clavier, lisible et compréhensible.'
        : 'Our commitment to keeping NexusVault keyboard-friendly, readable, and understandable.';
@endphp

<x-layouts.legal :title="$title" :description="$description">
    @if($isFrench)
        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">1. Engagement</h2>
            <p>NexusVault vise une expérience accessible et inclusive. Notre cible de conception est WCAG 2.2 niveau AA lorsque cela est raisonnablement applicable à l’application.</p>
            <p>Comme NexusVault protège des données sensibles, l’accessibilité doit coexister avec la sécurité: les flux d’authentification, de passkey et de coffre doivent rester sûrs sans devenir inutilement difficiles à utiliser.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">2. Mesures déjà prévues</h2>
            <p>L’interface utilise des contrastes élevés, des libellés de champs, des états visuels, une navigation responsive, un sélecteur de langue et des zones d’action suffisamment grandes pour les écrans tactiles.</p>
            <p>Les pages importantes doivent rester utilisables au clavier autant que possible, avec un ordre de lecture cohérent et des textes qui ne dépendent pas uniquement de la couleur.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">3. Limites connues</h2>
            <p>Certains écrans de sécurité dépendent du navigateur ou de fournisseurs externes, notamment les dialogues passkey, les écrans OAuth et certains comportements de gestionnaire de mots de passe. Ces éléments ne sont pas toujours entièrement contrôlés par NexusVault.</p>
            <p>NexusVault est encore en évolution. Des audits manuels au clavier, au lecteur d’écran et sur mobile doivent être répétés avant chaque déploiement majeur.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">4. Signaler un problème</h2>
            <p>Si vous rencontrez une barrière d’accessibilité, contactez-nous à <a href="mailto:privacy@nexusvault.dev" class="text-emerald-400 hover:text-emerald-300">privacy@nexusvault.dev</a> avec la page concernée, votre navigateur, votre technologie d’assistance si applicable, et une description du problème.</p>
        </section>
    @else
        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">1. Commitment</h2>
            <p>NexusVault aims to provide an accessible and inclusive experience. Our design target is WCAG 2.2 Level AA where reasonably applicable to the application.</p>
            <p>Because NexusVault protects sensitive data, accessibility must coexist with security: authentication, passkey, and vault flows should remain safe without becoming unnecessarily difficult to use.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">2. Measures already in place</h2>
            <p>The interface uses strong contrast, field labels, visual states, responsive navigation, a language switcher, and action areas sized for touch screens.</p>
            <p>Important pages should remain keyboard-usable as much as possible, with a coherent reading order and text that does not depend on color alone.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">3. Known limits</h2>
            <p>Some security screens depend on the browser or external providers, including passkey dialogs, OAuth screens, and some password-manager behaviors. NexusVault does not fully control those elements.</p>
            <p>NexusVault is still evolving. Manual keyboard, screen-reader, and mobile audits should be repeated before each major deployment.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">4. Report an issue</h2>
            <p>If you encounter an accessibility barrier, contact us at <a href="mailto:privacy@nexusvault.dev" class="text-emerald-400 hover:text-emerald-300">privacy@nexusvault.dev</a> with the affected page, your browser, assistive technology if applicable, and a description of the issue.</p>
        </section>
    @endif
</x-layouts.legal>
