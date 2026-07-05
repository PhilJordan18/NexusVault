<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\RegisterServiceInterface;
use Illuminate\Support\Facades\Hash;

final readonly class RegisterService implements RegisterServiceInterface
{
    public function register(array $data): User
    {
        $password = Hash::make($data['password']);
        $vaultKeyEnvelope = json_decode($data['vault_key_envelope'], true, 512, JSON_THROW_ON_ERROR);
        $vaultRecoveryEnvelope = json_decode($data['vault_recovery_envelope'], true, 512, JSON_THROW_ON_ERROR);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'salt' => bin2hex(random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES)),
            'public_key' => $data['public_key'],
            'private_key' => $data['encrypted_private_key'],
            'private_nonce' => null,
            'encrypted_master_key' => null,
            'vault_key_envelope' => $vaultKeyEnvelope,
            'vault_recovery_envelope' => $vaultRecoveryEnvelope,
            'mfa_enabled' => false,
            'totp_secret' => null,
        ]);

        $user->sendEmailVerificationNotification();

        return $user;
    }
}
