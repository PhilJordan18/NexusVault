<?php

namespace App\Models;

use App\Notifications\EmailVerifier;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'salt', 'public_key', 'private_key', 'encrypted_master_key', 'mfa_enabled', 'totp_secret', 'email_verified_at', 'pfp', 'private_nonce'
    ];
    protected $hidden = [
        'password', 'salt', 'private_key', 'private_nonce', 'totp_secret', 'remember_token'
    ];
    protected $casts = [
        'email_verified_at' => 'datetime', 'mfa_enabled' => 'boolean',
    ];

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new EmailVerifier());
    }
}
