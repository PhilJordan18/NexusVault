<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Security\CryptoService;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Random\RandomException;
use RuntimeException;
use SodiumException;

final readonly class UserKeyService implements Contracts\UserKeyServiceInterface
{
    public function __construct(private CryptoService $cryptoService) {}

    public function provisionKey(?string $password = null): array
    {
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $saltHex = bin2hex($salt);

        $masterKey = $password !== null
            ? $this->cryptoService->deriveMasterKey($password, $saltHex)
            : random_bytes(32);

        $keyPair = $this->cryptoService->generateKeyPair();
        $encryptedPrivate = $this->cryptoService->encryptPrivateKey($keyPair['private_key'], $masterKey);

        $encryptedMasterKey = Crypt::encrypt($masterKey);

        return [
            'salt'                  => $saltHex,
            'public_key'            => $keyPair['public_key'],
            'private_key'           => $encryptedPrivate['ciphertext'],
            'private_nonce'         => $encryptedPrivate['nonce'],
            'encrypted_master_key'  => $encryptedMasterKey,
        ];
    }

    public function reassembleKey(User $user, ?string $password = null): string
    {
        if ($password !== null) {
            return $this->cryptoService->deriveMasterKey($password, $user->salt);
        }

        if (empty($user->encrypted_master_key)) {
            throw new RuntimeException('Master key is missing for this account. Please login with password first.');
        }

        return Crypt::decrypt($user->encrypted_master_key);
    }

    public function storeMasterKey(User $user, ?string $password = null): void
    {
        $masterKey = $this->reassembleKey($user, $password);
        Session::put('masterKey', base64_encode($masterKey));

        if (empty($user->encrypted_master_key)) {
            $user->update(['encrypted_master_key' => Crypt::encrypt($masterKey)]);
        }
    }

    public function getMasterKey(): string
    {
        $encoded = Session::get('masterKey');

        if (!$encoded && auth()->check()) {
            $user = auth()->user();

            try {
                if (!empty($user->encrypted_master_key)) {
                    $masterKey = Crypt::decrypt($user->encrypted_master_key);
                } else {
                    throw new RuntimeException('Master key is missing. Please log in again.');
                }

                Session::put('masterKey', base64_encode($masterKey));
                $encoded = Session::get('masterKey');

            } catch (Exception $e) {
                auth()->logout();
                Session::invalidate();
                throw new RuntimeException('Session expired. Please log in again.');
            }
        }

        if (!$encoded) {
            throw new RuntimeException('Master key not found. Please log in again.');
        }

        return base64_decode($encoded);
    }

    public function getDecryptedPrivateKey(User $user): string
    {
        $masterKey = $this->getMasterKey();
        $ciphertext = base64_decode($user->private_key);
        $nonce = $user->private_nonce;

        return $this->cryptoService->decryptPrivateKey($ciphertext, $nonce, $masterKey);
    }
}
