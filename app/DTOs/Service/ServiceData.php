<?php

namespace App\DTOs\Service;

use App\Models\Service;

final readonly class ServiceData
{
    public function __construct(
        public string $name,
        public ?string $url,
        public string $username,
        public string $password,
        public ?string $notes = null,
        public ?string $domain = null,
        public string $type = Service::TYPE_LOGIN,
        public bool $clientEncrypted = false,
        public ?string $usernameIv = null,
        public ?string $usernameTag = null,
        public ?string $passwordIv = null,
        public ?string $passwordTag = null,
        public ?string $notesIv = null,
        public ?string $notesTag = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            url: $data['url'] ?? null,
            username: $data['username'],
            password: $data['password'],
            notes: $data['notes'] ?? null,
            domain: $data['domain'] ?? null,
            type: $data['type'] ?? Service::TYPE_LOGIN,
            clientEncrypted: (bool) ($data['client_encrypted'] ?? false),
            usernameIv: $data['username_iv'] ?? null,
            usernameTag: $data['username_tag'] ?? null,
            passwordIv: $data['password_iv'] ?? null,
            passwordTag: $data['password_tag'] ?? null,
            notesIv: $data['notes_iv'] ?? null,
            notesTag: $data['notes_tag'] ?? null
        );
    }

    public function isLogin(): bool
    {
        return $this->type === Service::TYPE_LOGIN;
    }
}
