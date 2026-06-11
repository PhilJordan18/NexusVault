<?php

namespace App\DTOs\Share;

use App\Models\Service;

final readonly class SharePayload
{
    public function __construct(
        public string $encryptedAesKey,
        public array $encryptedData,
        public string $name,
        public ?string $url,
        public ?string $favicon,
        public string $type = Service::TYPE_LOGIN
    ) {}
}
