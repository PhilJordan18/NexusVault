# NexusVault — Rapport de revue sécurité (10 novembre 2026)

Périmètre : dépôt `main` (commit b6a0591), docs/ADR, workflows GitHub, conversation ChatGPT/Codex partagée (déploiement Hetzner, revue du mentor), `.env` locaux. 71 tests Pest passent. Deux hypothèses critiques ont été confirmées par un test jetable (supprimé ensuite).

## 1. Verdict

L'architecture zéro-connaissance (clé de coffre aléatoire, PBKDF2 600k, AES-GCM, enveloppe de récupération, partage RSA-OAEP) est saine et bien documentée. Le problème principal n'est pas la crypto, c'est la **couche session/authentification autour du coffre** : deux contournements MFA confirmés, et plusieurs actions destructrices sans ré-authentification. Le code legacy côté serveur (clé maître en session) reste vivant et double la surface d'attaque.

## 2. Constats

### Critique (confirmés)

C1. **Contournement MFA par ré-enrôlement.** `routes/web.php:108-109` : `/mfa/setup` et `/mfa/qr-code` ne sont protégés que par `auth`. Une session « mot de passe seulement » (MFA pas encore validée) reçoit 200 sur les deux, et `MfaController::showSetup()` réutilise le `totp_secret` existant. Un attaquant qui a le mot de passe enrôle son propre authentificateur, puis valide `/mfa/verify`. Correctif : `mfa` sur setup/qr-code, et ne jamais ré-afficher un secret déjà confirmé (générer un secret temporaire en session jusqu'à `verifySetup`).

C2. **Drapeaux de session qui survivent à un nouveau login.** `LoginService::performLogin()` (`app/Services/Auth/LoginService.php:90-91`) fait `Auth::login` + `Session::regenerate()`, qui conserve les données. `/login/password` n'a pas de middleware `guest`. Un attaquant valide la MFA sur *son* compte, puis se connecte avec le mot de passe de la victime : `mfa_verified` et `vault_unlocked_at` restent à `true`, `RequireMfa` laisse passer. Correctif : `session()->forget(['mfa_verified','vault_unlocked_at','masterKey'])` avant `Auth::login`, `Session::invalidate()` plutôt que `regenerate()` si un autre utilisateur était connecté, et `guest` sur les routes de login.

### Élevé

E1. **OAuth sans vérification d'état** : `OAuthController.php:19` utilise `stateless()`, ce qui désactive la protection CSRF du flux (login CSRF, fixation de compte). Retirer `stateless()` ; le besoin venait sans doute d'un problème de session/cookie en prod, à corriger à la source.

E2. **Auto-liaison OAuth par email** (`OAuthService`) : un compte Google/GitHub dont l'email correspond est connecté au compte existant sans lien `provider_id` ni vérification que l'email est confirmé chez le fournisseur. Stocker `provider` + `provider_id`, exiger `email_verified` côté fournisseur, et demander le mot de passe local à la première liaison.

E3. **Actions destructrices sans ré-authentification** : suppression de compte (`SettingsController::destroy`), désactivation MFA (`MfaController::disableMfa`), réinitialisation du coffre (`VaultResetController`). Une session volée suffit. Exiger mot de passe ou code TOTP frais (`password.confirm`-style) sur ces trois actions.

E4. **Le serveur fait confiance à `client_unlocked=1`** (`VaultUnlockController`) : le drapeau `vault_unlocked_at` est posé sur simple POST, sans preuve que le client possède la clé. Ce n'est pas une fuite de données (le serveur n'a pas la clé) mais la porte à `/dashboard` et aux ciphertexts s'ouvre sans preuve. Piste : challenge signé avec la clé privée RSA du client, ou simplement accepter que ce drapeau n'est qu'un indicateur UX et le documenter.

E5. **Partage : clé publique du destinataire non vérifiée** (`zero-knowledge-sharing.ts`, `ShareService::getValidPublicKey` ne vérifie qu'une sous-chaîne). Un serveur compromis peut substituer sa propre clé et lire les éléments partagés. Afficher une empreinte (fingerprint) à comparer hors bande, ou au minimum la mémoriser côté client (TOFU).

E6. **Secrets potentiellement non rotés après la compromission du premier VPS.** Le chat montre un mot de passe BD collé en clair et un refus de le changer. Le `.env` local contient des secrets OAuth/Resend réels et, en commentaire, ce qui ressemble à un mot de passe de coffre et une clé de récupération `NV-…`. Tout ce qui a transité par l'ancien serveur ou par le chat doit être considéré compromis : rotation Google/GitHub/Resend/BD/APP_KEY/APP_PEPPER, et retirer ces lignes du `.env`.

### Moyen

M1. Pas de CSP ni HSTS (ni `SESSION_SECURE_COOKIE` forcé) ; Font Awesome chargé depuis cdnjs sans SRI (`app.blade.php:11`). Pour un coffre dont la clé vit en `sessionStorage`, XSS = compromission totale : CSP stricte (nonce) est le premier rempart.
M2. `.env.production` local en désaccord avec les docs : `SESSION_ENCRYPT=false`, `DB_CONNECTION=sqlite`. Soit obsolète, soit la prod tourne ainsi. À réconcilier avant tout redéploiement.
M3. Politique de mot de passe : `Password::defaults()` (AppServiceProvider) n'est référencée nulle part ; `RegisterUserRequest:42` et `ResetPasswordRequest:28` acceptent `min:8`, `UpdatePasswordRequest:27` exige 12. Harmoniser sur `Password::defaults()` + vérification HIBP déjà disponible.
M4. Énumération de comptes : `ResetPasswordRequest:27` (`exists:users,email`), `/register/validate`, et `LoginService::validateCredentials` qui saute `Hash::check` si l'utilisateur est inconnu (temps de réponse différent).
M5. Modèle de permissions du partage : tout destinataire peut écraser le ciphertext du propriétaire (`syncClientEncryptedSharedGroup`) ; la révocation ne fait pas tourner la clé partagée (le révoqué garde une clé valide pour les anciens ciphertexts). Ajouter un rôle lecture/écriture et une rotation de clé à la révocation.
M6. Clé de coffre en `sessionStorage` sans auto-verrouillage d'inactivité ; pas de AAD dans AES-GCM (un ciphertext peut être déplacé d'un champ/élément à un autre sans détection). Ajouter `service_id:field` comme AAD à la prochaine version du format.
M7. Code legacy serveur toujours actif : `CryptoService`, `UserKeyService` (clé maître en session), accesseurs déchiffrants de `Service`, `ShareService::share/accept` legacy, `encrypted_master_key` chiffré avec `APP_KEY`. Tant qu'il existe, un serveur compromis peut lire les comptes legacy. Planifier une migration forcée puis supprimer.
M8. WebAuthn : `config/webauthn.php:53` `user_verification => 'discouraged'` (passkey sans PIN/biométrie acceptée) ; paquet laragear/webauthn abandonné → migrer vers `laravel/passkeys`. TOTP fenêtre 2 (`MfaService.php:88`) = ±60 s, ramener à 1. Pas de codes de secours MFA.

### Faible / hygiène

F1. Limitation de débit uniquement par IP (pas par compte) sur login/MFA.
F2. `lint.yml:18` `contents: write` pour un job de lint ; aucun `composer audit` / `npm audit` / scan de secrets en CI. Audits actuels : symfony/yaml (dev) et 8 vulnérabilités npm (vite, dev-only).
F3. Fichiers `.DS_Store` et `public/scripts/WebAuthn.js(.map)` suivis par git ; `public/hot` traîne localement.
F4. `landing.blade.php` utilise `{!! __() !!}` (acceptable sur des chaînes statiques, à ne pas généraliser).
F5. `$hidden` de `User` n'exclut pas `vault_key_envelope` / `vault_recovery_envelope` / `encrypted_master_key` d'une sérialisation accidentelle.

## 3. Points positifs

Design ZK propre et honnête dans les ADR ; contrôles de propriété présents sur items, partages, passkeys et sessions ; throttling sur toutes les routes sensibles ; HIBP en k-anonymat ; `DB::prohibitDestructiveCommands` ; mot de passe oublié non énumérant ; aucun secret dans l'historique git ; 71 tests verts avec un CI multi-PHP.

## 4. Avant de supprimer le serveur Hetzner

1. Exporter un dump MariaDB (`mysqldump --single-transaction`) + le `.env` de prod réel, chiffrés (age ou gpg), stockés hors du dépôt. Tester une restauration en local.
2. Noter les versions exactes (PHP, MariaDB, Nginx, Certbot) et la config Nginx/PHP-FPM/UFW/Fail2Ban dans `docs/` ou un dépôt infra privé.
3. Roter les secrets (E6) **avant** ou **au moment** de la suppression ; révoquer les clés OAuth et Resend liées au serveur.
4. Mettre les DNS Cloudflare en pause/page « bientôt de retour » pour éviter un NXDOMAIN qui tue les sessions OAuth des utilisateurs.
5. Supprimer les snapshots Hetzner ou vérifier qu'ils sont chiffrés ; ils contiennent le `.env`.

## 5. Plan proposé

**Sprint 0 (quick wins, 1 à 2 jours)** : C1, C2, E1, E3, M3, M4, M8-fenêtre TOTP, `guest` sur login, tests Pest de non-régression pour chaque contournement.
**Sprint 1 (durcissement web)** : M1 (CSP nonce + HSTS + secure cookies + SRI), M2, F2 (audit CI, secret scanning, permissions minimales), F3.
**Sprint 2 (identité)** : E2, M8 (laravel/passkeys, user_verification required, codes de secours MFA), E4.
**Sprint 3 (crypto v2)** : M6 (AAD, auto-lock), M5 (rôles de partage, rotation à la révocation), E5 (empreintes), puis M7 (retrait du legacy).
**Transversal** : threat model écrit, checklist de revue pour les agents, pipeline CI/CD avec gates sécurité, procédure de déploiement reproductible (Ansible ou Docker Compose, cette fois pour de vrai).
