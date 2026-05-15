<?php

namespace App\Services\Auth;

use App\Models\Service;
use App\Models\Share;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AccountDeletionService
{
    public function delete(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();

        Service::where('shared_user_id', $user->id)->delete();

        $user->services()->delete();

        Share::where('from_user_id', $user->id)
            ->orWhere('to_user_id', $user->id)
            ->delete();

        $user->webAuthnCredentials()->delete();

        $user->delete();
    }
}
