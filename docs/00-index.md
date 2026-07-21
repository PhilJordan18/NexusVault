# NexusVault Documentation Index

Cette documentation sert a trois choses:

1. expliquer ce que NexusVault fait;
2. permettre a un autre developpeur de reprendre ou reconstruire la solution;
3. rendre explicites les decisions de securite, surtout le modele zero-knowledge.

NexusVault n'est pas seulement une application Laravel avec des formulaires. C'est
un gestionnaire de mots de passe avec plusieurs frontieres de confiance:

- le navigateur, qui detient le mot de passe du coffre et la cle de coffre;
- le serveur Laravel, qui authentifie, autorise, stocke, synchronise et envoie les emails;
- la base MariaDB, qui stocke les enveloppes et les ciphertexts;
- le VPS, qui expose l'application en HTTPS;
- les fournisseurs externes: Google/GitHub OAuth, Resend, DNS/Cloudflare.

## Ordre de lecture recommande

```text
00-index.md
01-vision-et-carte-du-projet.md
02-architecture-applicative-et-code.md
03-authentification-sessions-et-acces.md
04-modele-cryptographique-zero-knowledge.md
05-cycle-de-vie-des-cles-et-recovery.md
06-coffre-items-et-partage.md
07-threat-model-limites-et-risques.md
08-tests-et-strategie-qualite.md
09-deploiement-vps-hardening-exploitation.md
10-notice-utilisation.md
11-roadmap-maintenance-et-ameliorations.md
adr/
```

## Responsabilites des documents

`01` donne la vision globale: objectif, utilisateurs, fonctionnalites, routes,
domain model et workflows majeurs.

`02` explique comment le code est organise: Laravel, controllers, services,
requests, models, Blade, TypeScript, Vite et tests.

`03` detaille l'authentification, les sessions, MFA, OAuth, passkeys et le
middleware `master_key`.

`04` est le document central sur le zero-knowledge: quelles donnees sont
chiffrees, avec quelles cles, ou le chiffrement se fait, et ce que le serveur
peut ou ne peut pas connaitre.

`05` suit les cles dans le temps: creation, unlock, session navigateur,
recovery, reset destructif et nettoyage.

`06` documente les items de coffre et le partage: login, cartes, notes,
partage ponctuel, partage synchronise, revocation et suppression.

`07` est le threat model: serveur compromis, base volee, XSS, appareil compromis,
administrateur malveillant, faiblesse du mot de passe, perte du secret.

`08` explique les tests existants, les commandes, la strategie qualite et les
tests manquants.

`09` est le guide de deploiement/exploitation: VPS, firewall, Nginx, MariaDB,
Certbot, deploy, logs, backup, rollback.

`10` est la notice utilisateur.

`11` est la roadmap technique: ce qui est livre, ce qui est a renforcer, et les
ameliorations recommandees.

`adr/` contient les decisions d'architecture importantes. Un ADR ne remplace pas
la documentation technique; il explique pourquoi une decision a ete prise.

## Etat actuel du projet

Le projet est livrable et deploye. Les grandes fonctionnalites sont presentes:

- inscription email/password avec verification email;
- OAuth Google/GitHub;
- passkeys WebAuthn;
- MFA TOTP;
- mot de passe de connexion separe du mot de passe de coffre;
- coffre chiffre cote navigateur pour les utilisateurs modernes;
- recovery key;
- reset destructif du coffre;
- creation de login, carte bancaire et note securisee;
- partage chiffre et partage synchronise;
- revoke/delete de partage;
- preference de langue anglais/francais;
- pages legales;
- deploiement VPS avec HTTPS.

Les sections `07` et `11` expliquent les limites restantes.
