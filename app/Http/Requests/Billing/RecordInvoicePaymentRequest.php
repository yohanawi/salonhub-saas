<?php

namespace App\Http\Requests\Billing;

use App\Http\Requests\Billing\Concerns\ResolvesPosCashier;
use App\Models\PaymentMethod;
use App\Services\Settings\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Collects the remaining balance of an invoice, optionally split across methods. The older single
 * `payment_method_id` / `amount` fields are still accepted as one line.
 */
class RecordInvoicePaymentRequest extends FormRequest
{
    use ResolvesPosCashier;

    public function authorize(): bool
    {
        return $this->user()?->can('collectPayment', $this->route('invoice')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('payments') && $this->has('payment_method_id')) {
            $this->merge([
                'single_line' => true,
                'payments' => [[
                    'payment_method_id' => $this->input('payment_method_id'),
                    'amount' => $this->input('amount'),
                    'cash_received' => $this->input('cash_received'),
                    'transaction_reference' => $this->input('transaction_reference'),
                ]],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'payments' => ['required', 'array', 'min:1', 'max:10'],
            'payments.*' => ['array'],
            'payments.*.payment_method_id' => ['required', 'integer'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.cash_received' => ['nullable', 'numeric', 'min:0'],
            'payments.*.transaction_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'payment_notes' => ['nullable', 'string', 'max:3000'],
        ] + $this->posCashierRules();
    }

    public function attributes(): array
    {
        return [
            'payments.*.payment_method_id' => 'payment method',
            'payments.*.amount' => 'payment amount',
            'payments.*.cash_received' => 'cash received',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $invoice = $this->route('invoice');
                $lines = collect((array) $this->input('payments', []))->filter(fn ($line) => is_array($line));
                // Errors for the old single-field form keep their original field names.
                $field = fn (int|string $index, string $name) => $this->boolean('single_line') ? $name : "payments.{$index}.{$name}";

                if ($lines->count() > 1 && ! app(SettingService::class)->get('pos.allow_split_payment', $invoice->tenant_id, $invoice->branch_id)) {
                    $validator->errors()->add('payments', 'Split payments are disabled for this salon. Use a single payment method.');
                }

                foreach ($lines as $index => $line) {
                    $method = PaymentMethod::withoutTenantScope()
                        ->where('tenant_id', $invoice->tenant_id)
                        ->find($line['payment_method_id'] ?? null);

                    if (! $method || ! $method->is_active) {
                        $validator->errors()->add($field($index, 'payment_method_id'), 'Select an active payment method for this salon.');
                        continue;
                    }

                    if ($method->requires_reference && blank($line['transaction_reference'] ?? null)) {
                        $validator->errors()->add($field($index, 'transaction_reference'), "A reference is required for {$method->name}.");
                    }

                    if ($method->type === PaymentMethod::TYPE_CASH && filled($line['cash_received'] ?? null) && (float) $line['cash_received'] < (float) ($line['amount'] ?? 0)) {
                        $validator->errors()->add($field($index, 'cash_received'), 'Cash received cannot be less than the payment amount.');
                    }
                }

                // The balance is known here, so the lines must not add up to more than is owed.
                $total = round((float) $lines->sum(fn ($line) => (float) ($line['amount'] ?? 0)), 2);

                if ($total > (float) $invoice->balance_amount + 0.009) {
                    $validator->errors()->add(
                        $this->boolean('single_line') ? 'amount' : 'payments',
                        'Payment amount cannot exceed the remaining balance of LKR ' . number_format((float) $invoice->balance_amount, 2) . '.'
                    );
                }

                if ($invoice->branch) {
                    $this->validatePosCashier($validator, $invoice->branch);
                }
            },
        ];
    }
}
