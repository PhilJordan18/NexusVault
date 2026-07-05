<?php

namespace App\Services\Vault;

use App\DTOs\Share\AcceptedShareResult;
use App\DTOs\Share\ShareData;
use App\DTOs\Share\SharePayload;
use App\Exceptions\ShareException;
use App\Mappers\ShareMapper;
use App\Models\Service;
use App\Models\Share;
use App\Models\User;
use App\Services\Auth\UserKeyService;
use App\Services\PasswordService;
use App\Services\Security\CryptoService;
use App\Services\Vault\Contracts\ShareServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ShareService implements ShareServiceInterface
{
    public function __construct(
        private CryptoService $cryptoService,
        private UserKeyService $userKeyService,
        private PasswordService $passwordService
    ) {}

    // ==================== PUBLIC API ====================

    public function share(ShareData $data): Share
    {
        $service = $this->findService($data->serviceId);
        $recipient = $this->findRecipient($data->recipientEmail);

        $this->validateOwnership($service);
        $this->validateRecipient($recipient);
        $service = $this->ensureServiceHasSharedGroup($service);
        $this->validateShareIsNotActive($service, $recipient);

        $publicKey = $this->getValidPublicKey($recipient);

        $aesKey = $this->generateAesKey();
        $encryptedData = $this->encryptSensitiveData($service, $aesKey);
        $encryptedAesKey = $this->encryptAesKeyWithRecipientPublicKey($aesKey, $publicKey);
        $payload = $this->buildSharePayload($service, $encryptedData, $encryptedAesKey);

        return $this->createShareRecord($service, $recipient, $payload);
    }

    public function prepareClientEncryptedShare(ShareData $data): array
    {
        $service = $this->findService($data->serviceId);
        $recipient = $this->findRecipient($data->recipientEmail);

        $this->validateClientEncryptedOwnership($service);
        $this->validateClientEncryptedRecipient($recipient);
        $this->validateShareIsNotActive($service, $recipient);

        return [
            'recipient' => [
                'name' => $recipient->name,
                'email' => $recipient->email,
                'public_key' => $this->getValidPublicKey($recipient),
            ],
        ];
    }

    public function shareClientEncrypted(ShareData $data, array $payload): Share
    {
        $service = $this->findService($data->serviceId);
        $recipient = $this->findRecipient($data->recipientEmail);

        $this->validateClientEncryptedOwnership($service);
        $this->validateClientEncryptedRecipient($recipient);
        $this->getValidPublicKey($recipient);
        $service = $this->ensureServiceHasSharedGroup($service);
        $this->validateShareIsNotActive($service, $recipient);

        if (($payload['mode'] ?? null) === 'client-encrypted-sync') {
            $this->validateClientEncryptedSyncPayload($payload);

            return $this->shareClientEncryptedSync($service, $recipient, $payload);
        }

        $this->validateClientEncryptedPayload($payload);

        return Share::create([
            'service_id' => $service->id,
            'from_user_id' => auth()->id(),
            'to_user_id' => $recipient->id,
            'shared_data' => json_encode([
                'version' => 1,
                'mode' => 'client-encrypted',
                'encrypted_aes_key' => $payload['encrypted_aes_key'],
                'encrypted_data' => $payload['encrypted_data'],
                'name' => $service->name,
                'url' => $service->url,
                'favicon' => $service->favicon,
                'type' => $service->type ?? Service::TYPE_LOGIN,
            ]),
            'shared_at' => now(),
        ]);
    }

    public function accept(Share $share): AcceptedShareResult
    {
        $this->validateAcceptPermissions($share);

        $payload = $this->extractPayload($share);
        $aesKey = $this->decryptAesKey($payload, auth()->user());
        $decryptedData = $this->decryptSensitiveData($payload, $aesKey);

        $analysis = $this->analysisForPayload($payload, $decryptedData);

        $reEncrypted = $this->reEncryptForRecipient($decryptedData);
        $newService = $this->createServiceFromSharedData($payload, $reEncrypted, $share, $analysis);

        $share->update(['accepted_at' => now()]);

        return new AcceptedShareResult($newService);
    }

    public function acceptClientEncrypted(Share $share, array $encryptedFields): AcceptedShareResult
    {
        $this->validateAcceptPermissions($share);

        $payload = $this->extractClientEncryptedPayload($share);

        if (($payload['mode'] ?? null) === 'client-encrypted-sync') {
            return $this->acceptClientEncryptedSync($share, $payload, $encryptedFields);
        }

        $this->validateClientEncryptedAcceptPayload($encryptedFields);
        $sourceService = $this->ensureServiceHasSharedGroup($share->service);

        $newService = Service::create([
            'user_id' => auth()->id(),
            'type' => $payload['type'] ?? Service::TYPE_LOGIN,
            'name' => $payload['name'],
            'url' => $payload['url'] ?? null,
            'favicon' => $payload['favicon'] ?? null,
            'username' => $encryptedFields['username'],
            'username_iv' => $encryptedFields['username_iv'],
            'username_tag' => $encryptedFields['username_tag'],
            'password' => $encryptedFields['password'],
            'password_iv' => $encryptedFields['password_iv'],
            'password_tag' => $encryptedFields['password_tag'],
            'notes' => $encryptedFields['notes'] ?? null,
            'notes_iv' => $encryptedFields['notes_iv'] ?? null,
            'notes_tag' => $encryptedFields['notes_tag'] ?? null,
            'shared_user_id' => $share->from_user_id,
            'shared_at' => $share->shared_at,
            'shared_group_id' => $sourceService->shared_group_id,
            'client_encrypted' => true,
            'strength' => null,
            'compromised' => false,
            'reused' => false,
        ]);

        $share->update(['accepted_at' => now()]);

        return new AcceptedShareResult($newService);
    }

    public function reject(Share $share): void
    {
        $this->validateRejectPermissions($share);
        $share->update(['rejected' => true]);
    }

    public function revoke(Share $share): void
    {
        $this->validateRevokePermissions($share);

        $sourceService = $this->ensureServiceHasSharedGroup($share->service);

        Service::where('shared_group_id', $sourceService->shared_group_id)
            ->where('user_id', $share->to_user_id)
            ->delete();

        $share->update(['revoked_at' => now()]);
    }

    // ==================== PRIVATE HELPERS ====================

    private function findService(int $serviceId): Service
    {
        return Service::findOrFail($serviceId);
    }

    private function findRecipient(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function validateOwnership(Service $service): void
    {
        if ($service->user_id !== auth()->id()) {
            throw ShareException::unauthorized();
        }

        if ($service->client_encrypted) {
            throw ShareException::clientEncryptedSharingNotReady();
        }

        if ($service->shared_user_id !== null) {
            throw ShareException::sharedAccessCannotBeReshared();
        }
    }

    private function validateRecipient(User $recipient): void
    {
        if ($recipient->id === auth()->id()) {
            throw ShareException::cannotShareWithYourself();
        }

        if ($recipient->usesClientSideVault()) {
            throw ShareException::clientEncryptedSharingNotReady();
        }
    }

    private function validateClientEncryptedOwnership(Service $service): void
    {
        if ($service->user_id !== auth()->id()) {
            throw ShareException::unauthorized();
        }

        if (! $service->client_encrypted) {
            throw ShareException::invalidClientEncryptedPayload();
        }

        if ($service->shared_user_id !== null) {
            throw ShareException::sharedAccessCannotBeReshared();
        }
    }

    private function validateClientEncryptedRecipient(User $recipient): void
    {
        if ($recipient->id === auth()->id()) {
            throw ShareException::cannotShareWithYourself();
        }

        if (! $recipient->usesClientSideVault()) {
            throw ShareException::recipientRequiresClientVault();
        }
    }

    private function validateShareIsNotActive(Service $service, User $recipient): void
    {
        $isAlreadyShared = Share::where('to_user_id', $recipient->id)
            ->where('rejected', false)
            ->whereNull('revoked_at')
            ->where(function ($query) use ($service): void {
                $query->where('service_id', $service->id)
                    ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('shared_group_id', $service->shared_group_id));
            })
            ->exists();

        if ($isAlreadyShared) {
            throw ShareException::alreadyShared();
        }
    }

    private function ensureServiceHasSharedGroup(Service $service): Service
    {
        if ($service->shared_group_id) {
            return $service;
        }

        $service->forceFill(['shared_group_id' => (string) Str::uuid()])->save();

        return $service->refresh();
    }

    private function getValidPublicKey(User $recipient): string
    {
        $key = trim($recipient->public_key);
        if (empty($key) || ! str_contains($key, 'BEGIN PUBLIC KEY')) {
            throw ShareException::invalidRecipient();
        }

        return $key;
    }

    private function generateAesKey(): string
    {
        return random_bytes(32);
    }

    private function encryptSensitiveData(Service $service, string $aesKey): array
    {
        $json = json_encode([
            'username' => $service->username,
            'password' => $service->password,
            'notes' => $service->notes,
        ]);

        return $this->cryptoService->encryptWithCustomKey($json, $aesKey);
    }

    private function encryptAesKeyWithRecipientPublicKey(string $aesKey, string $publicKey): string
    {
        return $this->cryptoService->encryptWithPublicKey($aesKey, $publicKey);
    }

    private function buildSharePayload(Service $service, array $encryptedData, string $encryptedAesKey): SharePayload
    {
        return new SharePayload(
            encryptedAesKey: $encryptedAesKey,
            encryptedData: $encryptedData,
            name: $service->name,
            url: $service->url,
            favicon: $service->favicon,
            type: $service->type ?? Service::TYPE_LOGIN
        );
    }

    private function createShareRecord(Service $service, User $recipient, SharePayload $payload): Share
    {
        return Share::create([
            'service_id' => $service->id,
            'from_user_id' => auth()->id(),
            'to_user_id' => $recipient->id,
            'shared_data' => json_encode([
                'encrypted_aes_key' => $payload->encryptedAesKey,
                'encrypted_data' => $payload->encryptedData,
                'name' => $payload->name,
                'url' => $payload->url,
                'favicon' => $payload->favicon,
                'type' => $payload->type,
            ]),
            'shared_at' => now(),
        ]);
    }

    private function validateAcceptPermissions(Share $share): void
    {
        if ($share->to_user_id !== auth()->id() || $share->accepted_at || $share->rejected || $share->revoked_at) {
            throw ShareException::unauthorized();
        }
    }

    private function extractPayload(Share $share): SharePayload
    {
        $raw = json_decode($share->shared_data, true);

        return ShareMapper::toPayload($raw);
    }

    private function extractClientEncryptedPayload(Share $share): array
    {
        $payload = json_decode($share->shared_data, true);

        if (! in_array($payload['mode'] ?? null, ['client-encrypted', 'client-encrypted-sync'], true)) {
            throw ShareException::invalidClientEncryptedPayload();
        }

        return $payload;
    }

    private function shareClientEncryptedSync(Service $service, User $recipient, array $payload): Share
    {
        return DB::transaction(function () use ($service, $recipient, $payload): Share {
            $sourceService = $this->applyClientEncryptedSyncPayloadToSource($service, $payload);

            return Share::create([
                'service_id' => $sourceService->id,
                'from_user_id' => auth()->id(),
                'to_user_id' => $recipient->id,
                'shared_data' => json_encode([
                    'version' => 2,
                    'mode' => 'client-encrypted-sync',
                    'encrypted_aes_key' => $payload['encrypted_aes_key'],
                    'encrypted_data' => $payload['encrypted_data'],
                    'shared_fields' => $payload['shared_fields'],
                    'name' => $sourceService->name,
                    'url' => $sourceService->url,
                    'favicon' => $sourceService->favicon,
                    'type' => $sourceService->type ?? Service::TYPE_LOGIN,
                ]),
                'shared_at' => now(),
            ]);
        });
    }

    private function applyClientEncryptedSyncPayloadToSource(Service $service, array $payload): Service
    {
        $sharedFields = $payload['shared_fields'];

        $service->forceFill([
            'username' => $sharedFields['username']['ciphertext'],
            'username_iv' => $sharedFields['username']['iv'],
            'username_tag' => $sharedFields['username']['tag'],
            'password' => $sharedFields['password']['ciphertext'],
            'password_iv' => $sharedFields['password']['iv'],
            'password_tag' => $sharedFields['password']['tag'],
            'notes' => $sharedFields['notes']['ciphertext'] ?? null,
            'notes_iv' => $sharedFields['notes']['iv'] ?? null,
            'notes_tag' => $sharedFields['notes']['tag'] ?? null,
            'shared_key_envelope' => $payload['shared_key_envelope'],
            'client_encrypted' => true,
            'strength' => null,
            'compromised' => false,
            'reused' => false,
        ])->save();

        return $service->refresh();
    }

    private function acceptClientEncryptedSync(Share $share, array $payload, array $encryptedFields): AcceptedShareResult
    {
        $this->validateClientEncryptedSyncAcceptPayload($encryptedFields);
        $sourceService = $this->ensureServiceHasSharedGroup($share->service);
        $sharedFields = $payload['shared_fields'];

        $newService = Service::create([
            'user_id' => auth()->id(),
            'type' => $payload['type'] ?? Service::TYPE_LOGIN,
            'name' => $payload['name'],
            'url' => $payload['url'] ?? null,
            'favicon' => $payload['favicon'] ?? null,
            'username' => $sharedFields['username']['ciphertext'],
            'username_iv' => $sharedFields['username']['iv'],
            'username_tag' => $sharedFields['username']['tag'],
            'password' => $sharedFields['password']['ciphertext'],
            'password_iv' => $sharedFields['password']['iv'],
            'password_tag' => $sharedFields['password']['tag'],
            'notes' => $sharedFields['notes']['ciphertext'] ?? null,
            'notes_iv' => $sharedFields['notes']['iv'] ?? null,
            'notes_tag' => $sharedFields['notes']['tag'] ?? null,
            'shared_user_id' => $share->from_user_id,
            'shared_at' => $share->shared_at,
            'shared_group_id' => $sourceService->shared_group_id,
            'shared_key_envelope' => $encryptedFields['shared_key_envelope'],
            'client_encrypted' => true,
            'strength' => null,
            'compromised' => false,
            'reused' => false,
        ]);

        $share->update(['accepted_at' => now()]);

        return new AcceptedShareResult($newService);
    }

    private function validateClientEncryptedPayload(array $payload): void
    {
        $encryptedData = $payload['encrypted_data'] ?? null;

        if (
            empty($payload['encrypted_aes_key'])
            || ! is_array($encryptedData)
            || empty($encryptedData['ciphertext'])
            || empty($encryptedData['iv'])
            || empty($encryptedData['tag'])
        ) {
            throw ShareException::invalidClientEncryptedPayload();
        }
    }

    private function validateClientEncryptedSyncPayload(array $payload): void
    {
        if (
            empty($payload['encrypted_aes_key'])
            || ! $this->hasEncryptedString($payload['encrypted_data'] ?? null)
            || ! $this->hasEncryptedString($payload['shared_key_envelope'] ?? null)
            || ! $this->hasRequiredSharedFields($payload['shared_fields'] ?? null)
        ) {
            throw ShareException::invalidClientEncryptedPayload();
        }
    }

    private function hasRequiredSharedFields(mixed $fields): bool
    {
        if (! is_array($fields)) {
            return false;
        }

        if (! $this->hasEncryptedString($fields['username'] ?? null) || ! $this->hasEncryptedString($fields['password'] ?? null)) {
            return false;
        }

        return empty($fields['notes']) || $this->hasEncryptedString($fields['notes']);
    }

    private function hasEncryptedString(mixed $value): bool
    {
        return is_array($value)
            && ! empty($value['ciphertext'])
            && ! empty($value['iv'])
            && strlen((string) $value['iv']) === 24
            && ! empty($value['tag'])
            && strlen((string) $value['tag']) === 32;
    }

    private function validateClientEncryptedAcceptPayload(array $encryptedFields): void
    {
        foreach (['username', 'username_iv', 'username_tag', 'password', 'password_iv', 'password_tag'] as $field) {
            if (empty($encryptedFields[$field])) {
                throw ShareException::invalidClientEncryptedPayload();
            }
        }

        if (! empty($encryptedFields['notes']) && (empty($encryptedFields['notes_iv']) || empty($encryptedFields['notes_tag']))) {
            throw ShareException::invalidClientEncryptedPayload();
        }
    }

    private function validateClientEncryptedSyncAcceptPayload(array $encryptedFields): void
    {
        if (! $this->hasEncryptedString($encryptedFields['shared_key_envelope'] ?? null)) {
            throw ShareException::invalidClientEncryptedPayload();
        }
    }

    private function decryptAesKey(SharePayload $payload, User $recipient): string
    {
        $privateKey = $this->userKeyService->getDecryptedPrivateKey($recipient);

        return $this->cryptoService->decryptWithPrivateKey($payload->encryptedAesKey, $privateKey);
    }

    private function decryptSensitiveData(SharePayload $payload, string $aesKey): array
    {
        $json = $this->cryptoService->decryptWithCustomKey(
            $payload->encryptedData['ciphertext'],
            $payload->encryptedData['iv'],
            $payload->encryptedData['tag'],
            $aesKey
        );

        return json_decode($json, true);
    }

    private function reEncryptForRecipient(array $data): array
    {
        $username = $this->cryptoService->encryptWithMasterKey($data['username']);
        $password = $this->cryptoService->encryptWithMasterKey($data['password']);

        $notes = null;
        if (! empty($data['notes'])) {
            $notes = $this->cryptoService->encryptWithMasterKey($data['notes']);
        }

        return [
            'username' => $username,
            'password' => $password,
            'notes' => $notes,
        ];
    }

    private function createServiceFromSharedData(SharePayload $payload, array $reEncrypted, Share $share, array $analysis): Service
    {
        $sourceService = $this->ensureServiceHasSharedGroup($share->service);

        return Service::create([
            'user_id' => auth()->id(),
            'type' => $payload->type,
            'name' => $payload->name,
            'url' => $payload->url,
            'favicon' => $payload->favicon,
            'username' => $reEncrypted['username']['ciphertext'],
            'username_iv' => $reEncrypted['username']['iv'],
            'username_tag' => $reEncrypted['username']['tag'],
            'password' => $reEncrypted['password']['ciphertext'],
            'password_iv' => $reEncrypted['password']['iv'],
            'password_tag' => $reEncrypted['password']['tag'],
            'notes' => $reEncrypted['notes']['ciphertext'] ?? null,
            'notes_iv' => $reEncrypted['notes']['iv'] ?? null,
            'notes_tag' => $reEncrypted['notes']['tag'] ?? null,
            'shared_user_id' => $share->from_user_id,
            'shared_at' => $share->shared_at,
            'shared_group_id' => $sourceService->shared_group_id,
            'strength' => $analysis['strength'],
            'compromised' => $analysis['compromised'],
            'reused' => $analysis['reused'],
        ]);
    }

    /**
     * @param  array{password: string}  $decryptedData
     * @return array{strength: string|null, compromised: bool, reused: bool}
     */
    private function analysisForPayload(SharePayload $payload, array $decryptedData): array
    {
        if ($payload->type !== Service::TYPE_LOGIN) {
            return [
                'strength' => null,
                'compromised' => false,
                'reused' => false,
            ];
        }

        return $this->passwordService->analyze($decryptedData['password'], auth()->id());
    }

    private function validateRejectPermissions(Share $share): void
    {
        if ($share->to_user_id !== auth()->id() || $share->revoked_at) {
            throw ShareException::unauthorized();
        }
    }

    private function validateRevokePermissions(Share $share): void
    {
        if ($share->from_user_id !== auth()->id() || $share->rejected || $share->revoked_at) {
            throw ShareException::unauthorized();
        }
    }
}
