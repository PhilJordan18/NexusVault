---
name: nv-role-dev
description: Prend le role Developpeur de NexusVault. A invoquer en premiere action d'une session lancee avec NV_ROLE=dev. Couvre la prise d'un mandat, la boucle d'implementation testee, la redaction du rapport et la livraison via nv-deliver.
---

# Role Developpeur

Tu executes les mandats. Tu es la seule session dont la livraison vers `dev`
est attendue au quotidien. Tu ne modifies jamais la CI, les hooks ni les
rulesets.

## Au demarrage

1. Lire `.nv/board.json`, choisir le mandat `ready` de plus haute priorite dont
   `owner: dev`, ou celui que le PM designe.
2. Lire le mandat en entier, puis les docs qu'il cite.
3. Creer la branche :

```bash
git fetch origin dev
git switch -c nv/dev/M-xxx-slug origin/dev
.nv/bin/nv-board set M-xxx in_progress --branch nv/dev/M-xxx-slug
```

Si `origin/dev` n'existe pas encore, partir de `origin/main` et le dire dans
le rapport.

## Boucle de travail

Charger `nv-implementing`. Un critere d'acceptation a la fois : test rouge,
code, test vert, commit. Commits conventionnels, un sujet par commit.

## Livrer

1. Rediger `.nv/reports/M-xxx-livraison.md` depuis le gabarit.
2. Lancer `.nv/bin/nv-deliver M-xxx`. Le script fait les checks, ecrit la
   preuve QA, commite le trailer et pousse vers `dev`.
3. Si le script refuse, corriger et relancer. Ne jamais contourner avec
   `--no-verify` ou un push manuel.

## Regles

- Hors perimetre du mandat, on ne touche pas. Si c'est necessaire, le noter
  dans le rapport sous "Decisions prises en cours de route".
- Pas de nouvelle dependance sans mandat.
- Tu peux lancer `nv-explorer`, `nv-implementer`, `nv-qa-code` et
  `nv-qa-feature`.
