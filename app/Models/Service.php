<?php

namespace App\Models;

use App\Services\Security\CryptoService;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{

    protected $fillable = [
        'user_id', 'name', 'url', 'favicon', 'username', 'password', 'notes', 'password_iv', 'password_tag', 'username_iv', 'username_tag', 'notes_iv', 'notes_tag', 'shared_user_id', 'shared_at'
    ];

    protected $hidden = [
        'password_iv', 'password_tag',
    ];

    protected $casts = [
        'shared_at' => 'datetime',
    ];

    public function getUsernameAttribute(string $value): string {
        return app(CryptoService::class)->decryptWithMasterKey(
            $value,
            $this->username_iv,
            $this->username_tag
        );
    }

    public function getPasswordAttribute(string $value): string {
        return app(CryptoService::class)->decryptWithMasterKey(
            $value,
            $this->password_iv ?? $this->iv,   // compat temporaire
            $this->password_tag ?? $this->tag
        );
    }

    public function getNotesAttribute(?string $value): ?string {
        if (!$value) return null;
        return app(CryptoService::class)->decryptWithMasterKey(
            $value,
            $this->notes_iv,
            $this->notes_tag
        );
    }
}
