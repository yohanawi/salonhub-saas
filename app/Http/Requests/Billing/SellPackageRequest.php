<?php

namespace App\Http\Requests\Billing;

use App\Http\Requests\Billing\Concerns\ResolvesPosCashier;
use App\Http\Requests\Billing\Concerns\ValidatesSalePayment;
use App\Models\Customer;
use App\Models\ServicePackage;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SellPackageRequest extends FormRequest
{
    use ResolvesPosCashier;
    use ValidatesSalePayment;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->tenant_id !== null && $user->can('service_packages.sell');
    }

    public function rules(): array
    {
        return $this->salePaymentRules() + $this->posCashierRules() + [
            'customer_id' => ['required', 'integer'],
            'service_package_id' => ['required', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'service_package_id' => 'package',
            'payments.*.payment_method_id' => 'payment method',
            'payments.*.amount' => 'payment amount',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = (int) $this->user()->tenant_id;
                $branch = app(BranchContext::class)->current($this->user());

                if (! $branch) {
                    $validator->errors()->add('branch', 'Select an active branch before continuing.');

                    return;
                }

                if ($this->filled('customer_id') && ! Customer::withoutTenantScope()->where('tenant_id', $tenantId)->whereKey($this->integer('customer_id'))->exists()) {
                    $validator->errors()->add('customer_id', 'The selected customer does not belong to this salon.');
                }

                $package = $this->filled('service_package_id')
                    ? ServicePackage::withoutTenantScope()->where('tenant_id', $tenantId)->find($this->integer('service_package_id'))
                    : null;

                if ($this->filled('service_package_id') && (! $package || ! $package->is_active)) {
                    $validator->errors()->add('service_package_id', 'Select an active package from this salon.');
                }

                if ($package && (float) $package->price > 0 && collect((array) $this->input('payments', []))->filter(fn ($line) => is_array($line))->isEmpty()) {
                    $validator->errors()->add('payments', 'Take payment for the package.');
                }

                $this->validateSalePayment($validator, $tenantId, $branch->id);
                $this->validatePosCashier($validator, $branch);
            },
        ];
    }
}
