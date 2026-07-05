<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
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
        $type = $this->input('type', Service::TYPE_LOGIN);
        $secretMinLength = $type === Service::TYPE_LOGIN ? 6 : 1;

        return [
            'type' => ['sometimes', 'required', 'string', Rule::in(Service::types())],
            'name' => 'sometimes|required|string|max:255',
            'url' => ['nullable', 'url'],
            'username' => 'sometimes|required|string',
            'password' => "sometimes|required|string|min:{$secretMinLength}",
            'notes' => 'nullable|string',
            'client_encrypted' => [Rule::requiredIf(fn () => $this->user()?->usesClientSideVault()), 'boolean'],
            'username_iv' => ['required_if:client_encrypted,1', 'nullable', 'string', 'size:24'],
            'username_tag' => ['required_if:client_encrypted,1', 'nullable', 'string', 'size:32'],
            'password_iv' => ['required_if:client_encrypted,1', 'nullable', 'string', 'size:24'],
            'password_tag' => ['required_if:client_encrypted,1', 'nullable', 'string', 'size:32'],
            'notes_iv' => ['nullable', 'string', 'size:24'],
            'notes_tag' => ['nullable', 'string', 'size:32'],
        ];
    }
}
