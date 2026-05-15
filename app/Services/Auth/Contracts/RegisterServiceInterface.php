<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface RegisterServiceInterface
{
    public function register(array $data): User;
}
