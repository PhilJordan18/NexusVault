<?php

namespace App\Services\Auth\Contracts;

use Illuminate\Http\RedirectResponse;

interface LoginServiceInterface
{
    public function authenticate(array $credentials):RedirectResponse;
    public function login(array $credentials):RedirectResponse;
    public function logout():void;
}
