<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
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
        $tenant = $this->route('tenant');
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return [
            'name' => ['sometimes', 'string', 'max:80'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:120', Rule::unique('tenants', 'domain')->ignore($tenantId)],
            'status' => ['sometimes', Rule::in(['pending', 'active', 'suspended'])],
            'plan_code' => ['sometimes', 'string', 'exists:plans,code'],
        ];
    }
}
