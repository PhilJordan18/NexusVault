# 08 - Tests et strategie qualite

## Objectif

La securite qui n'est pas testee finit par regresser. NexusVault a deja une base
de tests utile, mais un password manager demande une strategie plus large:

- tests feature Laravel;
- tests unitaires de services;
- tests TypeScript/WebCrypto;
- tests E2E navigateur;
- tests de deploiement;
- tests de restauration backup.

## Commandes actuelles

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run lint:check
npm run format:check
npm run types:check
npm run build
composer run ci:check
```

Pendant le developpement:

```bash
composer run dev
```

Cette commande lance:

- serveur Laravel;
- queue listener;
- pail logs;
- Vite dev server.

## Tests existants

```text
tests/Feature/LegalPagesTest.php
tests/Feature/LocalePreferenceTest.php
tests/Feature/MfaSetupTest.php
tests/Feature/PasskeyManagementTest.php
tests/Feature/ServiceViewRedirectTest.php
tests/Feature/SharedServiceSyncTest.php
tests/Feature/VaultItemTypesTest.php
tests/Feature/VaultUnlockTest.php
tests/Feature/ZeroKnowledgeSharingTest.php
tests/Unit/FaviconServiceTest.php
```

## Ce que couvrent les tests importants

### `VaultUnlockTest`

Verifie:

- redirection vers unlock avant dashboard;
- unlock par vault password;
- refus password invalide;
- login password ne deverrouille pas un coffre separe;
- lock nettoie l'etat serveur;
- users client-side peuvent acceder avec `vault_unlocked_at`;
- creation d'items modernes comme ciphertext opaque;
- refus d'items clairs pour coffre moderne;
- OAuth sans coffre redirige vers setup;
- ancienne voie OAuth legacy non exposee.

### `ZeroKnowledgeSharingTest`

Verifie:

- partage client encrypted;
- acceptation/refus;
- recipient zero-knowledge requis;
- acceptation sync avec `shared_key_envelope`;
- propagation d'edits sous ciphertext;
- absence de mots de passe en clair dans les payloads attendus.

### `MfaSetupTest`

Verifie:

- page setup MFA;
- route image `/mfa/qr-code`;
- `Content-Type: image/*`;
- headers anti-cache;
- support de sortie SVG brute du QR service.

### `PasskeyManagementTest`

Verifie:

- gestion/suppression de passkeys;
- interdiction de supprimer une passkey qui n'appartient pas au user.

### `VaultItemTypesTest`

Verifie:

- cartes et notes;
- pas d'analyse password pour les types non-login;
- stockage chiffre.

## Matrice minimale de securite a maintenir

| Cas | Test attendu |
| --- | --- |
| User moderne cree un item clair | 422 ou session errors |
| User moderne update avec `client_encrypted=0` | refuse |
| OAuth sans vault envelope | redirect `/vault/setup` |
| Legacy OAuth unlock | non expose/refuse |
| Recipient sans vault client | partage refuse |
| Recipient accepte sans shared key envelope | refuse |
| Owner delete shared item | copies retirees |
| Recipient delete shared copy | original conserve |
| MFA QR | route image OK |
| Passkey delete autre user | 403 |

## Tests manquants recommandes

### Tests TypeScript/WebCrypto

Objectif:

- verifier que `createRegistrationVaultPackage` produit des enveloppes coherentes;
- verifier unlock avec vault password;
- verifier unlock avec recovery key;
- verifier echec avec mauvais password;
- verifier encrypt/decrypt field;
- verifier shared key envelope.

Probleme:

WebCrypto est navigateur. Il faut soit:

- tests dans un environnement browser avec Playwright;
- tests Node avec WebCrypto compatible;
- extraction de logique pure testable.

### Tests E2E Playwright

Flux recommandés:

```text
Inscription
-> validation serveur
-> generation recovery key
-> verification email simulee
-> unlock vault
-> creation service
-> logout
-> login
-> unlock
-> service visible/dechiffre
```

```text
Owner cree item
-> partage avec recipient
-> recipient accepte
-> owner modifie
-> recipient voit sync
-> owner revoke
-> recipient perd acces
```

```text
OAuth nouveau compte
-> callback
-> vault setup obligatoire
-> recovery key
-> dashboard
```

### Tests de threat model

Tests a ajouter:

- `rg` ou test automatisé verifiant absence de `legacy_unlock` dans `app/` et
  `resources/views`;
- test confirmant qu'aucun endpoint `/password/generate` serveur n'est expose en
  mode zero-knowledge;
- test confirmant que les logs ne contiennent pas de secrets apres erreurs
  connues.

### Tests de deploiement

Script manuel ou CI:

```bash
php artisan about
php artisan route:list --except-vendor
curl -I https://nexusvault.dev
curl -I https://nexusvault.dev/up
```

Pour `/mfa/qr-code`, il faut etre authentifie, donc verification navigateur ou
test feature.

## CI

Workflows presents:

```text
.github/workflows/lint.yml
.github/workflows/tests.yml
```

Objectif CI ideal:

```bash
composer install --no-interaction --prefer-dist
npm ci
composer run ci:check
npm run build
```

Attention: si le build genere des assets ignores localement, CI doit simplement
verifier qu'il compile, pas necessairement committer `public/build`.

## Strategie de qualite pour chaque PR

Checklist:

```text
[ ] Changement localise
[ ] Tests ajoutes ou adaptes
[ ] Pas de secret dans logs/diff
[ ] Pas de clair envoye pour users modernes
[ ] php artisan test --compact OK
[ ] npm run build OK si TS/routes/views modifiees
[ ] vendor/bin/pint --dirty --format agent OK si PHP modifie
[ ] Documentation mise a jour si decision importante
```

## Tests de non-regression crypto

Les tests ne doivent pas chercher a reimplementer AES-GCM. Ils doivent verifier
les invariants applicatifs:

- les champs stockes ne contiennent pas le secret en clair;
- un mauvais password ne decrypte pas l'enveloppe;
- la recovery key correcte ouvre le coffre;
- le serveur refuse `client_encrypted=0`;
- le partage ne stocke pas `plain-password`;
- le recipient ne peut pas accepter sans payload chiffre.

## Gaps actuels

1. Peu ou pas de tests browser reels pour WebCrypto.
2. Pas encore de tests E2E Playwright.
3. Pas encore de test de restore backup.
4. Pas encore de test CSP/XSS automatise.
5. Pas encore de test de rotation recovery/vault password, car feature absente.
6. Pas encore de monitoring de production teste.

## Priorites

1. Ajouter tests E2E pour inscription/unlock/create/share.
2. Ajouter tests WebCrypto.
3. Ajouter tests de backup/restore en staging.
4. Ajouter regression test pour chaque bug prod corrige.
5. Documenter toute nouvelle faille/fix dans `11-roadmap`.
