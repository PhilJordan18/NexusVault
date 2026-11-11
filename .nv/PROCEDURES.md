# Procedures

## Installer le socle sur une machine

```bash
.nv/bin/nv-setup
```

Le script active `core.hooksPath=.githooks`, verifie le lien
`.claude/skills -> ../.agents/skills`, verifie Python 3 et cree
`.nv/worktrees/`.

## Lancer une session avec son role

```bash
NV_ROLE=architect claude
NV_ROLE=secops claude
NV_ROLE=ui claude
NV_ROLE=dev claude
```

Avec Codex, la meme variable suffit pour les hooks git. Les skills partages
sont lus depuis `.agents/skills/`.

Premiere action de chaque session : invoquer le skill de son role, par exemple
`/nv-role-dev`, puis lire `.nv/board.json`.

## Rulesets GitHub a creer (action humaine H-002)

Depot public, donc rulesets disponibles gratuitement. Settings, Rules, Rulesets.

Ruleset `nv-main` sur `refs/heads/main` :

- Restrict deletions
- Block force pushes
- Require linear history
- Require a pull request before merging, 1 approbation, dismiss stale reviews
- Require status checks to pass : `ci` une fois le workflow unique en place
- Allowed merge methods : rebase uniquement
- Bypass list : vide

Ruleset `nv-dev` sur `refs/heads/dev` :

- Restrict deletions
- Block force pushes
- Require linear history
- Require status checks to pass : `ci`
- Pas de PR obligatoire, le push passe par `nv-deliver`

Activer aussi dans Settings, Code security : Dependabot alerts et updates,
Secret scanning et Push protection, CodeQL default setup.

## Creer la branche dev (action humaine H-003)

```bash
git fetch origin main
git push origin origin/main:refs/heads/dev
```

A faire avant le premier `nv-deliver`.

## Rotation des secrets (action humaine H-001)

Voir E6 dans `.nv/reports/2026-11-10-revue-securite.md`. Tout secret qui a
transite par l'ancien VPS ou par un chat est compromis : Google, GitHub,
Resend, base de donnees, `APP_KEY`, `APP_PEPPER`. Retirer les lignes
commentees du `.env` local qui ressemblent a un mot de passe de coffre ou a une
cle de recuperation.

## Promotion dev vers main

1. La session Architecture verifie que tous les mandats du lot sont `accepted`.
2. Le PM ouvre la PR `dev -> main`.
3. La session Securite fait la revue et approuve.
4. Le PM fusionne en rebase. Aucune session ne fusionne.
