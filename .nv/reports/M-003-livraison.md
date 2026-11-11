---
mandate: M-003
author: ui
branch: claude/eloquent-darwin-jd6pqq
head: 50daafd82f7dafe2795ab17612cfa928550b0f44
created: 2026-11-11
review: pending
---

# Livraison M-003 - Plan de migration du frontend vers Inertia Vue 3

## Ce qui a été fait

- **Inventaire et ordre** : sections 1 et 2 ci-dessous. Trente vues Blade
  applicatives réparties en six domaines, cinq lots ordonnés du moins au plus
  sensible, les écrans crypto en dernier et sous co-signature Sécurité.
- **Page de référence en Vue** : les quatre pages légales (`/terms`,
  `/privacy`, `/cookies`, `/accessibility`) sont rendues par
  `resources/js/pages/legal/Show.vue` via `LegalController`, avec le layout
  `resources/js/layouts/LegalLayout.vue`, le composant
  `resources/js/components/LanguageSwitch.vue` et le composable
  `useTranslations`. Lint, format et types verts.
- **Aucune régression** : `tests/Feature/LegalPagesTest.php` passe de deux à
  quatre tests (rendu Inertia de chaque page, locale, props partagées,
  parité de structure EN/FR). Suite complète verte, voir la section Tests.
- **Captures avant et après** : `.nv/reports/M-003/`.

## 1. Inventaire des vues Blade par domaine

Trente vues applicatives, plus `app.blade.php` (racine Inertia), un gabarit
d'e-mail et seize gabarits `vendor/mail` qui restent en Blade (les e-mails ne
sont pas du frontend navigateur).

| Domaine | Vue | Lignes | Layout | JS lié | Crypto navigateur | Lot |
|---|---|---|---|---|---|---|
| Légal | `legal/terms`, `legal/privacy`, `legal/cookies`, `legal/accessibility` | 103, 105, 59, 57 | `layouts.legal` | aucun | non | **0, livré** |
| Légal | `components/layouts/legal` | 81 | - | aucun | non | 0, remplacé par `LegalLayout.vue` |
| Public | `landing` | 310 | autonome | 3 scripts inline (thème, menu) | non, mais `{!! __() !!}` (F4) | 3 |
| Auth | `auth/login` | 75 | `layouts.auth` | `auth.ts` (passkey-btn) | non, WebAuthn | 1 |
| Auth | `auth/login-password` | 65 | `layouts.auth` | `auth.ts` | non | 1 |
| Auth | `auth/forgot-password`, `auth/reset-password` | 44, 68 | `layouts.auth` | `auth.ts` (jauge de force) | non | 1 |
| Auth | `auth/verify-email` | 44 | `layouts.auth` | aucun | non | 1 |
| Auth | `auth/mfa/setup`, `auth/mfa/verify` | 49, 38 | `layouts.auth` | aucun | non | 1, après M-002 |
| Auth | `components/layouts/auth` | 151 | - | thème, toast, `window.nexusVaultTranslations` | non | 1, remplacé par `AuthLayout.vue` |
| Auth | `auth/register` | 179 | `layouts.auth` | `auth.ts` register-form | **oui** : dérivation de la clé de coffre et enveloppes à l'inscription | 4a |
| Auth | `auth/vault-setup` | 86 | `layouts.auth` | `auth.ts` vault-setup-form | **oui** | 4a |
| Auth | `auth/vault-unlock` | 128 | `layouts.auth` | `auth.ts` unlock, recovery, reset | **oui** : enveloppes injectées dans `window` | 4b |
| Settings | `settings/index` | 300 | `layouts.app` | `sessions.ts`, `password-utils.ts` | non | 2 |
| Settings | `passkey/index` | 76 | `layouts.app` | `passkey.ts`, `WebAuthn.ts` | non, WebAuthn (sensible, pas coffre) | 2 |
| Admin | `admin/index` | 120 | `layouts.app` | aucun | non | 2 |
| Coffre | `components/layouts/app` | 357 | - | sidebar, thème, toast, recherche, `window.*` | **oui** par inclusion des modales | 2 (sans modales) puis 4 |
| Coffre | `dashboard/index` | 143 | `layouts.app` | `zero-knowledge-forms.ts` | **oui** : déchiffrement de la liste | 4c |
| Coffre | `dashboard/service` | 299 | `layouts.app` | `pages/service.ts` | **oui** : déchiffrement, édition, partage | 4c |
| Coffre | `services/create-modal` | 313 | inclus par `layouts.app` | `zero-knowledge-forms.ts` | **oui** : chiffrement avant envoi | 4c |
| Coffre | `components/search-results`, `components/navbar` | 35, 42 | fragments | `search.ts` (innerHTML de HTML serveur) | non | 4c |
| Partage | `shares/modal` | 63 | inclus par `layouts.app` | `zero-knowledge-sharing.ts` | **oui** : RSA-OAEP sur la clé d'élément | 4d |
| Partage | `notifications/index` | 108 | `layouts.app` | `zero-knowledge-sharing.ts` | **oui** : clé privée chiffrée injectée dans `window` | 4d |
| Partials | `partials/language-switch`, `partials/favicons` | 31, 4 | - | aucun | non | 0 (`LanguageSwitch.vue`), favicons restent dans la racine |
| Hors migration | `emails/email-verification`, `vendor/mail/*` | 84, 16 fichiers | - | - | - | jamais |

Le `app.blade.php` existant est déjà la racine Inertia (`HandleInertiaRequests`,
`<x-inertia::app />`). `pages/Welcome.vue` est une page du starter kit jamais
rendue : à supprimer au lot 1.

## 2. Ordre de migration proposé

Chaque lot est découpable en un mandat par la session Architecture. Un écran
est soit Blade soit Vue, jamais un mélange (ADR-005) ; les deux layouts Blade
coexistent avec leurs équivalents Vue jusqu'au lot 4.

| Lot | Contenu | Prérequis | Risque |
|---|---|---|---|
| **0** (ce mandat) | Pages légales, `LegalLayout.vue`, `LanguageSwitch.vue`, `useTranslations`, props partagées `locale`, `locales`, `translations`, alias `@/` corrigé, types partagés | - | nul |
| **1** | `AuthLayout.vue` (thème, toasts via flash Inertia), `login`, `login-password`, `forgot-password`, `reset-password`, `verify-email`, `mfa/setup`, `mfa/verify`. Suppression de `Welcome.vue`. | **M-002 livré** : il touche `LoginController`, `MfaController` et les routes `/login`, `/mfa/*`. Démarrer après pour éviter les conflits. | faible ; le bouton passkey de `login` appelle `WebAuthn.ts` sans toucher au coffre |
| **2** | `AppLayout.vue` (sidebar, topbar, thème, toasts, menu mobile) **sans** les modales création/partage ni la recherche, puis `settings/index`, `passkey/index`, `admin/index`. `sessions.ts` devient `router.delete` + `useForm`. | lot 0 | faible ; tant que le lot 4 n'est pas livré, le bouton « Nouvel élément » de `AppLayout.vue` renvoie vers `/dashboard` (Blade) |
| **3** | `landing` : contenu structuré en props, `<strong>` via composants et non `v-html`. Retire le dernier `{!! !!}`. | lot 0 | nul |
| **4a** (co-signé Sécurité) | `register`, `vault-setup` : génération de clé, enveloppes, clé de récupération | lot 1, Vitest sur `zero-knowledge.ts`, Playwright inscription → setup | élevé |
| **4b** (co-signé Sécurité) | `vault-unlock` + recovery + reset : les enveloppes passent par des props Inertia et non par `window` | 4a, Playwright unlock | élevé |
| **4c** (co-signé Sécurité) | `dashboard/index`, `dashboard/service`, `CreateItemModal.vue`, recherche en JSON (`SearchController` renvoie des données, plus du HTML) | lot 2, 4b | élevé |
| **4d** (co-signé Sécurité) | `ShareModal.vue`, `notifications/index` : clé publique du destinataire, RSA-OAEP, acceptation. Lier au correctif E5 (empreinte de clé). | 4c | élevé |
| **5** | Nettoyage : suppression des layouts Blade, de `resources/ts/auth.ts` (glue DOM), de `pages/service.ts`, des `window.*`, de Font Awesome CDN (M1) au profit d'icônes inline. Mise à jour de `docs/02`. | 4d | faible |

Pourquoi cet ordre : les lots 0 à 3 ne manipulent aucune clé et peuvent
avancer pendant que le sprint 0 sécurité (M-001, M-002) se termine. Le lot 4
reprend la chaîne crypto dans l'ordre du cycle de vie d'un coffre (création,
déverrouillage, usage, partage), chaque sous-lot livrant ses tests Vitest et
Playwright avant le suivant.

## 3. Conventions imposées à partir du lot 1

Déjà appliquées sur la page de référence.

**Dossiers** (`resources/js/`)

- `pages/<domaine>/<Nom>.vue` : une page par `Inertia::render('<domaine>/<Nom>')`,
  domaine en minuscules, composant en PascalCase (`legal/Show`, `auth/Login`).
- `layouts/<Nom>Layout.vue` : `LegalLayout`, `AuthLayout`, `AppLayout`. Un
  layout reçoit ses props explicitement depuis la page (pas de `setLayoutProps`
  tant qu'un layout persistant n'est pas nécessaire).
- `components/` : composants partagés en PascalCase, un fichier par composant.
- `composables/use<Nom>.ts` : logique réutilisable (`useTranslations`).
- `types/` : types partagés, exportés depuis `types/index.ts` ; les props
  partagées sont déclarées dans `types/global.d.ts` (`sharedPageProps`).
- `resources/ts/` reste le domaine des modules crypto et WebAuthn. Ils ne
  touchent plus le DOM à terme : ils exposent des fonctions pures appelées par
  les composants Vue.

**Serveur**

- Un contrôleur par domaine, `final`, retourne `Inertia\Response`. Le contenu
  statique multilingue vit dans `lang/{locale}/<domaine>.php` (tableaux
  structurés), jamais dans le composant ni en HTML.
- `HandleInertiaRequests` partage `auth.user`, `locale`, `locales` et
  `translations` (le catalogue `lang/{locale}.json`, évalué paresseusement).
  Toute nouvelle chaîne d'interface passe par `lang/en.json` et `lang/fr.json`
  puis `t('clé', { param })` côté Vue.
- Props différées (`Inertia::defer`) avec squelette `animate-pulse` dès qu'une
  page charge une liste.

**Navigation**

- Routes via Wayfinder uniquement : `@/routes/<nom>` pour les routes nommées
  (`terms.url()`), `@/actions/...` pour les contrôleurs. Aucune URL en dur.
- `<Link>` vers une page Vue, `<a>` vers une page encore en Blade. Quand la
  cible migre, le lien passe à `<Link>` dans le même mandat.
- Formulaires : `<Form>` ou `useForm`, jamais de `fetch` manuel vers une
  route Inertia. `useHttp` pour les points JSON (`/api/services/suggest`).

**Style**

- Tailwind 4 avec les tokens CSS existants (`var(--bg-primary)`,
  `var(--text-secondary)`, classe `card`) : la charte ne change pas. Thème
  via la classe `html.light` posée par la racine.
- Prettier (`printWidth` 80, plugin Tailwind) et ESLint tels que configurés.
  `npm run lint && npm run format && npm run types:check` avant chaque commit
  front.

**Sécurité front**

- Interdits : `v-html`, `{!! !!}`, `innerHTML` sur toute donnée venant du
  serveur ou d'un déchiffrement. Les textes riches se composent avec des
  slots.
- Aucune donnée crypto dans `window.*` : les enveloppes et ciphertexts
  arrivent par props Inertia typées, la clé de coffre reste dans le module
  `zero-knowledge.ts` (`sessionStorage`).

**Tests**

- Une page migrée garde ses tests feature, réécrits avec `assertInertia`
  (composant, props) ; `assertSee` ne voit plus le texte rendu côté client.
- Lot 4 : Vitest sur les fonctions pures de `resources/ts/zero-knowledge*.ts`
  et un parcours Playwright par sous-lot (voir `docs/08`).

## 4. Règles d'accessibilité et de responsive

Appliquées sur la page de référence, à vérifier par `nv-ui-reviewer` sur
chaque page livrée.

1. **Clavier** : tout élément interactif atteignable et actionnable au
   clavier, dans l'ordre visuel ; lien « Aller au contenu principal » en
   premier dans le DOM, visible au focus ; `<main id="main-content"
   tabindex="-1">` pour recevoir le focus.
2. **Focus visible** : `focus-visible:outline-2 focus-visible:outline-offset-2
   focus-visible:outline-emerald-500` sur liens et boutons. Le `outline: none`
   global de `app.css` sur les champs sera remplacé par un `focus-visible` au
   lot 1.
3. **Structure** : un seul `<h1>`, sections avec `aria-labelledby`,
   `<nav aria-label>`, `<header>`, `<main>`, `<footer>`.
4. **Libellés** : chaque champ a un `<label>` ; les groupes de boutons ont
   `role="group"` et `aria-label` ; les boutons bascule exposent
   `aria-pressed` ; les boutons de langue portent `lang`.
5. **Contraste** : texte sur fond aux tokens existants (AA respecté en sombre
   et en clair) ; l'information n'est jamais portée par la couleur seule.
6. **Mouvement** : `prefers-reduced-motion` pour toute animation ajoutée.
7. **Responsive** : 360 px sans défilement horizontal, cibles tactiles de
   44 px minimum, vérification à 360, 768 et 1280 px.
8. **Langue** : `<html lang>` suit la locale ; tout texte vient de `lang/`.

## 5. Points où la crypto navigateur touche le DOM

À traiter avec la session Sécurité avant chaque sous-lot 4. Lignes de la
version formatée sur cette branche.

| Fichier | Lignes | Ce qui se passe | Sous-lot |
|---|---|---|---|
| `resources/ts/auth.ts` | 121-124, 274-289 | `register-form` : lit `password` et `vault_password`, dérive la clé, remplit les enveloppes avant soumission | 4a |
| `resources/ts/auth.ts` | 221-235 | `vault-setup-form` : idem pour un compte OAuth | 4a |
| `resources/ts/auth.ts` | 342-364 | `vault-unlock-form` : lit `vault_password`, ouvre `window.nexusVaultKeyEnvelope` | 4b |
| `resources/ts/auth.ts` | 380-394, 410-424 | `vault-recovery-form`, `vault-reset-form` : clé de récupération, réinitialisation destructive | 4b |
| `resources/views/auth/vault-unlock.blade.php` | 116-117 | `window.nexusVaultKeyEnvelope`, `window.nexusVaultRecoveryEnvelope` via `Js::from` | 4b : props Inertia |
| `resources/ts/zero-knowledge.ts` | 293-305 | clé de coffre dans `sessionStorage` (`vaultKeyStorageKey`) | 4b : inchangé, hors DOM |
| `resources/views/components/layouts/app.blade.php` | 188 | `window.nexusVaultUsesClientEncryption` | 2 : prop partagée |
| `resources/ts/zero-knowledge-forms.ts` | 53 | `create-service-form` : chiffre les champs avant envoi | 4c |
| `resources/js/pages/service.ts` | 96, 788 | CSRF depuis `<meta>` ; `panel.innerHTML` avec des chaînes de traduction uniquement, pas de données déchiffrées | 4c : composant Vue |
| `resources/views/dashboard/service.blade.php` | 293 | `accounts`, `nexusVaultCurrentAccount` (ciphertexts) dans `window` | 4c : props Inertia |
| `resources/ts/search.ts` | 47 | `dropdown.innerHTML = html` avec du HTML serveur (`components/search-results`) | 4c : réponse JSON + composant |
| `resources/ts/zero-knowledge-sharing.ts` | 46, 203-221 | `share-form`, `modal-service-id`, CSRF ; RSA-OAEP sur la clé d'élément avec la clé publique reçue du serveur (E5) | 4d |
| `resources/views/notifications/index.blade.php` | 100-105 | `window.nexusVaultPendingClientShares`, `window.nexusVaultEncryptedPrivateKey` | 4d : props Inertia |
| `resources/ts/WebAuthn.ts`, `resources/ts/passkey.ts` | 95 ; 39 | CSRF depuis `<meta>` ; `register-passkey-btn` | 2 : pas de clé de coffre, mais route sous `master_key` |
| `resources/ts/sessions.ts` | 71-72, 108-109 | `innerHTML` statique (spinner) | 2 : supprimé avec le composant Vue |

Le `<meta name="csrf-token">` n'existe que dans les layouts Blade : les
pages Vue passent par le client Inertia, qui gère le jeton via le cookie
`XSRF-TOKEN`. Les modules `resources/ts` qui lisent la balise devront
recevoir le jeton en paramètre au lot 4.

## 6. Page de référence : pages légales

- `app/Http/Controllers/LegalController.php` : quatre actions, une méthode
  privée `render()` qui lit `lang/{locale}/legal.php`.
- `lang/en/legal.php`, `lang/fr/legal.php` : contenu extrait des quatre vues
  Blade, mot pour mot, sous forme de sections et paragraphes. Les paragraphes
  avec un lien `mailto:` sont des tableaux `text`, `email`, `suffix`, ce qui
  évite tout HTML dans les données.
- `resources/js/pages/legal/Show.vue` : `<Head>` avec titre et meta
  description, sections `aria-labelledby`, lien `mailto:` composé sans
  `v-html`.
- `resources/js/layouts/LegalLayout.vue` : lien d'évitement, `<header>` avec
  `<nav>`, `<main>` focusable, `<footer>` avec `<Link>` Wayfinder vers les
  quatre pages, `<a>` vers `home`, `login`, `register`, `dashboard` (encore
  Blade).
- `resources/js/components/LanguageSwitch.vue` : `role="group"`,
  `aria-pressed`, `lang`, `router.post(update.url(), { locale })`.
- `resources/js/composables/useTranslations.ts` : `t(key, replacements)` et
  `locale`, miroir de `__()`.
- Les quatre vues Blade `legal/*` et `components/layouts/legal` sont
  supprimées (un écran est soit Blade soit Vue).

Captures dans `.nv/reports/M-003/` : `avant-*.png` (Blade sur `origin/dev`)
et `apres-*.png` (Vue sur cette branche), page `/privacy`, aux largeurs 360,
768 et 1280 px, plus la version française en thème clair.

## Décisions prises en cours de route

1. **Prettier sur quinze fichiers existants** (commit `style(front)`).
   `npm run format:check` était rouge sur `origin/dev`, donc
   `composer run ci:check` aussi, ce qui bloque `nv-deliver`. Reformatage
   pur : pour les douze fichiers TypeScript, dont `zero-knowledge*.ts` et
   `auth.ts`, la sortie `esbuild --minify` est identique à l'octet près avant
   et après. Les trois autres sont deux CSS et `Welcome.vue`. Aucune ligne de
   logique n'a été touchée ; `git diff -w` reste lisible pour la revue.
2. **Alias `@/`** : `tsconfig.json` pointait vers `resources/ts/*` alors que
   Vite (plugin Laravel) et Wayfinder utilisent `resources/js/*`. Corrigé vers
   `resources/js/*`, seule valeur cohérente avec `global.d.ts` et ADR-005.
   Aucun fichier de `resources/ts` n'utilisait l'alias.
3. **Catalogue de traductions partagé** : `translations` expose tout
   `lang/{locale}.json` (environ 360 clés) à chaque page Vue, en closure donc
   évalué seulement quand la page est rendue. Suffisant pour les lots 0 à 3 ;
   à découper par domaine (`lang/{locale}/ui.php`) si le poids devient
   gênant au lot 4.
4. **Titre de page** : `NexusVault • Titre` devient `Titre - NexusVault`,
   convention de `resources/js/app.ts` déjà en place pour Inertia.
5. **Tests `assertSee` remplacés par `assertInertia`** : le texte n'est plus
   dans le HTML initial mais dans les props. Les mêmes garanties sont
   testées (titre, locale, contenu français), plus la parité EN/FR.
6. **`Welcome.vue` conservé** : page morte du starter kit, sa suppression est
   notée au lot 1 pour garder ce diff centré sur les pages légales.
7. **`<html lang>` synchronisé côté client** : Inertia ne rend pas
   `app.blade.php` à chaque visite, donc `lang` restait `en` après un
   changement de langue. `app.ts` écoute `router.on('success')` et
   `router.on('navigate')` et recopie `page.props.locale`. Les deux
   événements sont nécessaires : `navigate` ne se déclenche pas sur la
   visite déclenchée par `router.post` du commutateur.
8. **`withoutVite()` dans `tests/TestCase.php`** : `public/build/` est
   suivi par git avec un manifeste périmé, et `nv-deliver` lance les tests
   avant `npm run build`. Sans cela, la première page Vue nouvelle fait
   échouer la suite (`ViteException`). À retirer quand `public/build/`
   sortira du dépôt (à décider par Architecture, voir plus bas).
9. **Lien d'évitement avec le même anneau de focus** que les autres cibles,
   pour qu'il soit repérable dès le premier Tab, y compris en thème clair.

## Tests

Commandes lancées sur la branche, sans `.env` (clé passée par `APP_KEY=`
en variable d'environnement, comme pour `nv-deliver`) :

| Commande | Résultat |
| --- | --- |
| `npm run lint` | vert, aucune correction automatique |
| `npm run format:check` | vert |
| `npm run types:check` | vert (vue-tsc, lib ES2020) |
| `vendor/bin/pint --dirty --format agent` | vert |
| `php artisan test --compact` | 73 tests, 471 assertions, tous verts |
| `npm run build` | vert, `legal/Show` et `LegalLayout` dans le manifeste |
| `python3 -m unittest discover -s .claude/hooks/tests` | vert |

Les tests Pest affichent un avertissement par test
`file_get_contents(.env): No such file` : il vient du code vendor qui
tente de lire `.env` en l'absence du fichier, les assertions passent. La
suite `composer run ci:check` est rejouée par `nv-deliver` et son résultat
est dans `.nv/qa/M-003.json`.

Tests ajoutés ou réécrits dans `tests/Feature/LegalPagesTest.php` :

- chaque page légale rend `legal/Show` avec `page`, `title`, `description`,
  `lastUpdated` et des `sections` non vides (dataset de quatre pages) ;
- `/privacy` en session `fr` renvoie le titre, la locale et le contenu
  français, et le catalogue `translations` traduit `Legal Center` ;
- les props partagées `locale` et `locales` sont présentes ;
- `lang/en/legal.php` et `lang/fr/legal.php` ont la même structure
  (nombre de sections et de paragraphes par page).

Vérifications manuelles (Playwright, Chromium préinstallé) sur `/privacy`,
`origin/dev` servi dans un worktree sur le port 8091 et cette branche sur
le port 8090 :

- captures avant/après à 360, 768 et 1280 px, plus FR en thème clair à 360
  et 1280 px, rendu équivalent, aucun défilement horizontal à 360 px ;
- parcours clavier complet : lien d'évitement, logo, EN, FR, lien
  `Create account`, lien `mailto:`, quatre liens du pied de page, chacun
  avec un contour de focus visible de 2 px ;
- repères : un seul `h1`, `main#main-content`, deux `nav` nommées, huit
  sections nommées par `aria-labelledby`, groupe `Language` sur le
  commutateur ;
- après le passage en français via Inertia, `<html lang>` vaut `fr` ;
- aucun `v-html` dans `resources/js`.

## Points d'attention pour la revue sécurité

1. `HandleInertiaRequests::share()` expose `auth.user` tel quel, comme avant
   ce mandat. Avec la sérialisation Inertia, le `$hidden` de `User` fait foi :
   le constat F5 de la revue du 10 novembre (`vault_key_envelope`,
   `vault_recovery_envelope`, `encrypted_master_key` absents de `$hidden`)
   devient plus pressant dès qu'une page Vue est servie à un utilisateur
   connecté. À traiter dans M-002 ou un mandat dédié avant le lot 1.
2. Le commit `style(front)` touche `zero-knowledge.ts`,
   `zero-knowledge-forms.ts`, `zero-knowledge-sharing.ts` et `auth.ts` :
   vérifier avec `git diff -w 172df8a..86cd016 -- resources/ts` et, au
   besoin, rejouer la comparaison esbuild décrite plus haut.
3. Aucun `v-html`, aucun `{!! !!}`, aucun `innerHTML` dans le code ajouté.
   Le seul HTML sortant des données est le `mailto:` construit depuis une
   adresse figée dans `lang/`.
4. Les pages légales restent publiques, sans middleware, comme avant.
5. Fichiers `.pyc` sous `.claude/hooks/__pycache__/` suivis par git et
   modifiés à chaque `nv-setup` : à ignorer dans `.gitignore` (session
   Sécurité ou Architecture).
6. `public/build/` est suivi par git : chaque `npm run build` modifie le
   manifeste et les assets hachés, et les tests dépendent de l'état du
   manifeste commité (d'où `withoutVite()`). Proposition : ignorer
   `public/build/` et construire en CI, à arbitrer par Architecture.

## Avis Sécurité

A remplir par la session secops. `review: pending`.

## Décision Architecture

A remplir par la session architect.
