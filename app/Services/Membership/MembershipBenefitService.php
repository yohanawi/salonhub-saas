<?php

namespace App\Services\Membership;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\Invoice;
use App\Models\MembershipBenefit;
use App\Models\MembershipBenefitUsage;
use App\Models\User;
use Illuminate\Support\Collection;

class MembershipBenefitService
{
    public function activeMembership(Customer $customer, mixed $date = null): ?CustomerMembership
    {
        $date = $date ?: today();

        return CustomerMembership::withoutTenantScope()
            ->with(['plan.benefits'])
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->where('status', CustomerMembership::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->latest('end_date')
            ->first();
    }

    public function applyToItemSnapshots(?Customer $customer, Collection $items): array
    {
        if (! $customer) {
            return ['items' => $items, 'membership' => null, 'discount' => 0.0];
        }

        $membership = $this->activeMembership($customer);

        if (! $membership) {
            return ['items' => $items, 'membership' => null, 'discount' => 0.0];
        }

        $discountTotal = 0.0;
        $benefits = $membership->plan->benefits
            ->where('status', MembershipBenefit::STATUS_ACTIVE)
            ->sortBy('priority')
            ->values();

        $items = $items->map(function (array $item) use ($benefits, $membership, &$discountTotal) {
            $benefit = $this->bestBenefitForItem($benefits, $item);

            if (! $benefit) {
                return $item + [
                    'customer_membership_id' => null,
                    'membership_benefit_id' => null,
                    'membership_discount_amount' => 0,
                ];
            }

            $lineBase = max(0, (float) $item['gross_amount'] - (float) ($item['discount_amount'] ?? 0));
            $discount = $this->discountAmount($benefit, $lineBase);

            if ($discount <= 0) {
                return $item + [
                    'customer_membership_id' => null,
                    'membership_benefit_id' => null,
                    'membership_discount_amount' => 0,
                ];
            }

            $discountTotal += $discount;
            $totalDiscount = (float) ($item['discount_amount'] ?? 0) + $discount;
            $net = max(0, (float) $item['gross_amount'] - $totalDiscount + (float) ($item['tax_amount'] ?? 0));

            return array_merge($item, [
                'customer_membership_id' => $membership->id,
                'membership_benefit_id' => $benefit->id,
                'membership_discount_amount' => $discount,
                'discount' => $totalDiscount,
                'discount_amount' => $totalDiscount,
                'net_amount' => $net,
                'total' => $net,
                'total_amount' => $net,
            ]);
        });

        return ['items' => $items, 'membership' => $membership, 'discount' => $discountTotal];
    }

    public function recordInvoiceUsage(Invoice $invoice, User $user): void
    {
        $invoice->loadMissing(['items']);

        $invoice->items
            ->filter(fn ($item) => $item->customer_membership_id && $item->membership_benefit_id)
            ->each(function ($item) use ($invoice, $user) {
                MembershipBenefitUsage::create([
                    'tenant_id' => $invoice->tenant_id,
                    'customer_membership_id' => $item->customer_membership_id,
                    'membership_benefit_id' => $item->membership_benefit_id,
                    'customer_id' => $invoice->customer_id,
                    'appointment_id' => $invoice->appointment_id,
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $item->id,
                    'service_id' => $item->service_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'discount_amount' => $item->membership_discount_amount,
                    'used_at' => now(),
                    'created_by' => $user->id,
                ]);
            });
    }

    private function bestBenefitForItem(Collection $benefits, array $item): ?MembershipBenefit
    {
        return $benefits
            ->filter(fn (MembershipBenefit $benefit) => $this->matches($benefit, $item))
            ->sortByDesc(fn (MembershipBenefit $benefit) => $this->discountAmount($benefit, max(0, (float) $item['gross_amount'] - (float) ($item['discount_amount'] ?? 0))))
            ->first();
    }

    private function matches(MembershipBenefit $benefit, array $item): bool
    {
        if (! in_array($benefit->benefit_type, [
            MembershipBenefit::TYPE_SERVICE_DISCOUNT,
            MembershipBenefit::TYPE_PRODUCT_DISCOUNT,
            MembershipBenefit::TYPE_FREE_SERVICE,
        ], true)) {
            return false;
        }

        if ($benefit->benefit_type === MembershipBenefit::TYPE_SERVICE_DISCOUNT && ($item['item_type'] ?? null) !== 'service') {
            return false;
        }

        if ($benefit->benefit_type === MembershipBenefit::TYPE_PRODUCT_DISCOUNT && ($item['item_type'] ?? null) !== 'product') {
            return false;
        }

        if ($benefit->service_id && (int) ($item['service_id'] ?? 0) !== (int) $benefit->service_id) {
            return false;
        }

        if ($benefit->product_id && (int) ($item['product_id'] ?? 0) !== (int) $benefit->product_id) {
            return false;
        }

        if ($benefit->service_category_id && (int) data_get($item, 'service_category_id') !== (int) $benefit->service_category_id) {
            return false;
        }

        return true;
    }

    private function discountAmount(MembershipBenefit $benefit, float $lineBase): float
    {
        if ($benefit->benefit_type === MembershipBenefit::TYPE_FREE_SERVICE) {
            return $lineBase;
        }

        return match ($benefit->discount_type) {
            MembershipBenefit::DISCOUNT_FIXED => min($lineBase, (float) $benefit->discount_value),
            default => min($lineBase, round($lineBase * ((float) $benefit->discount_value / 100), 2)),
        };
    }
}
