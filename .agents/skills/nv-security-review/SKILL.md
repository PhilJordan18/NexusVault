---
name: nv-security-review
description: Checklist de revue securite NexusVault pour une livraison ou un diff. A charger par la session Securite ou l'agent nv-pentester avant de donner un avis approved ou changes_requested.
---

# Revue securite d'une livraison

Lire d'abord le mandat, puis le diff complet `origin/dev...HEAD` de la branche
ou la plage livree. Pour chaque point, chercher le scenario d'abus, pas la
conformite de forme.

## Authentification et session

- Chaque route touchee porte `auth`, `verified`, `mfa` et `vault.unlocked`
  quand la voisine les porte.
- Aucun drapeau de session ne survit a un login, un logout ou un changement
  d'utilisateur.
- Les actions destructrices exigent une re-authentification fraiche.
- Les reponses ne permettent pas d'enumerer des comptes, ni par message ni par
  temps de reponse.
- Throttling present et par compte quand c'est possible.

## Zero-knowledge

- Le serveur ne recoit jamais vault password, recovery key, vault key ou
  private key en clair. Verifier les payloads des requetes et les logs.
- Tout nouveau champ chiffre suit le format de `docs/04` et porte IV, tag et
  version.
- Aucun accesseur Eloquent ne dechiffre cote serveur pour un utilisateur
  moderne.
- Le DOM ne recoit pas de secret via `v-html`, `{!! !!}` ou un attribut.

## Autorisation

- Chaque acces a un item, un partage, une passkey ou une session verifie la
  propriete. Chercher les requetes par id sans filtre utilisateur.
- Les routes admin verifient le role et sont journalisees.

## Dependances et configuration

- Pas de nouvelle dependance sans mandat. `composer audit` et `npm audit`
  propres.
- Pas de secret, de `.env`, de `.DS_Store` ni de build dans le diff.
- Les workflows n'ont que `contents: read` et des actions epinglees.

## Tests

- Chaque constat corrige a un test qui echouait avant.
- La preuve `.nv/qa/M-xxx.json` porte le sha livre.

## Rendu

Dans le rapport de livraison, section "Avis Securite" :

- `approved` avec les points verifies;
- ou `changes_requested` avec fichier, ligne, scenario d'abus et correctif
  attendu pour chaque point.

Mettre a jour `review:` dans le frontmatter.
