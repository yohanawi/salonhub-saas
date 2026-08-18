<?php

namespace App\Http\Requests\Commission;

use App\Models\CommissionPayout;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommissionPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CommissionPayout::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'adjustment_amount' => ['nullable', 'numeric', 'min:-999999.99', 'max:999999.99'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
