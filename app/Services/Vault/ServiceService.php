<?php

namespace App\Services\Vault;

use App\DTOs\Service\ServiceData;
use App\Models\Service;
use App\Models\Share;
use App\Models\User;
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
        if (! $domain) {
            $domain = $this->extractDomainFromName($data->name);
        }

        $url = $domain ? "https://www.{$domain}" : null;
        $favicon = $domain ? "https://www.google.com/s2/favicons?domain={$domain}&sz=64" : null;

        $encrypted = $this->encryptServiceData($data);
        $analysis = $this->analyzePassword($data->password);

        return Service::create([
            'user_id' => auth()->id(),
            'name' => $data->name,
            'url' => $url,
            'favicon' => $favicon,
            ...$encrypted,
            ...$analysis,
        ]);
    }

    public function update(Service $service, ServiceData $data): Service
    {
        $updates = $this->prepareUpdateData($service, $data);

        $service->update($updates);
        $service->refresh();

        $this->syncSharedGroup($service, $data, $updates);

        return $service->fresh();
    }

    public function delete(Service $service): bool
    {
        if (empty($service->shared_group_id)) {
            return $service->delete();
        }

        if ($service->shared_user_id) {
            return $this->deleteRecipientSharedCopy($service);
        }

        return $this->deleteSharedGroup($service);
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

        $services->transform(fn ($s) => tap($s, fn ($item) => $item->last_modified = $item->last_modified ? Carbon::parse($item->last_modified) : null
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
            'username' => $encUsername['ciphertext'],
            'username_iv' => $encUsername['iv'],
            'username_tag' => $encUsername['tag'],
            'password' => $encPassword['ciphertext'],
            'password_iv' => $encPassword['iv'],
            'password_tag' => $encPassword['tag'],
            'notes' => $encNotes['ciphertext'] ?? null,
            'notes_iv' => $encNotes['iv'] ?? null,
            'notes_tag' => $encNotes['tag'] ?? null,
        ];
    }

    private function encryptField(string $value, ?User $user = null): array
    {
        return $user
            ? $this->cryptoService->encryptWithUserMasterKey($value, $user)
            : $this->cryptoService->encryptWithMasterKey($value);
    }

    private function analyzePassword(string $password, ?int $excludeServiceId = null): array
    {
        $result = $this->passwordService->analyze($password, auth()->id(), $excludeServiceId);

        return [
            'strength' => $result['strength'],
            'compromised' => $result['compromised'],
            'reused' => $result['reused'],
        ];
    }

    private function prepareUpdateData(
        Service $service,
        ServiceData $data,
        ?User $encryptionUser = null,
        ?array $passwordAnalysis = null,
        bool $forceSensitiveEncryption = false
    ): array {
        $updates = [
            'name' => $data->name,
            'url' => $data->url,
            'favicon' => $this->getFaviconUrl($data->url),
        ];

        if ($forceSensitiveEncryption || $data->username !== $service->username) {
            $enc = $this->encryptField($data->username, $encryptionUser);
            $updates += [
                'username' => $enc['ciphertext'],
                'username_iv' => $enc['iv'],
                'username_tag' => $enc['tag'],
            ];
        }

        if ($forceSensitiveEncryption || $data->password !== $service->password) {
            $enc = $this->encryptField($data->password, $encryptionUser);
            $updates += [
                'password' => $enc['ciphertext'],
                'password_iv' => $enc['iv'],
                'password_tag' => $enc['tag'],
                ...($passwordAnalysis ?? $this->analyzePassword($data->password, $service->id)),
            ];
        }

        if ($forceSensitiveEncryption || $data->notes !== $service->notes) {
            if ($data->notes) {
                $enc = $this->encryptField($data->notes, $encryptionUser);
                $updates += [
                    'notes' => $enc['ciphertext'],
                    'notes_iv' => $enc['iv'],
                    'notes_tag' => $enc['tag'],
                ];
            } else {
                $updates += ['notes' => null, 'notes_iv' => null, 'notes_tag' => null];
            }
        }

        return $updates;
    }

    private function syncSharedGroup(Service $sourceService, ServiceData $data, array $sourceUpdates): void
    {
        if (empty($sourceService->shared_group_id)) {
            return;
        }

        $passwordAnalysis = $this->extractPasswordAnalysis($sourceUpdates, $sourceService);

        Service::with('user')
            ->where('shared_group_id', $sourceService->shared_group_id)
            ->whereKeyNot($sourceService->id)
            ->get()
            ->each(function (Service $sharedService) use ($data, $passwordAnalysis): void {
                if (! $sharedService->user) {
                    return;
                }

                $sharedService->update($this->prepareUpdateData(
                    service: $sharedService,
                    data: $data,
                    encryptionUser: $sharedService->user,
                    passwordAnalysis: $passwordAnalysis,
                    forceSensitiveEncryption: true
                ));
            });
    }

    private function deleteRecipientSharedCopy(Service $service): bool
    {
        return DB::transaction(function () use ($service): bool {
            Share::where('to_user_id', $service->user_id)
                ->where('from_user_id', $service->shared_user_id)
                ->whereNull('revoked_at')
                ->whereHas('service', fn ($query) => $query->where('shared_group_id', $service->shared_group_id))
                ->update(['revoked_at' => now()]);

            return (bool) $service->delete();
        });
    }

    private function deleteSharedGroup(Service $service): bool
    {
        return DB::transaction(function () use ($service): bool {
            $serviceIds = Service::where('shared_group_id', $service->shared_group_id)->pluck('id');

            Share::whereIn('service_id', $serviceIds)->delete();

            return Service::whereKey($serviceIds)->delete() > 0;
        });
    }

    private function extractPasswordAnalysis(array $updates, Service $service): array
    {
        return [
            'strength' => $updates['strength'] ?? $service->strength,
            'compromised' => $updates['compromised'] ?? $service->compromised,
            'reused' => $updates['reused'] ?? $service->reused,
        ];
    }

    private function getFaviconUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $domain = parse_url($url, PHP_URL_HOST);

        return "https://www.google.com/s2/favicons?domain={$domain}&sz=64";
    }

    private function extractDomainFromName(string $name): ?string
    {
        $slug = Str::slug($name);

        return $slug ? "{$slug}.com" : null;
    }
}
