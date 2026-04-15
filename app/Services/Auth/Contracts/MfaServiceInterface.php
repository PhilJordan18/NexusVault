<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface MfaServiceInterface
{
    public function generateSecret() : string;
    public function getQrCodeUrl(User $user) : string;
    public function verifyCode(User $user, string $code) : bool;
    public function enableMfa(User $user, string $code) : bool;
    public function disableMfa(User $user) : bool;
}
