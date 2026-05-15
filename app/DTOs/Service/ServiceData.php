<?php

namespace App\DTOs\Service;

final readonly class ServiceData
{
    public function __construct(public string $name, public ?string $url, public string $username, public string $password, public ?string $notes = null, public ?string $domain = null) {}

    public static function fromArray(array $data): self {
        return new self(name: $data['name'], url: $data['url'] ?? null, username: $data['username'], password: $data['password'], notes: $data['notes'] ?? null, domain: $data['domain'] ?? null);
    }
}
