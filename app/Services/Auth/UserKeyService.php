<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Security\CryptoService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Random\RandomException;
use RuntimeException;
use SodiumException;

final readonly class UserKeyService implements Contracts\UserKeyServiceInterface
{
    public function __construct(private CryptoService $service) {}

    /**
     * @throws RandomException|SodiumException
     */
    public function provisionKey(string $password = null): array
    {
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $saltHex = bin2hex($salt);

        $masterKey = $password !== null
            ? $this->service->deriveMasterKey($password, $saltHex)
            : random_bytes(32);

        $keyPair = $this->service->generateKeyPair();
        $encryptedPrivate = $this->service->encryptPrivateKey($keyPair['private_key'], $masterKey);

        $encryptedMasterKey = $password === null
            ? Crypt::encrypt($masterKey)
            : null;

        return [
            'salt'                  => $saltHex,
            'public_key'            => $keyPair['public_key'],
            'private_key'           => $encryptedPrivate['ciphertext'],
            'private_nonce'         => $encryptedPrivate['nonce'],
            'encrypted_master_key'  => $encryptedMasterKey,
        ];
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function reassembleKey(User $user, ?string $password = null): string
    {
        if ($password !== null) {
            return $this->service->deriveMasterKey($password, $user->salt);
        }

        if (empty($user->encrypted_master_key)) {
            $masterKey = random_bytes(32);
            $encrypted = Crypt::encrypt($masterKey);
            $user->update(['encrypted_master_key' => $encrypted]);
            return $masterKey;
        }

        return Crypt::decrypt($user->encrypted_master_key);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function storeMasterKey(User $user, ?string $password = null): void
    {
        $masterKey = $this->reassembleKey($user, $password);
        Session::put('masterKey', base64_encode($masterKey));
    }

    public function getMasterKey(): string
    {
        $encoded = Session::get('masterKey');
        if (!$encoded) {
            throw new RuntimeException('Master key not found in session. User must login again.');
        }
        return base64_decode($encoded);
    }
}
