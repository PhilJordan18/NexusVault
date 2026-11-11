<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

const WEAK_PASSWORD = 'password1';
const STRONG_PASSWORD = 'Strong-Passw0rd!2026';

test('registration validation applies the shared password policy', function () {
    $this->postJson(route('register.validate'), [
        'name' => 'Philippe MBA',
        'email' => 'policy@example.com',
        'password' => WEAK_PASSWORD,
        'password_confirmation' => WEAK_PASSWORD,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);

    $this->postJson(route('register.validate'), [
        'name' => 'Philippe MBA',
        'email' => 'policy@example.com',
        'password' => STRONG_PASSWORD,
        'password_confirmation' => STRONG_PASSWORD,
    ])
        ->assertOk()
        ->assertJson(['valid' => true]);
});

test('registration applies the shared password policy', function () {
    Notification::fake();

    $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Philippe MBA',
            'email' => 'policy@example.com',
            'password' => WEAK_PASSWORD,
            'password_confirmation' => WEAK_PASSWORD,
            'vault_key_envelope' => json_encode(['version' => 1]),
            'vault_recovery_envelope' => json_encode(['version' => 1]),
            'public_key' => 'public-key',
            'encrypted_private_key' => json_encode(['version' => 1]),
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['password']);

    $this->assertDatabaseMissing('users', ['email' => 'policy@example.com']);
});

test('password reset applies the shared password policy', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->from(route('password.reset', ['token' => $token]))
        ->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => WEAK_PASSWORD,
            'password_confirmation' => WEAK_PASSWORD,
        ])
        ->assertRedirect(route('password.reset', ['token' => $token]))
        ->assertSessionHasErrors(['password']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => STRONG_PASSWORD,
        'password_confirmation' => STRONG_PASSWORD,
    ])->assertRedirect(route('login'));

    expect(Hash::check(STRONG_PASSWORD, $user->refresh()->password))->toBeTrue();
});

test('password change applies the shared password policy', function () {
    $user = User::factory()->create(['vault_key_envelope' => ['version' => 1]]);
    $session = ['mfa_verified' => true, 'vault_unlocked_at' => now()->timestamp];

    $this->actingAs($user)
        ->withSession($session)
        ->from(route('settings'))
        ->post(route('settings.password.update'), [
            'current_password' => 'password',
            'new_password' => 'twelve-chars',
            'new_password_confirmation' => 'twelve-chars',
        ])
        ->assertRedirect(route('settings'))
        ->assertSessionHasErrors(['new_password']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();

    $this->actingAs($user)
        ->withSession($session)
        ->post(route('settings.password.update'), [
            'current_password' => 'password',
            'new_password' => STRONG_PASSWORD,
            'new_password_confirmation' => STRONG_PASSWORD,
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check(STRONG_PASSWORD, $user->refresh()->password))->toBeTrue();
});
