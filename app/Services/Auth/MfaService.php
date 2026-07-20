<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\MfaServiceInterface;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;

final readonly class MfaService implements MfaServiceInterface
{
    private Google2FAQRCode $google2FA;

    public function __construct(?Google2FAQRCode $google2FA = null)
    {
        $this->google2FA = $google2FA ?? new Google2FAQRCode;
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function generateSecret(): string
    {
        return $this->google2FA->generateSecretKey(32);
    }

    /**
     * @throws MissingQrCodeServiceException
     */
    public function getQrCodeUrl(User $user): string
    {
        $qrCode = $this->google2FA->getQRCodeInline('NexusVault', $user->email, $user->totp_secret, 220);

        if (str_starts_with($qrCode, 'data:image/')) {
            return $qrCode;
        }

        $mimeType = str_starts_with(ltrim($qrCode), '<') ? 'image/svg+xml' : 'image/png';

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($qrCode));
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
            $user->update(['mfa_enabled' => true]);

            return true;
        }

        return false;
    }

    public function disableMfa(User $user): bool
    {
        $user->update(['mfa_enabled' => false, 'totp_secret' => null]);

        return true;
    }
}
