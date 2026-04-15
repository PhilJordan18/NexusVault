<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\MfaServiceInterface;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

final readonly class MfaService implements Contracts\MfaServiceInterface
{

    public function __construct(private Google2FA $google2FA) {}

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function generateSecret(): string
    {
        return $this->google2FA->generateSecretKey(32);
    }

    public function getQrCodeUrl(User $user): string
    {
        return $this->google2FA->getQRCodeUrl('NexusVault', $user->email, $user->totp_secret);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (empty($user->totp_secret)) {
            return false;
        }

        return $this->google2FA->verifyKey($user->totp_secret, $code, 2);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function enableMfa(User $user, string $code): bool
    {
       if ($this->verifyCode($user, $code)) {
           $user->update(['mfa_enabled' => true ,'totp_secret' => $user->totp_secret]);
           return true;
       }
       return false;
    }

    public function disableMfa(User $user): bool
    {
        $user->update(['mfa_enabled' => false ,'totp_secret' => null]);
        return true;
    }
}
