<?php

namespace App\Http\Requests\Settings;

use App\Models\Setting;
use App\Models\Tenant;
use App\Services\Settings\SettingDefinitionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', [Setting::class, $this->route('section')]) ?? false;
    }

    public function rules(): array
    {
        $section = (string) $this->route('section');
        $registry = app(SettingDefinitionRegistry::class);
        $rules = [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'nullable' : 'prohibited', 'integer', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'settings' => ['nullable', 'array'],
        ];

        foreach ($registry->definitionsForSection($section) as $key => $definition) {
            $escapedKey = str_replace('.', '\\.', $key);
            $rules["settings.{$escapedKey}"] = $definition['rules'];
        }

        if ($section === 'general') {
            $rules += [
                'tenant.name' => ['nullable', 'string', 'max:255'],
                'tenant.email' => ['nullable', 'email', 'max:255'],
                'tenant.phone' => ['nullable', 'string', 'max:50'],
                'tenant.country' => ['nullable', 'string', 'max:100'],
                'tenant.currency' => ['nullable', 'string', 'size:3'],
                'tenant.timezone' => ['nullable', 'string', 'max:100'],
                'tenant.business_type' => ['nullable', Rule::in(Tenant::BUSINESS_TYPES)],
                'tenant.logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ];
        }

        return $rules;
    }
}
