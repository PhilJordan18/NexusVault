<?php

use App\Models\User;

const THROTTLE_TOTP_SECRET = '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O';

function createThrottledMfaUser(): User
{
    return User::factory()->create([
        'mfa_enabled' => true,
        'totp_secret' => THROTTLE_TOTP_SECRET,
        'vault_key_envelope' => ['version' => 1],
        'vault_recovery_envelope' => ['version' => 1],
    ]);
}

function throttledUnlockedSession(): array
{
    return ['mfa_verified' => true, 'vault_unlocked_at' => now()->timestamp];
}

dataset('destructive actions confirmed by totp', [
    'disable mfa' => ['post', 'mfa.disable'],
    'delete account' => ['delete', 'settings.account.destroy'],
]);

test('a stolen session cannot brute force the totp code of a destructive action', function (string $method, string $routeName) {
    $user = createThrottledMfaUser();

    foreach (range(1, 6) as $attempt) {
        $this->actingAs($user)
            ->withSession(throttledUnlockedSession())
            ->{$method}(route($routeName), ['totp_code' => '000000'])
            ->assertRedirect();
    }

    $seventhAttempt = $this->actingAs($user)
        ->withSession(throttledUnlockedSession())
        ->{$method}(route($routeName), ['totp_code' => '000000']);

    expect($seventhAttempt->status())->toBe(429);

    expect($user->refresh()->mfa_enabled)->toBeTrue();
})->with('destructive actions confirmed by totp');

test('both destructive actions share one attempt budget per user', function () {
    $user = createThrottledMfaUser();

    foreach (range(1, 3) as $attempt) {
        $this->actingAs($user)
            ->withSession(throttledUnlockedSession())
            ->post(route('mfa.disable'), ['totp_code' => '000000'])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(throttledUnlockedSession())
            ->delete(route('settings.account.destroy'), ['totp_code' => '000000'])
            ->assertRedirect();
    }

    $seventhAttempt = $this->actingAs($user)
        ->withSession(throttledUnlockedSession())
        ->post(route('mfa.disable'), ['totp_code' => '000000']);

    expect($seventhAttempt->status())->toBe(429);
});

test('the attempt budget is counted per user, not per address', function () {
    $attacker = createThrottledMfaUser();
    $otherUser = createThrottledMfaUser();

    foreach (range(1, 7) as $attempt) {
        $this->actingAs($attacker)
            ->withSession(throttledUnlockedSession())
            ->post(route('mfa.disable'), ['totp_code' => '000000']);
    }

    $this->actingAs($otherUser)
        ->withSession(throttledUnlockedSession())
        ->post(route('mfa.disable'), ['totp_code' => '000000'])
        ->assertRedirect();
});
