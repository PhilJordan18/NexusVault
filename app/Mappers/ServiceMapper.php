<?php

namespace App\Mappers;

use App\DTOs\Service\ServiceData;
use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Support\Collection;

final class ServiceMapper
{
    public function fromCreateRequest(CreateServiceRequest $request): ServiceData
    {
        return ServiceData::fromArray($request->validated());
    }

    public function fromUpdateRequest(UpdateServiceRequest $request): ServiceData
    {
        return ServiceData::fromArray($request->validated());
    }

    /**
     * @return array<string, mixed>
     */
    public function toBrowserPayload(Service $service): array
    {
        $sharedWith = $service->getAttribute('shared_with');

        return [
            'id' => $service->id,
            'type' => $service->type,
            'name' => $service->name,
            'url' => $service->url,
            'username' => $service->username,
            'username_iv' => $service->username_iv,
            'username_tag' => $service->username_tag,
            'password' => $service->password,
            'password_iv' => $service->password_iv,
            'password_tag' => $service->password_tag,
            'notes' => $service->notes,
            'notes_iv' => $service->notes_iv,
            'notes_tag' => $service->notes_tag,
            'client_encrypted' => $service->client_encrypted,
            'shared_user_id' => $service->shared_user_id,
            'shared_group_id' => $service->shared_group_id,
            'shared_key_envelope' => $service->shared_key_envelope,
            'strength' => $service->strength,
            'compromised' => $service->compromised,
            'reused' => $service->reused,
            'shared_with' => $sharedWith instanceof Collection ? $sharedWith->values()->all() : ($sharedWith ?? []),
        ];
    }
}
