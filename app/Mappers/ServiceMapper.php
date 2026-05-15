<?php

namespace App\Mappers;

use App\DTOs\Service\ServiceData;
use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;

final class ServiceMapper
{
    public function fromCreateRequest(CreateServiceRequest $request): ServiceData {
        return ServiceData::fromArray($request->validated());
    }

    public function fromUpdateRequest(UpdateServiceRequest $request): ServiceData {
        return ServiceData::fromArray($request->validated());
    }
}
