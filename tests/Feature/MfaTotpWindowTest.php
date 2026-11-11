<?php

use App\Models\User;
use App\Services\Auth\MfaService;
use PragmaRX\Google2FA\Google2FA;

const TOTP_WINDOW_SECRET = '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O';

function totpCodeAtOffset(int $periods): string
{
    $google2FA = new Google2FA;
    $counter = $google2FA->getTimestamp() + $periods;

    return $google2FA->oathTotp(TOTP_WINDOW_SECRET, $counter);
}

test('totp codes are only accepted within one period of drift', function () {
    $user = User::factory()->create([
        'mfa_enabled' => true,
        'totp_secret' => TOTP_WINDOW_SECRET,
    ]);
    $service = app(MfaService::class);

    expect($service->verifyCode($user, totpCodeAtOffset(0)))->toBeTrue()
        ->and($service->verifyCode($user, totpCodeAtOffset(-1)))->toBeTrue()
        ->and($service->verifyCode($user, totpCodeAtOffset(1)))->toBeTrue()
        ->and($service->verifyCode($user, totpCodeAtOffset(-2)))->toBeFalse()
        ->and($service->verifyCode($user, totpCodeAtOffset(2)))->toBeFalse();
});

test('an mfa login challenge rejects a code from two periods ago', function () {
    $user = User::factory()->create([
        'mfa_enabled' => true,
        'totp_secret' => TOTP_WINDOW_SECRET,
    ]);

    $this->actingAs($user)
        ->from(route('mfa.verify.login'))
        ->post(route('mfa.verify'), ['code' => totpCodeAtOffset(-2)])
        ->assertRedirect(route('mfa.verify.login'));

    expect(session('mfa_verified'))->toBeNull();

    $this->actingAs($user)
        ->post(route('mfa.verify'), ['code' => totpCodeAtOffset(0)])
        ->assertRedirect(route('vault.unlock'));

    expect(session('mfa_verified'))->toBeTrue();
});
