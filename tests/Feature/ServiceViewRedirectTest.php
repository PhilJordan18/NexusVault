<?php

use App\Models\Service;
use App\Models\User;

function createUnlockedClientVaultUserForServiceView(): User
{
    return User::factory()->create([
        'encrypted_master_key' => null,
        'vault_key_envelope' => ['version' => 1],
        'vault_recovery_envelope' => ['version' => 1],
    ]);
}

test('service detail redirects to dashboard when the named service group is empty', function () {
    $user = createUnlockedClientVaultUserForServiceView();

    $this->actingAs($user)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->get(route('services.show', [
            'name' => 'DigitalOcean',
            'type' => Service::TYPE_LOGIN,
        ]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', __('This service is no longer available.'));
});

test('legacy dashboard service detail redirects when the named service group is empty', function () {
    $user = createUnlockedClientVaultUserForServiceView();

    $this->actingAs($user)
        ->withSession(['vault_unlocked_at' => now()->timestamp])
        ->get(route('dashboard.services', 'DigitalOcean'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', __('This service is no longer available.'));
});
