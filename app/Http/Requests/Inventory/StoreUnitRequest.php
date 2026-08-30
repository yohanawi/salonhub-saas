<?php

namespace App\Http\Requests\Inventory;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Unit::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->hasRole('Super Admin') ? $this->input('tenant_id') : $this->user()?->tenant_id;

        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->where('tenant_id', $tenantId)],
            'symbol' => ['required', 'string', 'max:30', Rule::unique('units', 'symbol')->where('tenant_id', $tenantId)],
            'type' => ['required', Rule::in(Unit::TYPES)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
