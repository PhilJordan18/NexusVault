<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Security\CryptoService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Random\RandomException;

final readonly class UserKeyService implements Contracts\UserKeyServiceInterface
{
    public function __construct(private CryptoService $service) {}

    /**
     * @throws RandomException
     */
    public function provisionKey(string $password = null): array
    {
        $salt = random_bytes(32);
        $saltHex = bin2hex($salt);
        if ($password !== null) {
            $masterKey = $this->service->deriveMasterKey($password, $saltHex);
        } else {
            $masterKey = random_bytes(32);
        }

        $keyPair = $this->service->generateKeyPair();
        $encryptedPrivateKey = $this->service->encryptPrivateKey($keyPair['private_key'], $masterKey);
        $encryptedMasterKey = $password === null ? Crypt::encrypt($masterKey) : null;
        return ['salt' => $saltHex, 'masterKey' => $masterKey, 'public_key' => $keyPair['public_key'], 'private_key' => $encryptedPrivateKey['ciphertext'], 'encryptedMasterKey'  => $encryptedMasterKey, 'private_nonce' => $encryptedPrivateKey['nonce']];
    }

    public function reassembleKey(User $user, ?string $password = null): string
    {
        if ($password !== null) {
            return $this->service->deriveMasterKey($password, $user->salt);
        }
        return Crypt::decrypt($user->encrypted_master_key);
    }

    public function storeMasterKey(User $user, ?string $password = null): void
    {
        $masterKey = $this->reassembleKey($user, $password);
        Session::put('masterKey', base64_encode($masterKey));
    }

    public function getMasterKey(): string
    {
        $encoded = Session::get('masterKey');
        if (!$encoded) {
            throw new \RuntimeException('Master key not found in session. User must login again.');
        }
        return base64_decode($encoded);
    }
}
