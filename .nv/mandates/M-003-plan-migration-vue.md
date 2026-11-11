---
id: M-003
title: Plan de migration du frontend vers Inertia Vue 3
owner: ui
status: ready
author: architect
created: 2026-11-11
branch: nv/ui/M-003-plan-migration-vue
depends_on: []
---

# M-003 - Plan de migration du frontend vers Inertia Vue 3

## Contexte

Le frontend compte quarante-huit vues Blade et une page Vue. ADR-005 acte la
migration vers Inertia Vue 3. Avant de coder, il faut un plan que la session
Architecture puisse decouper en mandats pour la session Developpeur.

## Objectif

Un plan de migration ordonne, avec un design system minimal, livre sous forme
de rapport et d'une premiere page migree qui sert de reference.

## Perimetre

- inventaire des vues Blade par domaine : auth, dashboard, admin, legal,
  settings, partage;
- ordre de migration propose, en commencant par les ecrans sans crypto;
- conventions : dossiers `resources/js/pages`, layouts, composants partages,
  Wayfinder pour les routes, Tailwind 4;
- regles d'accessibilite et de responsive a imposer;
- une page de reference migree, par exemple les pages legales;
- liste des points ou la crypto navigateur touche le DOM, a traiter avec la
  session Securite.

## Hors perimetre

- migrer les ecrans du coffre ou de l'unlock, ils attendent la revue Securite;
- changer la charte visuelle.

## Criteres d'acceptation

- [ ] rapport `.nv/reports/M-003-livraison.md` avec inventaire et ordre;
- [ ] page de reference en Vue, lint, format et types verts;
- [ ] aucune regression sur les tests feature des pages migrees.

## Exigences QA

- `composer run ci:check` vert;
- captures avant et apres de la page de reference jointes au rapport.

## Contraintes securite

Pas de `v-html` sur des donnees utilisateur. Toute page qui manipule la cle de
coffre reste hors de ce mandat.
