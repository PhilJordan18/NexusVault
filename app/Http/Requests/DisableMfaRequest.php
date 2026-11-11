<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ConfirmsFreshAuthentication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class DisableMfaRequest extends FormRequest
{
    use ConfirmsFreshAuthentication;

    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->freshAuthenticationRules();
    }
}
