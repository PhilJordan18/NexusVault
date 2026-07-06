<?php

use App\Models\User;

test('guests can store their language preference in the session', function () {
    $this->from('/login')
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect('/login')
        ->assertSessionHas('locale', 'fr');

    $this->get('/login')
        ->assertOk()
        ->assertSee('Retour à l’accueil');
});

test('authenticated users persist their language preference', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->from('/settings')
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect('/settings')
        ->assertSessionHas('locale', 'fr');

    expect($user->refresh()->locale)->toBe('fr');
});

test('the locale middleware applies the authenticated user preference', function () {
    $user = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Fonctionnalités');
});
