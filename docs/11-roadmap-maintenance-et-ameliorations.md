# 11 - Roadmap, maintenance et ameliorations

## Etat actuel

NexusVault est livrable:

- deploiement HTTPS fonctionne;
- auth email/password fonctionne;
- OAuth fonctionne;
- passkeys fonctionnent;
- MFA fonctionne;
- coffre moderne zero-knowledge fonctionne;
- partage et partage sync fonctionnent;
- items login/card/note fonctionnent;
- tests feature passent.

Cette roadmap liste ce qui renforcerait le projet pour aller vers un produit plus
proche d'un password manager professionnel.

## Priorite 1 - Securite zero-knowledge

### Supprimer definitivement le legacy server-side crypto

Objectif:

- retirer `encrypted_master_key` des flux actifs;
- retirer le besoin de `UserKeyService` pour les users modernes;
- isoler ou supprimer `CryptoService` serveur pour vault contents;
- migrer/vider les anciens users non-client.

Pourquoi:

Le code legacy augmente la surface de confusion. Meme si les routes modernes sont
protegees, la documentation doit rester honnete: tant que le legacy existe, il
faut le nommer.

### Ajouter AAD a AES-GCM

Inclure un contexte:

```text
user_id
service_id
field_name
version
```

But:

- lier cryptographiquement un ciphertext a son champ;
- reduire les risques de permutation.

### Rotation recovery key

Fonction:

- coffre deja unlocked;
- generer nouvelle recovery key;
- remplacer `vault_recovery_envelope`;
- afficher nouvelle recovery key;
- invalider l'ancienne.

### Changement non destructif du vault password

Fonction:

- coffre unlocked;
- demander nouveau vault password;
- nouvelle wrapping key;
- nouveau `vault_key_envelope`;
- garder la meme vault key.

## Priorite 2 - XSS/CSP

### CSP progressive

Ajouter une Content Security Policy testee.

Attention:

- Vite/dev a des besoins differents;
- certains scripts inline doivent recevoir nonce/hash;
- Font Awesome, styles et assets doivent etre testes.

### Reduire `innerHTML`

Remplacer le rendu HTML dynamique par creation DOM lorsque possible.

### Nettoyage lock navigateur

S'assurer que le bouton lock/logout appelle aussi:

```ts
clearStoredVaultKey()
```

## Priorite 3 - Partage avance

### Rotation de shared item key apres revoke

Quand owner revoke un recipient:

1. generer nouvelle shared key;
2. rechiffrer l'item;
3. rechiffrer la shared key pour recipients restants;
4. supprimer la copie du revoked.

### Audit de partage

Ajouter un journal:

- created share;
- accepted;
- rejected;
- revoked;
- deleted shared group;
- synced update.

Ne jamais logger les secrets.

## Priorite 4 - Extension Chrome

Objectif:

- popup d'extension;
- detection du domaine courant;
- recherche des credentials du domaine;
- autofill controle;
- communication securisee entre extension et web app;
- stockage minimal;
- permissions Chrome strictes.

Questions de securite:

- comment l'extension obtient-elle la vault key?
- session web ou token dedie?
- origine autorisee?
- protection contre pages malveillantes?
- jamais injecter un secret sans action utilisateur?

## Priorite 5 - Tests E2E et crypto

Ajouter:

- Playwright pour flows complets;
- tests WebCrypto;
- tests de partage sync navigateur;
- tests de lock/logout cote browser;
- tests de CSP;
- tests de backup restore.

## Priorite 6 - Exploitation prod

### Monitoring

- uptime monitoring;
- disk usage;
- CPU/memory;
- certbot expiry;
- erreurs Laravel;
- queue failed jobs;
- fail2ban alerts.

### Backups

- dump MariaDB quotidien chiffre;
- retention;
- test restore;
- documentation restore.

### Incident response

Procedure pour:

- cle Resend compromise;
- OAuth secret compromis;
- serveur compromis;
- DB volee;
- bug de chiffrement;
- domaine/DNS casse.

## Priorite 7 - UX produit

- onboarding plus clair sur vault password vs login password;
- backup codes MFA;
- export chiffre;
- import CSV;
- recherche locale dechiffree;
- audit local de passwords cote navigateur;
- meilleur dashboard de sante de securite;
- UI extension.

## Decision: quand dire que c'est "termine"?

NexusVault est termine comme projet de fin de session et portfolio avance.

Il n'est pas termine comme produit commercial de password manager.

La difference est normale. Un password manager commercial exige:

- audit crypto externe;
- politique de vulnerability disclosure;
- threat model public;
- CSP robuste;
- extension auditee;
- restore drills;
- monitoring;
- tests E2E;
- procedures de support et recovery.

## Definition of Done pour les prochaines features

```text
[ ] Besoin documente
[ ] Threat model impacte mis a jour si securite
[ ] ADR si decision importante
[ ] Tests feature/unit
[ ] Tests browser si crypto/frontend sensible
[ ] Pas de secret en clair cote serveur pour users modernes
[ ] npm run build OK
[ ] php artisan test --compact OK
[ ] Deploiement documente si necessaire
```
