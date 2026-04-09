<?php

namespace App\Http\Controllers;

use App\Services\Auth\Contracts\OAuthServiceInterface;
use Laravel\Socialite\Facades\Socialite;


final class OAuthController extends Controller
{
    public function __construct(private readonly OAuthServiceInterface $service) {}

    public function redirectGithub() { return Socialite::driver('github')->redirect(); }
    public function redirectGoogle() { return Socialite::driver('google')->redirect(); }
    public function handleGithub() { return $this->handleCallback('github'); }
    public function handleGoogle() { return $this->handleCallback('google'); }

    private function handleCallback(string $provider) {
        $oauthUser =  Socialite::driver($provider)->stateless()->user();
        $this->service->handleCallback($oauthUser, $provider);
        return redirect()->intended('/dashboard');
    }
}
