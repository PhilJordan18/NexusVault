<?php

namespace App\DTOs\Share;

final readonly class ShareData
{
    public function __construct(public int $serviceId, public string $recipientEmail) {}
}
