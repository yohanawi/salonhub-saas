<?php

namespace App\Http\Requests\Loyalty;

use App\Models\LoyaltyPointTransaction;
use Illuminate\Foundation\Http\FormRequest;

class AdjustPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LoyaltyPointTransaction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'points' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:3000'],
        ];
    }
}
