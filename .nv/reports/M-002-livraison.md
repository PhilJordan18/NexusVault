---
mandate: M-002
author: dev
branch: claude/autocompact-300k-bu83lf
head: cd4ff2b
created: 2026-11-11
review: changes_requested
---

# Livraison M-002

## Ce qui a ete fait

Branche cloud attribuee par la plateforme, repartie de `origin/dev`. Un
commit `security(...)` par constat, chaque fois precede d'un test Pest vu
rouge avant le correctif.

- **C1, re-enrolement MFA** (`f4f0447`). Les routes `mfa.setup`,
  `mfa.qr-code` et `mfa.setup.verify` passent sous `['auth', 'mfa']`.
  `MfaController::showSetup()` redirige vers les reglages si la MFA est deja
  active, sinon genere un secret temporaire conserve en session
  (`mfa_pending_secret`) jusqu'a `verifySetup`. Le secret confirme n'est
  plus jamais reaffiche ni reutilise. `MfaService::enableMfa()` ne persiste
  `totp_secret` qu'apres verification du code. Test :
  `tests/Feature/MfaEnrollmentBypassTest.php`.
- **C2, drapeaux de session survivants** (`4d87cde`). Nouveau service
  `App\Services\Auth\AuthenticatedSessionService` : `prepareForLogin()`
  purge `mfa_verified`, `vault_unlocked_at`, `masterKey` et
  `mfa_pending_secret`, et fait `Auth::logout()` + `Session::invalidate()`
  + `regenerateToken()` si un autre utilisateur etait connecte. Il est
  appele par `LoginService`, `OAuthService`, `RegisterController` et
  `WebAuthnLoginController`. Middleware `guest` sur register, login (les
  deux etapes), OAuth et les routes de login WebAuthn. Redirection des
  utilisateurs deja connectes via `Middleware::redirectUsersTo` vers la
  verification MFA ou le coffre. Test :
  `tests/Feature/LoginSessionIsolationTest.php`.
- **E1, OAuth sans verification d'etat** (`5a644de`). `stateless()` retire
  de `OAuthController`. `InvalidStateException` est attrapee et renvoie sur
  `login` avec un message generique. Test :
  `tests/Feature/OAuthStateTest.php`.
- **E3, actions destructrices sans re-authentification** (`2974357`).
  Nouveau service `App\Services\Auth\ReauthenticationService` et trait
  `App\Http\Requests\Concerns\ConfirmsFreshAuthentication` : la requete
  doit porter `current_password` (compte local) ou `totp_code` (MFA
  active). Applique a `DeleteAccountRequest` (nouveau),
  `DisableMfaRequest` (nouveau) et `VaultResetRequest`. Partial Blade
  `partials/fresh-authentication.blade.php` inclus dans les trois
  formulaires. Test : `tests/Feature/ReauthenticationTest.php`.
- **M3, politique de mot de passe unique** (`aea098d`).
  `Password::defaults()` dans `AppServiceProvider` : 12 caracteres,
  casse mixte, lettres, chiffres, symboles, `uncompromised()` en
  production seulement. `RegisterUserRequest`, `ResetPasswordRequest` et
  `UpdatePasswordRequest` l'utilisent. Les vues register et reset-password
  passent a `minlength="12"`. Test : `tests/Feature/PasswordPolicyTest.php`.
- **M4, enumeration de comptes** (`a8835f4`). `ResetPasswordRequest` ne
  porte plus `exists:users,email` ; `ResetPasswordController` renvoie le
  meme message `passwords.token` pour tout echec. `LoginService::
  validateCredentials()` fait toujours un `Hash::check` contre un hash
  factice mis en cache quand le compte est inconnu, puis un seul message
  `Invalid credentials`. Test : `tests/Feature/AccountEnumerationTest.php`.
- **M8, fenetre TOTP** (`60256ad`). `MfaService::TOTP_WINDOW = 1`, une
  seule periode de tolerance au lieu de deux. Test :
  `tests/Feature/MfaTotpWindowTest.php`.
- **Docs** (`cd4ff2b`). `docs/03-authentification-sessions-et-acces.md`
  decrit le service de session, les routes `guest`, la politique de mot de
  passe, l'etat OAuth, le flux d'enrolement MFA, la re-authentification,
  les cles de session, la matrice d'acces et les messages uniformes.

Aucune route existante n'a ete supprimee. Seuls les middlewares de
`mfa.setup`, `mfa.qr-code`, `mfa.setup.verify` et des routes invite ont
change.

## Decisions prises en cours de route

- **Rebase sur `dev` apres M-001 et M-003.** `origin/dev` a recu les
  livraisons M-001 (CI, prettier et pint sur l'existant) et M-003 (pages
  legales Inertia) pendant ce mandat. `nv-deliver` a rebase la branche :
  conflits resolus dans `lang/en.json`, `lang/fr.json` (cles ajoutees des
  deux cotes, les deux conservees), `OAuthController` et deux FormRequests
  (dev n'y avait fait que du reformatage, version M-002 conservee). Mes
  deux commits de reformatage preliminaires sont devenus vides apres le
  rebase et ont ete abandonnes.
- **`/register/validate` laisse hors de M4.** Cette route sert a la
  validation en direct du formulaire et renvoie par construction si l'email
  est pris. La rendre uniforme impose un flux d'inscription par email
  d'abord (lien de verification avant creation), ce qui depasse un quick
  win. Note dans docs/03 comme limite connue, a traiter avec E2 au
  sprint 2.
- **Comptes OAuth sans MFA et E3.** Un compte OAuth n'a pas de mot de passe
  local. S'il n'a pas active la MFA, il ne peut pas confirmer une action
  destructrice : le formulaire lui demande d'activer la MFA d'abord. C'est
  volontaire, une session volee ne doit pas suffire.
- **Ordre logout / suppression de compte.** `SettingsController::destroy`
  deconnecte avant de supprimer : `Auth::logout()` apres `delete()`
  recyclait le remember token et reinserait la ligne. Bug preexistant
  decouvert par le test de suppression.
- **Formulaire de logout sur la page de verification MFA.** Le lien GET
  `/logout` renvoyait 405 ; remplace par un formulaire POST.
- **Test `MfaSetupTest` adapte, non supprime** : il suit le nouveau flux
  (secret temporaire en session, signature `getQrCodeImage($user,
  $secret)`). `VaultUnlockTest` adapte pour la politique M3 et le champ
  `current_password` du reset.
- **Hash factice M4** stocke dans le cache applicatif
  (`auth.unknown_account_hash`), pas en session, conformement a la
  contrainte du mandat.

## Tests

Environnement : conteneur cloud, PHP 8.3.6, `APP_KEY` jetable exportee
dans l'environnement (aucun `.env`, d'ou un avertissement Pest par test,
sans effet sur les resultats).

```
composer run ci:check                       -> vert (105 tests, 591 assertions)
npm run build                               -> vert
php artisan test --compact --filter=Mfa     -> 15 tests, 105 assertions, vert
php artisan test --compact --filter=Login   -> 14 tests, 104 assertions, vert
python3 -m unittest discover -s .claude/hooks/tests -> vert
```

Nouveaux fichiers de test : `MfaEnrollmentBypassTest`,
`LoginSessionIsolationTest`, `OAuthStateTest`, `ReauthenticationTest`,
`PasswordPolicyTest`, `AccountEnumerationTest`, `MfaTotpWindowTest`.
La preuve machine est dans `.nv/qa/M-002.json`.

## Points d'attention pour la revue securite

1. `AuthenticatedSessionService::prepareForLogin()` : la liste
   `PER_USER_FLAGS` est la source de verite des drapeaux a purger. Toute
   nouvelle cle de session par utilisateur doit y etre ajoutee.
2. `ReauthenticationService::confirm()` : TOTP accepte sans protection
   contre le rejeu dans la fenetre d'une periode. Recommandation :
   `verifyKeyNewer` avec une colonne de dernier timestamp (sprint 2, avec
   les codes de secours).
3. `redirectUsersTo` dans `bootstrap/app.php` envoie un utilisateur
   connecte mais non verifie MFA vers `mfa.verify.login` : verifier que
   cela ne cree pas de boucle avec `RequireMfa`.
4. `OAuthController` sans `stateless()` depend de `same_site=lax` et d'un
   cookie de session fonctionnel en production (voir E1 dans la revue :
   la cause initiale etait probablement un probleme de cookie en prod).
5. `uncompromised()` seulement en production : les tests et le dev
   n'appellent pas HIBP. A confirmer que c'est le compromis voulu.
6. Le hash factice de M4 est genere une fois par cache ; si le cache est
   vide a chaque requete (driver `array`), le cout reste celui d'un
   `Hash::make` + `Hash::check`, toujours uniforme.

## Avis Securite

`review: changes_requested` (secops, 2026-11-11, plage `94631ac..0ff5744`,
branche de revue `nv/secops/M-002-revue-securite`). Revue manuelle selon
`nv-security-review` plus une passe nv-pentester (29 tests jetables, 94
assertions, rien de commite) : les protections C1, C2, E1, E3 et M4 ci-dessous
sont prouvees par sonde, pas seulement par lecture.

### Points verifies

- **C1, enrolement MFA.** `mfa.setup`, `mfa.qr-code` et `mfa.setup.verify`
  portent `auth` et `mfa`. Le secret en attente n'est jamais persiste avant
  `enableMfa()`, `qr-code` repond 404 des que `mfa_enabled` est vrai ou
  qu'aucun secret n'est en attente, un secret confirme n'est jamais
  reaffiche. `MfaEnrollmentBypassTest` couvre les quatre scenarios.
- **C2, isolation de session.** `AuthenticatedSessionService::establish()`
  purge `PER_USER_FLAGS` (ou invalide toute la session si un autre compte
  etait connecte) puis regenere l'identifiant. `guest` couvre register,
  login, OAuth et WebAuthn. `redirectUsersTo` envoie vers `mfa.verify.login`
  qui n'est pas derriere `mfa` : pas de boucle avec `RequireMfa` (point 3 du
  rapport clos). `LoginSessionIsolationTest` rejoue la chaine attaquant puis
  victime.
- **E1, etat OAuth.** `stateless()` retire, `InvalidStateException` renvoie
  sur `login` avec un message generique. Cookie de session `secure`,
  `http_only`, `same_site=lax`, chiffre, driver `database` : le retour OAuth
  cross-site en GET top-level porte le cookie, la cause probable d'E1 en prod
  etait un cookie non `secure` ou un domaine mal configure (point 4).
- **E3, re-authentification.** `ConfirmsFreshAuthentication` branche
  `ReauthenticationService` sur suppression de compte, desactivation MFA et
  reset du coffre. Le mot de passe de substitution OAuth est refuse, le
  message d'erreur est unique quel que soit le facteur rate. Un compte OAuth
  sans MFA garde une sortie : `mfa.setup` n'exige pas `vault.unlocked`, donc
  activer la MFA puis confirmer par TOTP reste possible meme coffre bloque.
- **M3, politique de mot de passe.** `Password::defaults()` partout (12
  caracteres, casse, chiffres, symboles). `uncompromised()` seulement en
  production : compromis accepte, aucun appel reseau en test et en dev.
- **M4, enumeration.** Hash factice en cache applicatif (driver `database`,
  genere une fois), `Hash::check` execute meme sans compte, reset repond
  toujours `passwords.token`, `exists:users` retire. `/register/validate`
  reste enumerable, limite documentee dans docs/03, acceptee pour ce sprint.
- **M8, fenetre TOTP.** `TOTP_WINDOW = 1`, `MfaTotpWindowTest` verifie les
  bornes -2 et +2.
- **Contrainte "pas de nouveau stockage de secret en session".**
  `mfa_pending_secret` est bien un secret en session. Accepte avec reserve :
  le secret n'est pas encore actif, la session est chiffree et stockee en
  base, la cle est purgee par `PER_USER_FLAGS` et apres verification. La
  session Architecture doit acter cette derogation dans sa decision.
- **Zero-knowledge.** Aucun nouveau champ chiffre, aucun secret dans le DOM
  (le partiel `fresh-authentication` ne rend aucune valeur), le reset du
  coffre ne recoit que des enveloppes.
- **Hygiene du diff.** Pas de `.env`, de `.DS_Store`, de build ni de nouvelle
  dependance. `.nv/qa/M-002.json` porte `6699715`, le commit precedant le
  commit `nv-deliver`, conforme au fonctionnement du script. docs/03 mis a
  jour.
- **Rejeu TOTP** (point 2 du rapport) : accepte en report au sprint 2 avec les
  codes de secours, a condition que le correctif ci-dessous limite le nombre
  d'essais.

### Changements demandes

1. **Brute force du code TOTP sur les actions destructrices.**
   `routes/web.php:128` (`mfa.disable`) et `routes/web.php:168`
   (`settings.account.destroy`) acceptent un `totp_code` sans aucun
   `throttle`, alors que `mfa.verify` est a `throttle:6,1` et
   `vault.reset.store` a `throttle:3,1`.
   Scenario d'abus : un cookie de session vole, deja verifie MFA et coffre
   deverrouille, suffit pour enumerer les codes a six chiffres. Avec la
   fenetre a une periode, trois codes sont valides a chaque instant, soit un
   succes attendu apres environ 330 000 essais, quelques minutes sans limite.
   Sonde Pest jetable executee sur `0ff5744` : 60 codes faux consecutifs sur
   chacune des deux routes donnent 60 reponses 302, jamais 429, tandis que
   `mfa.verify` repond 429 au septieme essai. Resultat : la MFA est
   desactivee puis re-enrolee avec le secret de l'attaquant (persistance
   sans l'appareil de la victime), ou le compte et son coffre sont supprimes.
   Correctif attendu : `throttle:6,1` sur `mfa.disable` et
   `settings.account.destroy`, de preference un limiteur nomme par
   utilisateur (`RateLimiter::for` sur `$request->user()->id`, pas sur l'IP),
   et un test Pest ecrit rouge d'abord qui attend 429 au septieme essai sur
   chaque route. `settings.password.update` (`current_password` sans
   throttle) est preexistant et hors M-002 : a inscrire au sprint 1.

2. **Oracle d'enumeration par le throttle du broker sur `/forgot-password`.**
   `app/Http/Controllers/ForgotPasswordController.php:21-23` : seuls
   `RESET_LINK_SENT` et `INVALID_USER` recoivent le message generique ; tout
   autre statut, dont `RESET_THROTTLED`, remonte en erreur de validation.
   Scenario d'abus : un anonyme soumet deux fois le meme email en moins de
   60 secondes. Compte existant : "Please wait before retrying." Compte
   inconnu : message generique. Sonde nv-pentester sur `0ff5744` : les deux
   reponses different de facon deterministe, soit trois emails testes par
   minute sous `throttle:6,1`. Le fichier n'est pas dans le diff mais le
   constat est dans le perimetre M4 ("reponses uniformes sur reset").
   Correctif attendu : traiter `RESET_THROTTLED` comme `INVALID_USER` (meme
   status generique, le throttle reste silencieux), avec un test Pest ecrit
   rouge d'abord qui compare les deux secondes reponses.

### Constats du nv-pentester acceptes en dette tracee

A inscrire par Architecture dans les mandats du sprint 1 ou 2 ; aucun ne
bloque M-002 une fois les deux changements ci-dessus livres.

- **Rejeu TOTP** (`ReauthenticationService.php:26-27`, `MfaService.php:103-106`,
  `verifyKey` sans `verifyKeyNewer`) : un seul code phishe sert au login,
  puis est rejoue sur `vault.reset` et `mfa.disable` dans la meme fenetre,
  prouve par sonde. Piste : `verifyKeyNewer` + colonne `totp_last_used_at`,
  au login et en re-authentification. Sprint 2 avec les codes de secours.
- **Verification d'email decorative** : `RegisterController.php:36` ouvre
  une session complete, aucune route ne porte `verified`, un compte non
  verifie utilise tout le coffre et peut squatter l'email d'un tiers.
  Preexistant, aggrave par E2 ; merite un mandat propre.
- **Enrolement MFA sans re-authentification** (`MfaController.php:63-80`) :
  une session volee d'un compte sans MFA enrole le second facteur de
  l'attaquant et verrouille la victime. Piste : `ConfirmsFreshAuthentication`
  sur `verifySetup`.
- **`mfa_pending_secret` vit toute la session** (`MfaController.php:34-39`)
  et est reaffiche tel quel, meme apres `vault.lock`. Piste : regenerer a
  chaque `GET /mfa/setup` ou TTL court, `forget` dans `lock()` et `logout()`.
- **Mot de passe seul suffit a desactiver la MFA** d'un compte local avec
  MFA (`ReauthenticationService.php:22-24`), par design "mot de passe OU
  TOTP". Piste : si `mfa_enabled`, exiger le TOTP pour `mfa.disable` et
  `settings.account.destroy`.
- **`users.totp_secret` est stocke en clair** (preexistant, hors M-002) :
  le secret en session n'est donc pas une regression, le vrai manque est un
  cast `encrypted` sur la colonne. Mandat sprint 1.
- **Notifications synchrones** : `ResetPassword` n'est pas `ShouldQueue`, en
  production l'appel Resend ne se fait que sur le chemin "compte connu",
  canal temporel possible (non mesurable en local). Piste : file d'attente.

### Hors M-002, pour Architecture et PM

- **La CI de `dev` est rouge** a `0ff5744` (run 42946331380) et l'etait deja
  a `94631ac` (M-003, run 42944838872). Deux causes, aucune imputable au code
  applicatif de M-002 :
  - `npm run types:check` : M-003 importe `@/routes/...`, genere par
    Wayfinder dans `resources/js/routes`, dossier ignore par git ; la CI ne
    lance jamais `php artisan wayfinder:generate`. Correctif secops dans
    `ci.yml` (et dans le script composer `ci:check` pour que local et CI
    soient identiques). Demande de mandat.
  - gitleaks : 7 alertes `generic-api-key`, toutes sur le secret TOTP de
    fixture des tests (`MfaTotpWindowTest:7`, `MfaSetupTest:25,50`,
    `MfaEnrollmentBypassTest:10,83`, `ReauthenticationTest:7`,
    `LoginSessionIsolationTest:21`). Faux positif, valeur de test deja
    presente dans `MfaSetupTest` avant M-002. Correctif secops : un
    `.gitleaks.toml` avec une allowlist ciblee sur cette valeur sous
    `tests/`. Rien a changer cote dev. Demande de mandat.
- **Chemin de livraison d'un avis secops.** `nv-deliver` exige un mandat
  `in_progress` et `dev` n'accepte que ses pousses. Cet avis est donc commite
  sur `nv/secops/M-002-revue-securite` et pousse sur cette branche, pas sur
  `dev`. Le socle doit prevoir comment un avis atteint `dev`.

## Decision Architecture

A remplir par la session architect. `accepted | rejected` et motif.
