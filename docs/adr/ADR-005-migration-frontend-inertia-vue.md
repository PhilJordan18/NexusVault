# ADR-005 - Migration du frontend vers Inertia Vue 3

## Statut

Accepte.

## Contexte

Le frontend compte quarante-huit vues Blade et une page Vue. Les outils du
projet, Vite, Wayfinder, Tailwind 4, ESLint, vue-tsc et le bloc Laravel Boost,
sont deja configures pour Inertia Vue 3. L'etat hybride double les
conventions, complique les tests navigateur et ralentit toute evolution UI.

La crypto zero-knowledge tourne dans le navigateur. Chaque ecran qui touche la
cle de coffre est un point sensible : la migration doit etre ordonnee, pas
opportuniste.

## Decision

Le frontend cible est Inertia Vue 3 avec TypeScript. Tout nouvel ecran se
fait en Vue sous `resources/js/pages`. Les vues Blade existantes migrent par
lots, selon le plan produit par le mandat M-003, en commencant par les ecrans
sans crypto : pages legales, auth, settings, admin.

Les ecrans qui manipulent la cle de coffre, l'unlock, les items et le partage
migrent en dernier, chacun sous un mandat co-signe par la session Securite,
avec des tests Vitest sur le code WebCrypto et un parcours Playwright.

Conventions :

- routes via Wayfinder, jamais d'URL en dur ;
- aucun `v-html` sur une donnee utilisateur ;
- props deferees avec etat de chargement ;
- layouts partages dans `resources/js/layouts`, composants dans
  `resources/js/components` ;
- traductions via `lang/` exposees aux pages.

## Alternatives considerees

Rester en Blade et retirer Vue : coherent, mais a contre-courant de
l'outillage installe et moins adapte a un coffre interactif.

Migrer tout d'un coup : trop risque sur les ecrans crypto, et bloque les
correctifs securite du sprint 0.

## Consequences

Pendant la migration, les deux moteurs coexistent. Un ecran est soit Blade
soit Vue, jamais un melange.

Le mandat M-003 produit l'inventaire, l'ordre et la premiere page de
reference. Les mandats suivants sont decoupes par la session Architecture.

La documentation `docs/02` sera mise a jour quand le premier lot sera livre.
