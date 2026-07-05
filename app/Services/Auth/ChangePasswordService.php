<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final readonly class ChangePasswordService
{
    public function change(User $user, string $newPassword): void
    {
        if ($user->is_oauth) {
            abort(403, 'OAuth users cannot change their password.');
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();
    }
}
