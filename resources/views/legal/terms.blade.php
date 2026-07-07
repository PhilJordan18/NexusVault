@php
    $isFrench = app()->getLocale() === 'fr';
    $title = __('Terms of Use');
    $description = $isFrench
        ? 'Les règles d’utilisation de NexusVault et les responsabilités liées à un coffre chiffré zéro connaissance.'
        : 'The rules for using NexusVault and the responsibilities that come with a zero-knowledge encrypted vault.';
@endphp

<x-layouts.legal :title="$title" :description="$description">
    @if($isFrench)
        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">1. Acceptation des conditions</h2>
            <p>En créant un compte ou en utilisant NexusVault, vous acceptez ces conditions d’utilisation. Si vous n’êtes pas d’accord avec ces conditions, vous ne devez pas utiliser le service.</p>
            <p>NexusVault est un gestionnaire de mots de passe conçu autour du chiffrement côté navigateur et d’un modèle zéro connaissance. Ces conditions expliquent aussi ce que cela implique concrètement pour vous.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">2. Votre compte</h2>
            <p>Vous êtes responsable de l’exactitude des informations de compte que vous fournissez, de la sécurité de votre méthode de connexion et de toute activité effectuée depuis votre compte.</p>
            <p>Vous devez utiliser NexusVault uniquement à des fins légales et ne pas tenter d’accéder au compte, au coffre ou aux données d’une autre personne sans autorisation.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">3. Mot de passe du coffre et clé de récupération</h2>
            <p>Votre mot de passe de coffre sert à dériver les clés qui déchiffrent vos données localement dans votre navigateur. NexusVault ne reçoit pas ce mot de passe en clair et ne peut pas l’utiliser pour lire vos éléments.</p>
            <p>Vous êtes responsable de conserver votre mot de passe de coffre et votre clé de récupération. Si vous perdez les deux, NexusVault ne peut pas récupérer vos éléments chiffrés. Le seul recours prévu est une réinitialisation destructive qui supprime les données du coffre et crée un nouveau coffre vide.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">4. Données du coffre et partage</h2>
            <p>Les éléments que vous ajoutez à votre coffre, comme les identifiants, cartes et notes, doivent vous appartenir ou vous être confiés avec autorisation.</p>
            <p>Lorsque vous partagez un élément, vous choisissez le destinataire et acceptez que celui-ci puisse accéder au contenu déchiffré sur son propre appareil après acceptation. Vous restez responsable de révoquer les accès qui ne devraient plus exister.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">5. Disponibilité et sécurité</h2>
            <p>Nous faisons des efforts raisonnables pour garder NexusVault disponible, sécurisé et à jour. Aucun service en ligne ne peut toutefois garantir une disponibilité parfaite ou une absence totale de vulnérabilités.</p>
            <p>Vous acceptez d’utiliser des mots de passe robustes, d’activer les protections disponibles lorsque possible et de nous signaler tout comportement suspect ou problème de sécurité.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">6. Suppression et résiliation</h2>
            <p>Vous pouvez supprimer votre compte depuis les paramètres. La suppression retire les données associées à votre compte selon les contraintes techniques et légales applicables.</p>
            <p>Nous pouvons suspendre ou limiter l’accès à NexusVault en cas d’abus, de tentative d’intrusion, d’utilisation illégale ou de risque sérieux pour le service ou d’autres utilisateurs.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">7. Limitation de responsabilité</h2>
            <p>NexusVault est fourni “tel quel”. Dans la mesure permise par la loi, nous ne sommes pas responsables des pertes indirectes, interruptions, pertes de données causées par la perte de vos secrets de récupération, ou dommages résultant d’une mauvaise utilisation du service.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">8. Changements et contact</h2>
            <p>Ces conditions peuvent évoluer avec le produit. Les changements importants seront communiqués de manière raisonnable. Pour toute question, écrivez à <a href="mailto:privacy@nexusvault.dev" class="text-emerald-400 hover:text-emerald-300">privacy@nexusvault.dev</a>.</p>
        </section>
    @else
        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">1. Accepting these terms</h2>
            <p>By creating an account or using NexusVault, you agree to these Terms of Use. If you do not agree with them, you should not use the service.</p>
            <p>NexusVault is a password manager built around browser-side encryption and a zero-knowledge model. These terms also explain what that means for your responsibilities.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">2. Your account</h2>
            <p>You are responsible for the accuracy of the account information you provide, the security of your sign-in method, and all activity that occurs through your account.</p>
            <p>You may only use NexusVault for lawful purposes. You must not attempt to access another person’s account, vault, or data without authorization.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">3. Vault password and recovery key</h2>
            <p>Your vault password is used to derive the keys that decrypt your data locally in your browser. NexusVault does not receive that password in plaintext and cannot use it to read your vault items.</p>
            <p>You are responsible for keeping your vault password and recovery key safe. If you lose both, NexusVault cannot recover your encrypted items. The supported fallback is a destructive reset that deletes the vault data and creates a new empty vault.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">4. Vault data and sharing</h2>
            <p>Items you add to your vault, including logins, cards, and notes, must belong to you or be entrusted to you with permission.</p>
            <p>When you share an item, you choose the recipient and agree that the recipient may access the decrypted content on their own device after accepting the share. You remain responsible for revoking access that should no longer exist.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">5. Availability and security</h2>
            <p>We make reasonable efforts to keep NexusVault available, secure, and up to date. No online service can guarantee perfect availability or complete freedom from vulnerabilities.</p>
            <p>You agree to use strong passwords, enable available protections when possible, and report suspicious behavior or security issues.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">6. Deletion and termination</h2>
            <p>You may delete your account from settings. Deletion removes account data subject to applicable technical and legal limits.</p>
            <p>We may suspend or limit access to NexusVault in cases of abuse, attempted intrusion, unlawful use, or serious risk to the service or other users.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">7. Limitation of liability</h2>
            <p>NexusVault is provided “as is.” To the extent permitted by law, we are not responsible for indirect losses, interruptions, data loss caused by losing your recovery secrets, or damages caused by misuse of the service.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">8. Changes and contact</h2>
            <p>These terms may evolve with the product. Material changes will be communicated in a reasonable way. Questions can be sent to <a href="mailto:privacy@nexusvault.dev" class="text-emerald-400 hover:text-emerald-300">privacy@nexusvault.dev</a>.</p>
        </section>
    @endif
</x-layouts.legal>
