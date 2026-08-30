<?php

namespace App\Http\Requests\Inventory;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('unit')) ?? false;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->where('tenant_id', $unit->tenant_id)->ignore($unit->id)],
            'symbol' => ['required', 'string', 'max:30', Rule::unique('units', 'symbol')->where('tenant_id', $unit->tenant_id)->ignore($unit->id)],
            'type' => ['required', Rule::in(Unit::TYPES)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
