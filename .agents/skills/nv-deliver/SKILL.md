---
name: nv-deliver
description: Livrer un mandat NexusVault vers la branche dev avec le script nv-deliver. A utiliser quand le travail d'un mandat est termine et que le rapport de livraison est redige. Explique les prerequis, ce que le script verifie et quoi faire en cas de refus.
---

# Livrer avec nv-deliver

```bash
.nv/bin/nv-deliver M-xxx
```

## Prerequis

- branche courante `nv/<role>/M-xxx-slug`, arbre de travail propre;
- mandat `in_progress` dans le board;
- rapport `.nv/reports/M-xxx-livraison.md` redige;
- `origin/dev` existe.

## Ce que le script fait, dans l'ordre

1. `git fetch origin dev`, puis rebase local de la branche sur `origin/dev`.
   C'est le seul rebase autorise : il ne reecrit que des commits qui ne sont
   pas encore sur `dev`.
2. Refuse tout commit de merge dans la plage a livrer.
3. Lance `composer run ci:check` puis `npm run build`.
4. Ecrit `.nv/qa/M-xxx.json` avec le sha, les commandes et les codes de retour.
5. Passe le mandat a `delivered` dans le board.
6. Commite `chore(nv): deliver M-xxx` avec le trailer `NV-Deliver: M-xxx`.
7. Pousse `HEAD:dev` puis la branche de travail.

Le hook git `pre-push` verifie de son cote le trailer, la preuve et l'absence
de merge. Les deux doivent etre d'accord.

## En cas de refus

- checks rouges : corriger, commiter, relancer;
- conflit de rebase : resoudre, `git rebase --continue`, relancer;
- rapport manquant : le rediger depuis `.nv/templates/rapport-livraison.md`.

Ne jamais pousser `dev` a la main. Ne jamais utiliser `--no-verify`.
