# 10 - Notice d'utilisation

## Creer un compte

1. Aller sur `/register`.
2. Entrer:
   - nom;
   - email;
   - mot de passe de connexion;
   - confirmation du mot de passe de connexion;
   - mot de passe du coffre;
   - confirmation du mot de passe du coffre.
3. Le mot de passe de connexion et le mot de passe du coffre doivent etre
   differents.
4. Sauvegarder la recovery key affichee.
5. Confirmer que la recovery key est sauvegardee.
6. Valider l'email.

Important:

- le mot de passe de connexion ouvre le compte;
- le mot de passe du coffre ouvre les secrets;
- la recovery key est le dernier recours si le mot de passe du coffre est perdu.

## Connexion

1. Aller sur `/login`.
2. Entrer l'email.
3. Entrer le mot de passe de connexion.
4. Si MFA est active, entrer le code TOTP.
5. Entrer le mot de passe du coffre sur `/vault/unlock`.

## Connexion OAuth

1. Cliquer Google ou GitHub.
2. Autoriser l'application.
3. Si c'est un nouveau compte OAuth, NexusVault demande de creer un mot de passe
   de coffre.
4. Sauvegarder la recovery key.

## Deverrouiller avec recovery key

1. Aller sur `/vault/unlock`.
2. Ouvrir "Use recovery key".
3. Entrer la recovery key au format `NV-...`.
4. Valider.

## Ajouter MFA

1. Aller dans Settings.
2. Choisir MFA.
3. Scanner le QR code avec Microsoft Authenticator, Google Authenticator ou une
   app compatible TOTP.
4. Entrer le code 6 chiffres.
5. Valider.

Si le QR ne s'affiche pas, verifier directement `/mfa/qr-code` pendant que tu es
connecte.

## Ajouter une passkey

1. Deverrouiller le coffre.
2. Aller dans Passkeys.
3. Enregistrer une nouvelle passkey.
4. Utiliser Face ID, Touch ID, Windows Hello ou une cle securite selon l'appareil.

## Creer un login

1. Dashboard.
2. Add Account.
3. Type `login`.
4. Entrer nom du service, URL, identifiant, mot de passe, notes.
5. Utiliser le generateur si besoin.
6. Sauvegarder.

Le navigateur chiffre les champs sensibles avant l'envoi.

## Creer une carte bancaire

1. Add Account.
2. Type `payment_card`.
3. Entrer nom, titulaire, numero ou contenu sensible, notes.
4. Sauvegarder.

Les cartes ne declenchent pas l'analyse de force de mot de passe.

## Creer une note securisee

1. Add Account.
2. Type `secure_note`.
3. Entrer reference, contenu, notes.
4. Sauvegarder.

## Modifier un item

1. Ouvrir le service.
2. Selectionner l'item.
3. Cliquer Edit.
4. Modifier.
5. Sauvegarder.

Si l'item est partage synchronise, la modification est propagee aux copies
acceptees.

## Supprimer un item

Cas non partage:

- supprime l'item.

Cas owner d'un partage:

- supprime le groupe partage et retire l'acces aux recipients.

Cas recipient:

- supprime seulement la copie locale.

## Partager un item

1. Ouvrir un item.
2. Cliquer Share.
3. Entrer l'email du recipient.
4. Envoyer.

Conditions:

- le recipient doit exister;
- le recipient doit avoir un coffre zero-knowledge;
- on ne peut pas partager avec soi-meme;
- une copie recue ne peut pas etre repartagee.

## Accepter ou refuser un partage

1. Aller dans les notifications.
2. Choisir Accept ou Reject.
3. Si Accept, le navigateur prepare les enveloppes necessaires.
4. L'item apparait dans le coffre.

## Revoke un partage

1. Ouvrir l'item owner.
2. Voir la liste des recipients.
3. Cliquer Revoke.

Cela retire la copie du recipient. Pour un vrai service externe, si le secret est
tres sensible, changer aussi le mot de passe sur le site externe.

## Reset destructif du coffre

Utiliser seulement si:

- vault password perdu;
- recovery key perdue ou inutilisable;
- coffre compromis;
- besoin de repartir a zero.

Effet:

- supprime les items;
- supprime les partages;
- cree un nouveau coffre vide;
- genere une nouvelle recovery key.

## Changer la langue

Utiliser le switch anglais/francais. Pour un utilisateur connecte, la preference
est stockee sur le compte. Sinon, elle est stockee en session.

## Bonnes pratiques utilisateur

- utiliser un vault password long;
- sauvegarder la recovery key hors ligne;
- activer MFA;
- ajouter une passkey;
- ne pas utiliser NexusVault sur un appareil public;
- verrouiller le coffre apres utilisation;
- changer les mots de passe externes apres revoke sensible.
