<?php

use App\Models\Service;
use App\Models\Share;
use App\Models\User;
use App\Services\Auth\OAuthService;
use App\Services\Auth\UserKeyService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;

function createVaultUnlockUser(): User
{
    putenv('RANDFILE=/tmp/.rnd');

    $keys = app(UserKeyService::class)->provisionKey('vault-password-123!');

    return User::factory()->create([
        'password' => Hash::make('login-password-123!'),
        'salt' => $keys['salt'],
        'public_key' => $keys['public_key'],
        'private_key' => base64_encode($keys['private_key']),
        'private_nonce' => $keys['private_nonce'],
        'encrypted_master_key' => $keys['encrypted_master_key'],
    ]);
}

function fakeVaultKeyEnvelope(): string
{
    return json_encode([
        'version' => 1,
        'algorithm' => 'AES-GCM',
        'kdf' => 'PBKDF2-SHA-256',
        'iterations' => 600000,
        'salt' => base64_encode(random_bytes(16)),
        'iv' => bin2hex(random_bytes(12)),
        'ciphertext' => base64_encode(random_bytes(32)),
        'tag' => bin2hex(random_bytes(16)),
    ]);
}

function fakeEncryptedPrivateKeyEnvelope(): string
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

function fakeVaultRecoveryEnvelope(): string
{
    return json_encode([
        'version' => 1,
        'algorithm' => 'AES-GCM',
        'keySource' => 'recovery-key',
        'iv' => bin2hex(random_bytes(12)),
        'ciphertext' => base64_encode(random_bytes(32)),
        'tag' => bin2hex(random_bytes(16)),
    ]);
}

function createClientSideVaultUser(): User
{
    return User::factory()->create([
        'password' => Hash::make('login-password-123!'),
        'public_key' => "-----BEGIN PUBLIC KEY-----\ntest\n-----END PUBLIC KEY-----",
        'private_key' => fakeEncryptedPrivateKeyEnvelope(),
        'private_nonce' => null,
        'encrypted_master_key' => null,
        'vault_key_envelope' => json_decode(fakeVaultKeyEnvelope(), true),
        'vault_recovery_envelope' => json_decode(fakeVaultRecoveryEnvelope(), true),
    ]);
}

function createOAuthUserRequiringVaultSetup(): User
{
    return User::factory()->create([
        'password' => Hash::make('oauth-placeholder-password'),
        'salt' => str_repeat('b', 64),
        'public_key' => '',
        'private_key' => '',
        'private_nonce' => null,
        'encrypted_master_key' => null,
        'vault_key_envelope' => null,
        'vault_recovery_envelope' => null,
        'is_oauth' => true,
    ]);
}

test('authenticated users are redirected to unlock before opening the vault', function () {
    $user = createVaultUnlockUser();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('vault.unlock'));

    $this->assertSame(route('dashboard'), Session::get('url.intended'));
});

test('vault can be unlocked with the vault password', function () {
    $user = createVaultUnlockUser();

    $this->actingAs($user)
        ->post(route('vault.unlock.store'), ['vault_password' => 'vault-password-123!'])
        ->assertRedirect(route('dashboard'));

    $this->assertTrue(Session::has('masterKey'));
    $this->assertTrue(Session::has('vault_unlocked_at'));
});

test('vault unlock rejects an invalid password', function () {
    $user = createVaultUnlockUser();

    $this->actingAs($user)
        ->from(route('vault.unlock'))
        ->post(route('vault.unlock.store'), ['vault_password' => 'wrong-password'])
        ->assertRedirect(route('vault.unlock'))
        ->assertSessionHasErrors('vault_password');

    $this->assertFalse(Session::has('masterKey'));
});

test('the login password cannot unlock a vault created with a separate vault password', function () {
    $user = createVaultUnlockUser();

    $this->actingAs($user)
        ->from(route('vault.unlock'))
        ->post(route('vault.unlock.store'), ['vault_password' => 'login-password-123!'])
        ->assertRedirect(route('vault.unlock'))
        ->assertSessionHasErrors('vault_password');

    $this->assertFalse(Session::has('masterKey'));
});

test('locking the vault clears vault session state', function () {
    $user = createVaultUnlockUser();

    $this->actingAs($user)
        ->withSession([
            'masterKey' => 'test-key',
            'vault_unlocked_at' => now()->timestamp,
            'vault_legacy_unlock' => true,
        ])
        ->post(route('vault.lock'))
        ->assertRedirect(route('vault.unlock'));

    $this->assertFalse(Session::has('masterKey'));
    $this->assertFalse(Session::has('vault_unlocked_at'));
    $this->assertFalse(Session::has('vault_legacy_unlock'));
});

test('json requests receive a locked vault response', function () {
    $user = createVaultUnlockUser();

    $this->actingAs($user)
        ->getJson(route('dashboard'))
        ->assertStatus(423)
        ->assertJson(['message' => __('Vault is locked.')]);
});

test('registration provisions the vault with a separate vault password', function () {
    Notification::fake();

    $publicKey = "-----BEGIN PUBLIC KEY-----\ntest\n-----END PUBLIC KEY-----";

    $this->post(route('register'), [
        'name' => 'Philippe MBA',
        'email' => 'philippe@example.com',
        'password' => 'login-password-123!',
        'password_confirmation' => 'login-password-123!',
        'vault_key_envelope' => fakeVaultKeyEnvelope(),
        'vault_recovery_envelope' => fakeVaultRecoveryEnvelope(),
        'public_key' => $publicKey,
        'encrypted_private_key' => fakeEncryptedPrivateKeyEnvelope(),
    ])->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'philippe@example.com')->firstOrFail();

    expect(Hash::check('login-password-123!', $user->password))->toBeTrue()
        ->and($user->encrypted_master_key)->toBeNull()
        ->and($user->vault_key_envelope)->not->toBeEmpty()
        ->and($user->vault_recovery_envelope)->not->toBeEmpty()
        ->and($user->public_key)->toBe($publicKey);

    $this->post(route('vault.unlock.store'), ['client_unlocked' => 1])
        ->assertRedirect(route('dashboard'));

    $this->assertFalse(Session::has('masterKey'));
    $this->assertTrue(Session::has('vault_unlocked_at'));
});

test('registration requires a client encrypted vault envelope', function () {
    Notification::fake();

    $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Philippe MBA',
            'email' => 'philippe@example.com',
            'password' => 'same-password-123!',
            'password_confirmation' => 'same-password-123!',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['vault_key_envelope', 'vault_recovery_envelope', 'public_key', 'encrypted_private_key']);
});

test('registration validation checks account fields before vault package generation', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson(route('register.validate'), [
        'name' => 'Philippe MBA',
        'email' => 'available@example.com',
        'password' => 'login-password-123!',
        'password_confirmation' => 'login-password-123!',
    ])
        ->assertOk()
        ->assertJson(['valid' => true]);

    $this->assertDatabaseMissing('users', ['email' => 'available@example.com']);

    $this->postJson(route('register.validate'), [
        'name' => 'Philippe MBA',
        'email' => 'taken@example.com',
        'password' => 'login-password-123!',
        'password_confirmation' => 'login-password-123!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->postJson(route('register.validate'), [
        'name' => 'Philippe MBA',
        'email' => 'another@example.com',
        'password' => 'login-password-123!',
        'password_confirmation' => 'different-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('new oauth users are redirected to client side vault setup', function () {
    $oauthUser = (object) [
        'name' => 'OAuth User',
        'email' => 'oauth-user@nexusvault.test',
    ];

    $response = app(OAuthService::class)->handleCallback($oauthUser, 'google');

    expect($response->getTargetUrl())->toBe(route('vault.setup'));

    $user = User::where('email', 'oauth-user@nexusvault.test')->firstOrFail();

    expect($user->is_oauth)->toBeTrue()
        ->and($user->requiresClientVaultSetup())->toBeTrue()
        ->and($user->encrypted_master_key)->toBeNull()
        ->and($user->vault_key_envelope)->toBeNull()
        ->and($user->public_key)->toBe('')
        ->and($user->private_key)->toBe('');
});

test('oauth users without a vault are redirected to setup from protected routes', function () {
    $user = createOAuthUserRequiringVaultSetup();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('vault.setup'));

    $this->assertSame(route('dashboard'), Session::get('url.intended'));

    $this->actingAs($user)
        ->get(route('vault.unlock'))
        ->assertRedirect(route('vault.setup'));
});

test('oauth users can create their client side vault after login', function () {
    $user = createOAuthUserRequiringVaultSetup();
    $vaultEnvelope = fakeVaultKeyEnvelope();
    $recoveryEnvelope = fakeVaultRecoveryEnvelope();
    $publicKey = "-----BEGIN PUBLIC KEY-----\noauth-test\n-----END PUBLIC KEY-----";
    $privateKeyEnvelope = fakeEncryptedPrivateKeyEnvelope();

    $this->actingAs($user)
        ->withSession(['url.intended' => route('services.show', 'Youtube')])
        ->post(route('vault.setup.store'), [
            'vault_key_envelope' => $vaultEnvelope,
            'vault_recovery_envelope' => $recoveryEnvelope,
            'public_key' => $publicKey,
            'encrypted_private_key' => $privateKeyEnvelope,
        ])
        ->assertRedirect(route('dashboard'));

    $user->refresh();

    expect($user->requiresClientVaultSetup())->toBeFalse()
        ->and($user->usesClientSideVault())->toBeTrue()
        ->and($user->encrypted_master_key)->toBeNull()
        ->and($user->vault_key_envelope)->toBe(json_decode($vaultEnvelope, true))
        ->and($user->vault_recovery_envelope)->toBe(json_decode($recoveryEnvelope, true))
        ->and($user->public_key)->toBe($publicKey)
        ->and($user->private_key)->toBe($privateKeyEnvelope)
        ->and(Session::has('vault_unlocked_at'))->toBeTrue()
        ->and(Session::has('masterKey'))->toBeFalse();

    $this->assertFalse(Session::has('url.intended'));
});

test('client side vault setup cannot overwrite an existing vault', function () {
    $user = createClientSideVaultUser();
    $originalEnvelope = $user->vault_key_envelope;

    $this->actingAs($user)
        ->post(route('vault.setup.store'), [
            'vault_key_envelope' => fakeVaultKeyEnvelope(),
            'vault_recovery_envelope' => fakeVaultRecoveryEnvelope(),
            'public_key' => "-----BEGIN PUBLIC KEY-----\nreplacement\n-----END PUBLIC KEY-----",
            'encrypted_private_key' => fakeEncryptedPrivateKeyEnvelope(),
        ])
        ->assertRedirect(route('vault.unlock'));

    expect($user->refresh()->vault_key_envelope)->toBe($originalEnvelope);
});

test('client side vault users can access protected routes after browser unlock without a server master key', function () {
    $user = createClientSideVaultUser();

    $this->actingAs($user)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->get(route('dashboard'))
        ->assertOk();

    $this->assertFalse(Session::has('masterKey'));
});

test('client side vault users store vault items as opaque ciphertext', function () {
    $user = createClientSideVaultUser();

    $this->actingAs($user)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->post(route('services.store'), [
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
            'client_encrypted' => 1,
        ])
        ->assertRedirect(route('dashboard'));

    $service = Service::where('user_id', $user->id)->firstOrFail();

    expect($service->client_encrypted)->toBeTrue()
        ->and($service->getRawOriginal('username'))->toBe('cipher-username')
        ->and($service->getRawOriginal('password'))->toBe('cipher-password')
        ->and($service->getRawOriginal('notes'))->toBe('cipher-notes')
        ->and($service->strength)->toBeNull()
        ->and($service->compromised)->toBeFalse()
        ->and($service->reused)->toBeFalse();
});

test('client side vault users cannot submit clear vault items', function () {
    $user = createClientSideVaultUser();

    $this->actingAs($user)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->from(route('dashboard'))
        ->post(route('services.store'), [
            'type' => Service::TYPE_LOGIN,
            'name' => 'GitHub',
            'url' => 'https://github.com',
            'username' => 'philippe@example.com',
            'password' => 'plaintext-password',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('client_encrypted');

    expect(Service::where('user_id', $user->id)->exists())->toBeFalse();
});

test('client side vault users can destructively reset their vault without a server master key', function () {
    $user = createClientSideVaultUser();
    $recipient = User::factory()->create();
    $oldVaultEnvelope = $user->vault_key_envelope;
    $newVaultEnvelope = fakeVaultKeyEnvelope();
    $newRecoveryEnvelope = fakeVaultRecoveryEnvelope();

    $ownedService = Service::create([
        'user_id' => $user->id,
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
        'shared_group_id' => 'shared-group-id',
        'client_encrypted' => true,
    ]);

    Service::create([
        'user_id' => $recipient->id,
        'type' => Service::TYPE_LOGIN,
        'name' => 'GitHub Copy',
        'url' => 'https://github.com',
        'username' => 'shared-username',
        'username_iv' => '111111111111111111111111',
        'username_tag' => '22222222222222222222222222222222',
        'password' => 'shared-password',
        'password_iv' => '333333333333333333333333',
        'password_tag' => '44444444444444444444444444444444',
        'notes' => 'shared-notes',
        'notes_iv' => '555555555555555555555555',
        'notes_tag' => '66666666666666666666666666666666',
        'shared_user_id' => $user->id,
        'shared_group_id' => 'shared-group-id',
    ]);

    Share::create([
        'service_id' => $ownedService->id,
        'from_user_id' => $user->id,
        'to_user_id' => $recipient->id,
        'shared_data' => '{}',
    ]);

    $this->actingAs($user)
        ->post(route('vault.reset.store'), [
            'confirmation' => 'RESET',
            'vault_key_envelope' => $newVaultEnvelope,
            'vault_recovery_envelope' => $newRecoveryEnvelope,
            'public_key' => "-----BEGIN PUBLIC KEY-----\nnew-test\n-----END PUBLIC KEY-----",
            'encrypted_private_key' => fakeEncryptedPrivateKeyEnvelope(),
        ])
        ->assertRedirect(route('dashboard'));

    $user->refresh();

    expect(Session::has('masterKey'))->toBeFalse()
        ->and(Session::has('vault_unlocked_at'))->toBeTrue()
        ->and($user->vault_key_envelope)->not->toBe($oldVaultEnvelope)
        ->and($user->vault_key_envelope)->toBe(json_decode($newVaultEnvelope, true))
        ->and($user->vault_recovery_envelope)->toBe(json_decode($newRecoveryEnvelope, true))
        ->and(Service::where('user_id', $user->id)->exists())->toBeFalse()
        ->and(Service::where('shared_user_id', $user->id)->exists())->toBeFalse()
        ->and(Share::where('from_user_id', $user->id)->orWhere('to_user_id', $user->id)->exists())->toBeFalse();
});

test('vault reset requires explicit confirmation', function () {
    $user = createClientSideVaultUser();

    Service::create([
        'user_id' => $user->id,
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

    $this->actingAs($user)
        ->from(route('vault.unlock'))
        ->post(route('vault.reset.store'), [
            'confirmation' => 'DELETE',
            'vault_key_envelope' => fakeVaultKeyEnvelope(),
            'vault_recovery_envelope' => fakeVaultRecoveryEnvelope(),
            'public_key' => "-----BEGIN PUBLIC KEY-----\nnew-test\n-----END PUBLIC KEY-----",
            'encrypted_private_key' => fakeEncryptedPrivateKeyEnvelope(),
        ])
        ->assertRedirect(route('vault.unlock'))
        ->assertSessionHasErrors('confirmation');

    expect(Service::where('user_id', $user->id)->exists())->toBeTrue();
});
