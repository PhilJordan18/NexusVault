<?php

namespace App\Services\Auth\Contracts;

interface OAuthServiceInterface
{
    public function handleCallback($oauthUser, string $provider): void;
}
