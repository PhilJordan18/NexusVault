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

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function getMasterKey(): string
    {
        $encoded = Session::get('masterKey');

        if (!$encoded && auth()->check()) {
            $user = auth()->user();

            try {
                // On essaie de reconstituer la clé correctement
                if (!empty($user->encrypted_master_key)) {
                    // Cas des utilisateurs créés sans mot de passe (OAuth)
                    $masterKey = Crypt::decrypt($user->encrypted_master_key);
                } else {
                    // Cas normal (utilisateur avec mot de passe)
                    // On ne peut pas la régénérer sans le mot de passe → on force une reconnexion propre
                    throw new RuntimeException('Master key missing. Please login again.');
                }

                Session::put('masterKey', base64_encode($masterKey));
                $encoded = Session::get('masterKey');

            } catch (\Exception $e) {
                // On déconnecte proprement l’utilisateur
                auth()->logout();
                Session::invalidate();
                throw new RuntimeException('Session expirée. Veuillez vous reconnecter.');
            }
        }

        if (!$encoded) {
            throw new RuntimeException('Master key not found. Please login again.');
        }

        return base64_decode($encoded);
    }

    /**
     * Retourne la clé privée RSA déchiffrée (utilisée uniquement quand on en a besoin)
     *
     * @throws SodiumException|RandomException
     */
    public function getDecryptedPrivateKey(User $user): string
    {
        $masterKey = $this->getMasterKey();
        $ciphertext = base64_decode($user->private_key);
        $nonce = $user->private_nonce;
        return $this->service->decryptPrivateKey($ciphertext, $nonce, $masterKey);
    }
}
