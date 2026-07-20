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
     * @return array{mime_type: string, contents: string}
     *
     * @throws MissingQrCodeServiceException
     */
    public function getQrCodeImage(User $user): array
    {
        $qrCode = $this->google2FA->getQRCodeInline('NexusVault', $user->email, $user->totp_secret, 220);

        if (str_starts_with($qrCode, 'data:image/')) {
            return $this->decodeDataUri($qrCode);
        }

        $mimeType = str_starts_with(ltrim($qrCode), '<') ? 'image/svg+xml' : 'image/png';

        return [
            'mime_type' => $mimeType,
            'contents' => $qrCode,
        ];
    }

    /**
     * @throws MissingQrCodeServiceException
     */
    public function getQrCodeUrl(User $user): string
    {
        $qrCode = $this->getQrCodeImage($user);

        return sprintf('data:%s;base64,%s', $qrCode['mime_type'], base64_encode($qrCode['contents']));
    }

    /**
     * @return array{mime_type: string, contents: string}
     */
    private function decodeDataUri(string $dataUri): array
    {
        [$metadata, $contents] = explode(',', $dataUri, 2);
        $mimeType = str_replace(['data:', ';base64'], '', $metadata);

        return [
            'mime_type' => $mimeType,
            'contents' => base64_decode($contents, true) ?: '',
        ];
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
