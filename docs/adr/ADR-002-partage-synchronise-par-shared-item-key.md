# ADR-002 - Partage synchronise par shared item key

## Statut

Accepte.

## Contexte

Un partage par simple copie est plus facile: le owner chiffre une copie pour le
recipient, et le recipient garde cette copie. Mais ce modele ne permet pas une
bonne synchronisation. Si le owner modifie le mot de passe, la copie devient
ancienne.

L'objectif de NexusVault est que les services partages puissent communiquer:
quand le owner modifie un item partage synchronise, les recipients acceptes voient
le changement.

## Decision

Pour les partages synchronises, NexusVault utilise une shared item key:

1. une cle aleatoire est associee a l'item partage;
2. les champs sensibles de l'item sont chiffres avec cette cle;
3. la cle est chiffree pour chaque recipient avec sa public key RSA;
4. chaque utilisateur stocke une enveloppe de cette shared key sous sa propre
   vault key;
5. les edits propagent les nouveaux ciphertexts aux copies du meme
   `shared_group_id`.

## Alternatives considerees

### Copie independante

Avantages:

- simple;
- facile a accepter;
- pas besoin de synchronisation.

Inconvenients:

- la suppression/modification de l'original n'affecte pas la copie;
- controle owner plus faible;
- moins adapte au besoin "shared sync".

### Shared vault complet

Avantages:

- modele proche 1Password/Bitwarden organizations;
- permissions plus riches.

Inconvenients:

- plus complexe;
- necessite une modelisation d'organisations/vaults/members;
- surdimensionne pour le projet actuel.

### Shared item key

Avantages:

- synchronisation item par item;
- serveur ne voit pas la cle;
- compatible avec l'architecture existante;
- evolution possible vers shared vault plus tard.

Inconvenients:

- revocation parfaite impossible si recipient a deja vu le secret;
- rotation de shared key apres revoke pas encore complete;
- payloads plus complexes;
- tests E2E necessaires.

## Consequences positives

- Les edits du owner peuvent etre propages.
- Le serveur copie des ciphertexts sans connaitre le contenu.
- Chaque recipient protege la shared key sous sa propre vault key.

## Risques et compromis

- Revoke retire l'acces applicatif futur, mais ne peut pas effacer un secret deja
  memorise ou copie.
- Sans rotation de shared item key, un recipient qui a conserve la cle pourrait
  theoriquement lire des ciphertexts futurs s'il y accede.
- Les metadonnees de partage restent visibles au serveur.

## Amelioration future

Rotation de shared item key lors d'une revocation:

1. generer nouvelle shared key;
2. rechiffrer l'item;
3. rechiffrer la shared key pour recipients restants;
4. supprimer la copie du revoked;
5. journaliser l'evenement.
