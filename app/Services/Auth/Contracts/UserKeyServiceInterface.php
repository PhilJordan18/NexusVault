<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface UserKeyServiceInterface
{
    public function provisionKey(string $password = null) : array;
    public function storeMasterKey(User $user, ?string $password = null) : void;
    public function getMasterKey() : string;
    public function reassembleKey(User $user, ?string $password = null) : string;
}
