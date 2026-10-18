<?php

namespace App\Http\Requests\Billing;

use App\Http\Requests\Billing\Concerns\ResolvesPosCashier;
use App\Http\Requests\Billing\Concerns\ValidatesSalePayment;
use App\Models\Customer;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SellGiftCardRequest extends FormRequest
{
    use ResolvesPosCashier;
    use ValidatesSalePayment;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->tenant_id !== null && $user->can('gift_cards.sell');
    }

    public function rules(): array
    {
        return $this->salePaymentRules() + $this->posCashierRules() + [
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'code' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'customer_id' => ['nullable', 'integer'],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'The code may only contain letters, numbers and dashes.',
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

                if (collect((array) $this->input('payments', []))->filter(fn ($line) => is_array($line))->isEmpty()) {
                    $validator->errors()->add('payments', 'Take payment for the gift card.');
                }

                $this->validateSalePayment($validator, $tenantId, $branch->id);
                $this->validatePosCashier($validator, $branch);
            },
        ];
    }
}
