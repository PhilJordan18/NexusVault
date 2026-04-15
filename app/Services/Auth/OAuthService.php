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
        $dummyPassword = null;

        if (!$user) {
            $dummyPassword = Str::random(60);
            $hashedPassword = Hash::make($dummyPassword);
            $keys = $this->service->provisionKey($dummyPassword);

            $user = User::create([
                'name'                 => $oauthUser->name ?? 'User',
                'email'                => $oauthUser->email,
                'password'             => $hashedPassword,
                'salt'                 => $keys['salt'],
                'public_key'           => $keys['public_key'],
                'private_key'          => base64_encode($keys['private_key']),
                'encrypted_master_key' => $keys['encrypted_master_key'],
                'mfa_enabled'          => false,
                'totp_secret'          => null,
                'email_verified_at'    => now(),
            ]);
        }

        Auth::login($user);
        Session::regenerate();
        $this->service->storeMasterKey($user, $dummyPassword);
    }
}
