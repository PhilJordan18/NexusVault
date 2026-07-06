<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\OAuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

final readonly class OAuthService implements OAuthServiceInterface
{
    public function handleCallback($oauthUser, string $provider): RedirectResponse
    {
        $user = User::where('email', $oauthUser->email)->first();

        if (! $user) {
            $dummyPassword = Str::random(60);

            $user = User::create([
                'name' => $oauthUser->name ?? 'User',
                'email' => $oauthUser->email,
                'password' => Hash::make($dummyPassword),
                'salt' => bin2hex(random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES)),
                'public_key' => '',
                'private_key' => '',
                'private_nonce' => null,
                'encrypted_master_key' => null,
                'mfa_enabled' => false,
                'totp_secret' => null,
                'email_verified_at' => now(),
                'is_oauth' => true,
            ]);
        }

        Auth::login($user);
        Session::regenerate();

        if ($user->mfa_enabled) {
            return redirect()->route('mfa.verify.login');
        }

        return redirect()->route($user->requiresClientVaultSetup() ? 'vault.setup' : 'vault.unlock');
    }
}
