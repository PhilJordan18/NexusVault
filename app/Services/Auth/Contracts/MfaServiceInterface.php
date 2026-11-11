<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface MfaServiceInterface
{
    public function generateSecret(): string;

    /**
     * @return array{mime_type: string, contents: string}
     */
    public function getQrCodeImage(User $user, string $secret): array;

    public function getQrCodeUrl(User $user, string $secret): string;

    public function verifyCode(User $user, string $code): bool;

    public function verifySecretCode(string $secret, string $code): bool;

    public function enableMfa(User $user, string $secret, string $code): bool;

    public function disableMfa(User $user): bool;
}
