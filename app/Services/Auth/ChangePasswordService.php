<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\UserKeyServiceInterface;
use App\Services\Security\CryptoService;
use App\Services\Vault\EncryptionRotationService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

final readonly class ChangePasswordService
{
    public function __construct(
        private CryptoService $cryptoService,
        private EncryptionRotationService $rotationService,
        private UserKeyServiceInterface $userKeyService
    ) {}

    public function change(User $user, string $newPassword): void
    {
        if ($user->is_oauth) {
            abort(403, 'OAuth users cannot change their password.');
        }

        $oldMasterKey = Crypt::decrypt($user->encrypted_master_key);
        $newMasterKey = $this->cryptoService->deriveMasterKey($newPassword, $user->salt);

        // Ré-encrypter tous les services
        $this->rotationService->reEncryptAllServicesForUser($user->id, $oldMasterKey, $newMasterKey);

        // Rotation de la clé privée
        $this->userKeyService->rotatePrivateKey($user, $oldMasterKey, $newMasterKey);

        // Mise à jour du mot de passe + master key
        $user->forceFill([
            'password'             => Hash::make($newPassword),
            'encrypted_master_key' => Crypt::encrypt($newMasterKey),
        ])->save();

        // Mettre à jour la master key en session
        Session::put('masterKey', base64_encode($newMasterKey));
    }
}
