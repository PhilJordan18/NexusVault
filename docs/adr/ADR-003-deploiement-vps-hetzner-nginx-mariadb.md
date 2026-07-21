# ADR-003 - Deploiement VPS Hetzner avec Nginx, PHP-FPM et MariaDB

## Statut

Accepte.

## Contexte

Le projet devait etre accessible via une adresse web valide avec HTTPS. Le choix
a ete de deployer NexusVault sur un VPS paye, avec nom de domaine
`nexusvault.dev`.

Le deploiement devait aussi corriger les problemes de l'ancien serveur:

- erreurs 500;
- brute force SSH;
- configuration incomplete;
- DNS/Cloudflare 523;
- OAuth/mail mal configures.

## Decision

Utiliser:

- Hetzner Cloud VPS;
- Ubuntu;
- user non-root `nexus`;
- firewall Hetzner + UFW;
- Fail2Ban;
- Nginx;
- PHP 8.5 FPM;
- MariaDB;
- Certbot/Let's Encrypt;
- Resend;
- Cloudflare DNS.

## Alternatives considerees

### Laravel Cloud

Avantages:

- plus simple;
- gere beaucoup d'infrastructure;
- adapte Laravel.

Inconvenients:

- moins formateur DevOps;
- cout/controle a evaluer;
- le projet voulait pratiquer VPS, HTTPS, firewall, DNS.

### Shared hosting

Avantages:

- simple;
- peu cher.

Inconvenients:

- controle limite;
- PHP versions/extensions parfois bloquees;
- moins adapte a WebAuthn/OAuth/deploy propre.

### VPS manuel

Avantages:

- controle total;
- bon apprentissage;
- stack proche production reelle;
- firewall/SSH/Nginx/Certbot maitrisables.

Inconvenients:

- responsabilite securite;
- mises a jour;
- backups;
- monitoring;
- risque d'erreur de configuration.

## Consequences positives

- Deploiement reproductible;
- HTTPS Let's Encrypt;
- meilleure comprehension firewall/DNS/VPS;
- separation local/prod claire;
- logs et services controlables.

## Risques et compromis

- Le VPS doit etre maintenu.
- Les backups doivent etre testes.
- Les secrets `.env` doivent etre proteges.
- Les mises a jour systeme doivent etre appliquees.
- Une mauvaise config Nginx/PHP-FPM peut exposer ou casser le site.

## Runbook associe

Voir:

```text
docs/09-deploiement-vps-hardening-exploitation.md
```
