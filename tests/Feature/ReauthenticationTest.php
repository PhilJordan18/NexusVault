<?php

use App\Models\Service;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

const REAUTH_TOTP_SECRET = '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O';

function createUnlockedVaultUser(array $attributes = []): User
{
    return User::factory()->create([
        'vault_key_envelope' => ['version' => 1],
        'vault_recovery_envelope' => ['version' => 1],
        ...$attributes,
    ]);
}

function createUnlockedMfaUser(array $attributes = []): User
{
    return createUnlockedVaultUser([
        'mfa_enabled' => true,
        'totp_secret' => REAUTH_TOTP_SECRET,
        ...$attributes,
    ]);
}

function unlockedSession(): array
{
    return ['mfa_verified' => true, 'vault_unlocked_at' => now()->timestamp];
}

function freshTotpCode(): string
{
    return (new Google2FA)->getCurrentOtp(REAUTH_TOTP_SECRET);
}

function vaultResetPayload(array $confirmation = []): array
{
    return [
        'confirmation' => 'RESET',
        'vault_key_envelope' => json_encode(['version' => 2]),
        'vault_recovery_envelope' => json_encode(['version' => 2]),
        'public_key' => "-----BEGIN PUBLIC KEY-----\nnew\n-----END PUBLIC KEY-----",
        'encrypted_private_key' => json_encode(['version' => 2]),
        ...$confirmation,
    ];
}

test('account deletion requires a fresh password or totp confirmation', function () {
    $user = createUnlockedVaultUser();

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->delete(route('settings.account.destroy'))
        ->assertRedirect(route('settings'))
        ->assertSessionHasErrors('current_password');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $this->assertAuthenticatedAs($user);

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->delete(route('settings.account.destroy'), ['current_password' => 'wrong-password'])
        ->assertRedirect(route('settings'))
        ->assertSessionHasErrors('current_password');

    $this->assertDatabaseHas('users', ['id' => $user->id]);

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->delete(route('settings.account.destroy'), ['current_password' => 'password'])
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertGuest();
});

test('account deletion accepts a fresh totp code instead of the password', function () {
    $user = createUnlockedMfaUser(['is_oauth' => true]);

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->delete(route('settings.account.destroy'), ['totp_code' => '000000'])
        ->assertRedirect(route('settings'))
        ->assertSessionHasErrors('current_password');

    $this->assertDatabaseHas('users', ['id' => $user->id]);

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->delete(route('settings.account.destroy'), ['totp_code' => freshTotpCode()])
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('an oauth account cannot be confirmed with its placeholder password', function () {
    $user = createUnlockedVaultUser(['is_oauth' => true]);

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->delete(route('settings.account.destroy'), ['current_password' => 'password'])
        ->assertRedirect(route('settings'))
        ->assertSessionHasErrors('current_password');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('disabling mfa requires a fresh totp code or the password', function () {
    $user = createUnlockedMfaUser();

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->post(route('mfa.disable'))
        ->assertRedirect(route('settings'))
        ->assertSessionHasErrors('current_password');

    expect($user->refresh()->mfa_enabled)->toBeTrue()
        ->and($user->totp_secret)->toBe(REAUTH_TOTP_SECRET);

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->post(route('mfa.disable'), ['totp_code' => '000000'])
        ->assertRedirect(route('settings'))
        ->assertSessionHasErrors('current_password');

    expect($user->refresh()->mfa_enabled)->toBeTrue();

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->post(route('mfa.disable'), ['totp_code' => freshTotpCode()])
        ->assertRedirect(route('settings'))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->mfa_enabled)->toBeFalse()
        ->and($user->totp_secret)->toBeNull();
});

test('disabling mfa accepts the login password', function () {
    $user = createUnlockedMfaUser();

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->post(route('mfa.disable'), ['current_password' => 'password'])
        ->assertRedirect(route('settings'))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->mfa_enabled)->toBeFalse();
});

test('resetting the vault requires a fresh password or totp confirmation', function () {
    $user = createUnlockedVaultUser();
    $service = Service::create([
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
        'client_encrypted' => true,
    ]);

    $this->actingAs($user)
        ->from(route('vault.unlock'))
        ->post(route('vault.reset.store'), vaultResetPayload())
        ->assertRedirect(route('vault.unlock'))
        ->assertSessionHasErrors('current_password');

    $this->assertDatabaseHas('services', ['id' => $service->id]);

    $this->actingAs($user)
        ->from(route('vault.unlock'))
        ->post(route('vault.reset.store'), vaultResetPayload(['current_password' => 'wrong-password']))
        ->assertRedirect(route('vault.unlock'))
        ->assertSessionHasErrors('current_password');

    $this->assertDatabaseHas('services', ['id' => $service->id]);

    $this->actingAs($user)
        ->post(route('vault.reset.store'), vaultResetPayload(['current_password' => 'password']))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('services', ['id' => $service->id]);

    expect($user->refresh()->vault_key_envelope)->toBe(['version' => 2]);
});

test('resetting the vault accepts a fresh totp code', function () {
    $user = createUnlockedMfaUser();

    $this->actingAs($user)
        ->withSession(['mfa_verified' => true])
        ->post(route('vault.reset.store'), vaultResetPayload(['totp_code' => freshTotpCode()]))
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->vault_key_envelope)->toBe(['version' => 2]);
});

test('the confirmation error does not reveal which factor failed', function () {
    $user = createUnlockedMfaUser();
    $expectedError = __('Confirm this action with your login password or a current authenticator code.');

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->post(route('mfa.disable'), ['current_password' => 'wrong-password'])
        ->assertSessionHasErrors(['current_password' => $expectedError]);

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->post(route('mfa.disable'), ['totp_code' => '000000'])
        ->assertSessionHasErrors(['current_password' => $expectedError]);

    $this->actingAs($user)
        ->withSession(unlockedSession())
        ->from(route('settings'))
        ->post(route('mfa.disable'))
        ->assertSessionHasErrors(['current_password' => $expectedError]);
});
