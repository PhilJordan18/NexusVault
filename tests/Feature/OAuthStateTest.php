<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('services.google', [
        'client_id' => 'google-client-id',
        'client_secret' => 'google-client-secret',
        'redirect' => 'https://nexusvault.test/auth/google/callback',
    ]);
    Config::set('services.github', [
        'client_id' => 'github-client-id',
        'client_secret' => 'github-client-secret',
        'redirect' => 'https://nexusvault.test/auth/github/callback',
    ]);
});

test('the oauth redirect stores a state token in the session', function (string $provider) {
    $response = $this->get("/auth/{$provider}")->assertRedirect();

    $state = session('state');

    expect($state)->not->toBeEmpty()
        ->and($response->headers->get('Location'))->toContain('state='.$state);
})->with(['google', 'github']);

test('an oauth callback whose state does not match the session is rejected', function (string $provider) {
    User::factory()->create(['email' => 'victim@example.com']);

    $this->withSession(['state' => 'expected-state'])
        ->get("/auth/{$provider}/callback?code=attacker-code&state=forged-state")
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
})->with(['google', 'github']);

test('an oauth callback without any state in the session is rejected', function (string $provider) {
    $this->get("/auth/{$provider}/callback?code=attacker-code&state=forged-state")
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
})->with(['google', 'github']);
