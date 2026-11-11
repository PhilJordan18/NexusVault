---
mandate: M-005
author: secops
branch: nv/secops/M-005-ci-wayfinder-gitleaks
head: 280da2c
created: 2026-11-11
review: pending
---

# Livraison M-005 - CI de dev verte : Wayfinder en CI et allowlist gitleaks

## Ce qui a ete fait

Deux commits, aucun fichier applicatif ni test touche.

- **Routes Wayfinder generees par `ci:check`** (`280da2c`). Le script
  composer `ci:check` lance `php artisan wayfinder:generate` avant
  `npm run types:check`. `resources/js/routes` est ignore par git ; depuis
  M-003 les pages Inertia importent `@/routes/...`, donc un clone propre et
  la CI echouaient sur `types:check` (runs 42944838872 et 42946331380). La
  CI appelle deja `composer run ci:check` apres `php artisan key:generate`,
  aucune modification de `ci.yml` n'est necessaire : local et CI executent
  exactement la meme commande.
- **Allowlist gitleaks nominative** (`84ad658`). `.gitleaks.toml` a la
  racine, lu automatiquement par gitleaks-action. Il etend les regles par
  defaut et ajoute, sur la regle `generic-api-key` qui se declenchait, une
  allowlist `condition = "AND"` entre le chemin `^tests/.*\.php$` et les deux
  valeurs exactes de fixture TOTP des tests Pest. Rien d'autre n'est tolere.

## Decisions prises en cours de route

- **Allowlist au niveau de la regle, pas globale.** gitleaks-action v3.0.0
  installe gitleaks 8.24.3. Dans cette version, `config.go` ne connait qu'un
  `[allowlist]` global singulier (sans `condition`) et ignore un
  `[[allowlists]]` global ; en revanche `extend()` fusionne les allowlists
  d'une regle de meme id (`baseRule.Allowlists = append(...)`). La regle
  `[[rules]] id = "generic-api-key"` sans regex ne redefinit rien, elle ne
  porte que l'exception.
- **Version locale differente.** Homebrew installe gitleaks 8.30.1, dont les
  regles par defaut ne signalent plus ces fixtures. Pour reproduire la CI,
  la configuration par defaut de la version 8.24.3 a ete recuperee depuis le
  depot gitleaks (`config/gitleaks.toml@v8.24.3`, 208 regles) et utilisee
  comme base d'extension (`[extend] path = ...`) avec le moteur 8.30.1.
- **Mandat.** Les hooks interdisent a secops d'ecrire un mandat ou d'ajouter
  une entree au board. Le texte du mandat a ete envoye a la session
  Architecture ; le PM a autorise le travail le 2026-11-11. La livraison
  via `nv-deliver` attend que M-005 soit `in_progress` au board.

## Tests

```
composer run ci:check depuis un arbre sans resources/js/routes
  -> wayfinder:generate regenere le dossier, types:check vert,
     116 tests Pest verts (754 assertions)
npm run build                                        -> vert
python3 -m unittest discover -s .claude/hooks/tests  -> OK

gitleaks (moteur 8.30.1, regles 8.24.3 via extend path)
  plage M-002  94631ac..0ff5744  sans allowlist -> leaks found: 7 (= CI)
  plage M-002  94631ac..0ff5744  avec allowlist -> no leaks found
  plage M-002b 0ff5744..8ddb010  sans allowlist -> leaks found: 1
  plage M-002b 0ff5744..8ddb010  avec allowlist -> no leaks found
  meme valeur dans un fichier hors tests/       -> leaks found: 1 (detectee)
  autre valeur a forte entropie sous tests/     -> leaks found: 1 (detectee)
  .gitleaks.toml avec useDefault sur 8.30.1     -> parse, no leaks found
```

La preuve machine sera dans `.nv/qa/M-005.json` a la livraison. Le run
GitHub Actions sur `dev` sera ajoute ici apres livraison.

## Points d'attention pour la revue securite

1. L'allowlist ne couvre que deux valeurs litterales sous `tests/`. Toute
   nouvelle fixture a forte entropie fera echouer la CI : c'est voulu, la
   valeur doit etre ajoutee nommement.
2. `wayfinder:generate` s'execute avant les tests dans `ci:check` ; il a
   besoin d'une application bootable (`APP_KEY`), ce que la CI prepare deja
   a l'etape « Prepare test environment ».
3. Si gitleaks-action change de version par Dependabot, verifier que la
   fusion des allowlists de regle est conservee (elle l'est en 8.30.1).

## Avis Securite

Auto-livraison secops : la revue revient a la session Architecture.

## Decision Architecture

A remplir par la session architect. `accepted | rejected` et motif.
