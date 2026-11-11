---
mandate: M-001
author: secops
branch: nv/secops/M-001-ci-et-garde-fous
head: 51a4fa60b0dc4f6c9cd6132964a82fd1bd16bb53
created: 2026-11-10
review: pending
---

# Livraison M-001 - Garde-fous GitHub et CI unique avec gates securite

## Ce qui a ete fait

Par critere d'acceptation du mandat :

- **Un seul workflow, un job `ci` requis par les rulesets.** `lint.yml` et
  `tests.yml` sont remplaces par `.github/workflows/ci.yml`. Trois jobs :
  `checks` (matrice PHP 8.3 et 8.5 : `composer run ci:check`, `npm run build`,
  couverture sur 8.5), `security` (audits, gitleaks, tests des hooks) et `ci`,
  la porte qui n'est verte que si les deux autres le sont. C'est `ci` que les
  rulesets `nv-main` et `nv-dev` doivent exiger (H-002) : un seul nom, stable
  meme si la matrice change. `permissions: contents: read` au niveau du
  workflow, `persist-credentials: false` sur chaque checkout, quatre actions
  tierces epinglees par SHA avec la version en commentaire.
- **Un commit qui casse le format echoue.** `composer run ci:check` enchaine
  eslint, prettier --check, vue-tsc, pint --test et les tests Pest.
  Run rouge volontaire : https://github.com/PhilJordan18/NexusVault/actions/runs/42943715660 (branche jetable
  `nv/secops/M-001-run-rouge`, fichier hors style prettier et cle AWS factice).
- **Un commit qui ajoute un secret factice echoue.** gitleaks v3 sur
  l'historique complet (`fetch-depth: 0`), commentaires de PR et artefacts
  desactives pour rester en lecture seule. Meme run rouge que ci-dessus.
- **`composer audit` et `npm audit` executes, seuil documente.** Voir la
  section Seuils ci-dessous.
- **Preset securite Pest.** `tests/Unit/ArchitectureSecurityTest.php` charge
  `arch()->preset()->security()`. Une seule exclusion :
  `App\Services\PasswordService`, qui calcule un SHA-1 parce que le protocole
  k-anonymat de Have I Been Pwned l'exige (prefixe de 5 caracteres envoye a
  l'API). Verification faite qu'un nouvel usage de `md5` ailleurs fait echouer
  le test.
- **Couverture sur `app/Services/{Auth,Security,Vault}`.** `phpunit.xml`
  limite `<source>` a ces trois dossiers ; le job `checks` sur PHP 8.5 lance
  `php artisan test --coverage --min=80`. Couverture mesuree au premier
  run : 81,0 % (MfaService 66,7 %, UserKeyService 65,1 %, LoginService, PasswordResetService, ChangePasswordService, AccountDeletionService et EncryptionRotationService a 0 %).
- **`dependabot.yml`** hebdomadaire (lundi) pour composer, npm et
  github-actions, PR vers `dev`, 5 PR ouvertes maximum par ecosysteme.
- **Matrice PHP** : 8.3 (minimum de `composer.json`) et 8.5 (cible). 8.4 retire.

Run vert sur la branche du mandat : https://github.com/PhilJordan18/NexusVault/actions/runs/42943945674 (seuil de couverture inclus) ; premier run vert sans seuil : https://github.com/PhilJordan18/NexusVault/actions/runs/42943607495

## Seuils des audits

| Audit | Commande | Etat au moment de la livraison | Bloquant |
| --- | --- | --- | --- |
| npm | `npm audit --omit=dev --audit-level=high` | 0 avis apres `npm audit fix` (etait 8 : 6 high, 2 critical) | oui |
| composer | `composer audit --locked --no-dev --abandoned=report --format=summary` | 49 avis sur 12 paquets de production, `laragear/webauthn` abandonne | non, avertissement |
| gitleaks | config par defaut | 0 fuite sur l'historique | oui |

Pourquoi `composer audit` n'est pas bloquant aujourd'hui : `composer.lock`
date de novembre 2025. Les avis touchent `laravel/framework` 13.9 (dont un
contournement d'authentification TokenGuard), `symfony/http-foundation`,
`guzzlehttp/guzzle`, `league/commonmark`, etc. Les lever demande un
`composer update` qui franchit des majeures (`guzzle` 7 vers 8, `phpseclib` 3
vers 4, `brick/math` 0.14 vers 1.0) : c'est un mandat a part entiere, hors du
perimetre de M-001 et hors du role secops. Proposition : **M-004, mise a jour
des dependances PHP, owner dev, priorite 1**, avec pour critere
`composer audit --locked --no-dev` a zero. Le jour ou M-004 est accepte, rendre
l'etape bloquante revient a retirer le `|| { ... }` de l'etape dans `ci.yml`
(une ligne, commentee sur place).

## Decisions prises en cours de route

1. **`.githooks/pre-commit` cassait tout commit sur macOS.** `mapfile`
   n'existe pas dans le bash 3.2 de macOS ; avec `set -e` le hook sortait en
   erreur 127 et refusait chaque commit. Remplace par une boucle `while read`.
   `test_githooks.sh` passe maintenant de bout en bout sur ce Mac.
2. **`composer run ci:check` etait rouge sur `dev`.** 15 fichiers hors style
   prettier, 18 hors style pint. L'ancien workflow lancait les formateurs en
   mode ecriture, donc ne le voyait pas. Commit `style:` separe, changements de
   forme uniquement, 72 tests verts. Sans cela aucune livraison, y compris
   M-002, ne peut passer `nv-deliver`. Effet de bord utile : pint a corrige la
   deprecation PHP 8.5 sur le parametre nullable implicite de
   `UserKeyServiceInterface::provisionKey()`.
3. **`package-lock.json` rafraichi par `npm audit fix`** (patch et mineures
   dans les plages de `package.json`, aucune dependance ajoutee) pour que le
   seuil npm soit tenable des le premier jour. Build, lint, types et tests
   verifies.
4. **Declencheur `push` etendu aux branches de travail** `nv/**`, `claude/**`,
   `codex/**` en plus de `dev`. Le mandat ne citait que `dev` et les PR, mais
   l'exigence QA (run vert sur la branche du mandat) et le cycle `nv-deliver`
   (pas de PR vers `dev`) imposent que chaque branche de travail soit construite
   avant livraison.
5. **Job de porte `ci` plutot qu'un job unique.** Un job unique avec matrice
   produirait des statuts `ci (8.3)` et `ci (8.5)` ; la porte garde un seul
   nom requis par les rulesets.
6. **`public/build/` est suivi par git malgre `.gitignore`.** `npm run build`
   le modifie ; j'ai restaure les fichiers et rien n'est commite dessus.
   A traiter dans un mandat d'hygiene (`git rm -r --cached public/build`,
   apres avoir confirme que le deploiement reconstruit les assets).
7. **Branche jetable `nv/secops/M-001-run-rouge`** laissee sur origin : les
   hooks interdisent la suppression de branche aux sessions. Le PM peut la
   supprimer apres lecture du rapport. Elle contient une cle AWS factice ;
   ne jamais la fusionner.

## Tests

| Commande | Resultat |
| --- | --- |
| `composer run ci:check` | vert (eslint, prettier, vue-tsc, pint, 72 tests) |
| `npm run build` | vert |
| `npm audit --omit=dev --audit-level=high` | 0 avis |
| `python3 -m unittest discover -s .claude/hooks/tests` | 31 tests verts |
| `bash .githooks/tests/test_githooks.sh` | vert de bout en bout |
| Preset securite Pest avec un `md5` ajoute volontairement | echec attendu, puis retire |

La preuve machine est dans `.nv/qa/M-001.json` (ecrite par `nv-deliver`).

## Points d'attention pour la revue securite

1. **Ruleset `nv-dev` et `nv-deliver` sont incompatibles tels que decrits dans
   `PROCEDURES.md`.** "Require status checks to pass" sur `dev` s'applique
   aussi aux push directs : GitHub refuse un commit qui n'a pas encore de
   statut `ci` reussi, et le commit de livraison cree par `nv-deliver` n'a
   jamais ete construit. Deux options pour Architecture : (a) ne pas exiger
   de statut sur `nv-dev` et compter sur le run `ci` de la branche de travail,
   que `nv-deliver` pourrait verifier via `gh run list` avant de pousser ;
   (b) livrer par PR vers `dev`. Je recommande (a) avec le controle ajoute a
   `nv-deliver` dans un mandat secops suivant.
2. **`composer audit` non bloquant** tant que M-004 n'existe pas : c'est le
   seul ecart avec l'objectif du mandat, et il est visible dans chaque run
   (annotation warning).
3. **Secret scanning GitHub et push protection (H-002)** restent necessaires :
   gitleaks ne tourne qu'apres le push.
4. **Le token `GITHUB_TOKEN`** passe a gitleaks est le token automatique du
   run, en lecture seule ; aucun secret de depot n'est utilise.

## Avis Securite

Livraison faite par la session secops elle-meme : l'avis independant revient
a la session Architecture. `review: pending`.

## Decision Architecture

A remplir par la session architect. `accepted | rejected` et motif.
