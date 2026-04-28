<?php

namespace App\Services\Vault\Contracts;

use App\Models\Service;
use App\Models\Share;

interface ShareServiceInterface
{
    public function share(Service $service, string $recipientEmail): Share ;
    public function accept(Share $share): Service;
    public function reject(Share $share): void;
}
