<?php

namespace App\Http\Requests\Service;

use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Service::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('name', '')),
        ]);
    }

    public function rules(): array
    {
        $tenantId = $this->tenantId();

        return [
            'tenant_id' => [$this->user()->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('services', 'slug')->where('tenant_id', $tenantId)->withoutTrashed(),
            ],
            'category_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:3000'],
            'default_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'default_duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'branches' => ['nullable', 'array'],
            'branches.*.enabled' => ['nullable', 'boolean'],
            'branches.*.price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'branches.*.duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'branches.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = $this->tenantId();

                if ($this->filled('category_id') && ! ServiceCategory::query()->where('tenant_id', $tenantId)->whereKey($this->integer('category_id'))->exists()) {
                    $validator->errors()->add('category_id', 'The selected category does not belong to this salon.');
                }

                $branchIds = collect(array_keys($this->input('branches', [])))
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->all();

                if ($branchIds) {
                    $validCount = Branch::query()->where('tenant_id', $tenantId)->whereIn('id', $branchIds)->count();

                    if ($validCount !== count($branchIds)) {
                        $validator->errors()->add('branches', 'One or more selected branches do not belong to this salon.');
                    }
                }
            },
        ];
    }

    private function tenantId(): ?int
    {
        return $this->user()->hasRole('Super Admin')
            ? ($this->filled('tenant_id') ? $this->integer('tenant_id') : null)
            : $this->user()->tenant_id;
    }
}
