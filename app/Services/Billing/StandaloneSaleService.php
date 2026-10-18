<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * One-line invoices for things sold outside the service cart, such as gift cards and service
 * packages. They carry no discount or commission. A gift card is only a voucher, so its value is
 * taxed when it is spent; a package is a prepayment for services and is taxed when it is sold.
 */
class StandaloneSaleService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentMethodService $paymentMethods,
        private readonly InvoiceNumberService $invoiceNumbers,
        private readonly TaxService $taxes
    ) {
    }

    /**
     * @param  array{item_type: string, item_id: int, item_name: string, description?: ?string, amount: float, taxable?: bool}  $item
     * @param  array{payments?: array, payment_notes?: ?string, notes?: ?string}  $data
     */
    public function sell(Branch $branch, ?Customer $customer, array $item, array $data, User $user, ?int $cashierStaffId = null): Invoice
    {
        $branch->loadMissing('tenant');
        $this->paymentMethods->ensureDefaults($branch->tenant);

        return DB::transaction(function () use ($branch, $customer, $item, $data, $user, $cashierStaffId) {
            $amount = round((float) $item['amount'], 2);
            $issuedAt = now();
            $line = [
                'tenant_id' => $branch->tenant_id,
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'],
                'description' => $item['description'] ?? $item['item_name'],
                'item_name' => $item['item_name'],
                'quantity' => 1,
                'unit_price' => $amount,
                'gross_amount' => $amount,
                'discount' => 0,
                'discount_amount' => 0,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'net_amount' => $amount,
                'total' => $amount,
                'total_amount' => $amount,
            ];
            $tax = 0.0;
            $taxConfig = $this->taxes->configFor($branch);

            if (! empty($item['taxable']) && $taxConfig['enabled']) {
                $line = $this->taxes->applyToItemSnapshots(collect([$line]), $taxConfig)['items']->first();
                $tax = (float) $line['tax_amount'];
            }

            $total = round((float) $line['total_amount'], 2);

            $invoice = Invoice::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'customer_id' => $customer?->id,
                'invoice_number' => $this->invoiceNumbers->next($branch->tenant_id, $branch, $issuedAt),
                'subtotal' => $amount,
                'discount' => 0,
                'tax' => $tax,
                'tax_name' => $tax > 0 ? $taxConfig['name'] : null,
                'tax_inclusive' => $tax > 0 && $taxConfig['inclusive'],
                'total' => $total,
                'paid_amount' => 0,
                'balance_amount' => $total,
                'status' => Invoice::STATUS_ISSUED,
                'payment_status' => Invoice::PAYMENT_UNPAID,
                'issued_at' => $issuedAt,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'cashier_staff_id' => $cashierStaffId,
                'updated_by' => $user->id,
            ]);

            $invoice->items()->create($line);

            $lines = collect($data['payments'] ?? [])
                ->filter(fn ($line) => is_array($line))
                ->map(fn (array $line, int|string $index) => $line + ['index' => $index])
                ->values()
                ->all();

            $this->payments->recordLines($invoice, $lines, $data['payment_notes'] ?? null, $user, $cashierStaffId);

            return $invoice->fresh(['items', 'payments']);
        });
    }
}
