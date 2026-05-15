<?php

namespace App\DTOs\Auth;

final readonly class LoginData
{
    public function __construct(public string $email, public ?string $password = null) {}

    public function toArray(): array { return [ 'email' => $this->email, 'password' => $this->password ]; }
}
