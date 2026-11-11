<?php

namespace App\Http\Controllers;

use App\Services\Auth\Contracts\OAuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

final class OAuthController extends Controller
{
    public function __construct(private readonly OAuthServiceInterface $service) {}

    public function redirectGithub(): SymfonyRedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    public function redirectGoogle(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGithub(): RedirectResponse
    {
        return $this->handleCallback('github');
    }

    public function handleGoogle(): RedirectResponse
    {
        return $this->handleCallback('google');
    }

    /**
     * The callback is only accepted when its state matches the one stored in the session
     * at redirect time. This is the CSRF protection of the authorization code flow and it
     * requires the session cookie to survive the round trip to the provider (SameSite=lax).
     */
    private function handleCallback(string $provider): RedirectResponse
    {
        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->with('error', __('The sign-in request has expired or is invalid. Please try again.'));
        }

        return $this->service->handleCallback($oauthUser, $provider);
    }
}
