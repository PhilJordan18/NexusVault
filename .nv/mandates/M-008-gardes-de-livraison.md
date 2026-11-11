---
id: M-008
title: Gardes Claude alignes sur les trois livraisons et trailers reserves
owner: secops
status: ready
author: architect
created: 2026-11-11
branch: nv/secops/M-008-gardes-de-livraison
depends_on: [M-005]
---

# M-008 - Gardes Claude alignes sur les trois livraisons et trailers reserves

## Contexte

Les livraisons de gouvernance `socle-livraisons` et
`gardes-hors-gouvernance` introduisent trois sortes de livraison vers `dev`
(ADR-004, amendement du 2026-11-11) et excluent les gardes du perimetre de
gouvernance. Depuis, `.githooks/`, `.claude/hooks/` et
`.claude/settings.json` ne changent que par un mandat porte par Securite,
revu comme du code. Ce mandat est le premier.

Trois fichiers de `.claude/hooks/` attendent un patch que la session
Architecture n'a pas pu appliquer (auto-modification des gardes refusee par
son environnement) : `guard_bash.py`, `commit_rules.py` et
`guard_workflow_files.py`. Les hooks git appliquent deja la nouvelle
politique ; seuls les gardes Claude sont en retard.

## Objectif

Les gardes Claude connaissent les trois sortes de livraison et leurs roles,
et les trailers de livraison ne peuvent pas etre poses a la main.

## Perimetre

1. `.claude/hooks/guard_bash.py` : la regle « architect ne livre pas vers
   dev » devient `check_deliver(args, role)` : `--governance` reserve a
   `architect`, `--review` reserve a `secops`, un mandat nu refuse a
   `architect`.
2. `.claude/hooks/commit_rules.py` : `DELIVER_TRAILER_RE` couvre
   `NV-Deliver`, `NV-Review` et `NV-Governance`, message « les trailers
   NV-Deliver, NV-Review et NV-Governance sont reserves au script
   nv-deliver ». Meme regle dans le repli bash de `.githooks/commit-msg`.
3. `.claude/hooks/guard_workflow_files.py` : `.nv/journal.md` ecrit
   uniquement par `nv-deliver`.
4. `.githooks/tests/test_githooks.sh` : le scenario « NV-Review sans
   environnement » redevient un refus strict, plus un WARN.

## Hors perimetre

- toute modification de `nv-deliver` au-dela de ce que la politique impose ;
- les rulesets GitHub (H-002) ;
- le code applicatif.

## Criteres d'acceptation

- [ ] tests rouges d'abord : `test_deliver_kinds_by_role` ; trailers
      `NV-Review` et `NV-Governance` refuses hors `nv-deliver` ; edition de
      `.nv/journal.md` refusee ; puis verts ;
- [ ] `python3 -m unittest discover -s .claude/hooks/tests` et
      `bash .githooks/tests/test_githooks.sh` verts, sans WARN ;
- [ ] `composer run ci:check` vert, rien d'applicatif dans le diff.

## Exigences QA

- preuve `.nv/qa/M-008.json` ecrite par `nv-deliver` ;
- sortie des deux suites de tests dans le rapport.

## Contraintes securite

Ce mandat modifie les gardes : Architecture valide sur le diff complet, pas
sur le rapport seul. Aucun chemin d'exception, aucune variable
d'environnement qui contourne une regle existante.
