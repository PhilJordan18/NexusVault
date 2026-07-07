@php
    $isFrench = app()->getLocale() === 'fr';
    $title = __('Privacy Policy');
    $description = $isFrench
        ? 'Ce que NexusVault collecte, ce que le serveur peut voir, et ce qui reste chiffré côté navigateur.'
        : 'What NexusVault collects, what the server can see, and what stays encrypted in the browser.';
@endphp

<x-layouts.legal :title="$title" :description="$description">
    @if($isFrench)
        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">1. Résumé clair</h2>
            <p>NexusVault est conçu pour minimiser les données visibles par le serveur. Le contenu sensible de votre coffre est chiffré dans votre navigateur avant d’être envoyé au serveur.</p>
            <p>Nous pouvons voir certaines données nécessaires au fonctionnement du service, comme votre courriel, les métadonnées de session et les informations techniques liées à la sécurité. Nous ne devrions pas pouvoir lire vos mots de passe, notes sécurisées ou cartes en clair.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">2. Données de compte collectées</h2>
            <p>Nous collectons les données que vous fournissez lors de l’inscription et de l’utilisation du compte: nom, adresse courriel, préférence de langue, thème, photo de profil si vous en ajoutez une, et état de vérification du courriel.</p>
            <p>Si vous utilisez OAuth, nous recevons les informations nécessaires du fournisseur choisi, comme l’identifiant OAuth, le nom et l’adresse courriel associés au compte. Si vous utilisez une passkey, nous stockons les données publiques nécessaires à la vérification, jamais votre biométrie.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">3. Données de coffre</h2>
            <p>Les éléments du coffre sont stockés sous forme chiffrée. Cela peut inclure des blobs chiffrés, des sels cryptographiques, des clés chiffrées, des payloads de partage chiffrés et des métadonnées nécessaires à la synchronisation.</p>
            <p>Le serveur peut traiter des métadonnées comme l’existence d’un élément, un destinataire de partage, un statut d’acceptation ou une date de mise à jour. Le modèle zéro connaissance vise à empêcher le serveur de lire le contenu secret de l’élément.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">4. Données techniques et journaux</h2>
            <p>Pour protéger le service, diagnostiquer les erreurs et prévenir les abus, nous pouvons traiter des adresses IP, informations de navigateur, horodatages, routes demandées, erreurs applicatives et informations de session.</p>
            <p>Ces données doivent être utilisées pour la sécurité, la maintenance, la prévention de fraude ou le respect d’obligations légales, et non pour vendre votre profil publicitaire.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">5. Fournisseurs et sous-traitants</h2>
            <p>NexusVault peut utiliser des fournisseurs pour l’hébergement, l’envoi de courriels, le DNS/CDN, l’authentification OAuth et l’infrastructure technique. Par exemple: un hébergeur VPS, un service courriel transactionnel, et les fournisseurs OAuth que vous choisissez.</p>
            <p>Ces fournisseurs ne reçoivent pas votre mot de passe de coffre en clair de notre part. Les fournisseurs OAuth peuvent appliquer leurs propres politiques de confidentialité lorsque vous utilisez leur bouton de connexion.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">6. Conservation, suppression et récupération</h2>
            <p>Nous conservons les données de compte et de coffre tant que votre compte existe ou tant que cela est nécessaire pour fournir le service, sécuriser la plateforme ou respecter la loi.</p>
            <p>Lorsque vous supprimez votre compte, NexusVault supprime les données associées dans la mesure raisonnablement possible. Certains journaux techniques peuvent être conservés temporairement pour la sécurité, les sauvegardes ou les obligations légales.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">7. Vos choix</h2>
            <p>Vous pouvez modifier votre profil, changer la langue, gérer vos sessions, supprimer des passkeys, supprimer des éléments du coffre, révoquer des partages et supprimer votre compte depuis l’application.</p>
            <p>Vous pouvez aussi demander des informations ou signaler un problème de confidentialité à <a href="mailto:privacy@nexusvault.dev" class="text-emerald-400 hover:text-emerald-300">privacy@nexusvault.dev</a>.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">8. Incidents de sécurité</h2>
            <p>Si un incident de sécurité affecte des renseignements personnels, nous enquêterons, prendrons des mesures de mitigation et communiquerons avec les personnes concernées lorsque la situation l’exige.</p>
        </section>
    @else
        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">1. Plain-language summary</h2>
            <p>NexusVault is designed to minimize what the server can see. Sensitive vault content is encrypted in your browser before it is sent to the server.</p>
            <p>We can see some information needed to operate the service, such as your email address, session metadata, and technical security data. We should not be able to read your passwords, secure notes, or cards in plaintext.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">2. Account data we collect</h2>
            <p>We collect information you provide when creating and using your account: name, email address, language preference, theme, profile picture if you add one, and email verification status.</p>
            <p>If you use OAuth, we receive the necessary information from the provider you choose, such as the OAuth identifier, name, and email address linked to that account. If you use a passkey, we store public verification data, never your biometric data.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">3. Vault data</h2>
            <p>Vault items are stored in encrypted form. This may include encrypted blobs, cryptographic salts, encrypted keys, encrypted share payloads, and metadata needed for synchronization.</p>
            <p>The server may process metadata such as the existence of an item, a share recipient, an acceptance status, or an update time. The zero-knowledge model is designed to prevent the server from reading the secret content of the item.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">4. Technical data and logs</h2>
            <p>To protect the service, diagnose errors, and prevent abuse, we may process IP addresses, browser information, timestamps, requested routes, application errors, and session information.</p>
            <p>This data should be used for security, maintenance, fraud prevention, or legal compliance, not for selling your advertising profile.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">5. Providers and processors</h2>
            <p>NexusVault may use providers for hosting, email delivery, DNS/CDN, OAuth authentication, and technical infrastructure. Examples include a VPS host, a transactional email service, and the OAuth providers you choose.</p>
            <p>These providers do not receive your plaintext vault password from us. OAuth providers may apply their own privacy policies when you use their sign-in button.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">6. Retention, deletion, and recovery</h2>
            <p>We keep account and vault data while your account exists or while it is needed to provide the service, secure the platform, or comply with the law.</p>
            <p>When you delete your account, NexusVault deletes associated data where reasonably possible. Some technical logs may be retained temporarily for security, backups, or legal obligations.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">7. Your choices</h2>
            <p>You can update your profile, change language, manage sessions, delete passkeys, remove vault items, revoke shares, and delete your account from the application.</p>
            <p>You can also ask privacy questions or report an issue at <a href="mailto:privacy@nexusvault.dev" class="text-emerald-400 hover:text-emerald-300">privacy@nexusvault.dev</a>.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">8. Security incidents</h2>
            <p>If a security incident affects personal information, we will investigate, take mitigation steps, and communicate with affected people when the situation requires it.</p>
        </section>
    @endif
</x-layouts.legal>
