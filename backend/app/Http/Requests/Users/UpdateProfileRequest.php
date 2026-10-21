<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nickname' => ['sometimes', 'string', 'max:32'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'gender' => ['sometimes', 'nullable', Rule::in(['unknown', 'male', 'female', 'other'])],
        ];
    }
}
