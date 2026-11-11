---
name: nv-deliver
description: Livrer vers la branche dev de NexusVault avec le script nv-deliver, qu'il s'agisse d'un mandat, d'un avis securite ou d'une livraison de gouvernance. A utiliser quand le travail est termine et le rapport redige. Explique les prerequis, ce que le script verifie et quoi faire en cas de refus.
---

# Livrer avec nv-deliver

```bash
.nv/bin/nv-deliver M-xxx                 # mandat, par son owner (dev, ui, secops)
.nv/bin/nv-deliver --review M-xxx        # avis securite, par secops
.nv/bin/nv-deliver --governance <slug>   # mandats, ADR, board, socle, par architect
```

## Prerequis communs

- branche courante `nv/<role>/<sujet>` ou la branche attribuee a une session
  cloud, arbre de travail propre;
- `origin/dev` existe;
- le role courant est celui de la sorte de livraison.

## Mandat : `nv-deliver M-xxx`

- mandat `in_progress` dans le board, rapport `.nv/reports/M-xxx-livraison.md`
  redige;
- le script rebase sur `origin/dev`, refuse tout commit de merge, lance
  `composer run ci:check` puis `npm run build`, ecrit `.nv/qa/M-xxx.json`,
  passe le mandat a `delivered`, commite `chore(nv): deliver M-xxx` avec le
  trailer `NV-Deliver: M-xxx`, pousse `HEAD:dev` puis la branche de travail.

## Avis securite : `nv-deliver --review M-xxx`

- mandat `delivered`, frontmatter du rapport a `review: approved` ou
  `review: changes_requested`, avis redige dans la section Avis Securite;
- les commits a livrer ne touchent que `.nv/reports/` et `docs/`;
- le script passe le mandat a `reviewed` et commite `chore(nv): review M-xxx`
  avec le trailer `NV-Review: M-xxx`. Un `changes_requested` est livre comme
  un `approved` : c'est Architecture qui rejette ensuite.

## Gouvernance : `nv-deliver --governance <slug>`

- slug en minuscules et tirets, par exemple `mandats-sprint-1`;
- les commits a livrer ne touchent que `.nv/` (sauf `qa/`), `docs/`,
  `.claude/`, `.agents/`, `.githooks/`, `AGENTS.md`, `CLAUDE.md`, `README.md`
  et `.gitignore`;
- le script lance les tests des hooks Claude et des hooks git, puis commite
  `chore(nv): governance <slug>` avec le trailer `NV-Governance: <slug>`.

## Ce que le hook pre-push revalide

Le trailer, les chemins touches par la plage et par le commit de livraison,
la preuve QA et le rapport pour un mandat, le verdict pour un avis. La
politique est dans `.claude/hooks/delivery.py`. Le script et le hook doivent
etre d'accord.

## En cas de refus

- checks rouges : corriger, commiter, relancer;
- conflit de rebase : resoudre, `git rebase --continue`, relancer;
- rapport manquant : le rediger depuis `.nv/templates/rapport-livraison.md`;
- chemin interdit pour la sorte de livraison : le retirer de la branche, ou
  demander a Architecture un mandat.

Ne jamais pousser `dev` a la main. Ne jamais utiliser `--no-verify`.
