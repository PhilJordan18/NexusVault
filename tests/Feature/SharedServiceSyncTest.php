<?php

use App\DTOs\Service\ServiceData;
use App\DTOs\Share\ShareData;
use App\Exceptions\ShareException;
use App\Models\Service;
use App\Models\Share;
use App\Models\User;
use App\Services\Auth\UserKeyService;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use App\Services\Vault\Contracts\ShareServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

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

function createAcceptedSharedServiceForSync(TestCase $testCase): array
{
    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('', 200),
    ]);

    $owner = createVaultUserForSharedSync('owner@nexusvault.test');
    $recipient = createVaultUserForSharedSync('recipient@nexusvault.test');

    $testCase->actingAs($owner);
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

    $testCase->actingAs($recipient);
    app(UserKeyService::class)->storeMasterKey($recipient, 'password');

    $acceptedService = app(ShareServiceInterface::class)->accept($share->refresh())->service;

    return [$owner, $recipient, $service->refresh(), $share->refresh(), $acceptedService->refresh()];
}

test('accepted shared services stay synchronized when a participant edits them', function () {
    [$owner, $recipient, $service, $share, $acceptedService] = createAcceptedSharedServiceForSync($this);

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

test('shared payment cards keep their vault item type without password analysis', function () {
    Http::fake();

    $owner = createVaultUserForSharedSync('card-owner@nexusvault.test');
    $recipient = createVaultUserForSharedSync('card-recipient@nexusvault.test');

    $this->actingAs($owner);
    app(UserKeyService::class)->storeMasterKey($owner, 'password');

    $service = app(ServiceServiceInterface::class)->create(new ServiceData(
        name: 'Visa Desjardins',
        url: null,
        username: 'Philippe MBA',
        password: '4111 1111 1111 1111',
        notes: 'Expires 12/30 - CVC 123',
        type: Service::TYPE_PAYMENT_CARD
    ));

    $share = app(ShareServiceInterface::class)->share(new ShareData(
        serviceId: $service->id,
        recipientEmail: $recipient->email
    ));

    expect(json_decode($share->shared_data, true)['type'])->toBe(Service::TYPE_PAYMENT_CARD);

    $this->actingAs($recipient);
    app(UserKeyService::class)->storeMasterKey($recipient, 'password');

    $acceptedService = app(ShareServiceInterface::class)->accept($share->refresh())->service->refresh();

    expect($acceptedService->type)->toBe(Service::TYPE_PAYMENT_CARD)
        ->and($acceptedService->username)->toBe('Philippe MBA')
        ->and($acceptedService->password)->toBe('4111 1111 1111 1111')
        ->and($acceptedService->notes)->toBe('Expires 12/30 - CVC 123')
        ->and($acceptedService->strength)->toBeNull()
        ->and($acceptedService->compromised)->toBeFalse()
        ->and($acceptedService->reused)->toBeFalse();

    Http::assertNothingSent();
});

test('recipients cannot reshare shared vault items', function () {
    [$owner, $recipient, $service, $share, $acceptedService] = createAcceptedSharedServiceForSync($this);
    $thirdUser = createVaultUserForSharedSync('third-recipient@nexusvault.test');

    $this->actingAs($recipient);
    app(UserKeyService::class)->storeMasterKey($recipient, 'password');

    expect(fn () => app(ShareServiceInterface::class)->share(new ShareData(
        serviceId: $acceptedService->id,
        recipientEmail: $thirdUser->email
    )))->toThrow(ShareException::class, 'Shared items can only be shared by their original owner.');

    expect(Share::where('to_user_id', $thirdUser->id)->exists())->toBeFalse();
});

test('owner deletion removes every copy in the shared group', function () {
    [$owner, $recipient, $service, $share, $acceptedService] = createAcceptedSharedServiceForSync($this);
    $sharedGroupId = $service->shared_group_id;

    $this->actingAs($owner);
    app(UserKeyService::class)->storeMasterKey($owner, 'password');

    app(ServiceServiceInterface::class)->delete($service->refresh());

    expect(Service::where('shared_group_id', $sharedGroupId)->exists())->toBeFalse()
        ->and(Service::whereKey($service->id)->exists())->toBeFalse()
        ->and(Service::whereKey($acceptedService->id)->exists())->toBeFalse()
        ->and(Share::whereKey($share->id)->exists())->toBeFalse();
});

test('recipient deletion only removes their own shared access', function () {
    [$owner, $recipient, $service, $share, $acceptedService] = createAcceptedSharedServiceForSync($this);

    $this->actingAs($recipient);
    app(UserKeyService::class)->storeMasterKey($recipient, 'password');

    app(ServiceServiceInterface::class)->delete($acceptedService->refresh());

    expect(Service::whereKey($service->id)->exists())->toBeTrue()
        ->and(Service::whereKey($acceptedService->id)->exists())->toBeFalse()
        ->and($share->refresh()->revoked_at)->not->toBeNull();
});

test('owner can revoke an accepted share without deleting the original service', function () {
    [$owner, $recipient, $service, $share, $acceptedService] = createAcceptedSharedServiceForSync($this);

    $this->actingAs($owner);
    app(UserKeyService::class)->storeMasterKey($owner, 'password');

    app(ShareServiceInterface::class)->revoke($share->refresh());

    expect(Service::whereKey($service->id)->exists())->toBeTrue()
        ->and(Service::whereKey($acceptedService->id)->exists())->toBeFalse()
        ->and($share->refresh()->revoked_at)->not->toBeNull();
});
