<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laragear\WebAuthn\Models\WebAuthnCredential;

class Passkey extends WebAuthnCredential
{
    use HasFactory;

    protected $fillable = ['name', 'last_used_at',
        'user_id',
        'credential_id',
        'public_key',
        'user_handle'
    ];
    protected function casts(): array
    {
        return [
            'last_used_at' => 'timestamp',
        ];
    }
}
