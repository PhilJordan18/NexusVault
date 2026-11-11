<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

const ENUMERATION_PASSWORD = 'Strong-Passw0rd!2026';

test('password reset gives the same answer for unknown and known emails', function () {
    $user = User::factory()->create();

    $unknown = $this->from(route('password.reset', ['token' => 'bogus-token']))
        ->post(route('password.update'), [
            'token' => 'bogus-token',
            'email' => 'nobody@example.com',
            'password' => ENUMERATION_PASSWORD,
            'password_confirmation' => ENUMERATION_PASSWORD,
        ])
        ->assertRedirect(route('password.reset', ['token' => 'bogus-token']))
        ->assertSessionHasErrors('email');

    $unknownError = session('errors')->first('email');

    $known = $this->from(route('password.reset', ['token' => 'bogus-token']))
        ->post(route('password.update'), [
            'token' => 'bogus-token',
            'email' => $user->email,
            'password' => ENUMERATION_PASSWORD,
            'password_confirmation' => ENUMERATION_PASSWORD,
        ])
        ->assertRedirect(route('password.reset', ['token' => 'bogus-token']))
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe($unknownError)
        ->and($unknownError)->not->toContain('find');
});

test('login gives the same answer for unknown and known accounts', function () {
    $user = User::factory()->create();

    $this->from(route('login.password'))
        ->post(route('login.authenticate.password'), [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login.password'))
        ->assertSessionHasErrors(['email' => 'Invalid credentials']);

    $this->from(route('login.password'))
        ->post(route('login.authenticate.password'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login.password'))
        ->assertSessionHasErrors(['email' => 'Invalid credentials']);

    $this->assertGuest();
});

test('login hashes the password even when the account does not exist', function () {
    Cache::forever('auth.unknown_account_hash', Hash::make('placeholder'));

    Hash::partialMock()
        ->shouldReceive('check')
        ->once()
        ->andReturn(false);

    $this->post(route('login.authenticate.password'), [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('the email step of the login never reveals whether an account exists', function () {
    $user = User::factory()->create();

    $this->post(route('login.authenticate.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect(route('login.password', ['email' => 'nobody@example.com']));

    $this->post(route('login.authenticate.email'), ['email' => $user->email])
        ->assertRedirect(route('login.password', ['email' => $user->email]));
});
