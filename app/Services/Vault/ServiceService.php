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

    public function create(array $data): Service
    {
        $encUsername = $this->cryptoService->encryptWithMasterKey($data['username']);
        $encPassword = $this->cryptoService->encryptWithMasterKey($data['password']);
        $encNotes = isset($data['notes']) && $data['notes']
            ? $this->cryptoService->encryptWithMasterKey($data['notes'])
            : null;

        return Service::create([
            'user_id'      => auth()->id(),
            'name'         => $data['name'],
            'url'          => $data['url'] ?? null,
            'favicon'      => $this->getFaviconUrl($data['url'] ?? null),
            'username'     => $encUsername['ciphertext'],
            'username_iv'  => $encUsername['iv'],
            'username_tag' => $encUsername['tag'],
            'password'     => $encPassword['ciphertext'],
            'password_iv'  => $encPassword['iv'],
            'password_tag' => $encPassword['tag'],
            'notes'        => $encNotes['ciphertext'] ?? null,
            'notes_iv'     => $encNotes['iv'] ?? null,
            'notes_tag'    => $encNotes['tag'] ?? null,
        ]);
    }

    public function update(Service $service, array $data): Service
    {
        $updates = [
            'name' => $data['name'] ?? $service->name,
            'url'  => $data['url'] ?? $service->url,
        ];

        if (isset($data['username'])) {
            $enc = $this->cryptoService->encryptWithMasterKey($data['username']);
            $updates['username'] = $enc['ciphertext'];
            $updates['username_iv'] = $enc['iv'];
            $updates['username_tag'] = $enc['tag'];
        }

        if (isset($data['password'])) {
            $enc = $this->cryptoService->encryptWithMasterKey($data['password']);
            $updates['password'] = $enc['ciphertext'];
            $updates['password_iv'] = $enc['iv'];
            $updates['password_tag'] = $enc['tag'];
        }

        if (isset($data['notes'])) {
            if ($data['notes']) {
                $enc = $this->cryptoService->encryptWithMasterKey($data['notes']);
                $updates['notes'] = $enc['ciphertext'];
                $updates['notes_iv'] = $enc['iv'];
                $updates['notes_tag'] = $enc['tag'];
            } else {
                $updates['notes'] = null;
                $updates['notes_iv'] = null;
                $updates['notes_tag'] = null;
            }
        }

        $updates['favicon'] = $this->getFaviconUrl($updates['url'] ?? $service->url);

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

        $services->transform(function ($service) {
            $service->last_modified = $service->last_modified
                ? Carbon::parse($service->last_modified)
                : null;

            return $service;
        });

        return $services;
    }

    private function getFaviconUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $domain = parse_url($url, PHP_URL_HOST);

        return "https://www.google.com/s2/favicons?domain={$domain}&sz=64";
    }
}
