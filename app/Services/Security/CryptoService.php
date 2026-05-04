<?php

namespace App\Services\Security;

use App\Services\Auth\UserKeyService;
use Random\RandomException;
use RuntimeException;
use SodiumException;

final readonly class CryptoService
{
    /**
     * Derive a 256-bit master key from password + salt + pepper using Argon2id.
     */
    public function deriveMasterKey(string $password, string $salt): string
    {
        $pepper = config('app.pepper');

        return sodium_crypto_pwhash(
            32,
            $password . $pepper,
            hex2bin($salt),
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
    }

    /**
     * Generate a new RSA-2048 key pair.
     */
    public function generateKeyPair(): array
    {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);
        openssl_pkey_export($resource, $privateKey);
        $publicKey = openssl_pkey_get_details($resource)['key'];

        return [
            'private_key' => $privateKey,
            'public_key'  => $publicKey,
        ];
    }

    /**
     * Encrypt a private key using the master key (libsodium secretbox).
     */
    public function encryptPrivateKey(string $privateKey, string $masterKey): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = sodium_crypto_secretbox($privateKey, $nonce, $masterKey);

        return [
            'ciphertext' => $encrypted,
            'nonce'      => $nonce,
        ];
    }

    /**
     * Decrypt a private key using the master key.
     */
    public function decryptPrivateKey(string $ciphertext, string $nonce, string $masterKey): string
    {
        $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $masterKey);

        if ($decrypted === false) {
            throw new RuntimeException('Failed to decrypt private key. Master key may be incorrect.');
        }

        return $decrypted;
    }

    /**
     * Encrypt data using AES-256-GCM with the user's master key.
     */
    public function encryptWithMasterKey(string $data): array
    {
        $masterKey = app(UserKeyService::class)->getMasterKey();

        $iv = random_bytes(12);
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
            'iv'         => bin2hex($iv),
            'tag'        => bin2hex($tag),
        ];
    }

    /**
     * Decrypt data using AES-256-GCM with the user's master key.
     */
    public function decryptWithMasterKey(string $ciphertextBase64, string $ivHex, string $tagHex): string
    {
        $masterKey = app(UserKeyService::class)->getMasterKey();

        $ciphertext = base64_decode($ciphertextBase64);
        $iv = hex2bin($ivHex);
        $tag = hex2bin($tagHex);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $masterKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new RuntimeException('Failed to decrypt service data. The data may be corrupted.');
        }

        return $decrypted;
    }

    /**
     * Encrypt data with a recipient's RSA public key.
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
            throw new RuntimeException('RSA encryption failed: ' . implode(' | ', $errors));
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypt data with a user's RSA private key.
     */
    public function decryptWithPrivateKey(string $encryptedBase64, string $privateKey): string
    {
        if (empty($encryptedBase64)) {
            throw new RuntimeException('No encrypted data provided for RSA decryption.');
        }

        $encrypted = base64_decode($encryptedBase64);
        $decrypted = '';
        $success = openssl_private_decrypt($encrypted, $decrypted, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);

        if (!$success || $decrypted === '') {
            throw new RuntimeException('RSA decryption failed. The private key may be invalid.');
        }

        return $decrypted;
    }

    /**
     * Encrypt data with a custom AES key (used for sharing).
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
     * Decrypt data with a custom AES key (used for sharing).
     */
    public function decryptWithCustomKey(string $ciphertextBase64, string $ivHex, string $tagHex, string $key): string
    {
        $ciphertext = base64_decode($ciphertextBase64);
        $iv = hex2bin($ivHex);
        $tag = hex2bin($tagHex);

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($decrypted === false) {
            throw new RuntimeException('Failed to decrypt shared data with the provided key.');
        }

        return $decrypted;
    }
}
