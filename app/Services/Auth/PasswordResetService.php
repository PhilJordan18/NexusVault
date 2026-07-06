<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class PasswordResetService
{
    public function reset(User $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ])->save();
    }
}
