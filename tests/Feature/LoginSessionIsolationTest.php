<?php

use App\DTOs\Auth\LoginData;
use App\Models\User;
use App\Services\Auth\LoginService;
use App\Services\Auth\OAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;

const STALE_SESSION_FLAGS = [
    'mfa_verified' => true,
    'vault_unlocked_at' => 1700000000,
    'masterKey' => 'stale-master-key',
];

function createMfaVictim(): User
{
    return User::factory()->create([
        'mfa_enabled' => true,
        'totp_secret' => '2UMPTX247VZPRA4YGFUPMCMF6UHRGW5O',
        'vault_key_envelope' => ['version' => 1],
    ]);
}

test('a password login purges mfa and vault flags left in the session', function () {
    $victim = createMfaVictim();

    $this->withSession(STALE_SESSION_FLAGS)
        ->post(route('login.authenticate.password'), [
            'email' => $victim->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('mfa.verify.login'));

    $this->assertAuthenticatedAs($victim);

    expect(Session::has('mfa_verified'))->toBeFalse()
        ->and(Session::has('vault_unlocked_at'))->toBeFalse()
        ->and(Session::has('masterKey'))->toBeFalse();

    $this->get(route('dashboard'))
        ->assertRedirect(route('mfa.verify.login'));
});

test('logging in as another user invalidates the previous session entirely', function () {
    $attacker = User::factory()->create();
    $victim = createMfaVictim();

    Auth::login($attacker);
    Session::put(STALE_SESSION_FLAGS);
    Session::put('url.intended', route('dashboard'));
    $previousSessionId = Session::getId();

    app(LoginService::class)->login(new LoginData($victim->email, 'password'));

    $this->assertAuthenticatedAs($victim);

    expect(Session::getId())->not->toBe($previousSessionId)
        ->and(Session::has('mfa_verified'))->toBeFalse()
        ->and(Session::has('vault_unlocked_at'))->toBeFalse()
        ->and(Session::has('masterKey'))->toBeFalse()
        ->and(Session::has('url.intended'))->toBeFalse();
});

test('an oauth login purges mfa and vault flags left in the session', function () {
    $victim = createMfaVictim();

    Session::put(STALE_SESSION_FLAGS);

    $response = app(OAuthService::class)->handleCallback((object) [
        'name' => $victim->name,
        'email' => $victim->email,
    ], 'google');

    expect($response->getTargetUrl())->toBe(route('mfa.verify.login'))
        ->and(Session::has('mfa_verified'))->toBeFalse()
        ->and(Session::has('vault_unlocked_at'))->toBeFalse()
        ->and(Session::has('masterKey'))->toBeFalse();
});

test('login routes are not reachable from an authenticated session', function () {
    $attacker = User::factory()->create();
    $victim = createMfaVictim();

    $this->actingAs($attacker)->get(route('login'))->assertRedirect(route('vault.unlock'));
    $this->actingAs($attacker)->get(route('login.password'))->assertRedirect(route('vault.unlock'));

    $this->actingAs($attacker)
        ->post(route('login.authenticate.email'), ['email' => $victim->email])
        ->assertRedirect(route('vault.unlock'));

    $this->actingAs($attacker)
        ->post(route('login.authenticate.password'), [
            'email' => $victim->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('vault.unlock'));

    $this->assertAuthenticatedAs($attacker);

    $this->actingAs($attacker)->get(route('register'))->assertRedirect(route('vault.unlock'));
    $this->actingAs($attacker)->get('/auth/google')->assertRedirect(route('vault.unlock'));
    $this->actingAs($attacker)->get('/auth/github/callback')->assertRedirect(route('vault.unlock'));
    $this->actingAs($attacker)->postJson('/webauthn/login/options')->assertRedirect(route('vault.unlock'));
});

test('an authenticated session with pending mfa is sent back to the mfa challenge from login', function () {
    $victim = createMfaVictim();

    $this->actingAs($victim)
        ->get(route('login'))
        ->assertRedirect(route('mfa.verify.login'));
});

test('a verified mfa session cannot be reused to skip the mfa of another account', function () {
    $attacker = User::factory()->create([
        'mfa_enabled' => true,
        'totp_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
        'vault_key_envelope' => ['version' => 1],
    ]);
    $victim = createMfaVictim();

    $this->post(route('login.authenticate.password'), [
        'email' => $attacker->email,
        'password' => 'password',
    ])->assertRedirect(route('mfa.verify.login'));

    $this->post(route('mfa.verify'), [
        'code' => (new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP'),
    ])->assertRedirect(route('vault.unlock'));

    $this->post(route('vault.unlock.store'), ['client_unlocked' => 1])
        ->assertRedirect(route('dashboard'));

    $this->post(route('login.authenticate.password'), [
        'email' => $victim->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($attacker);

    $this->post(route('logout'));

    $this->post(route('login.authenticate.password'), [
        'email' => $victim->email,
        'password' => 'password',
    ])->assertRedirect(route('mfa.verify.login'));

    $this->assertAuthenticatedAs($victim);

    $this->get(route('dashboard'))->assertRedirect(route('mfa.verify.login'));
});
