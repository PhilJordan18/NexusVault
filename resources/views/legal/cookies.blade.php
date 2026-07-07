@php
    $isFrench = app()->getLocale() === 'fr';
    $title = __('Cookie Policy');
    $description = $isFrench
        ? 'Comment NexusVault utilise les cookies essentiels et le stockage local du navigateur.'
        : 'How NexusVault uses essential cookies and browser-side storage.';
@endphp

<x-layouts.legal :title="$title" :description="$description">
    @if($isFrench)
        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">1. Notre position actuelle</h2>
            <p>NexusVault n’utilise pas de cookies publicitaires ou de suivi marketing. Le service utilise uniquement des cookies et du stockage navigateur nécessaires au fonctionnement, à la sécurité et aux préférences de base.</p>
            <p>Pour cette raison, nous n’affichons pas de bannière “Accepter / Refuser” pour des cookies optionnels. Si des analytics ou outils non essentiels sont ajoutés plus tard, ils devront être bloqués jusqu’à votre consentement.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">2. Cookies essentiels</h2>
            <p>Les cookies essentiels peuvent inclure le cookie de session Laravel, le jeton CSRF de sécurité, les informations nécessaires à la connexion et les préférences comme la langue ou le thème.</p>
            <p>Sans ces cookies, NexusVault ne peut pas maintenir une session sécurisée, protéger les formulaires contre certaines attaques ou se souvenir de vos préférences immédiates.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">3. Stockage côté navigateur</h2>
            <p>NexusVault peut utiliser le stockage de session du navigateur pour garder temporairement l’état de déverrouillage du coffre pendant votre session. Ce stockage est local à votre navigateur.</p>
            <p>Les clés sensibles ne doivent pas être stockées de manière permanente dans un cookie. Le verrouillage du coffre et la déconnexion retirent l’état temporaire prévu par l’application.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">4. Contrôle par l’utilisateur</h2>
            <p>Vous pouvez supprimer les cookies et données de site depuis les paramètres de votre navigateur. Cela peut vous déconnecter, réinitialiser certaines préférences ou exiger un nouveau déverrouillage du coffre.</p>
            <p>Questions: <a href="mailto:privacy@nexusvault.dev" class="text-emerald-400 hover:text-emerald-300">privacy@nexusvault.dev</a>.</p>
        </section>
    @else
        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">1. Our current position</h2>
            <p>NexusVault does not use advertising cookies or marketing trackers. The service uses only cookies and browser storage needed for functionality, security, and basic preferences.</p>
            <p>For that reason, we do not show an “Accept / Reject” banner for optional cookies. If analytics or other non-essential tools are added later, they should be blocked until you consent.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">2. Essential cookies</h2>
            <p>Essential cookies may include the Laravel session cookie, CSRF security token, information required for sign-in, and preferences such as language or theme.</p>
            <p>Without these cookies, NexusVault cannot maintain a secure session, protect forms against certain attacks, or remember immediate preferences.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">3. Browser-side storage</h2>
            <p>NexusVault may use browser session storage to temporarily keep the vault unlock state during your session. This storage is local to your browser.</p>
            <p>Sensitive keys should not be stored permanently in a cookie. Locking the vault and signing out remove the temporary state managed by the application.</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-2xl font-semibold text-[var(--text-primary)]">4. User control</h2>
            <p>You can delete cookies and site data from your browser settings. This may sign you out, reset preferences, or require you to unlock the vault again.</p>
            <p>Questions: <a href="mailto:privacy@nexusvault.dev" class="text-emerald-400 hover:text-emerald-300">privacy@nexusvault.dev</a>.</p>
        </section>
    @endif
</x-layouts.legal>
