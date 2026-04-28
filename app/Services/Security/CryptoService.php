<?php

namespace App\Services\Security;

use App\Services\Auth\UserKeyService;
use Random\RandomException;
use RuntimeException;
use SodiumException;

final readonly class CryptoService
{
    /**
     * @throws SodiumException
     */
    public function deriveMasterKey(string $password, string $salt) : string {
        $pepper  = config('app.pepper');
        return sodium_crypto_pwhash(
            32,
            $password . $pepper,
            hex2bin($salt),
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
        );
    }

    public function generateKeyPair() : array {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ];
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function encryptPrivateKey(string $privateKey, string $masterKey) : array {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = sodium_crypto_secretbox($privateKey, $nonce, $masterKey);
        return [
            'ciphertext' => $encrypted,
            'nonce' => $nonce,
        ];
    }

    /**
     * @throws SodiumException
     */
    public function decryptPrivateKey(string $ciphertext, string $nonce, string $masterKey): string {
        $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $masterKey);

        if ($decrypted === false) {
            throw new RuntimeException('Impossible de déchiffrer la clé privée – master key incorrecte ou données corrompues.');
        }

        return $decrypted;
    }

    /**
     * @throws RandomException
     */
    public function encryptWithMasterKey(string $data): array
    {
        $masterKey = app(UserKeyService::class)->getMasterKey();

        $iv = random_bytes(12);                    // Recommandé pour GCM
        $tag = '';
        $ciphertext = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $masterKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv'         => bin2hex($iv),          // 24 caractères hex
            'tag'        => bin2hex($tag),         // 32 caractères hex
        ];
    }

    /**
     * Déchiffre une donnée sensible avec AES-256-GCM
     *
     * @throws RuntimeException
     */
    public function decryptWithMasterKey(string $ciphertextBase64, string $ivHex, string $tagHex): string
    {
        $masterKey = app(UserKeyService::class)->getMasterKey();

        $ciphertext = base64_decode($ciphertextBase64);
        $iv         = hex2bin($ivHex);
        $tag        = hex2bin($tagHex);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $masterKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new RuntimeException('Impossible de déchiffrer les données du service.');
        }

        return $decrypted;
    }

    /**
     * Chiffre des données avec la clé publique RSA d'un utilisateur
     */
    public function encryptWithPublicKey(string $data, string $publicKey): string
    {
        $encrypted = '';
        $success = openssl_public_encrypt($data, $encrypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);

        if (!$success || empty($encrypted)) {
            $errors = [];
            while ($err = openssl_error_string()) {
                $errors[] = $err;
            }
            throw new \RuntimeException('Échec du chiffrement RSA. Erreurs OpenSSL : ' . implode(' | ', $errors));
        }

        return base64_encode($encrypted);
    }

    /**
     * Déchiffre des données avec la clé privée RSA de l'utilisateur
     */
    public function decryptWithPrivateKey(string $encryptedBase64, string $privateKey): string
    {
        if (empty($encryptedBase64)) {
            throw new \RuntimeException('Aucune donnée chiffrée à déchiffrer (shared_data vide).');
        }

        $encrypted = base64_decode($encryptedBase64);

        $decrypted = '';
        $success = openssl_private_decrypt($encrypted, $decrypted, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);

        if (!$success || $decrypted === '') {
            throw new \RuntimeException('Impossible de déchiffrer avec la clé privée. La clé est peut-être invalide.');
        }

        return $decrypted;
    }

    /**
     * Chiffre des données avec une clé AES personnalisée
     */
    public function encryptWithCustomKey(string $data, string $key): array
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv'         => bin2hex($iv),
            'tag'        => bin2hex($tag),
        ];
    }

    /**
     * Déchiffre des données avec une clé AES personnalisée
     */
    public function decryptWithCustomKey(string $ciphertextBase64, string $ivHex, string $tagHex, string $key): string
    {
        $ciphertext = base64_decode($ciphertextBase64);
        $iv = hex2bin($ivHex);
        $tag = hex2bin($tagHex);

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($decrypted === false) {
            throw new RuntimeException('Impossible de déchiffrer les données avec la clé fournie.');
        }

        return $decrypted;
    }
}
