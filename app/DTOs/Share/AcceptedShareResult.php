<?php

namespace App\DTOs\Share;

use App\Models\Service;

final readonly class AcceptedShareResult
{
    public function __construct(public Service $service) {}
}
