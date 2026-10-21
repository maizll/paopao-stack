<?php

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:80'],
            'content' => ['sometimes', 'string', 'max:20000'],
            'cover' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
