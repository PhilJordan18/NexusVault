<?php

return [
    'terms' => [
        'title' => 'Terms of Use',
        'description' => 'The rules for using NexusVault and the responsibilities that come with a zero-knowledge encrypted vault.',
        'last_updated' => 'July 6, 2026',
        'sections' => [
            [
                'heading' => '1. Accepting these terms',
                'paragraphs' => [
                    'By creating an account or using NexusVault, you agree to these Terms of Use. If you do not agree with them, you should not use the service.',
                    'NexusVault is a password manager built around browser-side encryption and a zero-knowledge model. These terms also explain what that means for your responsibilities.',
                ],
            ],
            [
                'heading' => '2. Your account',
                'paragraphs' => [
                    'You are responsible for the accuracy of the account information you provide, the security of your sign-in method, and all activity that occurs through your account.',
                    'You may only use NexusVault for lawful purposes. You must not attempt to access another person’s account, vault, or data without authorization.',
                ],
            ],
            [
                'heading' => '3. Vault password and recovery key',
                'paragraphs' => [
                    'Your vault password is used to derive the keys that decrypt your data locally in your browser. NexusVault does not receive that password in plaintext and cannot use it to read your vault items.',
                    'You are responsible for keeping your vault password and recovery key safe. If you lose both, NexusVault cannot recover your encrypted items. The supported fallback is a destructive reset that deletes the vault data and creates a new empty vault.',
                ],
            ],
            [
                'heading' => '4. Vault data and sharing',
                'paragraphs' => [
                    'Items you add to your vault, including logins, cards, and notes, must belong to you or be entrusted to you with permission.',
                    'When you share an item, you choose the recipient and agree that the recipient may access the decrypted content on their own device after accepting the share. You remain responsible for revoking access that should no longer exist.',
                ],
            ],
            [
                'heading' => '5. Availability and security',
                'paragraphs' => [
                    'We make reasonable efforts to keep NexusVault available, secure, and up to date. No online service can guarantee perfect availability or complete freedom from vulnerabilities.',
                    'You agree to use strong passwords, enable available protections when possible, and report suspicious behavior or security issues.',
                ],
            ],
            [
                'heading' => '6. Deletion and termination',
                'paragraphs' => [
                    'You may delete your account from settings. Deletion removes account data subject to applicable technical and legal limits.',
                    'We may suspend or limit access to NexusVault in cases of abuse, attempted intrusion, unlawful use, or serious risk to the service or other users.',
                ],
            ],
            [
                'heading' => '7. Limitation of liability',
                'paragraphs' => [
                    'NexusVault is provided “as is.” To the extent permitted by law, we are not responsible for indirect losses, interruptions, data loss caused by losing your recovery secrets, or damages caused by misuse of the service.',
                ],
            ],
            [
                'heading' => '8. Changes and contact',
                'paragraphs' => [
                    ['text' => 'These terms may evolve with the product. Material changes will be communicated in a reasonable way. Questions can be sent to', 'email' => 'privacy@nexusvault.dev', 'suffix' => '.'],
                ],
            ],
        ],
    ],
    'privacy' => [
        'title' => 'Privacy Policy',
        'description' => 'What NexusVault collects, what the server can see, and what stays encrypted in the browser.',
        'last_updated' => 'July 6, 2026',
        'sections' => [
            [
                'heading' => '1. Plain-language summary',
                'paragraphs' => [
                    'NexusVault is designed to minimize what the server can see. Sensitive vault content is encrypted in your browser before it is sent to the server.',
                    'We can see some information needed to operate the service, such as your email address, session metadata, and technical security data. We should not be able to read your passwords, secure notes, or cards in plaintext.',
                ],
            ],
            [
                'heading' => '2. Account data we collect',
                'paragraphs' => [
                    'We collect information you provide when creating and using your account: name, email address, language preference, theme, profile picture if you add one, and email verification status.',
                    'If you use OAuth, we receive the necessary information from the provider you choose, such as the OAuth identifier, name, and email address linked to that account. If you use a passkey, we store public verification data, never your biometric data.',
                ],
            ],
            [
                'heading' => '3. Vault data',
                'paragraphs' => [
                    'Vault items are stored in encrypted form. This may include encrypted blobs, cryptographic salts, encrypted keys, encrypted share payloads, and metadata needed for synchronization.',
                    'The server may process metadata such as the existence of an item, a share recipient, an acceptance status, or an update time. The zero-knowledge model is designed to prevent the server from reading the secret content of the item.',
                ],
            ],
            [
                'heading' => '4. Technical data and logs',
                'paragraphs' => [
                    'To protect the service, diagnose errors, and prevent abuse, we may process IP addresses, browser information, timestamps, requested routes, application errors, and session information.',
                    'This data should be used for security, maintenance, fraud prevention, or legal compliance, not for selling your advertising profile.',
                ],
            ],
            [
                'heading' => '5. Providers and processors',
                'paragraphs' => [
                    'NexusVault may use providers for hosting, email delivery, DNS/CDN, OAuth authentication, and technical infrastructure. Examples include a VPS host, a transactional email service, and the OAuth providers you choose.',
                    'These providers do not receive your plaintext vault password from us. OAuth providers may apply their own privacy policies when you use their sign-in button.',
                ],
            ],
            [
                'heading' => '6. Retention, deletion, and recovery',
                'paragraphs' => [
                    'We keep account and vault data while your account exists or while it is needed to provide the service, secure the platform, or comply with the law.',
                    'When you delete your account, NexusVault deletes associated data where reasonably possible. Some technical logs may be retained temporarily for security, backups, or legal obligations.',
                ],
            ],
            [
                'heading' => '7. Your choices',
                'paragraphs' => [
                    'You can update your profile, change language, manage sessions, delete passkeys, remove vault items, revoke shares, and delete your account from the application.',
                    ['text' => 'You can also ask privacy questions or report an issue at', 'email' => 'privacy@nexusvault.dev', 'suffix' => '.'],
                ],
            ],
            [
                'heading' => '8. Security incidents',
                'paragraphs' => [
                    'If a security incident affects personal information, we will investigate, take mitigation steps, and communicate with affected people when the situation requires it.',
                ],
            ],
        ],
    ],
    'cookies' => [
        'title' => 'Cookie Policy',
        'description' => 'How NexusVault uses essential cookies and browser-side storage.',
        'last_updated' => 'July 6, 2026',
        'sections' => [
            [
                'heading' => '1. Our current position',
                'paragraphs' => [
                    'NexusVault does not use advertising cookies or marketing trackers. The service uses only cookies and browser storage needed for functionality, security, and basic preferences.',
                    'For that reason, we do not show an “Accept / Reject” banner for optional cookies. If analytics or other non-essential tools are added later, they should be blocked until you consent.',
                ],
            ],
            [
                'heading' => '2. Essential cookies',
                'paragraphs' => [
                    'Essential cookies may include the Laravel session cookie, CSRF security token, information required for sign-in, and preferences such as language or theme.',
                    'Without these cookies, NexusVault cannot maintain a secure session, protect forms against certain attacks, or remember immediate preferences.',
                ],
            ],
            [
                'heading' => '3. Browser-side storage',
                'paragraphs' => [
                    'NexusVault may use browser session storage to temporarily keep the vault unlock state during your session. This storage is local to your browser.',
                    'Sensitive keys should not be stored permanently in a cookie. Locking the vault and signing out remove the temporary state managed by the application.',
                ],
            ],
            [
                'heading' => '4. User control',
                'paragraphs' => [
                    'You can delete cookies and site data from your browser settings. This may sign you out, reset preferences, or require you to unlock the vault again.',
                    ['text' => 'Questions:', 'email' => 'privacy@nexusvault.dev', 'suffix' => '.'],
                ],
            ],
        ],
    ],
    'accessibility' => [
        'title' => 'Accessibility Statement',
        'description' => 'Our commitment to keeping NexusVault keyboard-friendly, readable, and understandable.',
        'last_updated' => 'July 6, 2026',
        'sections' => [
            [
                'heading' => '1. Commitment',
                'paragraphs' => [
                    'NexusVault aims to provide an accessible and inclusive experience. Our design target is WCAG 2.2 Level AA where reasonably applicable to the application.',
                    'Because NexusVault protects sensitive data, accessibility must coexist with security: authentication, passkey, and vault flows should remain safe without becoming unnecessarily difficult to use.',
                ],
            ],
            [
                'heading' => '2. Measures already in place',
                'paragraphs' => [
                    'The interface uses strong contrast, field labels, visual states, responsive navigation, a language switcher, and action areas sized for touch screens.',
                    'Important pages should remain keyboard-usable as much as possible, with a coherent reading order and text that does not depend on color alone.',
                ],
            ],
            [
                'heading' => '3. Known limits',
                'paragraphs' => [
                    'Some security screens depend on the browser or external providers, including passkey dialogs, OAuth screens, and some password-manager behaviors. NexusVault does not fully control those elements.',
                    'NexusVault is still evolving. Manual keyboard, screen-reader, and mobile audits should be repeated before each major deployment.',
                ],
            ],
            [
                'heading' => '4. Report an issue',
                'paragraphs' => [
                    ['text' => 'If you encounter an accessibility barrier, contact us at', 'email' => 'privacy@nexusvault.dev', 'suffix' => ' with the affected page, your browser, assistive technology if applicable, and a description of the issue.'],
                ],
            ],
        ],
    ],
];
