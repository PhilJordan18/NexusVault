# .nv - espace de travail agentique de NexusVault

Ce dossier est la memoire partagee des sessions Claude et Codex qui travaillent
sur NexusVault. Tout ce qu'une session produit pour une autre session passe par
ici, jamais par copier-coller entre fenetres.

Il est versionne. Un gestionnaire de mots de passe a besoin d'une piste d'audit
sur qui a demande quoi, qui l'a livre, et sur quelles preuves.

## Arborescence

```text
.nv/
  README.md          ce fichier
  PROCEDURES.md      procedures humaines: lancer une session, rulesets GitHub, rotation
  board.json         etat des mandats et des actions humaines
  mandates/          un fichier par mandat, redige par la session Architecture
  reports/           rapports de revue, de livraison, d'audit
  qa/                preuves QA generees par nv-deliver (sha, commandes, resultats)
  journal.md         une ligne par livraison vers dev, ecrit par nv-deliver
  templates/         gabarits de mandat, de rapport et de preuve QA
  bin/               scripts nv-setup, nv-deliver, nv-board
  worktrees/         ignore par git, worktrees locaux des sessions
```

## Les quatre sessions

| Role | Variable `NV_ROLE` | Autorite | Ne fait jamais |
| --- | --- | --- | --- |
| Architecture | `architect` | Vision, mandats, ADR, board, validation des livraisons | Coder, livrer autre chose que de la gouvernance |
| Integration et Securite | `secops` | CI, rulesets, threat model, revue de chaque livraison, veto | Coder des fonctionnalites |
| UI-UX | `ui` | Design system, maquettes, revue visuelle, front sur sa branche | Pousser vers `dev` sans mandat |
| Developpeur | `dev` | Execution des mandats, tests, livraison via `nv-deliver` | Modifier CI, hooks, rulesets |

Le role est annonce au lancement de la session par la variable d'environnement
`NV_ROLE`, ou par `.nv/bin/nv-role <role>` dans une session cloud de l'app
Claude, ou la variable ne peut pas etre posee. Sans declaration, les gardes
appliquent le role `dev`, le plus restreint.

Dans une session cloud, la branche attribuee par la plateforme, `claude/...`
ou `codex/...`, remplace la branche `nv/<role>/M-xxx-slug`. `nv-deliver`
l'accepte. Un hook SessionStart lance `nv-setup` a chaque demarrage.

## Cycle d'un mandat

```text
Architecture ecrit .nv/mandates/M-xxx-*.md  (status: ready)
        |
Developpeur cree nv/dev/M-xxx-slug depuis origin/dev  (status: in_progress)
        |
Developpeur implemente, teste, redige .nv/reports/M-xxx-livraison.md
        |
Developpeur lance .nv/bin/nv-deliver M-xxx  (checks, preuve QA, trailer, push dev)
        |
Securite revoit la livraison, ecrit son avis dans le rapport  (status: delivered -> reviewed)
        |
Architecture accepte ou rejette  (status: accepted | rejected)
```

Un mandat `rejected` revient a son owner : il le repasse `in_progress` via
`nv-board`, corrige sur une branche repartie de `origin/dev`, et relivre avec
`nv-deliver M-xxx`. Le rapport garde l'historique des avis, section apres
section.

Les identifiants sont `M-001`, `M-002`, etc. pour les mandats et `H-001`, etc.
pour les actions qui reviennent a un humain.

## Trois livraisons vers dev, un seul script

| Commande | Qui | Ce que les commits peuvent toucher | Checks | Board |
| --- | --- | --- | --- | --- |
| `nv-deliver M-xxx` | owner du mandat (`dev`, `ui`, `secops`) | tout, le commit de livraison seulement `.nv/` | `composer run ci:check`, `npm run build`, preuve `.nv/qa/` | `in_progress` vers `delivered` |
| `nv-deliver --review M-xxx` | `secops` | `.nv/reports/`, `docs/` | verdict `approved` ou `changes_requested` dans le rapport | `delivered` vers `reviewed` |
| `nv-deliver --governance <slug>` | `architect` | `.nv/` sauf `qa/`, `docs/`, `.claude/` sauf `hooks/` et `settings.json`, `.agents/`, `AGENTS.md`, `CLAUDE.md`, `README.md`, `.gitignore` ; jamais `.githooks/` | tests des hooks Claude et git | aucun changement de statut automatique |

Un push vers `dev` est valide par la politique deja sur `dev`
(`policy_at`), pas par celle de l'arbre de travail : une livraison ne peut
pas assouplir les regles qui la valident. Les gardes eux-memes
(`.githooks/`, `.claude/hooks/`, `.claude/settings.json`) ne passent jamais
par la gouvernance : ils changent par un mandat secops.

Chaque livraison se termine par un commit `chore(nv): <sorte> <ref>` qui porte
le trailer `NV-Deliver`, `NV-Review` ou `NV-Governance`, et une ligne dans
`.nv/journal.md`. La politique est dans `.claude/hooks/delivery.py`, lue par
`nv-deliver` et par le hook `pre-push` via `check_delivery.py`.

## Branches

- `main` : production. Personne ne pousse. Une PR depuis `dev`, revue par la
  session Securite, fusionnee par le PM en fast-forward.
- `dev` : integration. Historique lineaire, pas de commit de merge, pas de
  force push. Seul `nv-deliver` y pousse.
- `nv/<role>/<M-xxx-slug>` : branche de travail d'une session. Une branche
  livree est close, on n'y revient pas.

## Ce qui protege quoi

1. Rulesets GitHub sur `main` et `dev` : la seule barriere qui tient sans outil.
2. Hooks git dans `.githooks/` : s'appliquent a Claude, Codex et aux humains.
3. Hooks Claude Code dans `.claude/hooks/` : confort et garde de role.

`nv-setup` active les couches 2 et 3. La couche 1 se configure dans GitHub,
voir `PROCEDURES.md`.
