<?php

namespace App\Mappers;

use App\DTOs\Share\ShareData;
use App\DTOs\Share\SharePayload;
use App\Models\Share;

final class ShareMapper
{
    public static function fromRequest(array $data): ShareData
    {
        return new ShareData(
            serviceId: (int) $data['service_id'],
            recipientEmail: $data['email']
        );
    }

    public static function toPayload(array $data): SharePayload
    {
        return new SharePayload(
            encryptedAesKey: $data['encrypted_aes_key'],
            encryptedData: $data['encrypted_data'],
            name: $data['name'],
            url: $data['url'] ?? null,
            favicon: $data['favicon'] ?? null
        );
    }

    public static function fromModel(Share $share): array
    {
        return [
            'id'           => $share->id,
            'service_name' => $share->service?->name,
            'from'         => $share->fromUser?->name,
            'shared_at'    => $share->shared_at?->diffForHumans(),
        ];
    }
}
