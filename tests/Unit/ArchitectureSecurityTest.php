<?php

/*
 * Preset securite de Pest : interdit md5, sha1, eval, exec, unserialize, etc.
 * PasswordService utilise sha1 volontairement : c'est le protocole k-anonymat
 * de Have I Been Pwned, qui exige le SHA-1 du mot de passe pour interroger
 * l'API par prefixe de 5 caracteres. Aucun autre usage n'est tolere.
 */
arch()->preset()->security()->ignoring('App\Services\PasswordService');
