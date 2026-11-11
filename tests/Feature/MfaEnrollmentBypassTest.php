<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

function createUserWithConfirmedMfa(): User
{
    return User::factory()->create([
        'mfa_enabled' => true,
        'totp_secret' => '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O',
    ]);
}

test('a session that has not passed mfa cannot reach the enrollment routes', function () {
    $user = createUserWithConfirmedMfa();

    $this->actingAs($user)
        ->get(route('mfa.setup'))
        ->assertRedirect(route('mfa.verify.login'));

    $this->actingAs($user)
        ->get(route('mfa.qr-code'))
        ->assertRedirect(route('mfa.verify.login'));

    $this->actingAs($user)
        ->post(route('mfa.setup.verify'), ['code' => '123456'])
        ->assertRedirect(route('mfa.verify.login'));

    expect($user->refresh()->totp_secret)->toBe('2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O');
});

test('a confirmed totp secret is never displayed again', function () {
    $user = createUserWithConfirmedMfa();

    $this->actingAs($user)
        ->withSession(['mfa_verified' => true])
        ->get(route('mfa.setup'))
        ->assertRedirect(route('settings'));

    $this->actingAs($user)
        ->withSession(['mfa_verified' => true])
        ->get(route('mfa.qr-code'))
        ->assertNotFound();
});

test('mfa enrollment keeps the pending secret out of the database until it is verified', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('mfa.setup'))
        ->assertOk();

    $pendingSecret = $response->baseResponse->getOriginalContent()->getData()['totp_secret'];

    expect($pendingSecret)->not->toBeEmpty()
        ->and(session('mfa_pending_secret'))->toBe($pendingSecret)
        ->and($user->refresh()->totp_secret)->toBeNull();

    $this->actingAs($user)
        ->withSession(['mfa_pending_secret' => $pendingSecret])
        ->post(route('mfa.setup.verify'), ['code' => '000000'])
        ->assertRedirect();

    expect($user->refresh()->mfa_enabled)->toBeFalse()
        ->and($user->totp_secret)->toBeNull();

    $code = (new Google2FA)->getCurrentOtp($pendingSecret);

    $this->actingAs($user)
        ->withSession(['mfa_pending_secret' => $pendingSecret])
        ->post(route('mfa.setup.verify'), ['code' => $code])
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->mfa_enabled)->toBeTrue()
        ->and($user->totp_secret)->toBe($pendingSecret)
        ->and(session()->has('mfa_pending_secret'))->toBeFalse()
        ->and(session('mfa_verified'))->toBeTrue();
});

test('mfa enrollment ignores a half enrolled secret left in the database', function () {
    $user = User::factory()->create([
        'mfa_enabled' => false,
        'totp_secret' => '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O',
    ]);

    $response = $this->actingAs($user)
        ->get(route('mfa.setup'))
        ->assertOk();

    expect($response->baseResponse->getOriginalContent()->getData()['totp_secret'])
        ->not->toBe('2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O');

    $staleCode = (new Google2FA)->getCurrentOtp('2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O');

    $this->actingAs($user)
        ->post(route('mfa.setup.verify'), ['code' => $staleCode])
        ->assertRedirect();

    expect($user->refresh()->mfa_enabled)->toBeFalse();
});
