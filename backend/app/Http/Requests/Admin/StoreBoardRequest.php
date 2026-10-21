<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoardRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:40'],
            'slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:200'],
            'icon' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:published,hidden'],
        ];
    }
}
