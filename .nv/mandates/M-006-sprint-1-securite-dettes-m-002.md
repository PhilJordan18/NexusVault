---
id: M-006
title: Sprint 1 securite, dettes tracees par la revue de M-002
owner: dev
status: draft
author: architect
created: 2026-11-11
branch: nv/dev/M-006-sprint-1-securite
depends_on: [M-002]
---

# M-006 - Sprint 1 securite, dettes tracees par la revue de M-002

## Contexte

L'avis Securite sur M-002 (`.nv/reports/M-002-livraison.md`, section
« Constats du nv-pentester acceptes en dette tracee ») a accepte la
livraison en inscrivant six constats a traiter. L'ADR-006 accorde une
derogation au stockage du secret TOTP en attente en session, sous
conditions que ce mandat livre.

## Objectif

Un attaquant qui vole une session ou un code TOTP ne peut ni enroler son
propre second facteur, ni desactiver la MFA avec le seul mot de passe, ni
relire un secret en attente apres verrouillage ; et aucun secret TOTP n'est
lisible en clair dans la base.

## Perimetre

1. `users.totp_secret` : cast `encrypted` sur la colonne, migration de
   donnees pour les secrets existants, test qui lit la valeur brute en base
   et verifie qu'elle n'est pas le secret.
2. `mfa_pending_secret` (conditions de l'ADR-006) : regenere a chaque
   `GET /mfa/setup` ou expire apres dix minutes, oublie dans
   `VaultController::lock()` et au logout, jamais affiche apres
   verrouillage.
3. Enrolement MFA derriere `ConfirmsFreshAuthentication` sur
   `mfa.setup.verify`.
4. Si `mfa_enabled`, `mfa.disable` et `settings.account.destroy` exigent un
   TOTP, le mot de passe seul ne suffit plus.
5. `settings.password.update` derriere le limiteur `fresh-authentication`.
6. Notifications de reset et de verification en file d'attente
   (`ShouldQueue`), pour fermer le canal temporel de `/forgot-password`.

## Hors perimetre

- rejeu TOTP (`verifyKeyNewer`, `totp_last_used_at`) et codes de secours :
  sprint 2, mandat separe ;
- verification d'email effective : M-007 ;
- CSP, HSTS, SRI : mandat separe du sprint 1.

## Criteres d'acceptation

- [ ] un test rouge d'abord par point 1 a 6, vert apres correctif ;
- [ ] suite Pest complete verte, `composer run ci:check` vert ;
- [ ] `docs/03` et `docs/04` mis a jour la ou le comportement change ;
- [ ] aucune route existante supprimee sans note dans le rapport.

## Exigences QA

- tests Pest cibles nommes dans le rapport ;
- preuve `.nv/qa/M-006.json`.

## Contraintes securite

Aucun secret en clair dans les logs, les sessions ni le DOM. La migration
du cast `encrypted` est reversible et testee sur une base avec des secrets
existants.
