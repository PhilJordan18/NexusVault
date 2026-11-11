---
id: M-008
title: Gardes de livraison, hooks hors gouvernance et trailers reserves
owner: secops
status: ready
author: architect
created: 2026-11-11
branch: nv/secops/M-008-gardes-de-livraison
depends_on: [M-005]
---

# M-008 - Gardes de livraison, hooks hors gouvernance et trailers reserves

## Contexte

La livraison de gouvernance `socle-livraisons` introduit trois sortes de
livraison vers `dev` (ADR-004, amendement du 2026-11-11). L'avis Securite
rendu sur la branche est favorable avec une reserve : la politique laisse
Architecture livrer `.githooks/`, `.claude/hooks/` et `.claude/settings.json`
avec pour seul controle les tests que ces hooks portent eux-memes. Une session
ne doit pas livrer seule les regles qui la controlent.

Le correctif ne peut pas voyager dans `socle-livraisons` : il refuserait la
plage qui le porte, puisque celle-ci touche les gardes. `socle-livraisons`
est donc l'exception fondatrice, revue par Securite sur la branche avant la
livraison, et ce mandat ferme la porte juste derriere.

Trois fichiers de `.claude/hooks/` attendent par ailleurs un patch que la
session Architecture n'a pas pu appliquer (auto-modification des gardes
refusee par son environnement) : `guard_bash.py`, `commit_rules.py` et
`guard_workflow_files.py`. Ce mandat le porte.

## Objectif

Aucune livraison de gouvernance ne peut modifier un garde, et les gardes
Claude connaissent les trois sortes de livraison et leurs roles.

## Perimetre

1. `.claude/hooks/delivery.py` : `forbidden_paths` de `governance` passe de
   `(".nv/qa/",)` a `(".nv/qa/", ".githooks/", ".claude/hooks/",
   ".claude/settings.json")`. `.claude/skills/`, `.claude/agents/` et
   `.agents/` restent livrables par Architecture.
2. `.claude/hooks/guard_bash.py` : la regle « architect ne livre pas vers
   dev » devient `check_deliver(args, role)` : `--governance` reserve a
   `architect`, `--review` reserve a `secops`, un mandat nu refuse a
   `architect`.
3. `.claude/hooks/commit_rules.py` : `DELIVER_TRAILER_RE` couvre
   `NV-Deliver`, `NV-Review` et `NV-Governance`, message « les trailers
   NV-Deliver, NV-Review et NV-Governance sont reserves au script
   nv-deliver ». Meme regle dans le repli bash de `.githooks/commit-msg`.
4. `.claude/hooks/guard_workflow_files.py` : `.nv/journal.md` ecrit
   uniquement par `nv-deliver`.
5. `.githooks/tests/test_githooks.sh` : le scenario « NV-Review sans
   environnement » redevient un refus strict, plus un WARN ; nouveau
   scenario : une livraison de gouvernance touchant `.githooks/` ou
   `.claude/hooks/` est refusee.
6. Documentation alignee : table de `.nv/README.md`, skills `nv-deliver` et
   `nv-role-architect`, amendement de l'ADR-004.

## Hors perimetre

- toute modification de `nv-deliver` au-dela de ce que la politique impose ;
- les rulesets GitHub (H-002) ;
- le code applicatif.

## Criteres d'acceptation

- [ ] tests rouges d'abord : `test_paths_by_kind` refuse `.githooks/pre-push`,
      `.claude/hooks/guard_bash.py` et `.claude/settings.json` en
      gouvernance ; `test_deliver_kinds_by_role` ; trailers `NV-Review` et
      `NV-Governance` refuses hors `nv-deliver` ; edition de `.nv/journal.md`
      refusee ; puis verts ;
- [ ] `python3 -m unittest discover -s .claude/hooks/tests` et
      `bash .githooks/tests/test_githooks.sh` verts, sans WARN ;
- [ ] depuis une branche de gouvernance touchant `.claude/hooks/`,
      `nv-deliver --governance x` refuse avant tout rebase ;
- [ ] `composer run ci:check` vert, rien d'applicatif dans le diff.

## Exigences QA

- preuve `.nv/qa/M-008.json` ecrite par `nv-deliver` ;
- sortie des deux suites de tests dans le rapport.

## Contraintes securite

Ce mandat modifie les gardes : Architecture valide sur le diff complet, pas
sur le rapport seul. Aucun chemin d'exception, aucune variable
d'environnement qui contourne la nouvelle regle.
