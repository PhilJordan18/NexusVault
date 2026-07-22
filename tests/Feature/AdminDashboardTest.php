<?php

use App\Models\Service;
use App\Models\User;

test('non admin users cannot access the admin dashboard', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);

    config(['nexusvault.admin_emails' => ['owner@example.com']]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('configured admins can view operational metadata without vault secrets', function () {
    $admin = User::factory()->create([
        'email' => 'owner@example.com',
        'vault_key_envelope' => ['version' => 1],
    ]);
    $member = User::factory()->create(['email' => 'member@example.com']);

    Service::create([
        'user_id' => $member->id,
        'type' => Service::TYPE_LOGIN,
        'name' => 'Private Service',
        'url' => 'https://example.com',
        'favicon' => 'https://example.com/favicon.ico',
        'username' => 'encrypted-username-marker',
        'username_iv' => str_repeat('a', 24),
        'username_tag' => str_repeat('b', 32),
        'password' => 'encrypted-password-marker',
        'password_iv' => str_repeat('c', 24),
        'password_tag' => str_repeat('d', 32),
        'notes' => 'encrypted-notes-marker',
        'notes_iv' => str_repeat('e', 24),
        'notes_tag' => str_repeat('f', 32),
        'client_encrypted' => true,
    ]);

    config(['nexusvault.admin_emails' => ['owner@example.com']]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('owner@example.com')
        ->assertSee('member@example.com')
        ->assertSee('Operational metadata only')
        ->assertDontSee('encrypted-username-marker')
        ->assertDontSee('encrypted-password-marker')
        ->assertDontSee('encrypted-notes-marker');
});
