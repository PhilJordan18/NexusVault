<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\RegisterServiceInterface;
use App\Services\Auth\Contracts\UserKeyServiceInterface;
use Illuminate\Support\Facades\Hash;
use Random\RandomException;

final readonly class RegisterService implements RegisterServiceInterface
{
    public function __construct(private UserKeyServiceInterface $service) {}

    public function register(array $data): User
    {
        $keys = $this->service->provisionKey($data['password']);
       $password = Hash::make($data['password']);
       $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $password, 'salt' => $keys['salt'], 'public_key' => $keys['public_key'], 'private_key' => base64_encode($keys['private_key']), 'private_nonce' => $keys['private_nonce'], 'encrypted_master_key' => $keys['encrypted_master_key'], 'mfa_enabled' => false, 'totp_secret' => null]);
       $user->sendEmailVerificationNotification();
       return $user;
    }
}
