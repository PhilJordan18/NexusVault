---
id: M-004
title: Mise a jour des dependances et composer audit bloquant
owner: dev
status: draft
author: architect
created: 2026-11-11
branch: nv/dev/M-004-dependances-et-composer-audit
depends_on: [M-001, M-005]
---

# M-004 - Mise a jour des dependances et composer audit bloquant

## Contexte

La livraison M-001 a laisse `composer audit` en avertissement : au moment de
la livraison, 49 avis touchaient 12 paquets de production, et
`laragear/webauthn` est abandonne. Voir « Seuils des audits » dans
`.nv/reports/M-001-livraison.md`. Tant que ce mandat n'est pas livre, la CI
ne bloque pas sur ces avis.

## Objectif

`composer audit --locked --no-dev` ne remonte aucun avis, et l'etape devient
bloquante dans `ci.yml`.

## Perimetre

- mettre a jour `composer.lock` vers des versions sans avis, dans les
  contraintes de `composer.json` ;
- pour chaque montee de version majeure necessaire, une note dans le rapport
  et les tests qui prouvent que rien ne casse ;
- decider du sort de `laragear/webauthn` avec le PM : remplacement, fork ou
  acceptation documentee du risque, avec ADR si remplacement ;
- rendre l'etape `composer audit` bloquante dans `ci.yml` (c'est le seul
  fichier de `.github/` que ce mandat touche, en accord avec secops).

## Hors perimetre

- les dependances npm, deja a zero avis `high` ;
- toute nouvelle fonctionnalite.

## Criteres d'acceptation

- [ ] `composer audit --locked --no-dev --abandoned=report` sans avis ;
- [ ] suite Pest complete verte, `composer run ci:check` vert ;
- [ ] decision sur `laragear/webauthn` ecrite dans le rapport, ADR si besoin ;
- [ ] l'etape `composer audit` de `ci.yml` echoue si un avis apparait.

## Exigences QA

- `composer run ci:check` vert ;
- tests Pest de login, MFA, WebAuthn et coffre verts ;
- lien d'un run `ci` vert sur la branche.

## Contraintes securite

Pas de retrait de paquet sans remplacement ou decision ecrite. Pas de
`composer update` aveugle : chaque montee de version est nommee dans le
rapport.

## A decider avant `ready`

Le PM tranche sur `laragear/webauthn`. Sans cette decision, le mandat reste
`draft`.
