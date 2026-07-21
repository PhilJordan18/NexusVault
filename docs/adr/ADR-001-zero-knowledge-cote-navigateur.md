# ADR-001 - Chiffrement zero-knowledge cote navigateur

## Statut

Accepte.

## Contexte

La premiere version de NexusVault chiffrait surtout via une logique serveur. Cela
protegeait mieux qu'un stockage en clair, mais ce n'etait pas un vrai modele
zero-knowledge: si le serveur peut assembler une cle ou voir un secret pendant le
traitement, alors le serveur reste une partie de confiance trop puissante.

Un password manager doit limiter ce que le serveur sait.

## Decision

Les utilisateurs modernes chiffrent et dechiffrent les secrets du coffre dans le
navigateur avec WebCrypto.

Le serveur stocke:

- envelopes;
- ciphertexts;
- IVs;
- tags;
- metadonnees;
- public keys;
- private keys chiffrees.

Le serveur ne recoit pas:

- vault password;
- recovery key;
- vault key en clair;
- private key en clair;
- champs de coffre en clair.

## Alternatives considerees

### Chiffrement serveur avec master key en session

Avantages:

- plus simple;
- facilite analyse serveur des passwords;
- facilite partage serveur.

Inconvenients:

- serveur peut voir/dechiffrer;
- moins aligné avec zero-knowledge;
- compromission serveur plus grave.

### Tout chiffrer avec le login password

Avantages:

- UX simple;
- un seul mot de passe.

Inconvenients:

- melange authentification et chiffrement;
- reset login password devient dangereux;
- OAuth/passkey deviennent difficiles;
- moins propre cryptographiquement.

### Vault password separe + WebCrypto

Avantages:

- meilleure separation des responsabilites;
- login password peut etre reset sans ouvrir le coffre;
- OAuth/passkey possibles sans connaitre le vault password;
- serveur passif/base volee ne donnent pas les secrets.

Inconvenients:

- UX plus complexe;
- recovery key necessaire;
- XSS devient critique;
- tests navigateur plus importants;
- certaines features serveur comme analyse password doivent etre deplacees cote
  navigateur.

## Consequences positives

- Le serveur stocke des donnees opaques pour les secrets.
- Le projet a une vraie histoire securite a expliquer.
- Les nouveaux OAuth users sont forces a creer un coffre client.
- Le partage peut utiliser public/private keys sans exposer la vault key.

## Risques et compromis

- Si le serveur sert du JS malveillant, le modele tombe.
- Si XSS pendant coffre ouvert, les secrets peuvent etre voles.
- Le serveur voit encore les metadonnees.
- Le code legacy doit etre supprime progressivement pour eviter confusion.

## Decisions associees

- ADR-002: partage synchronise via shared item key.
- `docs/04-modele-cryptographique-zero-knowledge.md`.
- `docs/05-cycle-de-vie-des-cles-et-recovery.md`.
