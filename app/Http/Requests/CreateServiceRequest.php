<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateServiceRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(Service::types())],
            'name' => 'required|string|max:255',
            'url' => ['nullable', 'url'],
            'username' => 'required|string|max:255',
            'password' => "required|string|min:{$secretMinLength}",
            'notes' => 'nullable|string',
            'domain' => 'nullable|string|max:255',
        ];
    }
}
