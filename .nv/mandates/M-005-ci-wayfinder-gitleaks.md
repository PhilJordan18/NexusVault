---
id: M-005
title: CI de dev verte, Wayfinder dans ci:check et allowlist gitleaks nominative
owner: secops
status: in_progress
author: architect
created: 2026-11-11
branch: nv/secops/M-005-ci-wayfinder-gitleaks
depends_on: [M-001]
---

# M-005 - CI de dev verte, Wayfinder dans ci:check et allowlist gitleaks nominative

## Contexte

La CI de `dev` est rouge depuis la livraison de M-003 (`94631ac`, run
42944838872) et l'est restee a M-002 (`0ff5744`, run 42946331380 ;
`8ddb010`, run 43043897643). Deux causes, aucune dans le code applicatif,
relevees par la session Securite dans l'avis de M-002 :

- `npm run types:check` echoue sur un clone propre : les pages Inertia de
  M-003 importent `@/routes/...`, generes par Wayfinder dans
  `resources/js/routes`, dossier ignore par git, et la CI ne lance jamais
  `php artisan wayfinder:generate`.
- gitleaks signale `generic-api-key` sur le secret TOTP de fixture des tests
  Pest (`2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O`, sept occurrences sous `tests/`),
  valeur publique presente depuis `MfaSetupTest`.

Le PM a autorise le travail le 2026-11-11. Ce mandat formalise ce qui a ete
fait sur la branche et fixe les criteres de son acceptation.

## Objectif

Un push sur `dev` dont le code applicatif est sain produit un run `ci` vert,
en local comme en CI, avec la meme commande.

## Perimetre

- `composer.json` : `ci:check` lance `php artisan wayfinder:generate
  --no-interaction` avant `npm run types:check`, pour que local et CI
  executent exactement la meme suite. `ci.yml` n'est modifie que si l'etape
  « Prepare test environment » ne suffit pas a rendre l'application bootable.
- `.gitleaks.toml` a la racine, etendant les regles par defaut
  (`useDefault = true`), avec une allowlist portee par la regle
  `generic-api-key`, condition ET entre le chemin `^tests/.*\.php$` et les
  valeurs litterales de fixture. Aucune allowlist globale, aucun chemin hors
  `tests/`.
- Rapport `.nv/reports/M-005-livraison.md` avec les preuves ci-dessous.

## Hors perimetre

- toute modification de test ou de code applicatif ;
- le seuil de `composer audit`, c'est M-004 ;
- la version de gitleaks-action, suivie par Dependabot.

## Criteres d'acceptation

- [ ] `composer run ci:check` est vert depuis un arbre sans
      `resources/js/routes` ;
- [ ] gitleaks avec les regles de la version installee par l'action
      (8.24.3) : la plage `94631ac..0ff5744` passe de 7 alertes a 0 avec la
      configuration, et la plage `0ff5744..8ddb010` de 1 a 0 ;
- [ ] la meme valeur de fixture placee hors de `tests/` reste detectee, et
      une autre valeur a forte entropie sous `tests/` reste detectee ;
- [ ] un run `ci` vert sur `dev` apres livraison, lien dans le rapport.

## Exigences QA

- preuve `.nv/qa/M-005.json` ecrite par `nv-deliver` ;
- sortie de gitleaks pour chaque plage, dans le rapport ;
- lien du run GitHub Actions sur `dev`.

## Contraintes securite

Chaque exception gitleaks nomme une valeur et un chemin. Jamais de regle
desactivee, jamais d'allowlist sur un dossier entier. Toute nouvelle fixture
a forte entropie passe par un ajout nominatif, et donc par une revue.
