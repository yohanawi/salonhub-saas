<?php

namespace App\Http\Requests\Billing;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RecordInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('collectPayment', $this->route('invoice')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $invoice = $this->route('invoice');

                $method = PaymentMethod::withoutTenantScope()
                    ->where('tenant_id', $invoice->tenant_id)
                    ->find($this->integer('payment_method_id'));

                if (! $method || ! $method->is_active) {
                    $validator->errors()->add('payment_method_id', 'Select an active payment method for this salon.');
                    return;
                }

                if ($method->requires_reference && blank($this->input('transaction_reference'))) {
                    $validator->errors()->add('transaction_reference', 'A reference is required for this payment method.');
                }

                if ((float) $this->input('amount') > (float) $invoice->balance_amount) {
                    $validator->errors()->add('amount', 'Payment amount cannot exceed the remaining balance of LKR ' . number_format((float) $invoice->balance_amount, 2) . '.');
                }

                if ($method->type === PaymentMethod::TYPE_CASH && $this->filled('cash_received') && (float) $this->input('cash_received') < (float) $this->input('amount')) {
                    $validator->errors()->add('cash_received', 'Cash received cannot be less than the payment amount.');
                }
            },
        ];
    }
}
