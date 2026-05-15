<?php

namespace App\Services\Auth\Contracts;

use App\DTOs\Auth\LoginData;
use Illuminate\Http\RedirectResponse;

interface LoginServiceInterface
{
    public function authenticate(array $credentials):RedirectResponse;
    public function login(LoginData $data):RedirectResponse;
    public function logout():void;
}
