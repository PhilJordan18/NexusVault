<?php

use App\Models\Service;
use App\Models\User;

function suggestionUser(): User
{
    return User::factory()->create([
        'encrypted_master_key' => null,
        'vault_key_envelope' => null,
    ]);
}

test('unknown service suggestions do not invent a dot com url', function () {
    $user = suggestionUser();

    $this->actingAs($user)
        ->withSession(['masterKey' => base64_encode(random_bytes(32))])
        ->getJson(route('services.suggest', ['name' => 'Laughtube']))
        ->assertOk()
        ->assertJsonPath('0.name', 'Laughtube')
        ->assertJsonPath('0.url', null)
        ->assertJsonPath('0.favicon', '/logo/LogoMonogramme.svg');
});

test('domain-like custom suggestions preserve the typed domain', function () {
    $user = suggestionUser();

    $this->actingAs($user)
        ->withSession(['masterKey' => base64_encode(random_bytes(32))])
        ->getJson(route('services.suggest', ['name' => 'laughtube.ca']))
        ->assertOk()
        ->assertJsonPath('0.url', 'https://laughtube.ca')
        ->assertJsonPath('0.favicon', 'https://laughtube.ca/favicon.ico');
});

test('adn resolves to the official animation digital network domain', function () {
    $user = suggestionUser();

    $this->actingAs($user)
        ->withSession(['masterKey' => base64_encode(random_bytes(32))])
        ->getJson(route('services.suggest', ['name' => 'ADN']))
        ->assertOk()
        ->assertJsonPath('0.url', 'https://animationdigitalnetwork.fr')
        ->assertJsonPath('0.favicon', 'https://animationdigitalnetwork.fr/favicon.ico');
});

test('manual create url takes precedence over a stale suggested domain', function () {
    $user = User::factory()->create([
        'vault_key_envelope' => ['version' => 1],
        'encrypted_master_key' => null,
    ]);

    $this->actingAs($user)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->post(route('services.store'), [
            'type' => Service::TYPE_LOGIN,
            'name' => 'Laughtube',
            'domain' => 'wrong-example.com',
            'url' => 'https://laughtube.ca',
            'username' => 'cipher-username',
            'username_iv' => '111111111111111111111111',
            'username_tag' => '22222222222222222222222222222222',
            'password' => 'cipher-password',
            'password_iv' => '333333333333333333333333',
            'password_tag' => '44444444444444444444444444444444',
            'client_encrypted' => 1,
        ])
        ->assertRedirect(route('dashboard'));

    $service = Service::where('user_id', $user->id)->firstOrFail();

    expect($service->url)->toBe('https://laughtube.ca')
        ->and($service->favicon)->toBe('https://laughtube.ca/favicon.ico');
});
