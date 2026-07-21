# 06 - Coffre, items et partage

## Items du coffre

Les items sont stockes dans la table `services`.

Types supportes:

```text
login
payment_card
secure_note
```

Le nom `Service` vient de la version initiale: un service etait un compte externe
comme Netflix, YouTube, GitHub. Aujourd'hui le modele a ete elargi.

## Champs sensibles

Pour un user moderne, ces champs contiennent du ciphertext:

- `username`;
- `password`;
- `notes`.

Chaque champ chiffre a:

- un ciphertext;
- un IV;
- un tag.

Exemple pour `password`:

```text
password
password_iv
password_tag
```

## Creation d'un item

Fichiers:

```text
resources/views/dashboard/
resources/js/pages/service.ts
app/Http/Controllers/ServiceController.php
app/Services/Vault/ServiceService.php
app/Http/Requests/CreateServiceRequest.php
```

Flux moderne:

1. L'utilisateur saisit les champs en clair dans le navigateur.
2. TypeScript appelle `encryptVaultString` pour chaque champ sensible.
3. Le formulaire/API envoie:
   - ciphertext;
   - IV;
   - tag;
   - `client_encrypted=1`.
4. Laravel valide que `client_encrypted` est present.
5. Laravel stocke le payload opaque.

## Edition d'un item

Fichier:

```text
resources/js/pages/service.ts
```

Si l'item est normal:

- les champs sont rechiffres avec la vault key.

Si l'item est partage synchronise:

- les champs sont rechiffres avec la shared item key.

Ensuite `ServiceService::syncSharedGroup` propage les ciphertexts aux autres
copies du groupe.

## Suppression d'un item

La suppression depend du partage.

### Item non partage

Suppression simple du service.

### Recipient supprime une copie partagee

Le recipient supprime sa copie locale. L'original reste chez le owner.

### Owner supprime un item partage

Le groupe partage est supprime:

- les copies sont supprimees;
- les invitations associees sont supprimees.

## Favicon et branding

Fichier:

```text
app/Services/Vault/FaviconService.php
```

Pour les `login`, NexusVault tente de deduire:

- domaine;
- URL;
- favicon.

Pour `payment_card` et `secure_note`, l'URL est generalement nulle.

## Analyse de securite des passwords

Fichier:

```text
app/Services/PasswordService.php
```

Pour les anciens flux ou quand le serveur voit un mot de passe, il peut calculer:

- strength;
- compromised;
- reused.

Pour un item moderne `client_encrypted=true`, le serveur ne peut pas analyser le
mot de passe en clair. Le service met donc:

```text
strength = null
compromised = false
reused = false
```

Si on veut conserver une analyse zero-knowledge, elle doit etre faite dans le
navigateur avant chiffrement, puis stocker seulement un resultat non sensible.

## Partage: probleme a resoudre

Partager un mot de passe en zero-knowledge est difficile parce que:

- le serveur ne connait pas le secret;
- le recipient ne connait pas la vault key du owner;
- le owner ne connait pas la vault key du recipient;
- le partage doit pouvoir etre accepte/refuse/revoke;
- le partage synchronise doit permettre aux copies de rester coherentes.

## Deux modeles de partage

### Partage legacy

Le serveur dechiffre/rechiffre via `CryptoService` et `UserKeyService`. Ce modele
est historique et n'est pas le modele cible zero-knowledge.

### Partage client encrypted

Le navigateur cree un payload chiffre avant envoi. Le serveur stocke et valide la
forme.

### Partage client encrypted sync

Mode cible actuel pour synchroniser les edits.

## Partage synchronise zero-knowledge

Concept central:

```text
shared item key
```

Chaque item partage synchronise a une cle symetrique dediee. Les champs de cet
item sont chiffres avec cette shared item key, pas directement avec la vault key
personnelle du owner.

## Creation d'un partage sync

Fonction:

```ts
createClientSyncSharePayload(...)
```

Etapes:

1. Le owner decrypte l'item dans son navigateur.
2. Le navigateur cree une shared item key ou reutilise celle deja associee.
3. Les champs sensibles sont chiffres avec la shared item key.
4. La shared item key est chiffree pour le recipient avec sa public key RSA.
5. La shared item key est aussi chiffree sous la vault key du owner:
   `shared_key_envelope`.
6. Le serveur stocke:
   - `encrypted_aes_key` pour le recipient;
   - `shared_fields`;
   - metadonnees;
   - invitation `Share`.
7. Le service source est converti en item chiffre par shared key.

## Acceptation par le recipient

Etapes:

1. Le recipient ouvre son coffre.
2. Son navigateur decrypte sa private key avec sa vault key.
3. Il decrypte `encrypted_aes_key`.
4. Il obtient la shared item key.
5. Il chiffre cette shared item key sous sa propre vault key.
6. Il envoie `shared_key_envelope`.
7. Laravel cree une copie locale de l'item.
8. Le share passe a `accepted_at`.

Le recipient peut ensuite decrypter l'item partage avec:

```text
vault key -> shared_key_envelope -> shared item key -> fields
```

## Edition synchronisee

Quand le owner modifie l'item:

1. le navigateur du owner recupere la shared item key depuis son envelope;
2. il chiffre les nouveaux champs avec cette shared key;
3. le serveur met a jour le source item;
4. `ServiceService::syncClientEncryptedSharedGroup` propage les nouveaux
   ciphertexts aux copies.

Le serveur ne comprend toujours pas le contenu. Il recopie des ciphertexts
valides.

## Revocation

La revocation fait deux choses:

- supprime la copie du recipient;
- marque le share comme `revoked_at`.

Limite cryptographique importante:

> Si le recipient a deja vu le mot de passe, aucune revocation logicielle ne peut
> le forcer a oublier ce qu'il a vu.

Pour une revocation plus forte sur les futurs edits, il faut faire une rotation
de shared item key et rechiffrer pour les recipients restants.

## Suppression owner vs recipient

### Owner

Supprimer l'original supprime le groupe partage.

### Recipient

Supprimer sa copie retire son acces local mais ne supprime pas l'original.

Cette logique correspond au principe "single source of truth": le owner controle
l'objet original.

## Structure d'un payload sync

Conceptuellement:

```json
{
  "version": 2,
  "mode": "client-encrypted-sync",
  "encrypted_aes_key": "...shared key chiffree RSA pour recipient...",
  "encrypted_data": {
    "ciphertext": "...",
    "iv": "...",
    "tag": "..."
  },
  "shared_fields": {
    "username": { "ciphertext": "...", "iv": "...", "tag": "..." },
    "password": { "ciphertext": "...", "iv": "...", "tag": "..." },
    "notes": { "ciphertext": "...", "iv": "...", "tag": "..." }
  },
  "name": "Netflix",
  "url": "https://netflix.com",
  "type": "login"
}
```

## Validations serveur

Le serveur valide:

- ownership du service;
- recipient existe;
- impossible de partager avec soi-meme;
- recipient doit avoir un coffre zero-knowledge;
- impossible de reshared une copie;
- share actif deja existant interdit;
- presence des champs ciphertext/iv/tag;
- taille IV 24 caracteres hex;
- taille tag 32 caracteres hex.

## Ce que le serveur ne valide pas encore parfaitement

Le serveur ne peut pas verifier que le ciphertext correspond vraiment au texte
attendu, puisqu'il ne connait pas la cle. Il valide la forme, pas le contenu.

Ameliorations possibles:

- schema JSON strict pour les enveloppes;
- versioning plus explicite des payloads;
- AAD pour lier ciphertext a champ/item;
- rotation de shared key lors d'une revocation;
- audit log des partages/revocations.

## Recommandations d'utilisation

- privilegier le partage synchronise pour garder une source unique;
- revoke si un recipient ne doit plus voir les futurs changements;
- changer le mot de passe reel du service externe apres revoke sensible;
- ne jamais considerer une revocation comme effacement de memoire chez l'humain.
