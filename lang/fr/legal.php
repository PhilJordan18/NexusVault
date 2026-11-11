<?php

return [
    'terms' => [
        'title' => 'Conditions d’utilisation',
        'description' => 'Les règles d’utilisation de NexusVault et les responsabilités liées à un coffre chiffré zéro connaissance.',
        'last_updated' => '6 juillet 2026',
        'sections' => [
            [
                'heading' => '1. Acceptation des conditions',
                'paragraphs' => [
                    'En créant un compte ou en utilisant NexusVault, vous acceptez ces conditions d’utilisation. Si vous n’êtes pas d’accord avec ces conditions, vous ne devez pas utiliser le service.',
                    'NexusVault est un gestionnaire de mots de passe conçu autour du chiffrement côté navigateur et d’un modèle zéro connaissance. Ces conditions expliquent aussi ce que cela implique concrètement pour vous.',
                ],
            ],
            [
                'heading' => '2. Votre compte',
                'paragraphs' => [
                    'Vous êtes responsable de l’exactitude des informations de compte que vous fournissez, de la sécurité de votre méthode de connexion et de toute activité effectuée depuis votre compte.',
                    'Vous devez utiliser NexusVault uniquement à des fins légales et ne pas tenter d’accéder au compte, au coffre ou aux données d’une autre personne sans autorisation.',
                ],
            ],
            [
                'heading' => '3. Mot de passe du coffre et clé de récupération',
                'paragraphs' => [
                    'Votre mot de passe de coffre sert à dériver les clés qui déchiffrent vos données localement dans votre navigateur. NexusVault ne reçoit pas ce mot de passe en clair et ne peut pas l’utiliser pour lire vos éléments.',
                    'Vous êtes responsable de conserver votre mot de passe de coffre et votre clé de récupération. Si vous perdez les deux, NexusVault ne peut pas récupérer vos éléments chiffrés. Le seul recours prévu est une réinitialisation destructive qui supprime les données du coffre et crée un nouveau coffre vide.',
                ],
            ],
            [
                'heading' => '4. Données du coffre et partage',
                'paragraphs' => [
                    'Les éléments que vous ajoutez à votre coffre, comme les identifiants, cartes et notes, doivent vous appartenir ou vous être confiés avec autorisation.',
                    'Lorsque vous partagez un élément, vous choisissez le destinataire et acceptez que celui-ci puisse accéder au contenu déchiffré sur son propre appareil après acceptation. Vous restez responsable de révoquer les accès qui ne devraient plus exister.',
                ],
            ],
            [
                'heading' => '5. Disponibilité et sécurité',
                'paragraphs' => [
                    'Nous faisons des efforts raisonnables pour garder NexusVault disponible, sécurisé et à jour. Aucun service en ligne ne peut toutefois garantir une disponibilité parfaite ou une absence totale de vulnérabilités.',
                    'Vous acceptez d’utiliser des mots de passe robustes, d’activer les protections disponibles lorsque possible et de nous signaler tout comportement suspect ou problème de sécurité.',
                ],
            ],
            [
                'heading' => '6. Suppression et résiliation',
                'paragraphs' => [
                    'Vous pouvez supprimer votre compte depuis les paramètres. La suppression retire les données associées à votre compte selon les contraintes techniques et légales applicables.',
                    'Nous pouvons suspendre ou limiter l’accès à NexusVault en cas d’abus, de tentative d’intrusion, d’utilisation illégale ou de risque sérieux pour le service ou d’autres utilisateurs.',
                ],
            ],
            [
                'heading' => '7. Limitation de responsabilité',
                'paragraphs' => [
                    'NexusVault est fourni “tel quel”. Dans la mesure permise par la loi, nous ne sommes pas responsables des pertes indirectes, interruptions, pertes de données causées par la perte de vos secrets de récupération, ou dommages résultant d’une mauvaise utilisation du service.',
                ],
            ],
            [
                'heading' => '8. Changements et contact',
                'paragraphs' => [
                    ['text' => 'Ces conditions peuvent évoluer avec le produit. Les changements importants seront communiqués de manière raisonnable. Pour toute question, écrivez à', 'email' => 'privacy@nexusvault.dev', 'suffix' => '.'],
                ],
            ],
        ],
    ],
    'privacy' => [
        'title' => 'Politique de confidentialité',
        'description' => 'Ce que NexusVault collecte, ce que le serveur peut voir, et ce qui reste chiffré côté navigateur.',
        'last_updated' => '6 juillet 2026',
        'sections' => [
            [
                'heading' => '1. Résumé clair',
                'paragraphs' => [
                    'NexusVault est conçu pour minimiser les données visibles par le serveur. Le contenu sensible de votre coffre est chiffré dans votre navigateur avant d’être envoyé au serveur.',
                    'Nous pouvons voir certaines données nécessaires au fonctionnement du service, comme votre courriel, les métadonnées de session et les informations techniques liées à la sécurité. Nous ne devrions pas pouvoir lire vos mots de passe, notes sécurisées ou cartes en clair.',
                ],
            ],
            [
                'heading' => '2. Données de compte collectées',
                'paragraphs' => [
                    'Nous collectons les données que vous fournissez lors de l’inscription et de l’utilisation du compte: nom, adresse courriel, préférence de langue, thème, photo de profil si vous en ajoutez une, et état de vérification du courriel.',
                    'Si vous utilisez OAuth, nous recevons les informations nécessaires du fournisseur choisi, comme l’identifiant OAuth, le nom et l’adresse courriel associés au compte. Si vous utilisez une passkey, nous stockons les données publiques nécessaires à la vérification, jamais votre biométrie.',
                ],
            ],
            [
                'heading' => '3. Données de coffre',
                'paragraphs' => [
                    'Les éléments du coffre sont stockés sous forme chiffrée. Cela peut inclure des blobs chiffrés, des sels cryptographiques, des clés chiffrées, des payloads de partage chiffrés et des métadonnées nécessaires à la synchronisation.',
                    'Le serveur peut traiter des métadonnées comme l’existence d’un élément, un destinataire de partage, un statut d’acceptation ou une date de mise à jour. Le modèle zéro connaissance vise à empêcher le serveur de lire le contenu secret de l’élément.',
                ],
            ],
            [
                'heading' => '4. Données techniques et journaux',
                'paragraphs' => [
                    'Pour protéger le service, diagnostiquer les erreurs et prévenir les abus, nous pouvons traiter des adresses IP, informations de navigateur, horodatages, routes demandées, erreurs applicatives et informations de session.',
                    'Ces données doivent être utilisées pour la sécurité, la maintenance, la prévention de fraude ou le respect d’obligations légales, et non pour vendre votre profil publicitaire.',
                ],
            ],
            [
                'heading' => '5. Fournisseurs et sous-traitants',
                'paragraphs' => [
                    'NexusVault peut utiliser des fournisseurs pour l’hébergement, l’envoi de courriels, le DNS/CDN, l’authentification OAuth et l’infrastructure technique. Par exemple: un hébergeur VPS, un service courriel transactionnel, et les fournisseurs OAuth que vous choisissez.',
                    'Ces fournisseurs ne reçoivent pas votre mot de passe de coffre en clair de notre part. Les fournisseurs OAuth peuvent appliquer leurs propres politiques de confidentialité lorsque vous utilisez leur bouton de connexion.',
                ],
            ],
            [
                'heading' => '6. Conservation, suppression et récupération',
                'paragraphs' => [
                    'Nous conservons les données de compte et de coffre tant que votre compte existe ou tant que cela est nécessaire pour fournir le service, sécuriser la plateforme ou respecter la loi.',
                    'Lorsque vous supprimez votre compte, NexusVault supprime les données associées dans la mesure raisonnablement possible. Certains journaux techniques peuvent être conservés temporairement pour la sécurité, les sauvegardes ou les obligations légales.',
                ],
            ],
            [
                'heading' => '7. Vos choix',
                'paragraphs' => [
                    'Vous pouvez modifier votre profil, changer la langue, gérer vos sessions, supprimer des passkeys, supprimer des éléments du coffre, révoquer des partages et supprimer votre compte depuis l’application.',
                    ['text' => 'Vous pouvez aussi demander des informations ou signaler un problème de confidentialité à', 'email' => 'privacy@nexusvault.dev', 'suffix' => '.'],
                ],
            ],
            [
                'heading' => '8. Incidents de sécurité',
                'paragraphs' => [
                    'Si un incident de sécurité affecte des renseignements personnels, nous enquêterons, prendrons des mesures de mitigation et communiquerons avec les personnes concernées lorsque la situation l’exige.',
                ],
            ],
        ],
    ],
    'cookies' => [
        'title' => 'Politique de cookies',
        'description' => 'Comment NexusVault utilise les cookies essentiels et le stockage local du navigateur.',
        'last_updated' => '6 juillet 2026',
        'sections' => [
            [
                'heading' => '1. Notre position actuelle',
                'paragraphs' => [
                    'NexusVault n’utilise pas de cookies publicitaires ou de suivi marketing. Le service utilise uniquement des cookies et du stockage navigateur nécessaires au fonctionnement, à la sécurité et aux préférences de base.',
                    'Pour cette raison, nous n’affichons pas de bannière “Accepter / Refuser” pour des cookies optionnels. Si des analytics ou outils non essentiels sont ajoutés plus tard, ils devront être bloqués jusqu’à votre consentement.',
                ],
            ],
            [
                'heading' => '2. Cookies essentiels',
                'paragraphs' => [
                    'Les cookies essentiels peuvent inclure le cookie de session Laravel, le jeton CSRF de sécurité, les informations nécessaires à la connexion et les préférences comme la langue ou le thème.',
                    'Sans ces cookies, NexusVault ne peut pas maintenir une session sécurisée, protéger les formulaires contre certaines attaques ou se souvenir de vos préférences immédiates.',
                ],
            ],
            [
                'heading' => '3. Stockage côté navigateur',
                'paragraphs' => [
                    'NexusVault peut utiliser le stockage de session du navigateur pour garder temporairement l’état de déverrouillage du coffre pendant votre session. Ce stockage est local à votre navigateur.',
                    'Les clés sensibles ne doivent pas être stockées de manière permanente dans un cookie. Le verrouillage du coffre et la déconnexion retirent l’état temporaire prévu par l’application.',
                ],
            ],
            [
                'heading' => '4. Contrôle par l’utilisateur',
                'paragraphs' => [
                    'Vous pouvez supprimer les cookies et données de site depuis les paramètres de votre navigateur. Cela peut vous déconnecter, réinitialiser certaines préférences ou exiger un nouveau déverrouillage du coffre.',
                    ['text' => 'Questions:', 'email' => 'privacy@nexusvault.dev', 'suffix' => '.'],
                ],
            ],
        ],
    ],
    'accessibility' => [
        'title' => 'Déclaration d’accessibilité',
        'description' => 'Notre engagement pour rendre NexusVault utilisable au clavier, lisible et compréhensible.',
        'last_updated' => '6 juillet 2026',
        'sections' => [
            [
                'heading' => '1. Engagement',
                'paragraphs' => [
                    'NexusVault vise une expérience accessible et inclusive. Notre cible de conception est WCAG 2.2 niveau AA lorsque cela est raisonnablement applicable à l’application.',
                    'Comme NexusVault protège des données sensibles, l’accessibilité doit coexister avec la sécurité: les flux d’authentification, de passkey et de coffre doivent rester sûrs sans devenir inutilement difficiles à utiliser.',
                ],
            ],
            [
                'heading' => '2. Mesures déjà prévues',
                'paragraphs' => [
                    'L’interface utilise des contrastes élevés, des libellés de champs, des états visuels, une navigation responsive, un sélecteur de langue et des zones d’action suffisamment grandes pour les écrans tactiles.',
                    'Les pages importantes doivent rester utilisables au clavier autant que possible, avec un ordre de lecture cohérent et des textes qui ne dépendent pas uniquement de la couleur.',
                ],
            ],
            [
                'heading' => '3. Limites connues',
                'paragraphs' => [
                    'Certains écrans de sécurité dépendent du navigateur ou de fournisseurs externes, notamment les dialogues passkey, les écrans OAuth et certains comportements de gestionnaire de mots de passe. Ces éléments ne sont pas toujours entièrement contrôlés par NexusVault.',
                    'NexusVault est encore en évolution. Des audits manuels au clavier, au lecteur d’écran et sur mobile doivent être répétés avant chaque déploiement majeur.',
                ],
            ],
            [
                'heading' => '4. Signaler un problème',
                'paragraphs' => [
                    ['text' => 'Si vous rencontrez une barrière d’accessibilité, contactez-nous à', 'email' => 'privacy@nexusvault.dev', 'suffix' => ' avec la page concernée, votre navigateur, votre technologie d’assistance si applicable, et une description du problème.'],
                ],
            ],
        ],
    ],
];
