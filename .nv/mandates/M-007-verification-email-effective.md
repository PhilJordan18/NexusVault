---
id: M-007
title: Verification d'email effective avant usage du coffre
owner: dev
status: draft
author: architect
created: 2026-11-11
branch: nv/dev/M-007-verification-email
depends_on: [M-002]
---

# M-007 - Verification d'email effective avant usage du coffre

## Contexte

Constat du nv-pentester lors de la revue de M-002 : `RegisterController`
ouvre une session complete a l'inscription, aucune route ne porte le
middleware `verified`, un compte non verifie utilise tout le coffre et peut
occuper l'adresse d'un tiers. Preexistant, aggrave par la fermeture de
l'auto-liaison OAuth (E2). Voir `.nv/reports/M-002-livraison.md`.

## Objectif

Un compte dont l'adresse n'est pas verifiee ne peut ni ouvrir le coffre, ni
partager, ni bloquer durablement une adresse qu'il ne possede pas.

## Perimetre

- middleware `verified` sur les routes du coffre, du partage et des
  reglages, liste exacte a etablir avec `nv-explorer` et a ecrire dans le
  rapport ;
- ecran d'attente de verification en Vue (ADR-005), renvoi du lien avec
  limiteur ;
- expiration des comptes jamais verifies apres un delai a fixer avec le PM,
  pour liberer l'adresse ;
- `docs/03` mis a jour.

## Hors perimetre

- changement du fournisseur d'email ;
- tout changement de format crypto.

## Criteres d'acceptation

- [ ] test rouge d'abord : un compte non verifie recoit une redirection sur
      chaque route protegee, puis vert ;
- [ ] un compte verifie n'est pas affecte, suite complete verte ;
- [ ] le lien de verification ne revele pas si l'adresse existe deja.

## Exigences QA

- `composer run ci:check` vert ;
- tests Pest cibles nommes dans le rapport ;
- capture de l'ecran d'attente.

## Contraintes securite

Les reponses ne permettent pas d'enumerer des comptes. Le limiteur de
renvoi est par compte, pas seulement par adresse IP.

## A decider avant `ready`

Le delai d'expiration des comptes non verifies, avec le PM.
