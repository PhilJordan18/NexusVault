# ADR-004 - Workflow agentique multi-sessions et modele de branches

## Statut

Accepte.

## Contexte

La premiere version de NexusVault a ete codee par une seule personne, puis
reprise par des agents sans cadre. La revue securite du 10 novembre 2026 a
montre que le probleme n'etait pas la crypto mais la couche autour : sessions,
authentification, CI qui ne bloque rien, secrets qui trainent.

Le projet est desormais developpe par plusieurs sessions d'agents, Claude et
Codex, en parallele. Sans cadre, chaque session reinvente les regles, les
rapports se perdent entre fenetres, et rien n'empeche un push direct vers la
production.

## Decision

Quatre sessions avec des roles exclusifs : Architecture, qui a l'autorite et
ecrit les mandats ; Integration et Securite, qui possede la CI et a un droit
de veto ; UI-UX ; Developpeur, qui execute. Les roles sont decrits dans
`.nv/README.md` et portes par des skills partages.

Toute communication entre sessions passe par le dossier versionne `.nv/` :
board, mandats, rapports, preuves QA. Pas de mandat, pas de code.

Les skills vivent dans `.agents/skills/` pour etre lus par Codex, et
`.claude/skills/` est un lien symbolique vers ce dossier. Les agents Claude
sont des enveloppes qui chargent ces skills. `AGENTS.md` est la source de
verite, `CLAUDE.md` l'importe.

Modele de branches :

- `main` : production, PR depuis `dev` uniquement, revue Securite, fusion par
  le PM en rebase ;
- `dev` : integration, historique lineaire, push uniquement via `nv-deliver` ;
- `nv/<role>/M-xxx-slug` : branches de travail, closes apres livraison.

Les regles sont appliquees sur trois couches : rulesets GitHub, hooks git
versionnes dans `.githooks/`, hooks Claude Code dans `.claude/hooks/`. Seule la
premiere couche est une barriere reelle, les deux autres guident des agents
cooperatifs et donnent un retour immediat.

## Alternatives considerees

Une seule session qui fait tout : plus simple, mais aucun contre-pouvoir sur
un produit ou une faille coute la confiance des utilisateurs.

Des PR par mandat vers `dev` au lieu de `nv-deliver` : plus standard, mais
multiplie les merges et les revues de forme ; la revue Securite se fait sur le
rapport de livraison et sur `dev`, et la PR unique `dev` vers `main` reste.

Des hooks seulement cote Claude : ne couvre pas Codex ni les humains ; les
hooks git versionnes couvrent les trois.

## Consequences

Le socle est dans le depot : `.nv/`, `.agents/skills/`, `.claude/`,
`.githooks/`, `AGENTS.md`. Chaque machine lance `.nv/bin/nv-setup` une fois.

Le PM garde trois actions humaines : rotation des secrets, rulesets GitHub,
creation de `dev`. Tant que `dev` et ses rulesets n'existent pas, aucune
livraison n'est possible.

Les workflows CI actuels sont remplaces par le mandat M-001.
