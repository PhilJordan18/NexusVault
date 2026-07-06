<?php

use App\Models\User;
use Illuminate\Support\Str;
use Laragear\WebAuthn\Models\WebAuthnCredential;

function createPasskeyFor(User $user, ?string $id = null): WebAuthnCredential
{
    $credential = $user->makeWebAuthnCredential([
        'id' => $id ?? Str::random(32),
        'user_id' => (string) Str::uuid(),
        'alias' => 'Test device',
        'counter' => 0,
        'rp_id' => 'localhost',
        'origin' => 'http://localhost:8000',
        'transports' => ['internal'],
        'aaguid' => (string) Str::uuid(),
        'public_key' => 'test-public-key',
        'attestation_format' => 'none',
    ]);

    $credential->save();

    return $credential;
}

test('users can delete their own passkey', function () {
    $user = User::factory()->create(['encrypted_master_key' => 'present']);
    $credential = createPasskeyFor($user, 'owned-passkey');

    $this->actingAs($user)
        ->withSession(['masterKey' => base64_encode(random_bytes(32))])
        ->from(route('passkeys.index'))
        ->delete(route('webauthn.destroy', $credential))
        ->assertRedirect(route('passkeys.index'))
        ->assertSessionHas('success', __('Passkey deleted.'));

    $this->assertDatabaseMissing('webauthn_credentials', [
        'id' => 'owned-passkey',
    ]);
});

test('users cannot delete another users passkey', function () {
    $owner = User::factory()->create(['encrypted_master_key' => 'present']);
    $intruder = User::factory()->create(['encrypted_master_key' => 'present']);
    $credential = createPasskeyFor($owner, 'another-users-passkey');

    $this->actingAs($intruder)
        ->withSession(['masterKey' => base64_encode(random_bytes(32))])
        ->delete(route('webauthn.destroy', $credential))
        ->assertForbidden();

    $this->assertDatabaseHas('webauthn_credentials', [
        'id' => 'another-users-passkey',
    ]);
});
