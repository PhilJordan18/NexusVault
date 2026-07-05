<?php

namespace App\Services\Vault\Contracts;

use App\DTOs\Share\AcceptedShareResult;
use App\DTOs\Share\ShareData;
use App\Models\Share;

interface ShareServiceInterface
{
    public function share(ShareData $data): Share;

    public function prepareClientEncryptedShare(ShareData $data): array;

    public function shareClientEncrypted(ShareData $data, array $payload): Share;

    public function accept(Share $share): AcceptedShareResult;

    public function acceptClientEncrypted(Share $share, array $encryptedFields): AcceptedShareResult;

    public function reject(Share $share): void;

    public function revoke(Share $share): void;
}
