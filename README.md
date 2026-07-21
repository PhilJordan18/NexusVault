# NexusVault

NexusVault is a Laravel password manager built as a full-stack security project.
It includes classic authentication, OAuth, passkeys, MFA, encrypted vault setup,
recovery keys, password/card/note storage, encrypted sharing, localization, legal
pages, and a production deployment on a VPS with HTTPS.

The current product direction is zero-knowledge for vault contents: modern users
encrypt and decrypt vault secrets in the browser with WebCrypto, while the
server stores only encrypted envelopes, ciphertexts, IVs/tags, and metadata.

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm ci
npm run build
composer run dev
```

For the full documentation, read the docs in this order:

```text
docs/
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

## Main Stack

- Laravel 13, PHP 8.5, Blade, Vite, TypeScript.
- MariaDB in production, SQLite available locally.
- WebCrypto for browser-side vault encryption.
- Laravel Socialite for OAuth.
- Laragear WebAuthn for passkeys.
- Pragmarx Google2FA for TOTP MFA.
- Resend for production email.
- Nginx, PHP-FPM, Certbot, UFW and Fail2Ban on the VPS.

## Useful Commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
php artisan optimize:clear
php artisan optimize
```

Production deploy routine is documented in
`docs/09-deploiement-vps-hardening-exploitation.md`.
