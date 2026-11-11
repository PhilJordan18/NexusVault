---
id: M-002
title: Sprint 0 securite : contournements MFA et re-authentification
owner: dev
status: ready
author: architect
created: 2026-11-11
branch: nv/dev/M-002-sprint-0-securite
depends_on: []
---

# M-002 - Sprint 0 securite

## Contexte

La revue du 10 novembre confirme deux contournements MFA et plusieurs actions
destructrices sans re-authentification. Voir C1, C2, E1, E3, M3, M4 et M8 dans
`.nv/reports/2026-11-10-revue-securite.md`.

## Objectif

Aucun des contournements C1 et C2 n'est reproductible et chaque correctif est
couvert par un test Pest qui echouait avant.

## Perimetre

- C1 : middleware `mfa` sur setup et qr-code, secret temporaire en session
  jusqu'a la verification, jamais de re-affichage d'un secret confirme;
- C2 : purge des drapeaux de session avant tout nouveau login, invalidation
  complete si un autre utilisateur etait connecte, middleware `guest` sur les
  routes de login;
- E1 : retirer `stateless()` du flux OAuth et corriger la cause session/cookie;
- E3 : confirmation par mot de passe ou TOTP frais avant suppression de
  compte, desactivation MFA et reset du coffre;
- M3 : une seule politique de mot de passe via `Password::defaults()`;
- M4 : reponses et temps uniformes sur reset, register validate et login;
- M8 : fenetre TOTP ramenee a 1.

## Hors perimetre

- CSP, HSTS, SRI, c'est le sprint 1;
- OAuth auto-liaison E2 et passkeys M8 complet, sprint 2;
- tout changement de format crypto.

## Criteres d'acceptation

- [ ] un test par constat C1, C2, E1, E3, M3, M4, M8 ecrit en premier et rouge;
- [ ] tous verts apres correctif, suite complete verte;
- [ ] aucune route existante supprimee sans note dans le rapport;
- [ ] docs `03-authentification-sessions-et-acces.md` mise a jour.

## Exigences QA

- `composer run ci:check` vert;
- `php artisan test --compact --filter=Mfa` et `--filter=Login` nommes dans la preuve.

## Contraintes securite

Pas de nouveau stockage de secret en session. Les messages d'erreur ne doivent
pas distinguer un compte inconnu d'un mauvais mot de passe.
