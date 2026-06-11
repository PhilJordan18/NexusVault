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

    public static function cannotShareWithYourself(): self
    {
        return new self('You cannot share a service with yourself.');
    }

    public static function alreadyShared(): self
    {
        return new self('This service is already actively shared with this user.');
    }

    public static function sharedAccessCannotBeReshared(): self
    {
        return new self('Shared items can only be shared by their original owner.');
    }
}
