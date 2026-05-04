<?php

namespace App\Services\Vault\Contracts;

use App\DTOs\Share\AcceptedShareResult;
use App\DTOs\Share\ShareData;
use App\Models\Service;
use App\Models\Share;

interface ShareServiceInterface
{
    public function share(ShareData $data): Share ;
    public function accept(Share $share): AcceptedShareResult;
    public function reject(Share $share): void;
}
