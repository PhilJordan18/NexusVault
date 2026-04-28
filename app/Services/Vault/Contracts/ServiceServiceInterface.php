<?php

namespace App\Services\Vault\Contracts;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

interface ServiceServiceInterface
{
    public function create(array $data): Service;
    public function update(Service $service, array $data): Service;
    public function delete(Service $service): bool;
    public function getAllForUser(int $userId): Collection;
    public function getByIdForUser(int $id, int $userId): ?Service;
    public function getGroupedByName(int $userId): Collection;
}
