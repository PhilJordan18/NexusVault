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
        public string $type = Service::TYPE_LOGIN
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
            type: $data['type'] ?? Service::TYPE_LOGIN
        );
    }

    public function isLogin(): bool
    {
        return $this->type === Service::TYPE_LOGIN;
    }
}
