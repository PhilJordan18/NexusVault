<?php

use App\DTOs\Service\ServiceData;
use App\Http\Requests\CreateServiceRequest;
use App\Models\Service;
use App\Models\User;
use App\Services\Auth\UserKeyService;
use App\Services\Vault\Contracts\ServiceServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

function createVaultUserForItemTypes(): User
{
    putenv('RANDFILE=/tmp/.rnd');

    $keys = app(UserKeyService::class)->provisionKey('password');

    return User::factory()->create([
        'password' => Hash::make('password'),
        'salt' => $keys['salt'],
        'public_key' => $keys['public_key'],
        'private_key' => base64_encode($keys['private_key']),
        'private_nonce' => $keys['private_nonce'],
        'encrypted_master_key' => $keys['encrypted_master_key'],
    ]);
}

test('payment cards are encrypted without password security analysis', function () {
    Http::fake();

    $user = createVaultUserForItemTypes();

    $this->actingAs($user);
    app(UserKeyService::class)->storeMasterKey($user, 'password');

    $card = app(ServiceServiceInterface::class)->create(new ServiceData(
        name: 'Visa Desjardins',
        url: null,
        username: 'Philippe MBA',
        password: '4111 1111 1111 1111',
        notes: 'Expires 12/30 - CVC 123',
        type: Service::TYPE_PAYMENT_CARD
    ));

    expect($card->refresh()->type)->toBe(Service::TYPE_PAYMENT_CARD)
        ->and($card->username)->toBe('Philippe MBA')
        ->and($card->password)->toBe('4111 1111 1111 1111')
        ->and($card->notes)->toBe('Expires 12/30 - CVC 123')
        ->and($card->strength)->toBeNull()
        ->and($card->compromised)->toBeFalse()
        ->and($card->reused)->toBeFalse();

    Http::assertNothingSent();
});

test('login items can be validated without a submitted url', function () {
    $request = CreateServiceRequest::create('/services', 'POST', [
        'type' => Service::TYPE_LOGIN,
        'name' => 'GitHub',
        'username' => 'philippe@example.com',
        'password' => 'secure-password',
        'domain' => 'github.com',
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->passes())->toBeTrue();
});
