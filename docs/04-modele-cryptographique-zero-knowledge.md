# 04 - Modele cryptographique zero-knowledge

## Definition utilisee par NexusVault

Dans NexusVault, "zero-knowledge" signifie:

> Le serveur ne doit pas recevoir le mot de passe du coffre ni les secrets du
> coffre en clair. Le chiffrement et le dechiffrement des items modernes se font
> dans le navigateur.

Ce n'est pas une promesse absolue que le serveur ne sait rien. Le serveur connait
des metadonnees necessaires au produit:

- email;
- nom;
- etat OAuth/MFA/email verified;
- service name;
- type d'item;
- URL;
- favicon;
- dates;
- relations de partage;
- tailles approximatives des ciphertexts.

Le serveur ne doit pas connaitre:

- vault password;
- recovery key;
- vault key en clair;
- private key RSA en clair;
- usernames/passwords/notes/cartes en clair pour un utilisateur moderne;
- shared item key en clair.

## Fichier central

```text
resources/ts/zero-knowledge.ts
```

Ce fichier contient la logique WebCrypto.

## Primitives cryptographiques

### Vault key

La vault key est une cle aleatoire de 32 bytes.

```ts
const vaultKeyBytes = randomBytes(32);
```

Elle chiffre les items du coffre avec AES-GCM.

### Vault password

Le vault password n'est pas stocke et n'est pas envoye au serveur dans le flux
moderne.

Il sert a deriver une wrapping key:

```text
vault password
+ salt 16 bytes
+ PBKDF2-SHA-256, 600000 iterations
=> AES-GCM wrapping key
```

La wrapping key chiffre la vault key dans `vault_key_envelope`.

### AES-GCM

NexusVault utilise AES-GCM pour les champs de coffre.

Parametres:

- AES 256 bits;
- IV 12 bytes;
- tag 128 bits;
- ciphertext en base64;
- IV/tag en hex.

Structure:

```ts
type EncryptedString = {
    ciphertext: string;
    iv: string;
    tag: string;
};
```

### Recovery key

La recovery key est aleatoire, 32 bytes, affichee a l'utilisateur sous format:

```text
NV-xxxx-xxxx-...
```

Elle est convertie en bytes et importee comme cle AES-GCM. Elle chiffre aussi la
vault key, mais dans une enveloppe separee:

```ts
type VaultRecoveryEnvelope = EncryptedString & {
    version: 1;
    algorithm: 'AES-GCM';
    keySource: 'recovery-key';
};
```

### RSA-OAEP

A la creation du coffre, le navigateur genere une paire RSA-OAEP:

- modulus 2048;
- SHA-256;
- public exponent 65537.

La public key est stockee en clair sur le serveur. Elle sert aux autres
utilisateurs pour chiffrer une cle de partage a ton attention.

La private key est exportee au format PKCS#8 puis chiffree avec la vault key.

```ts
type EncryptedPrivateKeyEnvelope = EncryptedString & {
    version: 1;
    algorithm: 'AES-GCM';
    keyFormat: 'pkcs8';
};
```

## Creation du coffre

Fonction:

```ts
createRegistrationVaultPackage(vaultPassword)
```

Elle genere:

1. salt 16 bytes;
2. vault key 32 bytes;
3. recovery key 32 bytes;
4. wrapping key derivee du vault password;
5. `vault_key_envelope`;
6. `vault_recovery_envelope`;
7. paire RSA-OAEP;
8. private key chiffree avec la vault key;
9. public key PEM;
10. stockage temporaire de la vault key dans `sessionStorage`.

Payload envoye au serveur:

```text
vault_key_envelope
vault_recovery_envelope
public_key
encrypted_private_key
```

Payload non envoye:

```text
vault password
recovery key
vault key en clair
private key en clair
```

## Enveloppe de vault key

Structure:

```ts
type VaultKeyEnvelope = {
    version: 1;
    algorithm: 'AES-GCM';
    kdf: 'PBKDF2-SHA-256';
    iterations: number;
    salt: string;
    iv: string;
    ciphertext: string;
    tag: string;
};
```

Elle ne contient pas la vault key en clair. Elle contient la vault key chiffree
avec une cle derivee du vault password.

## Unlock

Fonction:

```ts
unlockVaultKey(envelope, vaultPassword)
```

Etapes:

1. importer le vault password comme materiau PBKDF2;
2. deriver la wrapping key avec le salt et les iterations de l'enveloppe;
3. joindre ciphertext + tag;
4. decrypter avec AES-GCM;
5. stocker la vault key dans `sessionStorage`.

Si le mot de passe est faux, AES-GCM echoue parce que le tag ne valide pas.

## Chiffrement d'un champ de coffre

Fonction:

```ts
encryptVaultString(value)
```

Etapes:

1. lire la vault key depuis `sessionStorage`;
2. importer la vault key comme AES-GCM;
3. generer IV 12 bytes;
4. chiffrer le texte UTF-8;
5. separer ciphertext/tag;
6. envoyer `ciphertext`, `iv`, `tag`.

Le serveur stocke ces trois parties dans `services`.

## Dechiffrement d'un champ

Fonction:

```ts
decryptVaultString(encrypted)
```

Etapes:

1. lire la vault key depuis `sessionStorage`;
2. reconstruire ciphertext + tag;
3. AES-GCM decrypt;
4. decoder UTF-8.

Si le tag ne valide pas, le dechiffrement echoue.

## Donnees authentifiees

AES-GCM authentifie le ciphertext avec son tag. Le code actuel n'utilise pas
d'AAD explicite. Cela signifie que le tag protege le contenu chiffre, mais ne lie
pas cryptographiquement le ciphertext a des metadonnees comme:

- `service_id`;
- `user_id`;
- `name`;
- `type`;
- `url`.

Cette absence d'AAD n'est pas forcement bloquante pour le projet actuel, mais
c'est une amelioration possible: ajouter un contexte stable dans AAD pour reduire
les risques de permutation de ciphertext entre champs/items.

## Ce que le serveur stocke

### `users`

| Champ | Sens |
| --- | --- |
| `password` | hash du login password |
| `public_key` | cle publique RSA |
| `private_key` | private key RSA chiffree avec vault key |
| `vault_key_envelope` | vault key chiffree avec wrapping key derivee du vault password |
| `vault_recovery_envelope` | vault key chiffree avec recovery key |
| `totp_secret` | secret TOTP, necessaire au serveur |

### `services`

| Champ | Sens moderne |
| --- | --- |
| `username` | ciphertext |
| `username_iv` | IV AES-GCM |
| `username_tag` | tag AES-GCM |
| `password` | ciphertext |
| `password_iv` | IV AES-GCM |
| `password_tag` | tag AES-GCM |
| `notes` | ciphertext nullable |
| `client_encrypted` | vrai pour zero-knowledge |
| `shared_key_envelope` | shared key chiffree sous vault key |

### `shares`

`shared_data` contient un JSON avec:

- encrypted shared key pour le destinataire;
- encrypted data ou shared fields;
- metadonnees d'affichage.

## Limites de la promesse zero-knowledge

### Le serveur connait les metadonnees

Exemples:

- l'utilisateur a un item nomme "Netflix";
- l'URL est `https://netflix.com`;
- un partage existe entre deux emails;
- le type d'item est `payment_card`;
- un item a ete modifie a telle date.

Ces metadonnees peuvent deja etre sensibles.

### XSS

Si un attaquant execute du JavaScript dans l'origine NexusVault pendant que le
coffre est deverrouille, il peut lire:

- la vault key dans `sessionStorage`;
- les champs dechiffres dans le DOM;
- les donnees avant chiffrement.

Le zero-knowledge protege contre un serveur/base compromis passif, pas contre un
JavaScript malveillant execute dans la session active.

### Appareil compromis

Si l'appareil ou le navigateur est compromis, la protection tombe.

### Mot de passe faible

Le vault password protege l'enveloppe de la vault key. Si la base est volee, un
attaquant peut tenter une attaque offline sur `vault_key_envelope`. PBKDF2 600k
ralentit l'attaque, mais la force reelle depend du mot de passe choisi.

### TOTP non zero-knowledge

Le secret TOTP est stocke cote serveur parce que le serveur doit verifier les
codes. Cela ne contredit pas le zero-knowledge du coffre, mais il faut l'expliquer.

### Code legacy

`CryptoService` et `UserKeyService` implementent un modele serveur historique.
Les utilisateurs modernes doivent avoir `client_encrypted=true`. Tant que du code
legacy existe, la documentation doit distinguer:

- coffre moderne zero-knowledge;
- compatibilite serveur historique.

## Pourquoi ne pas chiffrer directement avec le vault password?

Parce qu'il faut pouvoir changer ou reset certains mecanismes sans rechiffrer
tous les items a chaque fois. Le bon modele est:

```text
vault password -> wrapping key -> vault key -> items
```

La vault key est le secret qui chiffre les items. Le vault password ne sert qu'a
ouvrir l'enveloppe de cette cle.

## Pourquoi une recovery key?

Sans recovery key, oublier le vault password signifierait perdre le coffre. La
recovery key fournit une deuxieme enveloppe de la meme vault key.

Mais elle a une consequence:

- si quelqu'un vole la recovery key et la base, il peut ouvrir le coffre;
- elle doit etre affichee une seule fois et sauvegardee hors ligne.

## Pourquoi RSA pour le partage?

Le owner doit envoyer une cle de partage a un recipient sans connaitre son vault
password. Le recipient publie une public key. Le owner chiffre la shared item key
avec cette public key. Seule la private key du recipient peut la decrypter.

La private key du recipient est elle-meme chiffree avec sa vault key.

## Resume mental

```text
Login password
  -> authentifie le compte Laravel
  -> stocke en hash bcrypt
  -> ne chiffre pas le coffre moderne

Vault password
  -> reste dans le navigateur
  -> derive une wrapping key PBKDF2
  -> ouvre vault_key_envelope

Vault key
  -> aleatoire
  -> stockee seulement chiffree en base
  -> en clair seulement dans le navigateur unlocked
  -> chiffre les items et la private key

Recovery key
  -> aleatoire
  -> ouvre vault_recovery_envelope

RSA public key
  -> stockee sur serveur
  -> permet aux autres de chiffrer une shared item key

RSA private key
  -> chiffree avec vault key
  -> permet d'accepter/dechiffrer les partages
```
