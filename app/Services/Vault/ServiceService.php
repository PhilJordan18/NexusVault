<?php

namespace App\Services\Vault;

use App\DTOs\Service\ServiceData;
use App\Models\Service;
use App\Services\PasswordService;
use App\Services\Security\CryptoService;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ServiceService implements ServiceServiceInterface
{
    public function __construct(private CryptoService $cryptoService, private PasswordService $passwordService) {}

    public function create(ServiceData $data): Service
    {
        $domain = $data->domain;
        if (!$domain) {
            $domain = $this->extractDomainFromName($data->name);
        }

        $url = $domain ? "https://www.{$domain}" : null;
        $favicon = $domain ? "https://www.google.com/s2/favicons?domain={$domain}&sz=64" : null;

        $encrypted = $this->encryptServiceData($data);
        $analysis  = $this->analyzePassword($data->password);

        return Service::create([
            'user_id'  => auth()->id(),
            'name'     => $data->name,
            'url'      => $url,
            'favicon'  => $favicon,
            ...$encrypted,
            ...$analysis,
        ]);
    }

    public function update(Service $service, ServiceData $data): Service
    {
        $updates = $this->prepareUpdateData($service, $data);

        $service->update($updates);

        return $service->fresh();
    }

    public function delete(Service $service): bool
    {
        return $service->delete();
    }

    public function getAllForUser(int $userId): Collection
    {
        return Service::where('user_id', $userId)->get();
    }

    public function getByIdForUser(int $id, int $userId): ?Service
    {
        return Service::where('id', $id)->where('user_id', $userId)->first();
    }

    public function getGroupedByName(int $userId): Collection
    {
        $services = Service::select([
            'name',
            'favicon',
            'url',
            DB::raw('COUNT(*) as account_count'),
            DB::raw('MAX(updated_at) as last_modified'),
        ])
            ->where('user_id', $userId)
            ->groupBy('name', 'favicon', 'url')
            ->orderBy('name')
            ->get();

        $services->transform(fn ($s) => tap($s, fn ($item) =>
        $item->last_modified = $item->last_modified ? Carbon::parse($item->last_modified) : null
        ));

        return $services;
    }

    public function getAccountsByName(string $name, int $userId): Collection
    {
        return Service::where('user_id', $userId)
            ->where('name', $name)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    // ==================== PRIVATE HELPERS (très courtes) ====================

    private function encryptServiceData(ServiceData $data): array
    {
        $encUsername = $this->encryptField($data->username);
        $encPassword = $this->encryptField($data->password);
        $encNotes = $data->notes ? $this->encryptField($data->notes) : ['ciphertext' => null, 'iv' => null, 'tag' => null];

        return [
            'username'     => $encUsername['ciphertext'],
            'username_iv'  => $encUsername['iv'],
            'username_tag' => $encUsername['tag'],
            'password'     => $encPassword['ciphertext'],
            'password_iv'  => $encPassword['iv'],
            'password_tag' => $encPassword['tag'],
            'notes'        => $encNotes['ciphertext'] ?? null,
            'notes_iv'     => $encNotes['iv'] ?? null,
            'notes_tag'    => $encNotes['tag'] ?? null,
        ];
    }

    private function encryptField(string $value): array
    {
        return $this->cryptoService->encryptWithMasterKey($value);
    }

    private function analyzePassword(string $password, ?int $excludeServiceId = null): array
    {
        $result = $this->passwordService->analyze($password, auth()->id(), $excludeServiceId);

        return [
            'strength'    => $result['strength'],
            'compromised' => $result['compromised'],
            'reused'      => $result['reused'],
        ];
    }

    private function prepareUpdateData(Service $service, ServiceData $data): array
    {
        $updates = [
            'name'    => $data->name,
            'url'     => $data->url,
            'favicon' => $this->getFaviconUrl($data->url),
        ];

        if ($data->username !== $service->getRawOriginal('username')) {
            $enc = $this->encryptField($data->username);
            $updates += [
                'username'     => $enc['ciphertext'],
                'username_iv'  => $enc['iv'],
                'username_tag' => $enc['tag'],
            ];
        }

        if ($data->password !== $service->getRawOriginal('password')) {
            $enc = $this->encryptField($data->password);
            $updates += [
                'password'     => $enc['ciphertext'],
                'password_iv'  => $enc['iv'],
                'password_tag' => $enc['tag'],
                ...$this->analyzePassword($data->password, $service->id),
            ];
        }

        if ($data->notes !== $service->getRawOriginal('notes')) {
            if ($data->notes) {
                $enc = $this->encryptField($data->notes);
                $updates += [
                    'notes'     => $enc['ciphertext'],
                    'notes_iv'  => $enc['iv'],
                    'notes_tag' => $enc['tag'],
                ];
            } else {
                $updates += ['notes' => null, 'notes_iv' => null, 'notes_tag' => null];
            }
        }

        return $updates;
    }

    private function getFaviconUrl(?string $url): ?string
    {
        if (!$url) return null;

        $domain = parse_url($url, PHP_URL_HOST);
        return "https://www.google.com/s2/favicons?domain={$domain}&sz=64";
    }

    private function extractDomainFromName(string $name): ?string
    {
        $slug = Str::slug($name);
        return $slug ? "{$slug}.com" : null;
    }
}
