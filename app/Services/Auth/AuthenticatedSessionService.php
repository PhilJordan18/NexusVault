<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Establishes an authenticated session without inheriting anything from the previous one.
 *
 * MFA and vault flags are per-user proofs: they must never survive a login, and a
 * session that already belonged to another user is destroyed instead of being reused.
 */
final readonly class AuthenticatedSessionService
{
    /**
     * @var list<string>
     */
    private const PER_USER_FLAGS = ['mfa_verified', 'vault_unlocked_at', 'masterKey', 'mfa_pending_secret'];

    public function establish(User $user): void
    {
        $this->prepareForLogin();

        Auth::login($user);
        Session::regenerate();
    }

    /**
     * Purge every per-user proof from the session, and the whole session when it was
     * already authenticated, so that the login that follows starts clean.
     */
    public function prepareForLogin(): void
    {
        if (Auth::check()) {
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            return;
        }

        Session::forget(self::PER_USER_FLAGS);
    }
}
