<?php

namespace App\Services\Security;

use Random\RandomException;
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
}
