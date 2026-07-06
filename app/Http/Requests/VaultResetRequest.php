<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class VaultResetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string', 'in:RESET'],
            'vault_key_envelope' => ['required', 'json'],
            'vault_recovery_envelope' => ['required', 'json'],
            'public_key' => ['required', 'string'],
            'encrypted_private_key' => ['required', 'json'],
        ];
    }
}
