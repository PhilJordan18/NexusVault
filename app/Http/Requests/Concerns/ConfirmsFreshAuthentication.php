<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;
use App\Services\Auth\ReauthenticationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

/**
 * Form request mixin for destructive actions: the request must carry the login
 * password or a fresh TOTP code, checked after the field rules pass.
 */
trait ConfirmsFreshAuthentication
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function freshAuthenticationRules(): array
    {
        return [
            'current_password' => ['nullable', 'string'],
            'totp_code' => ['nullable', 'digits:6'],
        ];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(ReauthenticationService $reauthentication): array
    {
        return [
            function (Validator $validator) use ($reauthentication): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var User $user */
                $user = $this->user();

                $confirmed = $reauthentication->confirm(
                    $user,
                    $this->input('current_password'),
                    $this->input('totp_code'),
                );

                if (! $confirmed) {
                    $validator->errors()->add('current_password', __('Confirm this action with your login password or a current authenticator code.'));
                }
            },
        ];
    }
}
