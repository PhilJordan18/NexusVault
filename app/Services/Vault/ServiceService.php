<?php

namespace App\Services\Vault;

use App\Models\Service;
use App\Services\Security\CryptoService;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Random\RandomException;

final readonly class ServiceService implements ServiceServiceInterface
{
    public function __construct(private CryptoService $cryptoService) {}

    /**
     * @throws RandomException
     */
    public function create(array $data): Service
    {
        $encUsername = $this->cryptoService->encryptWithMasterKey($data['username']);
        $encPassword = $this->cryptoService->encryptWithMasterKey($data['password']);
        $encNotes = isset($data['notes']) ? $this->cryptoService->encryptWithMasterKey($data['notes']) : null;

        return Service::create([
            'user_id'      => auth()->id(),
            'name'         => $data['name'],
            'url'          => $data['url'] ?? null,
            'favicon'      => $this->getFaviconUrl($data['url'] ?? null),
            // Chiffré
            'username'     => $encUsername['ciphertext'],
            'username_iv'  => $encUsername['iv'],
            'username_tag' => $encUsername['tag'],
            'password'     => $encPassword['ciphertext'],
            'password_iv'  => $encPassword['iv'],
            'password_tag' => $encPassword['tag'],
            'notes'        => $encNotes ? $encNotes['ciphertext'] : null,
            'notes_iv'     => $encNotes ? $encNotes['iv'] : null,
            'notes_tag'    => $encNotes ? $encNotes['tag'] : null,
        ]);
    }

    public function update(Service $service, array $data): Service
    {
        $encUsername = isset($data['username']) ? $this->cryptoService->encryptWithMasterKey($data['username']) : null;
        $encPassword = isset($data['password']) ? $this->cryptoService->encryptWithMasterKey($data['password']) : null;
        $encNotes = isset($data['notes']) ? $this->cryptoService->encryptWithMasterKey($data['notes']) : null;

        $service->update([
            'name'         => $data['name'] ?? $service->name,
            'url'          => $data['url'] ?? $service->url,
            'favicon'      => $this->getFaviconUrl($data['url'] ?? $service->url),
            // Username
            'username'     => $encUsername ? $encUsername['ciphertext'] : $service->username,
            'username_iv'  => $encUsername ? $encUsername['iv'] : $service->username_iv,
            'username_tag' => $encUsername ? $encUsername['tag'] : $service->username_tag,
            // Password
            'password'     => $encPassword ? $encPassword['ciphertext'] : $service->password,
            'password_iv'  => $encPassword ? $encPassword['iv'] : $service->password_iv,
            'password_tag' => $encPassword ? $encPassword['tag'] : $service->password_tag,
            // Notes
            'notes'        => $encNotes ? $encNotes['ciphertext'] : $service->notes,
            'notes_iv'     => $encNotes ? $encNotes['iv'] : $service->notes_iv,
            'notes_tag'    => $encNotes ? $encNotes['tag'] : $service->notes_tag,
        ]);

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
            'name', 'favicon', 'url',
            DB::raw('COUNT(*) as account_count'),
            DB::raw('MAX(updated_at) as last_modified'),
        ])
            ->where('user_id', $userId)
            ->groupBy('name', 'favicon', 'url')
            ->orderBy('name')
            ->get();

        $services->transform(function ($service) {
            $service->last_modified = $service->last_modified
                ? Carbon::parse($service->last_modified)
                : null;
            return $service;
        });

        return $services;
    }

    private function getFaviconUrl(?string $url) : ?string
    {
        if (!$url) return null;
        $domain = parse_url($url, PHP_URL_HOST);
        return "https://www.google.com/s2/favicons?domain={$domain}&sz=64";
    }
}
