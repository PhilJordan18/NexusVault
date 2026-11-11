---
id: M-001
title: Garde-fous GitHub et CI unique avec gates securite
owner: secops
status: ready
author: architect
created: 2026-11-11
branch: nv/secops/M-001-ci-et-garde-fous
depends_on: [H-003]
---

# M-001 - Garde-fous GitHub et CI unique avec gates securite

## Contexte

Les deux workflows actuels ciblent des branches inexistantes, le job lint lance
des commandes qui corrigent au lieu de verifier, et il detient une permission
d'ecriture inutile. Aucun audit de dependances ni scan de secrets. Voir F2 du
rapport `.nv/reports/2026-11-10-revue-securite.md` et ADR-004.

## Objectif

Une PR vers `dev` ou `main` ne peut pas etre verte si le style, les types, les
tests, le build ou un audit echouent.

## Perimetre

- remplacer `lint.yml` et `tests.yml` par un workflow unique `ci.yml` dont le
  job s'appelle `ci`, declenche sur push `dev` et PR vers `dev` et `main`;
- `permissions: contents: read` partout;
- etapes : `composer run ci:check`, `npm run build`, `composer audit`,
  `npm audit --omit=dev --audit-level=high`, scan de secrets gitleaks;
- tests Pest avec couverture sur `app/Services/Security`,
  `app/Services/Auth`, `app/Services/Vault`;
- ajouter un test d'architecture Pest avec le preset securite;
- `dependabot.yml` pour composer, npm et github-actions, hebdomadaire;
- matrice PHP alignee sur `composer.json`, 8.3 minimum et 8.5 cible.

## Hors perimetre

- corriger les failles applicatives, c'est M-002;
- Playwright et Vitest, mandat separe apres M-003.

## Criteres d'acceptation

- [ ] un seul workflow, un job `ci`, requis par les rulesets;
- [ ] un commit qui casse le format echoue en CI;
- [ ] un commit qui ajoute un secret factice echoue en CI;
- [ ] `composer audit` et `npm audit` executes, seuil documente;
- [ ] le preset securite Pest passe ou ses exclusions sont justifiees dans le rapport.

## Exigences QA

- lien vers un run GitHub Actions vert sur la branche du mandat;
- lien vers un run rouge provoque volontairement puis corrige.

## Contraintes securite

Aucun secret dans le workflow. Aucune action tierce non epinglee par SHA.
