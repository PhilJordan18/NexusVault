<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...self::accountRules(),
            'vault_key_envelope' => 'required|json',
            'vault_recovery_envelope' => 'required|json',
            'public_key' => 'required|string',
            'encrypted_private_key' => 'required|json',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function accountRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
