---
name: nv-board
description: Lire et mettre a jour le board .nv/board.json de NexusVault avec le script nv-board. A utiliser pour connaitre l'etat des mandats, changer un statut, attacher une branche ou lister les actions humaines.
---

# Board NexusVault

Le board est `.nv/board.json`. On ne l'edite pas a la main, on passe par le
script pour garder le format et la date de mise a jour.

```bash
.nv/bin/nv-board list                       # tableau des mandats et actions
.nv/bin/nv-board show M-002                 # detail d'une entree
.nv/bin/nv-board set M-002 in_progress --branch nv/dev/M-002-sprint-0-securite
.nv/bin/nv-board set H-003 done
.nv/bin/nv-board add M-004 "Titre" --owner dev --file mandates/M-004-slug.md
```

## Statuts

`draft` en redaction, `ready` prenable, `in_progress` branche ouverte,
`delivered` pousse sur dev, `reviewed` avis Securite ecrit, `accepted` ou
`rejected` par Architecture, `done` fusionne dans main.

## Qui change quoi

- Architecture : tout.
- Securite : `delivered` vers `reviewed`.
- Developpeur et UI : `ready` vers `in_progress` sur leurs mandats. Le passage
  a `delivered` est fait par `nv-deliver`.
