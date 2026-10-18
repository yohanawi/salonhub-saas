<?php

namespace App\Http\Requests\Billing;

use App\Http\Requests\Billing\Concerns\ResolvesPosCashier;
use App\Http\Requests\Billing\Concerns\ValidatesSalePayment;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    use ResolvesPosCashier;
    use ValidatesSalePayment;

    public function authorize(): bool
    {
        return $this->user()?->can('checkout', Invoice::class) ?? false;
    }

    public function rules(): array
    {
        return $this->salePaymentRules() + $this->posCashierRules();
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = $this->user()->hasRole('Super Admin')
                    ? $this->route('appointment')?->tenant_id
                    : $this->user()->tenant_id;

                if (! $tenantId) {
                    return;
                }

                $this->validateSalePayment($validator, (int) $tenantId, $this->route('appointment')?->branch_id);

                if ($branch = $this->route('appointment')?->branch) {
                    $this->validatePosCashier($validator, $branch);
                }
            },
        ];
    }
}
