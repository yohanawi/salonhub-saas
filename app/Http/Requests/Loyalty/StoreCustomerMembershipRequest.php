<?php

namespace App\Http\Requests\Loyalty;

use App\Models\CustomerMembership;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CustomerMembership::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'membership_plan_id' => ['required', 'integer', 'exists:membership_plans,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'start_date' => ['required', 'date'],
            'price_paid' => ['nullable', 'numeric', 'min:0'],
            'joining_fee_paid' => ['nullable', 'numeric', 'min:0'],
            'auto_renew' => ['nullable', 'boolean'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'payment_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
