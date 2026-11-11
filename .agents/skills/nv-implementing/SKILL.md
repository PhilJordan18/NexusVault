---
name: nv-implementing
description: Boucle d'implementation NexusVault pour un mandat : test en premier, tests cibles Pest, conventions Laravel et TypeScript du projet, commits conventionnels. A charger avant d'ecrire du code applicatif.
---

# Implementer un mandat

## Boucle

Pour chaque critere d'acceptation :

1. Ecrire le test Pest qui decrit le comportement attendu. Le voir rouge.
2. Implementer le minimum. Le voir vert.
3. Lancer les tests cibles puis le format :

```bash
php artisan test --compact --filter=NomDuTest
vendor/bin/pest --dirty
vendor/bin/pint --dirty --format agent
```

4. Commiter : `type(scope): sujet a l'imperatif`, sujet de 72 caracteres au
   plus. Types : feat, fix, security, refactor, test, docs, style, perf,
   build, ci, chore, revert.

Avant la livraison, la suite complete :

```bash
composer run ci:check
```

## Conventions du projet

- Suivre le bloc Laravel Boost de `AGENTS.md` : services dans `app/Services`,
  requests dans `app/Http/Requests`, DTO dans `app/DTOs`, types explicites,
  PHPDoc avec array shapes.
- Activer les skills Boost `laravel-best-practices` et `pest-testing`, et pour
  le front `inertia-vue-development`.
- Utiliser `search-docs` avant d'utiliser une API Laravel ou Inertia dont on
  n'est pas sur.
- Factories pour toute donnee de test. Pas de tinker, pas de script de
  verification jetable.
- Pas de commentaire inline sauf logique vraiment complexe. Le PHPDoc explique
  l'intention.

## Securite, toujours

- Jamais de secret, de cle ou de mot de passe en clair cote serveur, en log ou
  en session, au-dela de ce que `docs/04` autorise.
- Toute route nouvelle passe par les middlewares de la route voisine la plus
  proche, au minimum `auth`, `verified`, `mfa`, et `vault.unlocked` si elle
  touche le coffre.
- Les messages d'erreur ne distinguent pas un compte inconnu d'un mauvais
  secret.
- Lire `.nv/reports/` pour ne pas reintroduire un constat deja corrige.
