<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\UserKeyServiceInterface;
use App\Services\Security\CryptoService;
use App\Services\Vault\EncryptionRotationService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class PasswordResetService
{
    public function __construct(
        private CryptoService             $cryptoService,
        private EncryptionRotationService $rotationService,
        private UserKeyServiceInterface   $userKeyService
    )
    {
    }

    /**
     * Réinitialise le mot de passe + effectue la rotation complète des clés
     */
    public function reset(User $user, string $newPassword): void
    {
        // 1. Récupérer l'ancienne master key
        $oldMasterKey = Crypt::decrypt($user->encrypted_master_key);

        // 2. Dériver la nouvelle master key
        $newMasterKey = $this->cryptoService->deriveMasterKey($newPassword, $user->salt);

        // 3. Ré-encrypter tous les services
        $this->rotationService->reEncryptAllServicesForUser($user->id, $oldMasterKey, $newMasterKey);

        // 4. Rotation de la clé privée
        $this->userKeyService->rotatePrivateKey($user, $oldMasterKey, $newMasterKey);

        // 5. Mettre à jour le mot de passe + la master key chiffrée
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
            'encrypted_master_key' => Crypt::encrypt($newMasterKey),
        ])->save();
    }
}
