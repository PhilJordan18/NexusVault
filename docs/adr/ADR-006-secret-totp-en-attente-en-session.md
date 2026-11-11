# ADR-006 - Secret TOTP en attente conserve en session pendant l'enrolement MFA

## Statut

Accepte le 2026-11-11, derogation sous conditions.

## Contexte

Le mandat M-002 interdisait « tout nouveau stockage de secret en session »
et demandait en meme temps, pour fermer le constat C1, un secret TOTP
temporaire conserve jusqu'a la verification du premier code. La livraison a
retenu la cle de session `mfa_pending_secret`, et la session Securite a
accepte avec reserve en demandant une decision Architecture.

Le secret en attente n'est pas encore un facteur actif : tant que
`MfaService::enableMfa()` n'a pas persiste `totp_secret`, il n'ouvre rien.
La session Laravel est chiffree et stockee en base. La cle est purgee par
`AuthenticatedSessionService` a chaque nouveau login et par `verifySetup`
apres succes. En revanche, l'avis note que le secret vit toute la session et
reste affichable apres `vault.lock`.

## Decision

La contrainte « pas de nouveau stockage de secret en session » vise les
secrets qui ouvrent quelque chose : vault key, recovery key, mot de passe,
private key. Elle ne vise pas un secret d'enrolement non encore actif.
`mfa_pending_secret` est donc tolere, a trois conditions livrees par M-006 :

1. le secret est regenere a chaque `GET /mfa/setup` ou expire apres dix
   minutes ;
2. il est oublie au verrouillage du coffre et au logout ;
3. la verification du premier code exige une authentification fraiche.

Toute autre cle de session contenant un secret reste interdite sans nouvel
ADR.

## Alternatives considerees

Cache applicatif indexe par utilisateur avec TTL : meme exposition, un
magasin de plus a proteger, et un comportement different en test et en
production.

Secret signe dans le formulaire et renvoye par le client : evite la session
mais expose le secret dans le DOM et les journaux de requetes, contraire a
`docs/04`.

Persister le secret en base avant verification avec un drapeau « en
attente » : c'est le defaut C1 qu'on vient de fermer.

## Consequences

`docs/03` decrit deja `mfa_pending_secret` ; il sera complete par M-006 avec
la duree de vie et les points de purge. La session Securite verifie les
trois conditions a la livraison de M-006. La decision Architecture de M-002
reference cet ADR.
