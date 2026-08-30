<?php

namespace App\Http\Requests\Service;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ServiceCategory::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('name', '')),
        ]);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->hasRole('Super Admin')
            ? ($this->filled('tenant_id') ? $this->integer('tenant_id') : null)
            : $this->user()->tenant_id;

        return [
            'tenant_id' => [$this->user()->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('service_categories', 'slug')->where('tenant_id', $tenantId)->withoutTrashed(),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
