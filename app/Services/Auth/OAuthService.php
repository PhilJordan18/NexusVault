<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\OAuthServiceInterface;
use App\Services\Auth\Contracts\UserKeyServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

final readonly class OAuthService implements OAuthServiceInterface
{
    public function __construct(private UserKeyServiceInterface $service) {}

    public function handleCallback($oauthUser, string $provider): void
    {
        $user = User::where('email', $oauthUser->email)->first();
        if (!$user) {
            $password = Hash::make(Str::random(60));
            $keys = $this->service->provisionKey();
            $user = User::create(['name' => $oauthUser->name ?? 'User', 'email' => $oauthUser->email, 'password' => $password, 'salt' => $keys['salt'], 'public_key' => $keys['public_key'], 'private_key' => base64_encode($keys['private_key']), 'encrypted_master_key' => $keys['encryptedMasterKey'], 'mfa_enabled' => false, 'totp_secret' => null ,'email_verified_at' => now()]);
        }
        Auth::login($user);
        Session::regenerate();
        $this->service->storeMasterKey($user);
    }
}
