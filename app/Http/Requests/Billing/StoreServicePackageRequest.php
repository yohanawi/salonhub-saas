<?php

namespace App\Http\Requests\Billing;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreServicePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->tenant_id !== null && $user->can('service_packages.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*' => ['array'],
            'items.*.service_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items.*.service_id' => 'service',
            'items.*.quantity' => 'sessions',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $ids = collect((array) $this->input('items', []))->pluck('service_id')->filter()->map(fn ($id) => (int) $id);
                $valid = Service::withoutTenantScope()->where('tenant_id', $this->user()->tenant_id)->whereIn('id', $ids)->pluck('id');

                foreach ((array) $this->input('items', []) as $index => $item) {
                    if (filled($item['service_id'] ?? null) && ! $valid->contains((int) $item['service_id'])) {
                        $validator->errors()->add("items.{$index}.service_id", 'Select a service from this salon.');
                    }
                }
            },
        ];
    }
}
