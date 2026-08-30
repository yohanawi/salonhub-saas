<?php

namespace App\Http\Requests\Staff;

use App\Models\Staff;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends StoreStaffRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('staff')) ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $staff = $this->route('staff');

        $rules['tenant_id'] = ['nullable'];
        $rules['employee_code'] = [
            'required',
            'string',
            'max:50',
            Rule::unique('staff', 'employee_code')
                ->where('tenant_id', $staff->tenant_id)
                ->ignore($staff)
                ->withoutTrashed(),
        ];

        return $rules;
    }

    protected function tenantId(): ?int
    {
        return $this->route('staff')?->tenant_id;
    }
}
