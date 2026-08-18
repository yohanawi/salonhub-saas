<?php

namespace App\Services\Commission;

use App\Models\CommissionSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\StaffCommission;
use App\Models\User;
use App\Services\PlanEntitlementService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionGenerator
{
    public function __construct(
        private readonly CommissionSettingsService $settings,
        private readonly CommissionRuleResolver $resolver,
        private readonly CommissionCalculator $calculator,
        private readonly PlanEntitlementService $entitlements,
    ) {
    }

    public function generateForInvoice(Invoice $invoice, User $user): Collection
    {
        $invoice->loadMissing(['tenant', 'branch', 'customer', 'appointment', 'items.staff', 'items.service', 'items.product']);

        if ($invoice->status === Invoice::STATUS_VOID || $invoice->payment_status !== Invoice::PAYMENT_PAID) {
            return collect();
        }

        if (! $this->entitlements->featureEnabled($invoice->tenant, 'staff_commissions')) {
            return collect();
        }

        $settings = $this->settings->ensure($invoice->tenant);

        if (! $settings->commission_enabled || $settings->earn_trigger !== CommissionSetting::TRIGGER_INVOICE_PAID) {
            return collect();
        }

        return DB::transaction(function () use ($invoice, $user) {
            return $invoice->items
                ->filter(fn (InvoiceItem $item) => $item->staff_id)
                ->map(fn (InvoiceItem $item) => $this->generateForItem($invoice, $item, $user))
                ->filter()
                ->values();
        });
    }

    public function reverseForInvoice(Invoice $invoice, User $user, string $reason): Collection
    {
        return DB::transaction(function () use ($invoice, $user, $reason) {
            return StaffCommission::withoutTenantScope()
                ->where('tenant_id', $invoice->tenant_id)
                ->where('invoice_id', $invoice->id)
                ->whereIn('status', [StaffCommission::STATUS_EARNED, StaffCommission::STATUS_APPROVED])
                ->get()
                ->map(function (StaffCommission $commission) use ($invoice, $user, $reason) {
                    $commission->update(['status' => StaffCommission::STATUS_REVERSED]);

                    return StaffCommission::create([
                        'tenant_id' => $commission->tenant_id,
                        'branch_id' => $commission->branch_id,
                        'staff_id' => $commission->staff_id,
                        'customer_id' => $commission->customer_id,
                        'appointment_id' => $commission->appointment_id,
                        'sale_id' => $commission->sale_id,
                        'sale_item_id' => null,
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => null,
                        'service_id' => $commission->service_id,
                        'product_id' => $commission->product_id,
                        'source_type' => Invoice::class,
                        'source_id' => $invoice->id,
                        'gross_amount' => $commission->gross_amount,
                        'discount_amount' => $commission->discount_amount,
                        'net_amount' => $commission->net_amount,
                        'commission_type' => $commission->commission_type,
                        'commission_rate' => $commission->commission_rate,
                        'base_amount' => $commission->base_amount,
                        'commission_base' => $commission->commission_base,
                        'commission_amount' => -1 * (float) $commission->commission_amount,
                        'status' => StaffCommission::STATUS_REVERSED,
                        'earned_at' => now(),
                        'reversed_commission_id' => $commission->id,
                        'notes' => $reason,
                    ]);
                });
        });
    }

    private function generateForItem(Invoice $invoice, InvoiceItem $item, User $user): ?StaffCommission
    {
        $exists = StaffCommission::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('sale_item_id', $item->id)
            ->where('staff_id', $item->staff_id)
            ->exists();

        if ($exists) {
            return null;
        }

        $rule = $this->resolver->resolve($invoice->tenant, $invoice->branch, $item->staff, $item->service, $item->product, $invoice->paid_at ?? now());

        if (! $rule) {
            return null;
        }

        $calculation = $this->calculator->calculate(
            (float) ($item->gross_amount ?? ((float) $item->quantity * (float) $item->unit_price)),
            (float) ($item->discount_amount ?? $item->discount ?? 0),
            $rule,
        );

        if ($calculation['commission_amount'] <= 0) {
            return null;
        }

        return StaffCommission::create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'staff_id' => $item->staff_id,
            'customer_id' => $invoice->customer_id,
            'appointment_id' => $invoice->appointment_id,
            'sale_id' => $invoice->id,
            'sale_item_id' => $item->id,
            'invoice_id' => $invoice->id,
            'invoice_item_id' => $item->id,
            'commission_rule_id' => $rule['commission_rule_id'],
            'service_id' => $item->service_id,
            'product_id' => $item->product_id,
            'source_type' => InvoiceItem::class,
            'source_id' => $item->id,
            'gross_amount' => $calculation['gross_amount'],
            'discount_amount' => $calculation['discount_amount'],
            'net_amount' => $calculation['net_amount'],
            'commission_type' => $rule['commission_type'],
            'commission_rate' => $rule['commission_value'],
            'base_amount' => $calculation['commission_base'],
            'commission_base' => $calculation['commission_base'],
            'commission_amount' => $calculation['commission_amount'],
            'status' => StaffCommission::STATUS_EARNED,
            'earned_at' => now(),
            'notes' => "Generated from {$invoice->invoice_number}.",
        ]);
    }
}
