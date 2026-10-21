<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:tenants,slug'],
            'domain' => ['nullable', 'string', 'max:120', 'unique:tenants,domain'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'suspended'])],
            'plan_code' => ['nullable', 'string', 'exists:plans,code'],
        ];
    }
}
