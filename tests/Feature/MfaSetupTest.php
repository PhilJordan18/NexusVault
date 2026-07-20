<?php

use App\Models\User;
use App\Services\Auth\MfaService;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;
use PragmaRX\Google2FAQRCode\QRCode\QRCodeServiceContract;

test('mfa setup renders a browser safe qr code data uri', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mfa.setup'))
        ->assertOk()
        ->assertSee('data:image/', false)
        ->assertDontSee('QR Code failed to load');

    expect($user->refresh()->totp_secret)->not->toBeNull();
});

test('mfa qr service wraps raw svg output as a data uri', function () {
    $rawSvgQrService = new class implements QRCodeServiceContract
    {
        public function getQRCodeInline($string, $size = 200, $encoding = 'utf-8'): string
        {
            return '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        }
    };

    $user = User::factory()->make([
        'email' => 'oauth-user@nexusvault.test',
        'totp_secret' => '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O',
    ]);

    $qrUrl = (new MfaService(new Google2FAQRCode($rawSvgQrService)))->getQrCodeUrl($user);

    $this->assertStringStartsWith('data:image/svg+xml;base64,', $qrUrl);
});
