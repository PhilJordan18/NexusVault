<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => 'required|integer|exists:services,id',
            'email' => [
                'required',
                'email',
                'exists:users,email',
                Rule::notIn([auth()->user()->email]),
            ],
            'client_encrypted' => ['nullable', 'boolean'],
            'mode' => ['nullable', 'string', Rule::in(['client-encrypted-sync'])],
            'encrypted_aes_key' => ['required_if:client_encrypted,1', 'nullable', 'string'],
            'encrypted_data' => ['required_if:client_encrypted,1', 'nullable', 'array'],
            'encrypted_data.ciphertext' => ['required_if:client_encrypted,1', 'nullable', 'string'],
            'encrypted_data.iv' => ['required_if:client_encrypted,1', 'nullable', 'string', 'size:24'],
            'encrypted_data.tag' => ['required_if:client_encrypted,1', 'nullable', 'string', 'size:32'],
            'shared_key_envelope' => ['nullable', 'array'],
            'shared_key_envelope.version' => ['required_with:shared_key_envelope', 'integer'],
            'shared_key_envelope.algorithm' => ['required_with:shared_key_envelope', 'string'],
            'shared_key_envelope.keySource' => ['required_with:shared_key_envelope', 'string'],
            'shared_key_envelope.ciphertext' => ['required_with:shared_key_envelope', 'string'],
            'shared_key_envelope.iv' => ['required_with:shared_key_envelope', 'string', 'size:24'],
            'shared_key_envelope.tag' => ['required_with:shared_key_envelope', 'string', 'size:32'],
            'shared_fields' => ['nullable', 'array'],
            'shared_fields.username' => ['required_with:shared_fields', 'array'],
            'shared_fields.username.ciphertext' => ['required_with:shared_fields.username', 'string'],
            'shared_fields.username.iv' => ['required_with:shared_fields.username', 'string', 'size:24'],
            'shared_fields.username.tag' => ['required_with:shared_fields.username', 'string', 'size:32'],
            'shared_fields.password' => ['required_with:shared_fields', 'array'],
            'shared_fields.password.ciphertext' => ['required_with:shared_fields.password', 'string'],
            'shared_fields.password.iv' => ['required_with:shared_fields.password', 'string', 'size:24'],
            'shared_fields.password.tag' => ['required_with:shared_fields.password', 'string', 'size:32'],
            'shared_fields.notes' => ['nullable', 'array'],
            'shared_fields.notes.ciphertext' => ['required_with:shared_fields.notes', 'string'],
            'shared_fields.notes.iv' => ['required_with:shared_fields.notes', 'string', 'size:24'],
            'shared_fields.notes.tag' => ['required_with:shared_fields.notes', 'string', 'size:32'],
        ];
    }
}
