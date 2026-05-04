<?php

namespace App\DTOs\Share;

final readonly class SharePayload
{
    public function __construct(public string $encryptedAesKey, public array $encryptedData, public string $name, public ?string $url,  public ?string $favicon) {}
}
