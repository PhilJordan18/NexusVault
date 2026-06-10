<?php

use App\DTOs\Service\ServiceData;
use App\DTOs\Share\ShareData;
use App\Models\User;
use App\Services\Auth\UserKeyService;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use App\Services\Vault\Contracts\ShareServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

function createVaultUserForSharedSync(string $email): User
{
    putenv('RANDFILE=/tmp/.rnd');

    $keys = app(UserKeyService::class)->provisionKey('password');

    return User::factory()->create([
        'name' => fake()->name(),
        'email' => $email,
        'password' => Hash::make('password'),
        'salt' => $keys['salt'],
        'public_key' => $keys['public_key'],
        'private_key' => base64_encode($keys['private_key']),
        'private_nonce' => $keys['private_nonce'],
        'encrypted_master_key' => $keys['encrypted_master_key'],
        'mfa_enabled' => false,
        'totp_secret' => null,
    ]);
}

test('accepted shared services stay synchronized when a participant edits them', function () {
    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('', 200),
    ]);

    $owner = createVaultUserForSharedSync('owner@nexusvault.test');
    $recipient = createVaultUserForSharedSync('recipient@nexusvault.test');

    $this->actingAs($owner);
    app(UserKeyService::class)->storeMasterKey($owner, 'password');

    $service = app(ServiceServiceInterface::class)->create(new ServiceData(
        name: 'GitHub',
        url: 'https://github.com',
        username: 'owner@nexusvault.test',
        password: 'Initial-password-123!',
        notes: 'Initial note',
        domain: 'github.com'
    ));

    $share = app(ShareServiceInterface::class)->share(new ShareData(
        serviceId: $service->id,
        recipientEmail: $recipient->email
    ));

    $this->actingAs($recipient);
    app(UserKeyService::class)->storeMasterKey($recipient, 'password');

    $acceptedService = app(ShareServiceInterface::class)->accept($share->refresh())->service;

    expect($acceptedService->shared_group_id)->toBe($service->refresh()->shared_group_id)
        ->and($acceptedService->username)->toBe('owner@nexusvault.test');

    app(ServiceServiceInterface::class)->update($acceptedService, new ServiceData(
        name: 'GitHub',
        url: 'https://github.com',
        username: 'sync@nexusvault.test',
        password: 'Updated-password-456!',
        notes: 'Updated from recipient'
    ));

    $acceptedService->refresh();

    expect($acceptedService->username)->toBe('sync@nexusvault.test')
        ->and($acceptedService->password)->toBe('Updated-password-456!')
        ->and($acceptedService->notes)->toBe('Updated from recipient');

    $this->actingAs($owner);
    app(UserKeyService::class)->storeMasterKey($owner, 'password');

    $service->refresh();

    expect($service->username)->toBe('sync@nexusvault.test')
        ->and($service->password)->toBe('Updated-password-456!')
        ->and($service->notes)->toBe('Updated from recipient');
});
