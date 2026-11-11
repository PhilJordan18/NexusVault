---
mandate: M-002
author: dev
branch: claude/autocompact-300k-bu83lf
head: fc6cf9b
created: 2026-11-11
review: pending
---

# Livraison M-002

## Ce qui a ete fait

Branche cloud attribuee par la plateforme, repartie de `origin/dev`. Un
commit `security(...)` par constat, chaque fois precede d'un test Pest vu
rouge avant le correctif.

- **C1, re-enrolement MFA** (`05f0a09`). Les routes `mfa.setup`,
  `mfa.qr-code` et `mfa.setup.verify` passent sous `['auth', 'mfa']`.
  `MfaController::showSetup()` redirige vers les reglages si la MFA est deja
  active, sinon genere un secret temporaire conserve en session
  (`mfa_pending_secret`) jusqu'a `verifySetup`. Le secret confirme n'est
  plus jamais reaffiche ni reutilise. `MfaService::enableMfa()` ne persiste
  `totp_secret` qu'apres verification du code. Test :
  `tests/Feature/MfaEnrollmentBypassTest.php`.
- **C2, drapeaux de session survivants** (`3426545`). Nouveau service
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
- **E1, OAuth sans verification d'etat** (`cb67f1d`). `stateless()` retire
  de `OAuthController`. `InvalidStateException` est attrapee et renvoie sur
  `login` avec un message generique. Test :
  `tests/Feature/OAuthStateTest.php`.
- **E3, actions destructrices sans re-authentification** (`912446e`).
  Nouveau service `App\Services\Auth\ReauthenticationService` et trait
  `App\Http\Requests\Concerns\ConfirmsFreshAuthentication` : la requete
  doit porter `current_password` (compte local) ou `totp_code` (MFA
  active). Applique a `DeleteAccountRequest` (nouveau),
  `DisableMfaRequest` (nouveau) et `VaultResetRequest`. Partial Blade
  `partials/fresh-authentication.blade.php` inclus dans les trois
  formulaires. Test : `tests/Feature/ReauthenticationTest.php`.
- **M3, politique de mot de passe unique** (`9e88038`).
  `Password::defaults()` dans `AppServiceProvider` : 12 caracteres,
  casse mixte, lettres, chiffres, symboles, `uncompromised()` en
  production seulement. `RegisterUserRequest`, `ResetPasswordRequest` et
  `UpdatePasswordRequest` l'utilisent. Les vues register et reset-password
  passent a `minlength="12"`. Test : `tests/Feature/PasswordPolicyTest.php`.
- **M4, enumeration de comptes** (`f637fdb`). `ResetPasswordRequest` ne
  porte plus `exists:users,email` ; `ResetPasswordController` renvoie le
  meme message `passwords.token` pour tout echec. `LoginService::
  validateCredentials()` fait toujours un `Hash::check` contre un hash
  factice mis en cache quand le compte est inconnu, puis un seul message
  `Invalid credentials`. Test : `tests/Feature/AccountEnumerationTest.php`.
- **M8, fenetre TOTP** (`fa8296a`). `MfaService::TOTP_WINDOW = 1`, une
  seule periode de tolerance au lieu de deux. Test :
  `tests/Feature/MfaTotpWindowTest.php`.
- **Docs** (`9c46eaa`). `docs/03-authentification-sessions-et-acces.md`
  decrit le service de session, les routes `guest`, la politique de mot de
  passe, l'etat OAuth, le flux d'enrolement MFA, la re-authentification,
  les cles de session, la matrice d'acces et les messages uniformes.

Aucune route existante n'a ete supprimee. Seuls les middlewares de
`mfa.setup`, `mfa.qr-code`, `mfa.setup.verify` et des routes invite ont
change.

## Decisions prises en cours de route

- **Deux commits de style hors perimetre** (`d9d50d9`, `fc6cf9b`).
  `composer run ci:check`, donc `nv-deliver`, exigeait `prettier --check`
  et `pint --test` verts. Quinze fichiers front et quinze fichiers PHP
  anterieurs au mandat ne respectaient pas la configuration du depot.
  Reformatage pur, aucun changement fonctionnel, isole dans ses propres
  commits pour que la revue puisse les ignorer. A verifier sur `dev` que
  la CI GitHub partage cette exigence.
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

A remplir par la session secops. `review: approved | changes_requested`.

## Decision Architecture

A remplir par la session architect. `accepted | rejected` et motif.
