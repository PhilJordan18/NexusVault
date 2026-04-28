<?php

namespace App\Services\Auth\Contracts;

use Illuminate\Http\RedirectResponse;

interface OAuthServiceInterface
{
    public function handleCallback($oauthUser, string $provider): RedirectResponse;
}
