<?php

namespace App\Services;

use App\Models\User;
use App\Services\Security\CryptoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class AuthService
{
    public function register(array $data): User {
        $crypto = app(CryptoService::class);
        $salt = random_bytes(32);
        $masterKey = $crypto->deriveMasterKey($data['password'], $salt);
        $keyPair = $crypto->generateKeyPair();
        $encryptedPrivate = $crypto->encryptPrivateKey($keyPair['private_key'], $masterKey);
        $password = Hash::make($data['password']);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'salt' => $salt,
            'public_key' => $keyPair['public_key'],
            'private_key' => $encryptedPrivate['ciphertext'],
            'mfa_enabled'  => false,
            'totp_secret'  => '',
        ]);

        $user->sendEmailVerificationNotification();
        return $user;
    }

    public function login(array $credentials) {
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        if (!$user->hasVerifiedEmail()) throw ValidationException::withMessages(['email' => 'Please verify your email before logging in']);
        Auth::login($user);
        Session::regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(): void {
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
    }

    public function handleOAuthCallback($oauthUser, string $provider): void {
        $user = User::where('email', $oauthUser->email)->first();
        if (!$user) {
            $dummyPassword = Hash::make(Str::random(60));
            $salt = Str::random(64);
            $publicKey = 'TODO_OAUTH_PUBLIC_KEY_' . Str::random(32);
            $privateKey = 'TODO_OAUTH_PRIVATE_KEY_' . Str::random(32);

            $user = User::create([
                'name' => $oauthUser->name ?? $oauthUser->nickname ?? 'User',
                'email' => $oauthUser->email,
                'password' => $dummyPassword,
                'salt' => $salt,
                'public_key' => $publicKey,
                'private_key' => $privateKey,
                'mfa_enabled' => false,
                'totp_secret' => '',
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user);
        Session::regenerate();
    }
}
