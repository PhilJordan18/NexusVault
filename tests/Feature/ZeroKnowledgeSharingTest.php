<?php

use App\Models\Service;
use App\Models\Share;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function zkSharingVaultEnvelope(): array
{
    return [
        'version' => 1,
        'algorithm' => 'AES-GCM',
        'kdf' => 'PBKDF2-SHA-256',
        'iterations' => 600000,
        'salt' => base64_encode(random_bytes(16)),
        'iv' => bin2hex(random_bytes(12)),
        'ciphertext' => base64_encode(random_bytes(32)),
        'tag' => bin2hex(random_bytes(16)),
    ];
}

function zkSharingRecoveryEnvelope(): array
{
    return [
        'version' => 1,
        'algorithm' => 'AES-GCM',
        'keySource' => 'recovery-key',
        'iv' => bin2hex(random_bytes(12)),
        'ciphertext' => base64_encode(random_bytes(32)),
        'tag' => bin2hex(random_bytes(16)),
    ];
}

function zkSharingPrivateKeyEnvelope(): string
{
    return json_encode([
        'version' => 1,
        'algorithm' => 'AES-GCM',
        'keyFormat' => 'pkcs8',
        'iv' => bin2hex(random_bytes(12)),
        'ciphertext' => base64_encode(random_bytes(256)),
        'tag' => bin2hex(random_bytes(16)),
    ]);
}

function createZkSharingUser(string $email): User
{
    return User::factory()->create([
        'name' => Str::before($email, '@'),
        'email' => $email,
        'password' => Hash::make('login-password-123!'),
        'public_key' => "-----BEGIN PUBLIC KEY-----\n{$email}\n-----END PUBLIC KEY-----",
        'private_key' => zkSharingPrivateKeyEnvelope(),
        'private_nonce' => null,
        'encrypted_master_key' => null,
        'vault_key_envelope' => zkSharingVaultEnvelope(),
        'vault_recovery_envelope' => zkSharingRecoveryEnvelope(),
    ]);
}

function createZkSharingService(User $owner): Service
{
    return Service::create([
        'user_id' => $owner->id,
        'type' => Service::TYPE_LOGIN,
        'name' => 'GitHub',
        'url' => 'https://github.com',
        'username' => 'cipher-username',
        'username_iv' => '111111111111111111111111',
        'username_tag' => '22222222222222222222222222222222',
        'password' => 'cipher-password',
        'password_iv' => '333333333333333333333333',
        'password_tag' => '44444444444444444444444444444444',
        'notes' => 'cipher-notes',
        'notes_iv' => '555555555555555555555555',
        'notes_tag' => '66666666666666666666666666666666',
        'client_encrypted' => true,
    ]);
}

function zkSharingPayload(): array
{
    return [
        'encrypted_aes_key' => base64_encode(random_bytes(256)),
        'encrypted_data' => [
            'ciphertext' => base64_encode(random_bytes(96)),
            'iv' => '777777777777777777777777',
            'tag' => '88888888888888888888888888888888',
        ],
    ];
}

function zkSharingEncryptedString(string $ciphertext, string $ivCharacter, string $tagCharacter): array
{
    return [
        'ciphertext' => $ciphertext,
        'iv' => str_repeat($ivCharacter, 24),
        'tag' => str_repeat($tagCharacter, 32),
    ];
}

function zkSharingSharedKeyEnvelope(string $ciphertext, string $ivCharacter = '1', string $tagCharacter = '2'): array
{
    return [
        'version' => 1,
        'algorithm' => 'AES-GCM',
        'keySource' => 'shared-item-key',
        ...zkSharingEncryptedString($ciphertext, $ivCharacter, $tagCharacter),
    ];
}

function zkSharingSharedFields(string $prefix): array
{
    return [
        'username' => zkSharingEncryptedString("{$prefix}-username", '3', '4'),
        'password' => zkSharingEncryptedString("{$prefix}-password", '5', '6'),
        'notes' => zkSharingEncryptedString("{$prefix}-notes", '7', '8'),
    ];
}

function zkSharingSyncPayload(string $prefix = 'sync'): array
{
    return [
        'mode' => 'client-encrypted-sync',
        'encrypted_aes_key' => base64_encode(random_bytes(256)),
        'encrypted_data' => zkSharingEncryptedString("{$prefix}-payload", '9', 'a'),
        'shared_key_envelope' => zkSharingSharedKeyEnvelope("{$prefix}-owner-key"),
        'shared_fields' => zkSharingSharedFields($prefix),
    ];
}

function zkSharingAcceptedFields(): array
{
    return [
        'client_encrypted' => 1,
        'username' => 'recipient-cipher-username',
        'username_iv' => '999999999999999999999999',
        'username_tag' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'password' => 'recipient-cipher-password',
        'password_iv' => 'bbbbbbbbbbbbbbbbbbbbbbbb',
        'password_tag' => 'cccccccccccccccccccccccccccccccc',
        'notes' => 'recipient-cipher-notes',
        'notes_iv' => 'dddddddddddddddddddddddd',
        'notes_tag' => 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
    ];
}

test('client encrypted share preparation returns only recipient public key metadata', function () {
    $owner = createZkSharingUser('zk-owner@nexusvault.test');
    $recipient = createZkSharingUser('zk-recipient@nexusvault.test');
    $service = createZkSharingService($owner);

    $this->actingAs($owner)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.prepare'), [
            'service_id' => $service->id,
            'email' => $recipient->email,
        ])
        ->assertOk()
        ->assertJsonPath('recipient.email', $recipient->email)
        ->assertJsonPath('recipient.public_key', $recipient->public_key);

    expect(Share::count())->toBe(0);
});

test('client encrypted shares are stored as opaque payloads', function () {
    $owner = createZkSharingUser('zk-owner@nexusvault.test');
    $recipient = createZkSharingUser('zk-recipient@nexusvault.test');
    $service = createZkSharingService($owner);
    $payload = zkSharingPayload();

    $this->actingAs($owner)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.store'), [
            'service_id' => $service->id,
            'email' => $recipient->email,
            'client_encrypted' => 1,
            ...$payload,
        ])
        ->assertOk()
        ->assertJsonPath('message', __('Share sent successfully!'));

    $share = Share::firstOrFail();
    $sharedData = json_decode($share->shared_data, true);

    expect($sharedData['mode'])->toBe('client-encrypted')
        ->and($sharedData['encrypted_aes_key'])->toBe($payload['encrypted_aes_key'])
        ->and($sharedData['encrypted_data'])->toBe($payload['encrypted_data'])
        ->and($sharedData['name'])->toBe('GitHub')
        ->and($share->service->refresh()->shared_group_id)->not->toBeNull()
        ->and($share->shared_data)->not->toContain('plain-password');
});

test('client encrypted sync shares convert the source service to shared group ciphertext', function () {
    $owner = createZkSharingUser('zk-owner@nexusvault.test');
    $recipient = createZkSharingUser('zk-recipient@nexusvault.test');
    $service = createZkSharingService($owner);
    $payload = zkSharingSyncPayload();

    $this->actingAs($owner)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.store'), [
            'service_id' => $service->id,
            'email' => $recipient->email,
            'client_encrypted' => 1,
            ...$payload,
        ])
        ->assertOk()
        ->assertJsonPath('message', __('Share sent successfully!'));

    $share = Share::firstOrFail();
    $sharedData = json_decode($share->shared_data, true);
    $service->refresh();

    expect($sharedData['mode'])->toBe('client-encrypted-sync')
        ->and($sharedData['shared_fields'])->toBe($payload['shared_fields'])
        ->and($service->shared_key_envelope)->toBe($payload['shared_key_envelope'])
        ->and($service->getRawOriginal('username'))->toBe($payload['shared_fields']['username']['ciphertext'])
        ->and($service->getRawOriginal('password'))->toBe($payload['shared_fields']['password']['ciphertext'])
        ->and($service->getRawOriginal('notes'))->toBe($payload['shared_fields']['notes']['ciphertext'])
        ->and($service->shared_group_id)->not->toBeNull();
});

test('client encrypted share recipients accept by submitting locally re-encrypted fields', function () {
    $owner = createZkSharingUser('zk-owner@nexusvault.test');
    $recipient = createZkSharingUser('zk-recipient@nexusvault.test');
    $service = createZkSharingService($owner);
    $payload = zkSharingPayload();

    $this->actingAs($owner)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.store'), [
            'service_id' => $service->id,
            'email' => $recipient->email,
            'client_encrypted' => 1,
            ...$payload,
        ])
        ->assertOk();

    $share = Share::firstOrFail();

    $this->actingAs($recipient)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.accept', $share), zkSharingAcceptedFields())
        ->assertOk()
        ->assertJsonPath('redirect', route('dashboard'));

    $acceptedService = Service::where('user_id', $recipient->id)->firstOrFail();

    expect($share->refresh()->accepted_at)->not->toBeNull()
        ->and($acceptedService->client_encrypted)->toBeTrue()
        ->and($acceptedService->shared_user_id)->toBe($owner->id)
        ->and($acceptedService->shared_group_id)->toBe($service->refresh()->shared_group_id)
        ->and($acceptedService->getRawOriginal('username'))->toBe('recipient-cipher-username')
        ->and($acceptedService->getRawOriginal('password'))->toBe('recipient-cipher-password')
        ->and($acceptedService->getRawOriginal('notes'))->toBe('recipient-cipher-notes')
        ->and($acceptedService->strength)->toBeNull()
        ->and($acceptedService->compromised)->toBeFalse()
        ->and($acceptedService->reused)->toBeFalse();
});

test('client encrypted sync recipients accept by storing their own shared key envelope', function () {
    $owner = createZkSharingUser('zk-owner@nexusvault.test');
    $recipient = createZkSharingUser('zk-recipient@nexusvault.test');
    $service = createZkSharingService($owner);
    $payload = zkSharingSyncPayload();
    $recipientEnvelope = zkSharingSharedKeyEnvelope('recipient-shared-key', 'b', 'c');

    $this->actingAs($owner)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.store'), [
            'service_id' => $service->id,
            'email' => $recipient->email,
            'client_encrypted' => 1,
            ...$payload,
        ])
        ->assertOk();

    $share = Share::firstOrFail();

    $this->actingAs($recipient)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.accept', $share), [
            'client_encrypted' => 1,
            'shared_key_envelope' => $recipientEnvelope,
        ])
        ->assertOk()
        ->assertJsonPath('redirect', route('dashboard'));

    $acceptedService = Service::where('user_id', $recipient->id)->firstOrFail();

    expect($acceptedService->client_encrypted)->toBeTrue()
        ->and($acceptedService->shared_key_envelope)->toBe($recipientEnvelope)
        ->and($acceptedService->shared_group_id)->toBe($service->refresh()->shared_group_id)
        ->and($acceptedService->getRawOriginal('username'))->toBe($payload['shared_fields']['username']['ciphertext'])
        ->and($acceptedService->getRawOriginal('password'))->toBe($payload['shared_fields']['password']['ciphertext'])
        ->and($acceptedService->getRawOriginal('notes'))->toBe($payload['shared_fields']['notes']['ciphertext']);
});

test('client encrypted sync edits propagate opaque ciphertext across accepted copies', function () {
    $owner = createZkSharingUser('zk-owner@nexusvault.test');
    $recipient = createZkSharingUser('zk-recipient@nexusvault.test');
    $service = createZkSharingService($owner);
    $payload = zkSharingSyncPayload();
    $recipientEnvelope = zkSharingSharedKeyEnvelope('recipient-shared-key', 'b', 'c');
    $updatedFields = zkSharingSharedFields('updated-sync');

    $this->actingAs($owner)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.store'), [
            'service_id' => $service->id,
            'email' => $recipient->email,
            'client_encrypted' => 1,
            ...$payload,
        ])
        ->assertOk();

    $share = Share::firstOrFail();

    $this->actingAs($recipient)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.accept', $share), [
            'client_encrypted' => 1,
            'shared_key_envelope' => $recipientEnvelope,
        ])
        ->assertOk();

    $acceptedService = Service::where('user_id', $recipient->id)->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->putJson(route('services.update', $service->refresh()), [
            'type' => Service::TYPE_LOGIN,
            'name' => 'GitHub',
            'url' => 'https://github.com',
            'username' => $updatedFields['username']['ciphertext'],
            'username_iv' => $updatedFields['username']['iv'],
            'username_tag' => $updatedFields['username']['tag'],
            'password' => $updatedFields['password']['ciphertext'],
            'password_iv' => $updatedFields['password']['iv'],
            'password_tag' => $updatedFields['password']['tag'],
            'notes' => $updatedFields['notes']['ciphertext'],
            'notes_iv' => $updatedFields['notes']['iv'],
            'notes_tag' => $updatedFields['notes']['tag'],
            'client_encrypted' => 1,
        ])
        ->assertOk();

    $acceptedService->refresh();

    expect($acceptedService->shared_key_envelope)->toBe($recipientEnvelope)
        ->and($acceptedService->getRawOriginal('username'))->toBe($updatedFields['username']['ciphertext'])
        ->and($acceptedService->getRawOriginal('password'))->toBe($updatedFields['password']['ciphertext'])
        ->and($acceptedService->getRawOriginal('notes'))->toBe($updatedFields['notes']['ciphertext']);
});

test('client encrypted shares require a zero knowledge recipient', function () {
    $owner = createZkSharingUser('zk-owner@nexusvault.test');
    $recipient = User::factory()->create(['email' => 'legacy-recipient@nexusvault.test']);
    $service = createZkSharingService($owner);

    $this->actingAs($owner)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->postJson(route('shares.prepare'), [
            'service_id' => $service->id,
            'email' => $recipient->email,
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', __('The recipient must have a zero-knowledge vault before receiving this item.'));

    expect(Share::count())->toBe(0);
});
