<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\MfaServiceInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Confirms that the person behind an authenticated session still holds a factor
 * before a destructive action. A stolen session alone is never enough.
 */
final readonly class ReauthenticationService
{
    public function __construct(private MfaServiceInterface $mfa) {}

    /**
     * Accept the login password (never the OAuth placeholder) or a TOTP code generated now.
     */
    public function confirm(User $user, ?string $password, ?string $totpCode): bool
    {
        if ($password !== null && $password !== '' && ! $user->is_oauth) {
            return Hash::check($password, $user->password);
        }

        if ($totpCode !== null && $totpCode !== '' && $user->mfa_enabled) {
            return $this->mfa->verifyCode($user, $totpCode);
        }

        return false;
    }
}
