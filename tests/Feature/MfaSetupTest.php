<?php

use App\Models\User;
use App\Services\Auth\MfaService;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;
use PragmaRX\Google2FAQRCode\QRCode\QRCodeServiceContract;

test('mfa setup renders a same origin qr code image route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mfa.setup'))
        ->assertOk()
        ->assertSee(route('mfa.qr-code', [], false), false)
        ->assertDontSee('QR Code failed to load');

    expect(session('mfa_pending_secret'))->not->toBeEmpty()
        ->and($user->refresh()->totp_secret)->toBeNull();
});

test('mfa qr code route returns an image response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['mfa_pending_secret' => '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O'])
        ->get(route('mfa.qr-code'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Cache-Control'))->toContain('no-cache')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=0');

    expect($response->headers->get('Content-Type'))->toStartWith('image/')
        ->and($response->baseResponse->getContent())->not->toBe('');
});

test('mfa qr service exposes raw svg output as svg image content', function () {
    $rawSvgQrService = new class implements QRCodeServiceContract
    {
        public function getQRCodeInline($string, $size = 200, $encoding = 'utf-8'): string
        {
            return '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        }
    };

    $user = User::factory()->make([
        'email' => 'oauth-user@nexusvault.test',
    ]);
    $pendingSecret = '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O';

    $mfaService = new MfaService(new Google2FAQRCode($rawSvgQrService));
    $qrImage = $mfaService->getQrCodeImage($user, $pendingSecret);

    expect($qrImage)->toBe([
        'mime_type' => 'image/svg+xml',
        'contents' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
    ]);

    $this->assertStringStartsWith('data:image/svg+xml;base64,', $mfaService->getQrCodeUrl($user, $pendingSecret));
});
