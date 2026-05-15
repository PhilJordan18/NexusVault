<?php

namespace App\Exceptions;

use RuntimeException;

class ShareException extends RuntimeException
{
    public static function invalidRecipient(): self
    {
        return new self('The recipient does not have a valid public key.');
    }

    public static function unauthorized(): self
    {
        return new self('You are not authorized to perform this action.');
    }
}
