<?php

namespace App\Models;

use App\Services\Security\CryptoService;
use Exception;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'user_id', 'name', 'url', 'favicon',
        'username', 'password', 'notes',
        'username_iv', 'username_tag',
        'password_iv', 'password_tag',
        'notes_iv', 'notes_tag',
        'shared_user_id', 'shared_at',
    ];

    protected $hidden = [
        'password_iv', 'password_tag',
        'username_iv', 'username_tag',
        'notes_iv', 'notes_tag',
    ];

    protected $casts = [
        'shared_at' => 'datetime',
    ];

    public function getUsernameAttribute(?string $value): ?string
    {
        if (empty($value) || empty($this->username_iv) || empty($this->username_tag)) {
            return null;
        }

        try {
            return app(CryptoService::class)->decryptWithMasterKey(
                $value, $this->username_iv, $this->username_tag
            );
        } catch (Exception $e) {
            return '[Decryption error]';
        }
    }

    public function getPasswordAttribute(?string $value): ?string
    {
        if (empty($value) || empty($this->password_iv) || empty($this->password_tag)) {
            return null;
        }

        try {
            return app(CryptoService::class)->decryptWithMasterKey(
                $value, $this->password_iv, $this->password_tag
            );
        } catch (Exception $e) {
            return '[Decryption error]';
        }
    }

    public function getNotesAttribute(?string $value): ?string
    {
        if (empty($value) || empty($this->notes_iv) || empty($this->notes_tag)) {
            return null;
        }

        try {
            return app(CryptoService::class)->decryptWithMasterKey(
                $value, $this->notes_iv, $this->notes_tag
            );
        } catch (Exception $e) {
            return '[Decryption error]';
        }
    }
}
