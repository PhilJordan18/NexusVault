<?php

namespace App\Mappers;

use App\DTOs\Auth\LoginData;

final class AuthMapper
{
    public static function fromEmailRequest(array $data): LoginData { return new LoginData( email: $data[ 'email' ], password: null ); }

    public static function fromPasswordRequest(array $data): LoginData { return new LoginData( email: $data[ 'email' ], password: $data[ 'password' ] ?? null); }
}
